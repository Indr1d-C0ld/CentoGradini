<?php

declare(strict_types=1);

namespace App\Game;

use App\Core\Database;
use App\Core\GameConfig;
use App\Core\Lock;
use App\Sim\Calendario;
use App\Sim\Folla;
use App\Sim\Luoghi;
use App\Sim\Meteo;
use App\Sim\Orologio;
use App\Sim\Rng;

/**
 * Il Segreto — il ciclo centrale del gioco.
 *
 *     usi un potere
 *        -> chi c'era se n'è accorto?          (tiro di Nota, uno per testimone)
 *        -> lo copri?                           (scusa / diversivo / faccia di bronzo / complice)
 *        -> se no, quel testimone si porta dietro un'ANOMALIA
 *        -> tre anomalie coerenti non sono un sospetto: sono una certezza
 *        -> e allora o ti confidi, o traslochi.
 *
 * **Perché è questo il cuore e non il combattimento.** In Kimagure Orange Road
 * nessuno salva il mondo: si cerca di finire l'anno scolastico senza che la
 * famiglia debba trasferirsi di nuovo. Ogni potere è insieme la soluzione più
 * rapida a un problema e il modo più rapido di crearne uno più grande. È
 * l'unica meccanica che il regolamento del 1990 aveva azzeccato in pieno, e
 * la sua frase la diciamo com'era: «il segreto dei poteri della loro famiglia
 * non deve mai essere scoperto dal mondo».
 *
 * **La valvola.** Confidarsi volontariamente con chi sospetta trasforma la
 * minaccia più grande nell'alleato più forte. È letteralmente l'arco di
 * Hikaru nel primo episodio, ed è la mossa più rischiosa del gioco: si
 * rinuncia alla sicurezza per avere qualcuno che sa.
 */
final class Segreto
{
    // --- Uso di un potere ---------------------------------------------------

    /**
     * @param array<string,mixed> $pg
     * @return array{ok:bool, error?:string, incidente?:int, testimoni?:list<array<string,mixed>>,
     *               notato?:bool, folla?:int, racconto?:string}
     */
    public static function usa(array $pg, string $pkey, string $scelta = ''): array
    {
        if (!(bool) $pg['esper']) {
            return ['ok' => false, 'error' => 'Non hai poteri.'];
        }
        if ($pg['verso'] !== null) {
            return ['ok' => false, 'error' => 'Sei per strada: aspetta di arrivare.'];
        }

        $potere = Database::first(
            'SELECT p.*, pp.primario, pp.controllo, pp.usi
             FROM personaggio_poteri pp JOIN poteri p ON p.pkey = pp.pkey
             WHERE pp.personaggio_id = ? AND pp.pkey = ?',
            [(int) $pg['id'], $pkey]
        );
        if ($potere === null) {
            return ['ok' => false, 'error' => 'Questo potere non ce l\'hai.'];
        }
        // I sogni premonitori «non si comandano» (lo dice la loro scheda):
        // arrivano la notte. Prima avevano un pulsante, costavano zero e non
        // si vedevano, e premerlo all'infinito faceva salire il Controllo
        // gratis.
        if ($pkey === 'sogni') {
            return ['ok' => false, 'error' => 'Questo non si comanda: arriva quando vuole, di solito la notte.'];
        }
        // Il blocco scatta da solo, quando qualcuno usa un potere davanti a chi
        // ce l'ha (vedi chiSpegne()): accenderlo a vuoto spendeva punti e
        // rischiava un incidente senza nessun effetto.
        if ($pkey === 'blocco') {
            return ['ok' => false, 'error' => 'Non si accende a mano: scatta da solo, quando qualcuno usa un potere davanti a te.'];
        }

        $luogo = (string) $pg['luogo'];
        $gts   = Orologio::lineare();

        // Prima di spendere: la scelta deve stare in piedi. Un teletrasporto
        // verso un posto che non conosci non parte, e non costa niente.
        $errore = Poteri::valida($pg, $pkey, $scelta, $gts);
        if ($errore !== null) {
            return ['ok' => false, 'error' => $errore];
        }

        // Il teletrasporto da secondario costa cinque solo se porta te: senza
        // una destinazione sposta un oggetto, e costa il normale.
        $costo = Scheda::costo($potere, $scelta !== '');
        if ((int) $pg['pp'] < $costo) {
            return ['ok' => false, 'error' => 'Non hai abbastanza punti potere: te ne servono ' . $costo . '.'];
        }
        // I punti si pagano qui, con una sola istruzione che li toglie solo se
        // ci sono. Prima si controllava $pg — letto a inizio richiesta — e si
        // sottraeva alla fine: sei richieste in parallelo da sei sessioni
        // passavano tutte e sei, con PP per due usi. L'audit del 13/10/2026 l'ha
        // riprodotto.
        $pagato = Database::run('UPDATE personaggi SET pp = pp - ? WHERE id = ? AND pp >= ?',
            [$costo, (int) $pg['id'], $costo])->rowCount() === 1 || $costo === 0;
        if (!$pagato) {
            return ['ok' => false, 'error' => 'Non hai abbastanza punti potere: te ne servono ' . $costo . '.'];
        }

        // Un esper malato non comanda i poteri: «teletrasporti casuali e
        // problemi con la telecinesi» (regolamento del 1990, ESSERE MALATI).
        // Si paga quello che si voleva fare, e succede un'altra cosa.
        $voluto = null;
        if (Stati::poteriSballati((int) $pg['id'], $gts)) {
            $dado = Rng::for(GameConfig::int('world.seed', 19870406), 'sballa', (int) $pg['id'], $gts);
            if ($dado->int(1, 100) <= GameConfig::int('stati.potere_sballa_pct', 40)) {
                $altro = Poteri::sballa($pg, Scheda::poteri((int) $pg['id']), $pkey, $dado, $gts);
                if ($altro !== null) {
                    $voluto = (string) $potere['nome'];
                    [$potere, $scelta] = $altro;
                    $pkey = (string) $potere['pkey'];
                }
            }
        }

        // Prima di tutto: c'è qualcuno qui che sa spegnere i poteri?
        // Va chiesto adesso, prima di aprire l'incidente, perché un potere
        // spento non è un potere usato male — è un potere che non è
        // successo, e non lascia testimoni né anomalie.
        $spento = self::chiSpegne($pg, $luogo, $gts, (int) $potere['controllo']);
        if ($spento !== null) {
            return self::potereSpento($pg, $potere, $spento, $costo);
        }

        $vistosita = (int) $potere['vistosita'];
        $folla     = Folla::a($luogo, $gts);
        // L'orologio del nonno: per un minuto il tempo e' fermo per tutti
        // tranne che per chi l'ha premuto. Nessuno vede, nemmeno la folla.
        $tempoFermo = Stati::ha((int) $pg['id'], 'tempo_fermo', $gts);
        if ($tempoFermo) {
            $folla = 0;
        }
        // Chi è presente e NON sa già: chi sa non si stupisce più. E chi
        // viene ipnotizzato non vede niente: è proprio il punto.
        $cieco     = Poteri::nonVede($pkey, $scelta);
        $presenti  = $tempoFermo ? [] : array_values(array_filter(self::testimoniPossibili($pg, $luogo),
            static fn (array $t): bool => (int) $t['id'] !== $cieco));
        // Il calore del posto: dove girano storie la gente guarda (PROGETTO §7).
        // E chi è invisibile si fa notare un quinto.
        $calore    = self::calore($luogo, $gts);
        $fattore   = Stati::ha((int) $pg['id'], 'invisibile', $gts) ? 0.2 : 1.0;

        Database::run(
            'INSERT INTO incidenti (attore_id, pkey, luogo, gts, vistosita, folla, stato)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [(int) $pg['id'], $pkey, $luogo, $gts, $vistosita, $folla, 'pulito']
        );
        $incId = Database::lastInsertId();

        // Il generatore si ancora all'**id dell'incidente**, non all'istante.
        // Ancorarlo all'istante sembrava giusto — è quello che si fa per il
        // meteo — ed era sbagliato: con la compressione 1:4 un secondo di
        // gioco dura un quarto di secondo vero, quindi due usi ravvicinati
        // cadevano nello stesso istante, condividevano lo stesso flusso e
        // davano lo **stesso identico esito**. Chi non veniva notato la prima
        // volta non veniva notato mai. L'id dell'incidente è unico per
        // costruzione e cresce sempre.
        $rng = Rng::for(GameConfig::int('world.seed', 19870406), 'uso', $incId);

        // --- Il tiro di Nota, uno per testimone -----------------------------
        $notato = [];
        foreach ($presenti as $t) {
            $p = self::probabilitaNota($vistosita, (int) $t['testa'], (int) $potere['controllo'], $gts, $calore, $fattore);
            $visto = $rng->int(1, 100) <= $p;
            Database::run(
                'INSERT INTO incidente_testimoni (incidente_id, personaggio_id, notato) VALUES (?, ?, ?)',
                [$incId, (int) $t['id'], $visto ? 1 : 0]
            );
            if ($visto) {
                $notato[] = $t;
            }
        }

        // --- E la gente che passava ------------------------------------------
        // Un passante non fa un'anomalia: fa una voce, e alza il calore.
        $vistoDallaFolla = false;
        if ($folla > 0 && $vistosita > 0) {
            $uno = self::probabilitaNota($vistosita, 7, (int) $potere['controllo'], $gts, $calore, $fattore) * 0.35 / 100;
            $almenoUno = 1.0 - pow(1.0 - min(0.9, $uno), $folla);
            $vistoDallaFolla = $rng->float() < $almenoUno;
        }

        Database::run('UPDATE personaggio_poteri SET usi = usi + 1 WHERE personaggio_id = ? AND pkey = ?',
            [(int) $pg['id'], $pkey]);

        $stato = ($notato === [] && !$vistoDallaFolla) ? 'pulito' : 'aperto';
        Database::run('UPDATE incidenti SET notato_da = ?, stato = ? WHERE id = ?',
            [count($notato), $stato, $incId]);

        if ($stato === 'pulito') {
            // Andata liscia: il Controllo cresce. È l'unica cosa che cresce.
            self::miglioraControllo((int) $pg['id'], $pkey);
        } else {
            if ($vistoDallaFolla) {
                self::alzaCalore($luogo, (int) ceil($vistosita / 2));
                Personaggio::traccia($luogo, null, 'voce',
                    self::vocePassante($potere, $rng), $gts, 70);
            }

            // E qui nasce la voce vera (F6). I testimoni la sanno bene
            // perche' c'erano; da domani comincera' a deformarsi passando di
            // bocca in bocca, e fra quattro passaggi sara' «uno del terzo
            // anno ha fatto una cosa strana». E' il modo in cui la caccia
            // all'esper parte da sola, senza che nessuno la programmi.
            $chiSa = array_map(static fn (array $t): int => (int) $t['id'], $notato);
            if ($chiSa !== [] || $vistoDallaFolla) {
                Voci::nasce('potere', (int) $pg['id'], null, $luogo, $chiSa, $pkey, $gts);
            }
        }

        // Chi ha capito e ha deciso di osservarti in silenzio non si stupisce
        // più: annota. Ogni potere usato davanti a lui diventa una voce
        // precisa, anche quando agli altri l'hai fatta passare liscia. È la
        // strada che il progetto chiama «la peggiore».
        // Col tempo fermo, o da invisibili, non vede niente nemmeno chi guarda
        // apposta.
        $osservatori = ($tempoFermo || $fattore < 1.0) ? [] : self::osservatoriPresenti($pg, $luogo);
        if ($osservatori !== [] && $vistosita > 0) {
            Voci::nasce('potere', (int) $pg['id'], null, $luogo, $osservatori, $pkey, $gts);
        }

        // E adesso, quello che il potere fa davvero.
        $effetto = Poteri::effetto($pg, $potere, $scelta, $gts);
        $racconto = self::racconto($potere, $luogo, $notato, $vistoDallaFolla, $folla + count($presenti));
        if ($voluto !== null) {
            $racconto = accorda(sprintf('Hai la febbre, e il potere non risponde: volevi usare %s, ed è '
                . 'partito tutt\'altro. ', mb_strtolower($voluto)), $pg) . $racconto;
        }

        return [
            'ok'        => true,
            'incidente' => $incId,
            'testimoni' => $notato,
            'notato'    => $stato === 'aperto',
            'folla'     => $folla,
            'racconto'  => trim($racconto . ' ' . $effetto),
            'potere'    => $pkey,
        ];
    }

    /**
     * Chi, presente, ha capito il tuo segreto e ha deciso di osservarti.
     *
     * @return list<int>
     */
    public static function osservatoriPresenti(array $pg, string $luogo): array
    {
        return array_map(static fn (array $r): int => (int) $r['id'], Database::all(
            "SELECT p.id FROM sanno s JOIN personaggi p ON p.id = s.chi_sa_id
             WHERE s.esper_id = ? AND s.atteggiamento = 'osserva' AND s.come <> 'confidato'
               AND p.luogo = ? AND p.verso IS NULL AND p.stato = 'attivo'",
            [(int) $pg['id'], $luogo]
        ));
    }

    /**
     * Chi, fra i presenti, può spegnere questo potere — o null.
     *
     * Nel canone il potere di **bloccare i poteri altrui** è di Kazuya e di
     * nessun altro; qui il codice non guarda il nome, guarda chi ha il
     * potere `blocco`. È la stessa scelta fatta per i PNG: niente casi
     * speciali intestati a una persona, perché il giorno che il canone ci
     * dicesse di un secondo personaggio capace di farlo, funzionerebbe da
     * solo.
     *
     * Il Controllo di chi usa il potere difende, ma non salva: chi ha la mano
     * fermissima resiste meglio, e basta. Kazuya è il più forte della
     * famiglia pur avendo otto anni, e deve vedersi.
     *
     * @param array<string,mixed> $pg
     * @return array<string,mixed>|null chi ha spento, se qualcuno ha spento
     */
    public static function chiSpegne(array $pg, string $luogo, int $gts, ?int $controllo = null): ?array
    {
        $bloccanti = Database::all(
            'SELECT p.id, p.nome, p.cognome, p.png, pp.controllo
             FROM personaggi p
             JOIN personaggio_poteri pp ON pp.personaggio_id = p.id AND pp.pkey = ?
             WHERE p.luogo = ? AND p.verso IS NULL AND p.stato = ? AND p.id <> ?
             ORDER BY p.id',
            ['blocco', $luogo, 'attivo', (int) $pg['id']]
        );
        if ($bloccanti === []) {
            return null;
        }

        $base       = GameConfig::int('blocco.prob_base', 55);
        $resistenza = GameConfig::int('blocco.resistenza_controllo', 35);
        $mio        = $controllo ?? 0;

        foreach ($bloccanti as $b) {
            $p = $base * (1.0 - ($resistenza / 100.0) * min(1.0, $mio / 100.0));
            // L'ancora tiene dentro l'ora di gioco e non l'istante: due
            // tentativi nello stesso minuto hanno lo stesso esito — te lo
            // sta spegnendo, insistere subito non serve — ma un'ora dopo il
            // dado è nuovo. È la stessa correzione fatta alle chiacchiere.
            $rng = Rng::for(GameConfig::int('world.seed', 19870406), 'blocco',
                (int) $b['id'], (int) $pg['id'], intdiv($gts, 3600));
            if ($rng->int(1, 100) <= (int) round($p)) {
                return $b;
            }
        }
        return null;
    }

    /**
     * Il potere non è successo. Si perde un punto potere lo stesso — la
     * spinta la si è data — e non c'è nessun incidente da coprire, perché
     * non c'è stato niente da vedere.
     *
     * @param array<string,mixed> $pg
     * @param array<string,mixed> $potere
     * @param array<string,mixed> $chi
     * @return array<string,mixed>
     */
    private static function potereSpento(array $pg, array $potere, array $chi, int $pagato = 0): array
    {
        // Si era gia' pagato tutto; uno spento costa solo la spinta.
        $costo = max(0, GameConfig::int('blocco.costo_pp', 1));
        $resto = max(0, $pagato - $costo);
        if ($resto > 0) {
            Database::run('UPDATE personaggi SET pp = LEAST(pp_max, pp + ?) WHERE id = ?', [$resto, (int) $pg['id']]);
        }
        $nome = trim((string) $chi['nome'] . ' ' . (string) $chi['cognome']);
        return [
            'ok'        => true,
            'spento'    => true,
            'da'        => $nome,
            'incidente' => null,
            'testimoni' => [],
            'notato'    => false,
            'folla'     => 0,
            'racconto'  => sprintf(
                'Ci provi, e non succede niente. Non è che ti riesca male: proprio non parte, '
                . 'come una parola che hai in bocca e non esce. Poi ti accorgi che %s ti sta '
                . 'guardando, e che ti guardava già da prima. Non dice niente. Ha l\'aria di '
                . 'chi si sta divertendo.',
                $nome
            ),
        ];
    }

    /**
     * Probabilità che un testimone si accorga, in percento.
     *
     * Quattro cose la muovono, e sono tutte cose che nell'opera si vedono: la
     * vistosità del potere, l'attenzione di chi guarda, il buio, e la
     * precisione di chi lo usa. Non arriva mai a 100 né scende a 0: c'è
     * sempre quello distratto e c'è sempre quello che guardava proprio lì.
     */
    public static function probabilitaNota(
        int $vistosita, int $testa, int $controllo, ?int $gts = null, int $calore = 0, float $fattore = 1.0
    ): int {
        if ($vistosita <= 0) {
            return 0;    // succede dentro la testa: non c'è niente da vedere
        }
        $p = $vistosita * 9.0 * $fattore;
        // Dove girano storie, la gente guarda: il calore pieno alza la Nota
        // della metà (manopola segreto.calore_nota_pct). Prima il calore era
        // solo una frase sulla pagina del luogo.
        $p *= 1.0 + (max(0, min(100, $calore)) / 100.0) * (GameConfig::int('segreto.calore_nota_pct', 50) / 100.0);
        $p *= 0.6 + 0.4 * ($testa / 15.0);          // chi è sveglio nota di più
        $p *= 1.0 - min(0.5, $controllo / 200.0);   // chi ha mano ferma si fa notare meno

        if (Meteo::notte($gts)) {
            $p *= 0.55;
        }
        $m = Meteo::a($gts);
        if ($m['pioggia'] >= 8.0 || $m['tifone']) {
            $p *= 0.6;      // sotto un rovescio nessuno alza la testa
        } elseif ($m['pioggia'] > 0) {
            $p *= 0.85;
        }

        return max(2, min(95, (int) round($p)));
    }

    /**
     * Chi potrebbe vedere: i presenti che non sanno già.
     * @return list<array<string,mixed>>
     */
    public static function testimoniPossibili(array $pg, string $luogo): array
    {
        return Database::all(
            'SELECT p.id, p.nome, p.cognome, p.testa, p.esper
             FROM personaggi p
             WHERE p.luogo = ? AND p.verso IS NULL AND p.id <> ? AND p.stato = ?
               AND NOT EXISTS (SELECT 1 FROM sanno s WHERE s.esper_id = ? AND s.chi_sa_id = p.id)
             ORDER BY p.id',
            [$luogo, (int) $pg['id'], 'attivo', (int) $pg['id']]
        );
    }

    // --- La copertura ---------------------------------------------------------

    public const COPERTURE = [
        'scusa'          => ['nome' => 'Inventare una scusa',   'abilita' => 'testa',    'base' => 25, 'per' => 4],
        'diversivo'      => ['nome' => 'Creare un diversivo',   'abilita' => 'dai_suki', 'base' => 25, 'per' => 4],
        'sfacciataggine' => ['nome' => 'Fare finta di niente',  'abilita' => 'kakko',    'base' => 20, 'per' => 6],
    ];

    /**
     * Prova a coprire un incidente aperto.
     *
     * Costa un punto di **compostezza**: mentire in faccia a qualcuno che ti
     * ha appena visto fare una cosa impossibile consuma. Chi è già a zero non
     * ci prova nemmeno — non perché non gli sia permesso, ma perché in quello
     * stato non gli riesce.
     *
     * @return array{ok:bool, error?:string, riusciti?:int, falliti?:int, racconto?:string}
     */
    public static function copri(array $pg, int $incidenteId, string $come): array
    {
        $inc = Database::first('SELECT * FROM incidenti WHERE id = ? AND attore_id = ?',
            [$incidenteId, (int) $pg['id']]);
        if ($inc === null) {
            return ['ok' => false, 'error' => 'Quell\'incidente non esiste.'];
        }
        if ((string) $inc['stato'] !== 'aperto') {
            return ['ok' => false, 'error' => 'Non c\'è più niente da coprire.'];
        }
        if ($come !== 'niente' && !isset(self::COPERTURE[$come])) {
            return ['ok' => false, 'error' => 'Non so cosa vorresti fare.'];
        }

        // Il lucchetto: due richieste ravvicinate non devono coprire due volte
        // lo stesso incidente e assolvere due volte gli stessi testimoni.
        return Lock::con('copri:' . $incidenteId, function () use ($pg, $inc, $come, $incidenteId): array {
            $fresco = Database::first('SELECT stato FROM incidenti WHERE id = ?', [$incidenteId]);
            if ($fresco === null || (string) $fresco['stato'] !== 'aperto') {
                return ['ok' => false, 'error' => 'Non c\'è più niente da coprire.'];
            }

            $gts = Orologio::lineare();
            $rng = Rng::for(GameConfig::int('world.seed', 19870406), 'copri', $incidenteId);

            $prob = 0;
            if ($come !== 'niente') {
                if ((int) $pg['compostezza'] < 1) {
                    return ['ok' => false, 'error' => 'Hai i nervi troppo a pezzi per mettere insieme una frase.'];
                }
                $c = self::COPERTURE[$come];
                $prob = $c['base'] + (int) $pg[$c['abilita']] * $c['per'];
                // Un complice che sa già: coprire in due è un'altra cosa.
                if (self::complicePresente($pg)) {
                    $prob += 25;
                }
                $prob = max(5, min(95, $prob));
                Database::run('UPDATE personaggi SET compostezza = GREATEST(compostezza, 1) - 1 WHERE id = ?',
                    [(int) $pg['id']]);
            }

            $riusciti = 0;
            $falliti  = 0;
            foreach (Database::all(
                'SELECT it.personaggio_id, p.nome, p.cognome
                 FROM incidente_testimoni it JOIN personaggi p ON p.id = it.personaggio_id
                 WHERE it.incidente_id = ? AND it.notato = 1', [$incidenteId]
            ) as $t) {
                if ($prob > 0 && $rng->int(1, 100) <= $prob) {
                    Database::run(
                        'UPDATE incidente_testimoni SET coperto = 1 WHERE incidente_id = ? AND personaggio_id = ?',
                        [$incidenteId, (int) $t['personaggio_id']]
                    );
                    $riusciti++;
                } else {
                    self::segnaAnomalia((int) $t['personaggio_id'], (int) $pg['id'], $incidenteId,
                        (string) $inc['pkey'], (string) $inc['luogo'], $gts);
                    $falliti++;
                }
            }

            Database::run('UPDATE incidenti SET stato = ?, copertura = ? WHERE id = ?',
                [$falliti === 0 ? 'coperto' : 'sfuggito', $come === 'niente' ? null : $come, $incidenteId]);

            if ($falliti === 0) {
                self::miglioraControllo((int) $pg['id'], (string) $inc['pkey']);
            }

            return [
                'ok'       => true,
                'riusciti' => $riusciti,
                'falliti'  => $falliti,
                'racconto' => match (true) {
                    $falliti === 0 && $riusciti > 0 => 'Ha funzionato. Nessuno ci ha creduto fino in fondo, ma nessuno ha insistito.',
                    $falliti === 0                  => 'Non c\'era nessuno da convincere.',
                    $riusciti > 0                   => 'Su qualcuno ha funzionato. Non su tutti.',
                    $come === 'niente'              => 'Non hai detto niente. Loro hanno visto.',
                    default                         => 'Non ci ha creduto nessuno.',
                },
            ];
        }, ['ok' => false, 'error' => 'Un attimo: stai già rispondendo.']);
    }

    /** Un altro personaggio presente che sa già il tuo segreto. */
    public static function complicePresente(array $pg): bool
    {
        return Database::first(
            'SELECT 1 FROM sanno s JOIN personaggi p ON p.id = s.chi_sa_id
             WHERE s.esper_id = ? AND p.luogo = ? AND p.verso IS NULL AND p.stato = ?',
            [(int) $pg['id'], (string) $pg['luogo'], 'attivo']
        ) !== null;
    }

    /**
     * Gli incidenti che nessuno ha chiuso.
     *
     * Il diagramma di progetto (PROGETTO.md §7) mette **«non fare niente»** fra
     * le quattro vie dopo un incidente aperto, e la fa confluire nello stesso
     * esito delle altre: chi non copre lascia al testimone un'anomalia. Nel
     * codice quella via c'era — `copri($pg, $id, 'niente')` — ma era una
     * SCELTA. Chi chiudeva il browser invece di sceglierla non pagava niente:
     * l'incidente restava «aperto» per sempre e nessuna anomalia nasceva.
     *
     * Il risultato era il contrario di quello che il gioco vuole insegnare —
     * dichiarare «non faccio niente» costava, andarsene no — e in un gioco
     * persistente una strategia dominante del genere la trova il primo
     * giocatore attento e la insegna a tutti gli altri.
     *
     * Quindi il tempo decide al posto di chi non decide: passati
     * `segreto.incidente_scade_minuti` minuti di gioco, l'incidente vale come
     * «non ho fatto niente». Non e' una punizione aggiuntiva, e' la stessa
     * regola applicata anche a chi non risponde: in quartiere si resta davanti
     * alla persona che ha visto, e il silenzio e' una risposta.
     *
     * @return int quanti incidenti sono stati chiusi dal tempo
     */
    public static function incidentiScaduti(?int $gts = null): int
    {
        $gts   = $gts ?? Orologio::lineare();
        $soglia = max(1, GameConfig::int('segreto.incidente_scade_minuti', 60)) * 60;

        $aperti = Database::all(
            "SELECT * FROM incidenti WHERE stato = 'aperto' AND gts <= ?",
            [$gts - $soglia]
        );
        $chiusi = 0;
        foreach ($aperti as $inc) {
            // Lo stesso lucchetto di copri(): se il giocatore sta scegliendo
            // proprio adesso, decide lui e non l'orologio.
            $fatto = Lock::con('copri:' . (int) $inc['id'], function () use ($inc): bool {
                $fresco = Database::first('SELECT stato FROM incidenti WHERE id = ?', [(int) $inc['id']]);
                if ($fresco === null || (string) $fresco['stato'] !== 'aperto') {
                    return false;
                }
                foreach (Database::all(
                    'SELECT personaggio_id FROM incidente_testimoni
                     WHERE incidente_id = ? AND notato = 1 AND coperto = 0',
                    [(int) $inc['id']]
                ) as $t) {
                    self::segnaAnomalia(
                        (int) $t['personaggio_id'], (int) $inc['attore_id'], (int) $inc['id'],
                        (string) $inc['pkey'], (string) $inc['luogo'], (int) $inc['gts']
                    );
                }
                Database::run(
                    "UPDATE incidenti SET stato = 'sfuggito', copertura = NULL WHERE id = ?",
                    [(int) $inc['id']]
                );
                return true;
            }, false);
            if ($fatto === true) {
                $chiusi++;
            }
        }
        return $chiusi;
    }

    // --- Le anomalie -----------------------------------------------------------

    private static function segnaAnomalia(
        int $osservatore, int $soggetto, int $incidenteId, string $pkey, string $luogo, int $gts
    ): void {
        $chi = Database::first('SELECT nome, cognome, sesso FROM personaggi WHERE id = ?', [$soggetto]);
        $pot = Database::first('SELECT nome FROM poteri WHERE pkey = ?', [$pkey]);
        $testo = sprintf(
            '%s %s, %s, %s: qualcosa che non torna. %s.',
            $chi['cognome'] ?? '?', $chi['nome'] ?? '?',
            Luoghi::nome($luogo),
            mb_strtolower(Orologio::breve($gts)),
            self::comeAppare((string) $pkey, (string) ($chi['sesso'] ?? 'm') === 'f')
        );
        Database::run(
            'INSERT INTO anomalie (osservatore_id, soggetto_id, incidente_id, gts, luogo, testo)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$osservatore, $soggetto, $incidenteId, $gts, $luogo, mb_substr($testo, 0, 255)]
        );
    }

    /**
     * Come appare, a chi non sa. Il testimone non vede «un teletrasporto»:
     * vede una persona che c'era e poi non c'è più. È la differenza fra un
     * indizio e un'etichetta, ed è tutto il mestiere di chi indaga.
     */
    private static function comeAppare(string $pkey, bool $donna = false): string
    {
        // Il testo resta scritto nel taccuino una volta per tutte, quindi
        // l'accordo va fatto qui: prima era tutto al maschile, e il taccuino
        // diceva di Kurumi «si è voltato», «è rimasto immobile», «lui guardava».
        $frase = match ($pkey) {
            'telecinesi'      => 'Una cosa si è mossa da sola, e {lui} guardava proprio lì',
            'teletrasporto'   => 'Era lì, e un istante dopo non c\'era più',
            'telepatia'       => 'Ha risposto a una domanda che nessuno aveva fatto ad alta voce',
            'supervelocita'   => 'Ha attraversato il cortile in un tempo che non è un tempo',
            'supersensi'      => 'Si è voltat{o} verso un rumore che non si sentiva',
            'chiaroveggenza'  => 'È rimast{o} immobile con gli occhi chiusi, e poi sapeva',
            'scambio_corpo'   => 'Per qualche minuto non si è comportat{o} come sé stess{o}',
            'cambio_identita' => 'Per un attimo è sembrat{o} un\'altra persona',
            'fantasmi'        => 'C\'era qualcosa nell\'aria che non poteva esserci',
            'ipnosi'          => 'Ha detto una frase, e l\'altro ha obbedito senza discutere',
            'autoipnosi'      => 'Si è mess{o} davanti a uno specchio e ne è uscit{o} divers{o}',
            'natura'          => 'Il gatto stava a sentire quello che {lui} diceva. Il gatto',
            'invisibilita'    => 'Ha sentito dei passi dove non c\'era nessuno',
            'voce'            => 'La voce veniva da dove {lui} non era',
            default           => 'Non saprebbe dire cosa, ma qualcosa',
        };
        return strtr($frase, ['{o}' => $donna ? 'a' : 'o', '{lui}' => $donna ? 'lei' : 'lui']);
    }

    /**
     * Cosa è appena successo, raccontato a chi l'ha fatto.
     *
     * È il testo che il giocatore legge subito dopo aver premuto: deve dire
     * due cose in tre righe — che il potere ha funzionato, e se qualcuno ha
     * guardato. Il secondo pezzo conta più del primo.
     *
     * @param list<array<string,mixed>> $notato
     */
    private static function racconto(
        array $potere, string $luogo, array $notato, bool $vistoDallaFolla, int $folla
    ): string {
        $dove = Luoghi::nomeInFrase($luogo);
        $che  = mb_strtolower((string) $potere['nome']);

        // $folla qui e' tutta la gente che c'era: i passanti e chi si conosce.
        // Contare solo i passanti faceva dire «non c'è anima viva» in un parco
        // con tre persone sedute sulla panchina.
        if ($notato === [] && !$vistoDallaFolla) {
            return $folla === 0
                ? "Usi {$che}. " . mb_strtoupper(mb_substr($dove, 0, 1)) . mb_substr($dove, 1) . ": non c'è anima viva, e nessuno saprà mai che è successo."
                : "Usi {$che}. C'era gente, ma nessuno guardava dalla tua parte. Stavolta è andata.";
        }

        $pezzi = [];
        if ($notato !== []) {
            $nomi = array_map(
                static fn (array $t): string => $t['cognome'] . ' ' . $t['nome'],
                $notato
            );
            $pezzi[] = count($nomi) === 1
                ? $nomi[0] . ' ti sta guardando, e ha visto tutto'
                : implode(' e ', [implode(', ', array_slice($nomi, 0, -1)), end($nomi)])
                    . ' hanno visto tutto';
        }
        if ($vistoDallaFolla) {
            $pezzi[] = 'e fra la gente che passava c\'è chi si è fermato a guardare';
        }

        return "Usi {$che}, e per un istante {$dove} smette di essere un posto normale. "
            . ucfirst(implode(', ', $pezzi)) . '.';
    }

    private static function vocePassante(array $potere, Rng $rng): string
    {
        return (string) $rng->pick([
            'Qualcuno giura di aver visto una cosa impossibile, qui, poco fa.',
            'Due passanti si sono fermati a discutere di cosa avessero visto. Non erano d\'accordo.',
            'Gira una storia strana su quello che è successo qui. Cambia a ogni racconto.',
            'C\'è chi dice che qui succedono cose. Lo dicono ridendo, ma lo dicono.',
        ]);
    }

    // --- Il taccuino ------------------------------------------------------------

    /**
     * Le anomalie che un personaggio si porta dietro, raggruppate per soggetto.
     *
     * @return list<array{soggetto:array<string,mixed>, anomalie:list<array<string,mixed>>,
     *                    quante:int, basta:bool, fondato:bool}>
     */
    public static function taccuino(int $osservatoreId): array
    {
        $finestra = GameConfig::int('segreto.finestra_giorni', 30) * 86400;
        $servono  = GameConfig::int('segreto.anomalie_per_sospetto', 3);
        $adesso   = Orologio::lineare();

        $righe = Database::all(
            'SELECT a.*, p.nome, p.cognome, p.sezione, p.anno
             FROM anomalie a JOIN personaggi p ON p.id = a.soggetto_id
             WHERE a.osservatore_id = ? AND a.gts >= ?
             ORDER BY a.soggetto_id, a.gts DESC',
            [$osservatoreId, $adesso - $finestra]
        );

        $per = [];
        foreach ($righe as $r) {
            $s = (int) $r['soggetto_id'];
            $per[$s] ??= [
                'soggetto' => ['id' => $s, 'nome' => $r['nome'], 'cognome' => $r['cognome'],
                               'sezione' => $r['sezione'], 'anno' => $r['anno']],
                'anomalie' => [],
            ];
            $r['fa_minuti'] = (int) floor(($adesso - (int) $r['gts']) / 60);
            $per[$s]['anomalie'][] = $r;
        }

        $out = [];
        foreach ($per as $s => $g) {
            $g['quante']  = count($g['anomalie']);
            $g['basta']   = $g['quante'] >= $servono;
            $g['fondato'] = self::sa($s, $osservatoreId);
            $g['atteggiamento'] = (string) (Database::first(
                'SELECT atteggiamento FROM sanno WHERE esper_id = ? AND chi_sa_id = ?', [$s, $osservatoreId]
            )['atteggiamento'] ?? '');
            $out[] = $g;
        }
        usort($out, static fn (array $a, array $b): int => $b['quante'] <=> $a['quante']);
        return $out;
    }

    public static function sa(int $esperId, int $chiSaId): bool
    {
        return Database::first('SELECT 1 FROM sanno WHERE esper_id = ? AND chi_sa_id = ?',
            [$esperId, $chiSaId]) !== null;
    }

    /**
     * Mettere insieme le anomalie: l'azione di chi osserva.
     *
     * Non è automatica, e non deve esserlo. Avere tre indizi non vuol dire
     * aver capito: bisogna decidere di guardarli insieme, e poi riuscirci. Il
     * tiro è l'**Intuizione**, cioè 30+TESTA×2 — la stessa formula del
     * regolamento del 1990, che qui diventa il verbo principale di chi non ha
     * poteri.
     *
     * @return array{ok:bool, error?:string, capito?:bool, racconto?:string}
     */
    public static function collega(array $osservatore, int $soggettoId): array
    {
        $servono  = GameConfig::int('segreto.anomalie_per_sospetto', 3);
        $finestra = GameConfig::int('segreto.finestra_giorni', 30) * 86400;
        $adesso   = Orologio::lineare();

        if (self::sa($soggettoId, (int) $osservatore['id'])) {
            return ['ok' => false, 'error' => 'Di quella persona sai già tutto.'];
        }

        $n = (int) (Database::first(
            'SELECT COUNT(*) n FROM anomalie WHERE osservatore_id = ? AND soggetto_id = ? AND gts >= ?',
            [(int) $osservatore['id'], $soggettoId, $adesso - $finestra]
        )['n'] ?? 0);
        if ($n < $servono) {
            return ['ok' => false, 'error' => "Ti servono almeno {$servono} annotazioni su quella persona: ne hai {$n}."];
        }

        // Un tentativo per anomalia raccolta: chi ha guardato di più ha più
        // probabilità, ma non si può insistere all'infinito sugli stessi indizi.
        $gia = (int) (Database::first(
            'SELECT COUNT(*) n FROM anomalie WHERE osservatore_id = ? AND soggetto_id = ? AND collegata = 1 AND gts >= ?',
            [(int) $osservatore['id'], $soggettoId, $adesso - $finestra]
        )['n'] ?? 0);
        if ($gia >= $n) {
            return ['ok' => false, 'error' => 'Ci hai già pensato su abbastanza. Ti serve qualcosa di nuovo.'];
        }

        $rng = Rng::for(GameConfig::int('world.seed', 19870406), 'collega',
            (int) $osservatore['id'], $soggettoId, $gia);
        $prob   = Scheda::intuizione($osservatore);
        $capito = $rng->int(1, 100) <= $prob;

        Database::run(
            'UPDATE anomalie SET collegata = 1 WHERE osservatore_id = ? AND soggetto_id = ? AND collegata = 0
             ORDER BY gts LIMIT 1',
            [(int) $osservatore['id'], $soggettoId]
        );

        if (!$capito) {
            return ['ok' => true, 'capito' => false,
                'racconto' => 'Ci giri intorno per un\'ora e non ne cavi niente. C\'è qualcosa, ma non si lascia prendere.'];
        }

        Database::run(
            'INSERT IGNORE INTO sanno (esper_id, chi_sa_id, come, gts) VALUES (?, ?, ?, ?)',
            [$soggettoId, (int) $osservatore['id'], 'scoperto', $adesso]
        );
        $chi = Database::first('SELECT nome, cognome FROM personaggi WHERE id = ?', [$soggettoId]);

        return ['ok' => true, 'capito' => true, 'racconto' => sprintf(
            'Metti in fila le annotazioni e all\'improvviso stanno insieme. %s %s non è come gli altri, '
            . 'e adesso lo sai. Resta da decidere cosa farne.',
            $chi['cognome'] ?? '?', $chi['nome'] ?? '?'
        )];
    }

    /**
     * Anche gli abitanti mettono insieme quello che hanno visto.
     *
     * Prima annotavano le anomalie e basta: nessuno le guardava mai insieme,
     * quindi un esper che usava i poteri solo davanti a loro non poteva essere
     * scoperto da nessuno — e con due giocatori nel quartiere il Segreto non
     * faceva paura. Adesso un abitante con abbastanza annotazioni su qualcuno
     * prova a collegarle, con lo stesso tiro e le stesse regole di un
     * giocatore: un tentativo per annotazione, e la TESTA che decide.
     *
     * Solo chi non ha poteri. I Kasuga sanno benissimo cos'e' un esper e
     * l'ultima cosa che farebbero e' dirlo in giro: per loro il segreto di un
     * altro della stirpe e' il loro stesso segreto.
     *
     * @return int quanti hanno capito
     */
    public static function abitantiCollegano(?int $adesso = null): int
    {
        $adesso  ??= Orologio::lineare();
        $servono  = GameConfig::int('segreto.anomalie_per_sospetto', 3);
        $finestra = GameConfig::int('segreto.finestra_giorni', 30) * 86400;
        $capiti   = 0;
        foreach (Database::all(
            "SELECT a.osservatore_id, a.soggetto_id, COUNT(*) n, SUM(a.collegata) usate
             FROM anomalie a
             JOIN personaggi o ON o.id = a.osservatore_id
             JOIN personaggi s ON s.id = a.soggetto_id
             WHERE o.png IS NOT NULL AND o.esper = 0 AND o.stato = 'attivo'
               AND s.png IS NULL AND s.stato = 'attivo' AND a.gts >= ?
               AND NOT EXISTS (SELECT 1 FROM sanno x WHERE x.esper_id = a.soggetto_id AND x.chi_sa_id = a.osservatore_id)
             GROUP BY a.osservatore_id, a.soggetto_id
             HAVING n >= ? AND usate < n",
            [$adesso - $finestra, $servono]
        ) as $r) {
            $chi = Database::first('SELECT * FROM personaggi WHERE id = ?', [(int) $r['osservatore_id']]);
            if ($chi === null) {
                continue;
            }
            $esito = self::collega($chi, (int) $r['soggetto_id']);
            if (($esito['capito'] ?? false) === true) {
                $capiti++;
                self::abitanteDecide($chi, (int) $r['soggetto_id']);
            }
        }
        return $capiti;
    }

    // --- Il sospetto fondato: cosa farne -------------------------------------------

    /**
     * Un abitante ha appena capito: sceglie la sua strada, e se la sua strada
     * e' raccontarlo, lo racconta subito a chi ha intorno.
     */
    private static function abitanteDecide(array $chi, int $esperId): void
    {
        $come = self::ATTEGGIAMENTO_DEGLI_ABITANTI[(string) $chi['png']] ?? 'osserva';
        if ($come === 'affronta' && !self::presenteCon($chi, $esperId)) {
            // Non e' qui: lo affrontera' appena lo vede. Intanto osserva.
            Database::run("UPDATE sanno SET atteggiamento = 'affronta' WHERE esper_id = ? AND chi_sa_id = ?",
                [$esperId, (int) $chi['id']]);
            return;
        }
        // Gli abitanti non si stancano e non hanno vergogna: niente compostezza.
        Database::run('UPDATE personaggi SET compostezza = GREATEST(compostezza, 1) WHERE id = ?', [(int) $chi['id']]);
        $esito = self::scegli(Database::first('SELECT * FROM personaggi WHERE id = ?', [(int) $chi['id']]) ?? $chi, $esperId, $come);
        if (!$esito['ok']) {
            // Se per qualunque ragione non e' riuscito adesso, la strada scelta
            // resta segnata e il battito la riprende: prima restava vuota per
            // sempre, e quel «scoperto» contava senza che l'esper potesse
            // rispondere.
            Database::run('UPDATE sanno SET atteggiamento = ? WHERE esper_id = ? AND chi_sa_id = ? AND atteggiamento = \'\'',
                [$come, $esperId, (int) $chi['id']]);
        }
    }

    /**
     * Gli abitanti che hanno deciso di affrontarti, e ti trovano: lo fanno.
     * Lo chiama il battito, dopo che gli abitanti si sono mossi.
     */
    public static function abitantiAffrontano(): int
    {
        $n = 0;
        foreach (Database::all(
            "SELECT s.esper_id, c.* FROM sanno s
             JOIN personaggi c ON c.id = s.chi_sa_id
             JOIN personaggi e ON e.id = s.esper_id
             WHERE s.atteggiamento = 'affronta' AND s.affrontato_gts IS NULL AND s.come <> 'confidato'
               AND c.png IS NOT NULL AND c.stato = 'attivo' AND e.stato = 'attivo'
               AND e.luogo = c.luogo AND e.verso IS NULL AND c.verso IS NULL"
        ) as $r) {
            $esperId = (int) $r['esper_id'];
            unset($r['esper_id']);
            Database::run('UPDATE personaggi SET compostezza = GREATEST(compostezza, 1) WHERE id = ?', [(int) $r['id']]);
            $r['compostezza'] = max(1, (int) $r['compostezza']);
            if (self::scegli($r, $esperId, 'affronta')['ok']) {
                $n++;
            }
        }
        return $n;
    }

    /** Le tre strade di PROGETTO §7, come le legge chi deve sceglierne una. */
    public const ATTEGGIAMENTI = [
        'affronta' => 'Prendere da parte la persona e dirglielo in faccia',
        'dice'     => 'Raccontarlo in giro',
        'osserva'  => 'Tenerselo per sé, e guardare',
    ];

    /**
     * Chi ha capito da solo sceglie cosa farne.
     *
     *  - **affrontare**: si fa davanti a lui, costa un punto di compostezza, e
     *    gli lascia una scelta — confidarsi, e allora chi ha capito diventa un
     *    complice, oppure negare. È l'occasione di confidarsi del progetto;
     *  - **dirlo in giro**: nasce una voce sul segreto, chiara, che parte da
     *    chi l'ha detto e da chi era lì ad ascoltare, e il posto si scalda.
     *    Una volta al giorno: ripeterlo non lo rende più vero;
     *  - **osservare**: non succede niente, subito. Da lì in poi ogni potere
     *    usato davanti a lui diventa una voce precisa (vedi `usa()`). È «il
     *    peggiore», e infatti non si vede.
     *
     * @return array{ok:bool, error?:string, racconto?:string}
     */
    public static function scegli(array $chi, int $esperId, string $come): array
    {
        return Lock::con('sospetto:' . $esperId . ':' . (int) $chi['id'],
            static fn (): array => self::scegliOra($chi, $esperId, $come),
            ['ok' => false, 'error' => 'Un momento: ci stai gia\' pensando.']);
    }

    private static function scegliOra(array $chi, int $esperId, string $come): array
    {
        if (!isset(self::ATTEGGIAMENTI[$come])) {
            return ['ok' => false, 'error' => 'Non so cosa vorresti fare.'];
        }
        $riga = Database::first("SELECT * FROM sanno WHERE esper_id = ? AND chi_sa_id = ? AND come <> 'confidato'",
            [$esperId, (int) $chi['id']]);
        if ($riga === null) {
            return ['ok' => false, 'error' => 'Di quella persona non sai niente di certo.'];
        }
        $esper = Database::first('SELECT * FROM personaggi WHERE id = ?', [$esperId]);
        if ($esper === null || (string) $esper['stato'] !== 'attivo') {
            return ['ok' => false, 'error' => 'Non è più nel quartiere.'];
        }
        $gts = Orologio::lineare();
        $nome = Personaggio::nomeCompleto($esper);

        if ($come === 'affronta') {
            if ((string) $esper['luogo'] !== (string) $chi['luogo'] || $esper['verso'] !== null) {
                return ['ok' => false, 'error' => 'Per dirglielo in faccia deve essere qui.'];
            }
            if ((int) $chi['compostezza'] < 1) {
                return ['ok' => false, 'error' => 'Non hai la faccia tosta, adesso. Riposati.'];
            }
            Database::run('UPDATE personaggi SET compostezza = GREATEST(compostezza, 1) - 1 WHERE id = ?', [(int) $chi['id']]);
            Database::run("UPDATE sanno SET atteggiamento = 'affronta', affrontato_gts = ? WHERE esper_id = ? AND chi_sa_id = ?",
                [$gts, $esperId, (int) $chi['id']]);
            return ['ok' => true, 'racconto' => sprintf(
                'Prendi %s da parte, dove nessuno sente, e glielo dici: lo so. Non aggiungi altro. '
                . 'Adesso tocca a %s decidere se fidarsi di te o guardarti negli occhi e dire che non è vero.',
                $nome, (string) $esper['sesso'] === 'f' ? 'lei' : 'lui')];
        }

        if ($come === 'dice') {
            $gia = Database::first(
                "SELECT 1 x FROM voci WHERE tipo = 'potere' AND dettaglio = 'segreto' AND soggetto_id = ? AND gts > ?
                   AND id IN (SELECT voce_id FROM voci_versioni WHERE personaggio_id = ? AND da_id IS NULL) LIMIT 1",
                [$esperId, $gts - 86400, (int) $chi['id']]);
            if ($gia !== null) {
                return ['ok' => false, 'error' => 'L\'hai già raccontato oggi. Ripeterlo non lo rende più vero.'];
            }
            $ascoltano = array_map(static fn (array $p): int => (int) $p['id'],
                Personaggio::presenti((string) $chi['luogo'], (int) $chi['id']));
            $ascoltano = array_values(array_filter($ascoltano, static fn (int $i): bool => $i !== $esperId));
            Voci::nasce('potere', $esperId, null, (string) $chi['luogo'], [(int) $chi['id'], ...$ascoltano], 'segreto', $gts);
            self::alzaCalore((string) $chi['luogo'], 8, $gts);
            Database::run("UPDATE sanno SET atteggiamento = 'dice' WHERE esper_id = ? AND chi_sa_id = ?",
                [$esperId, (int) $chi['id']]);
            return ['ok' => true, 'racconto' => $ascoltano === []
                ? sprintf('Non c\'è nessuno a cui dirlo, qui, ma la storia ce l\'hai pronta: la prossima persona che incontri saprà di %s.', $nome)
                : sprintf('Lo racconti. Non tutto, e non subito: abbastanza perché chi ti ascolta cominci a guardare %s con altri occhi.', $nome)];
        }

        Database::run("UPDATE sanno SET atteggiamento = 'osserva', affrontato_gts = NULL WHERE esper_id = ? AND chi_sa_id = ?",
            [$esperId, (int) $chi['id']]);
        return ['ok' => true, 'racconto' => sprintf(
            'Non dici niente a nessuno, nemmeno a %s. Guardi, e aspetti. Prima o poi lo rifarà, e tu ci sarai.', $nome)];
    }

    /** Questa persona e' qui, ferma, adesso? */
    public static function presenteCon(array $pg, int $altroId): bool
    {
        return Database::first(
            "SELECT 1 x FROM personaggi WHERE id = ? AND luogo = ? AND verso IS NULL AND stato = 'attivo'",
            [$altroId, (string) $pg['luogo']]) !== null;
    }

    /**
     * Chi ti ha preso da parte e aspetta una risposta.
     *
     * @return list<array<string,mixed>>
     */
    public static function affrontatoDa(int $esperId): array
    {
        return Database::all(
            "SELECT p.id, p.nome, p.cognome, p.sesso, s.affrontato_gts FROM sanno s JOIN personaggi p ON p.id = s.chi_sa_id
             WHERE s.esper_id = ? AND s.affrontato_gts IS NOT NULL AND s.come <> 'confidato' AND p.stato = 'attivo'
             ORDER BY s.affrontato_gts",
            [$esperId]
        );
    }

    /**
     * Negare, a chi ti ha preso da parte. Il tiro è la faccia tosta (KAKKO)
     * contro la sua testa: riesce, e torna a non esserne sicuro — le sue
     * annotazioni restano, ma il sospetto fondato no; non riesce, e adesso
     * oltre a saperlo è anche offeso.
     *
     * @return array{ok:bool, error?:string, riuscito?:bool, racconto?:string}
     */
    public static function nega(array $esper, int $chiId): array
    {
        $riga = Database::first(
            "SELECT s.*, p.testa, p.nome, p.sesso FROM sanno s JOIN personaggi p ON p.id = s.chi_sa_id
             WHERE s.esper_id = ? AND s.chi_sa_id = ? AND s.affrontato_gts IS NOT NULL AND s.come <> 'confidato'",
            [(int) $esper['id'], $chiId]);
        if ($riga === null) {
            return ['ok' => false, 'error' => 'Nessuno ti sta chiedendo niente.'];
        }
        // Si nega guardando in faccia, come ci si confida: tutte e due le
        // risposte vogliono la persona davanti. Prima «nega» funzionava a
        // distanza e «confidati» no.
        if (!self::presenteCon($esper, $chiId)) {
            return ['ok' => false, 'error' => 'Per guardarl' . ((string) $riga['sesso'] === 'f' ? 'a' : 'o')
                . ' negli occhi deve essere qui.'];
        }
        if ((int) $esper['compostezza'] < 2) {
            return ['ok' => false, 'error' => 'Non riusciresti a guardarl' . ((string) $riga['sesso'] === 'f' ? 'a' : 'o') . ' negli occhi, adesso.'];
        }
        $gts = Orologio::lineare();
        Database::run('UPDATE personaggi SET compostezza = GREATEST(compostezza, 2) - 2 WHERE id = ?', [(int) $esper['id']]);
        $prob = max(5, min(85, 30 + (int) $esper['kakko'] * 5 - (int) $riga['testa'] * 2));
        $rng  = Rng::for(GameConfig::int('world.seed', 19870406), 'nega', (int) $esper['id'], $chiId, (int) $riga['affrontato_gts']);
        if ($rng->int(1, 100) <= $prob) {
            Database::run('DELETE FROM sanno WHERE esper_id = ? AND chi_sa_id = ?', [(int) $esper['id'], $chiId]);
            return ['ok' => true, 'riuscito' => true, 'racconto' => sprintf(
                'Ridi. Ridi nel modo giusto, né troppo né poco, e chiedi a %s se si sente bene. Funziona: '
                . 'vedi il dubbio tornar%s in faccia. Non ha smesso di pensarci, ma non ne è più sicur%s.',
                $riga['nome'], (string) $riga['sesso'] === 'f' ? 'le' : 'gli', (string) $riga['sesso'] === 'f' ? 'a' : 'o')];
        }
        Database::run('UPDATE sanno SET affrontato_gts = NULL, atteggiamento = ? WHERE esper_id = ? AND chi_sa_id = ?',
            ['dice', (int) $esper['id'], $chiId]);
        Legami::muovi($chiId, (int) $esper['id'], -6, 10, $gts);
        return ['ok' => true, 'riuscito' => false, 'racconto' => sprintf(
            'Neghi, e %s ti lascia finire. Poi ti guarda come si guarda qualcuno che ti ha appena mentito in '
            . 'faccia, e se ne va senza dire niente. Adesso non lo sa soltanto: è anche offes%s, e non lo terrà per sé.',
            $riga['nome'], (string) $riga['sesso'] === 'f' ? 'a' : 'o')];
    }

    /**
     * Cosa fa un abitante quando capisce: dipende da chi è. Komatsu, Hatta e
     * Sayuri lo raccontano; Hikaru e Yusaku te lo dicono in faccia; Madoka, il
     * Master e Hiromi guardano e tacciono, che è il loro modo.
     */
    public const ATTEGGIAMENTO_DEGLI_ABITANTI = [
        'komatsu' => 'dice', 'hatta' => 'dice', 'sayuri' => 'dice',
        'hikaru' => 'affronta', 'yusaku' => 'affronta',
        'madoka' => 'osserva', 'master' => 'osserva', 'hiromi' => 'osserva',
    ];

    // --- Confidarsi ---------------------------------------------------------------

    /**
     * Dire il segreto a qualcuno, di propria volontà.
     *
     * Azzera le sue annotazioni e lo trasforma in un complice: da lì in poi
     * non può più accorgersi di niente, perché sa già, e se è presente aiuta a
     * coprire. È la mossa che toglie di mezzo il pericolo più grande — e che
     * lo consegna a una persona sola.
     *
     * @return array{ok:bool, error?:string, racconto?:string}
     */
    public static function confida(array $pg, int $aChi): array
    {
        if (!(bool) $pg['esper']) {
            return ['ok' => false, 'error' => 'Non hai niente da confidare.'];
        }
        if ($aChi === (int) $pg['id']) {
            return ['ok' => false, 'error' => 'Lo sai già.'];
        }
        $altro = Database::first(
            'SELECT * FROM personaggi WHERE id = ? AND luogo = ? AND verso IS NULL AND stato = ?',
            [$aChi, (string) $pg['luogo'], 'attivo']
        );
        if ($altro === null) {
            return ['ok' => false, 'error' => 'Quella persona non è qui.'];
        }
        $gia = Database::first('SELECT come FROM sanno WHERE esper_id = ? AND chi_sa_id = ?', [(int) $pg['id'], $aChi]);
        if ($gia !== null && (string) $gia['come'] === 'confidato') {
            return ['ok' => false, 'error' => 'Lo sa già.'];
        }

        $gts = Orologio::lineare();
        if ($gia !== null) {
            // Lo aveva capito da solo. Confidarsi adesso e' la valvola del
            // progetto: «trasforma la minaccia piu' grande nell'alleato piu'
            // forte». Chi lo sapeva come una scoperta lo sa adesso come una
            // confidenza, e non conta piu' per il trasloco.
            Database::run("UPDATE sanno SET come = 'confidato', atteggiamento = '', affrontato_gts = NULL, gts = ?
                           WHERE esper_id = ? AND chi_sa_id = ?", [$gts, (int) $pg['id'], $aChi]);
            Database::run('DELETE FROM anomalie WHERE osservatore_id = ? AND soggetto_id = ?', [$aChi, (int) $pg['id']]);
            Legami::muovi($aChi, (int) $pg['id'], 8, -15, $gts);
            return ['ok' => true, 'racconto' => sprintf(
                'Lo sapeva già, e tu lo sapevi che lo sapeva. Ma adesso gliel\'hai detto tu, ed è un\'altra '
                . 'cosa: %s non è più qualcuno che ti ha scoperto. È qualcuno a cui ti sei affidat%s.',
                $altro['nome'], (string) $pg['sesso'] === 'f' ? 'a' : 'o')];
        }
        Database::run('INSERT INTO sanno (esper_id, chi_sa_id, come, gts) VALUES (?, ?, ?, ?)',
            [(int) $pg['id'], $aChi, 'confidato', $gts]);
        // Le annotazioni non servono più: adesso ha la risposta, non gli indizi.
        Database::run('DELETE FROM anomalie WHERE osservatore_id = ? AND soggetto_id = ?',
            [$aChi, (int) $pg['id']]);

        Personaggio::traccia((string) $pg['luogo'], (int) $pg['id'], 'confidenza',
            sprintf('%s e %s hanno parlato a lungo, a voce bassa, e alla fine nessuno dei due rideva.',
                Personaggio::nomeCompleto($pg), $altro['cognome'] . ' ' . $altro['nome']), $gts, 40);

        return ['ok' => true, 'racconto' => sprintf(
            'Glielo dici. Non c\'è un modo elegante di dirlo, e infatti non lo trovi: lo dici e basta, '
            . 'e poi resti in silenzio ad aspettare. %s adesso sa. È la cosa più pericolosa che tu abbia fatto, '
            . 'ed è anche l\'unica che ti toglie un peso.',
            $altro['nome']
        )];
    }

    // --- Il calore ---------------------------------------------------------------------

    public static function calore(string $lkey, ?int $gts = null): int
    {
        $r = Database::first('SELECT valore, gts FROM calore WHERE luogo = ?', [$lkey]);
        if ($r === null) {
            return 0;
        }
        $gts ??= Orologio::lineare();
        $dimezza = max(1, GameConfig::int('segreto.calore_decadimento_ore', 48)) * 3600;
        $passate = max(0, $gts - (int) $r['gts']);
        return (int) round((int) $r['valore'] * pow(0.5, $passate / $dimezza));
    }

    public static function alzaCalore(string $lkey, int $quanto, ?int $gts = null): void
    {
        self::muoviCalore($lkey, max(0, $quanto), $gts);
    }

    /**
     * Sposta il calore di un luogo in su o in giu', fra zero e il tetto.
     *
     * Serve agli episodi, dove certe scelte calmano un posto invece di
     * scaldarlo. `alzaCalore()` resta per i poteri, che scaldano e basta.
     */
    public static function muoviCalore(string $lkey, int $quanto, ?int $gts = null): void
    {
        $gts ??= Orologio::lineare();
        $nuovo = max(0, min(GameConfig::int('segreto.calore_max', 100), self::calore($lkey, $gts) + $quanto));
        Database::run(
            'INSERT INTO calore (luogo, valore, gts) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE valore = VALUES(valore), gts = VALUES(gts)',
            [$lkey, $nuovo, $gts]
        );
    }

    public static function descrizioneCalore(int $c): string
    {
        return match (true) {
            $c <= 4  => '',
            $c <= 15 => 'Qui qualcuno ha cominciato a fare attenzione.',
            $c <= 35 => 'Da queste parti girano delle storie. La gente guarda.',
            $c <= 60 => 'Questo posto ha una fama. Chi ci passa tiene gli occhi aperti.',
            default  => 'Qui viene gente apposta, per vedere se è vero quello che si dice.',
        };
    }

    // --- Il Controllo ----------------------------------------------------------------------

    private static function miglioraControllo(int $pgId, string $pkey): void
    {
        Database::run(
            'UPDATE personaggio_poteri SET controllo = LEAST(100, controllo + ?)
             WHERE personaggio_id = ? AND pkey = ?',
            [max(1, GameConfig::int('segreto.controllo_per_uso', 2)), $pgId, $pkey]
        );
    }

    // --- Il Trasloco --------------------------------------------------------------------------

    /**
     * Quante persone hanno capito da sole — fra quelle ancora nel quartiere.
     *
     * Chi ha traslocato non conta: le chiacchiere hanno bisogno di qualcuno
     * che stia negli stessi posti, e chi se n'e' andato non ci sta piu'. Prima
     * contava lo stesso, e un esper poteva essere costretto al trasloco da
     * due persone di cui una non abitava piu' li'. Se torna, torna a contare.
     */
    public static function quantiSanno(int $pgId, string $come = 'scoperto'): int
    {
        if ($come === 'scoperto') {
            // Chi ha capito da solo conta uno, e chi era stato messo a parte e
            // poi si e' guastato con te pure. «Il rischio torna, moltiplicato»
            // (PROGETTO §7) non vuol dire che conti doppio: con la soglia a due
            // un solo confidente guastato faceva traslocare al battito dopo, e
            // il fraintendimento cresce anche per gesti fatti da altri. Conta
            // come chi ha capito da solo, e in piu' lo racconta (forseTradisce):
            // il rischio si moltiplica nelle voci.
            return (int) (Database::first(
                "SELECT COUNT(*) n FROM sanno s JOIN personaggi p ON p.id = s.chi_sa_id
                 WHERE s.esper_id = ? AND s.come IN ('scoperto', 'tradito') AND p.stato = 'attivo'",
                [$pgId]
            )['n'] ?? 0);
        }
        return (int) (Database::first(
            "SELECT COUNT(*) n FROM sanno s JOIN personaggi p ON p.id = s.chi_sa_id
             WHERE s.esper_id = ? AND s.come = ? AND p.stato = 'attivo'",
            [$pgId, $come]
        )['n'] ?? 0);
    }

    /**
     * Quante persone, nel quartiere, hanno sentito una voce abbastanza chiara
     * sui tuoi poteri da sapere di chi si parla.
     *
     * La FAQ 26 dice che i Kasuga traslocano quando il segreto diventa di
     * pubblico dominio, non quando una persona in piu' ha capito. Le voci
     * prima si leggevano e basta; adesso, oltre una soglia, pesano.
     */
    public static function dominio(int $pgId): int
    {
        $durata = GameConfig::int('voci.durata_giorni', 21) * 86400;
        // Solo le voci nate dopo l'ultimo trasloco: chi rientra «e' un altro
        // ragazzo in un'altra casa, e la voce e' vecchia» (rientra()). Prima le
        // voci vecchie contavano ancora, e chi tornava veniva rispedito via al
        // battito dopo. E solo quelle abbastanza nitide da sapere di chi si
        // parla (precisione 80, il nome intero): con 55 bastava mezzo nome, e
        // con sedici abitanti che chiacchierano la soglia si raggiungeva in tre
        // giorni senza che nessuno avesse capito niente (simulazione del 13/10).
        return (int) (Database::first(
            "SELECT COUNT(DISTINCT vv.personaggio_id) n
             FROM voci v JOIN voci_versioni vv ON vv.voce_id = v.id
             JOIN personaggi p ON p.id = vv.personaggio_id
             JOIN personaggi s ON s.id = v.soggetto_id
             WHERE v.tipo = 'potere' AND v.soggetto_id = ? AND vv.personaggio_id <> ?
               AND vv.precisione >= 80 AND v.gts >= ? AND v.gts > COALESCE(s.ultimo_trasloco_gts, 0)
               AND p.stato = 'attivo'",
            [$pgId, $pgId, Orologio::lineare() - $durata]
        )['n'] ?? 0);
    }

    /**
     * @return array{stato:string, scoperti:int, soglia:int, messaggio:string}
     */
    public static function pericolo(array $pg): array
    {
        $soglia   = max(1, GameConfig::int('segreto.sospetti_per_trasloco', 2));
        $scoperti = self::quantiSanno((int) $pg['id']);
        $sogliaD  = max(1, GameConfig::int('segreto.pubblico_dominio', 10));
        $dominio  = self::dominio((int) $pg['id']);
        $troppi   = $scoperti >= $soglia || $dominio >= $sogliaD;

        return [
            'stato'    => match (true) {
                $troppi                                => 'trasloco',
                $scoperti > 0 || $dominio * 2 >= $sogliaD => 'avviso',
                default                                => 'quieto',
            },
            'scoperti' => $scoperti,
            'soglia'   => $soglia,
            'dominio'  => $dominio,
            'soglia_dominio' => $sogliaD,
            'messaggio' => match (true) {
                $troppi       => 'Tuo padre ha cominciato a guardare gli annunci. Non è una minaccia: è una constatazione.',
                $scoperti > 0 => 'Qualcuno ha capito. A casa non se ne parla, ma il telefono squilla più del solito.',
                $dominio > 0  => 'In giro si parla di te più del solito, e non per i voti. Ancora un po\' e lo sapranno tutti.',
                default       => '',
            },
        ];
    }

    /**
     * Il Trasloco.
     *
     * Non è la morte: la famiglia di Kyosuke si era già trasferita sette volte
     * prima che la storia cominciasse. Il personaggio se ne va davvero — perde
     * il quartiere, i luoghi, le persone — e al suo posto arriva un altro
     * parente della stessa stirpe. Quello che resta al giocatore è la riga
     * nell'elenco di chi se n'è andato, e il motivo.
     *
     * @return array{ok:bool, error?:string, racconto?:string}
     */
    public static function trasloca(array $pg, string $motivo = ''): array
    {
        $id = (int) $pg['id'];
        if ((string) $pg['stato'] !== 'attivo') {
            return ['ok' => false, 'error' => 'Questo personaggio se n\'è già andato.'];
        }
        $gts = Orologio::lineare();

        Database::run(
            'UPDATE personaggi SET stato = ?, traslochi = traslochi + 1, ultimo_trasloco_gts = ?,
                    verso = NULL, arrivo_gts = NULL WHERE id = ?',
            ['trasferito', $gts, $id]
        );
        Database::run('UPDATE presenze SET al_gts = ? WHERE personaggio_id = ? AND al_gts IS NULL', [$gts, $id]);
        // Il quartiere nuovo non sa niente: le annotazioni degli altri su di
        // lui restano, ma lui non c'è più. Si cancellano perché non hanno più
        // un soggetto da riguardare.
        Database::run('DELETE FROM anomalie WHERE soggetto_id = ?', [$id]);

        // Gli oggetti unici non partono col camioncino: restano dove li si
        // stava tenendo in mano. Prima partivano, e il cappello di paglia —
        // uno solo per server — finiva in mano a chi non c'era piu'.
        Legami::ritrovaOggetti($gts);

        Personaggio::traccia((string) $pg['luogo'], null, 'trasloco',
            sprintf('La famiglia di %s ha traslocato, all\'improvviso, senza salutare nessuno.',
                Personaggio::nomeCompleto($pg)), $gts, 90);

        // Una sparizione improvvisa e' la voce che corre di piu' (F6), e la
        // sanno tutti quelli che lo conoscevano — non solo chi era li'. E'
        // l'unica voce che nasce senza testimoni oculari: il quartiere si
        // accorge di un'assenza anche quando non ha visto nessuno partire.
        $conoscenti = array_map(
            static fn (array $r): int => (int) $r['chi'],
            Database::all(
                'SELECT DISTINCT da_id AS chi FROM legami WHERE a_id = ? AND da_id <> ?
                 UNION SELECT DISTINCT chi_sa_id AS chi FROM sanno WHERE esper_id = ?',
                [$id, $id, $id]
            )
        );
        Voci::nasce('partenza', $id, null, (string) $pg['luogo'], $conoscenti, '', $gts);

        return ['ok' => true, 'racconto' =>
            'Il camioncino arriva di mattina presto, quando la strada è ancora vuota. Tuo padre non '
            . 'te lo ha chiesto e tu non hai discusso: si fa così, si è sempre fatto così. '
            . ($motivo !== '' ? $motivo . ' ' : '')
            . 'Dalla curva in fondo al viale si vedono ancora i tetti, e poi nemmeno quelli.'];
    }

    /**
     * Quando si potra' tornare: l'istante di gioco, o null se non si e' via.
     */
    public static function ritornoDa(array $pg): ?int
    {
        if ((string) $pg['stato'] !== 'trasferito') {
            return null;
        }
        $giorni = max(0, GameConfig::int('segreto.rientro_giorni', 2));
        return (int) ($pg['ultimo_trasloco_gts'] ?? 0) + $giorni * 86400;
    }

    /**
     * Il ritorno: «rientri nel quartiere come trasferito di recente».
     *
     * PROGETTO §7 lo dice cosi', e il tratto «Trasferito di recente» era nei
     * semi dall'inizio; il ritorno invece non esisteva, e dopo il trasloco il
     * giocatore restava un fantasma che non poteva nemmeno ricominciare.
     *
     * Cosa si tiene e cosa si perde e' scritto nel progetto, e qui si fa
     * esattamente quello: si TENGONO i poteri e il Controllo, i tratti, le
     * abilita', l'album dei ricordi e quello che si sa degli altri; si PERDONO
     * i legami (in tutte e due le direzioni: nessuno si ricorda di te come
     * prima), chi aveva capito il tuo segreto (sei un altro ragazzo in un'altra
     * casa, e la voce e' vecchia), i club. Si arriva in stazione, come arriva
     * chi si trasferisce, e ci si porta dietro il tratto nuovo.
     *
     * @return array{ok:bool, error?:string, racconto?:string}
     */
    public static function rientra(array $pg): array
    {
        $quando = self::ritornoDa($pg);
        if ($quando === null) {
            return ['ok' => false, 'error' => 'Sei già nel quartiere.'];
        }
        $gts = Orologio::lineare();
        if ($gts < $quando) {
            return ['ok' => false, 'error' => 'Il camioncino non è ancora ripartito dall\'altra parte.'];
        }
        $id = (int) $pg['id'];

        return Lock::con('rientro:' . $id, function () use ($id, $gts): array {
            $fresco = Database::first('SELECT stato FROM personaggi WHERE id = ?', [$id]);
            if ($fresco === null || (string) $fresco['stato'] !== 'trasferito') {
                return ['ok' => false, 'error' => 'Sei già nel quartiere.'];
            }

            Database::run('DELETE FROM legami WHERE da_id = ? OR a_id = ?', [$id, $id]);
            Database::run('DELETE FROM sanno WHERE esper_id = ?', [$id]);
            Database::run('DELETE FROM club_membri WHERE personaggio_id = ?', [$id]);
            Database::run('INSERT IGNORE INTO personaggio_tratti (personaggio_id, tkey) VALUES (?, ?)',
                [$id, 'trasferito']);
            Database::run(
                "UPDATE personaggi SET stato = 'attivo', luogo = 'stazione', arrivato_gts = ?,
                        verso = NULL, arrivo_gts = NULL, visto_gts = ?
                 WHERE id = ?",
                [$gts, $gts, $id]
            );
            Database::run('INSERT INTO presenze (personaggio_id, luogo, dal_gts) VALUES (?, ?, ?)',
                [$id, 'stazione', $gts]);

            $pg = (array) Database::first('SELECT * FROM personaggi WHERE id = ?', [$id]);
            $o  = (string) ($pg['sesso'] ?? 'm') === 'f' ? 'a' : 'o';
            Personaggio::traccia('stazione', $id, 'arrivo',
                sprintf('%s è sces%s dal treno con una borsa sola, e si è guardat%s intorno come chi '
                    . 'cerca di ricordarsi dove abita adesso.', Personaggio::nomeCompleto($pg), $o, $o),
                $gts, 40);

            return ['ok' => true, 'racconto' =>
                'Il treno si ferma a Nakagawa con uno sbuffo, e scendi. La stazione è la stessa, il '
                . 'viale anche; tu no. Chi ti conosceva non ti riconoscerà subito, e chi aveva '
                . 'capito qualcosa adesso ha solo una storia vecchia da raccontare. Hai tutto quello '
                . 'che ti porti dentro, e niente di quello che avevi qui. Si ricomincia.'];
        }, ['ok' => false, 'error' => 'Un momento: stai già tornando.']);
    }

    /** Fa traslocare chi ha superato la soglia. Lo chiama il battito. */
    public static function traslochiDovuti(): int
    {
        $n = 0;
        // Solo i giocatori. Oggi un abitante canonico non puo' arrivarci — non
        // usa poteri, quindi nessuno annota anomalie su di lui — ma il giorno in
        // cui un copione lo facesse, il battito spedirebbe Kyosuke fuori dal
        // quartiere per sempre. Meglio che sia impossibile per costruzione.
        foreach (Database::all("SELECT * FROM personaggi WHERE esper = 1 AND stato = 'attivo' AND png IS NULL") as $pg) {
            if (self::pericolo($pg)['stato'] === 'trasloco') {
                if (self::trasloca($pg)['ok']) {
                    $n++;
                }
            }
        }
        return $n;
    }
}
