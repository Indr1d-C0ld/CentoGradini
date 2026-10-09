<?php

declare(strict_types=1);

namespace App\Game;

use App\Core\Database;
use App\Core\GameConfig;
use App\Sim\Luoghi;
use App\Sim\Orologio;
use App\Sim\Rng;

/**
 * Quello che un potere FA.
 *
 * Fino al controllo del 9 ottobre 2026 un potere aveva un costo e un rischio,
 * e nessun effetto: il teletrasporto non spostava, la telepatia non leggeva
 * niente. Era un gioco in cui l'unica ragione per usare un potere era vedere
 * se qualcuno guardava. Nell'opera è il contrario — ogni potere è la
 * soluzione più rapida a un problema, ed è per questo che è una tentazione.
 *
 * Gli effetti stanno qui, separati dal Segreto: `Segreto::usa()` decide chi
 * ha visto, questo modulo decide cosa è successo. I limiti vengono dalle
 * schede dei poteri (db/seed/poteri.php), che a loro volta vengono dal
 * regolamento del 1990: il teletrasporto porta solo «verso posti che conosci
 * davvero», la telepatia da secondario dà «solo un'idea vaga», l'ipnosi vale
 * «una persona al giorno» e chi si risveglia «si ricorda abbastanza da essere
 * furioso».
 *
 * Non tutti i poteri hanno un effetto meccanico: la telecinesi, le
 * illusioni, il cambio d'identità, la voce restano gesti che si vedono e
 * basta, finché un copione non dia loro qualcosa da fare. Meglio così che un
 * effetto inventato per simmetria.
 */
final class Poteri
{
    /** Che cosa chiede un potere, oltre a essere usato: un posto, una persona, o niente. */
    public const PARAMETRO = [
        'teletrasporto'  => 'luogo',
        'supervelocita'  => 'luogo',
        'chiaroveggenza' => 'luogo',
        'telepatia'      => 'persona',
        'ipnosi'         => 'persona',
    ];

    /** I poteri che hanno un effetto, per la pagina: gli altri si vedono e basta. */
    public const CON_EFFETTO = ['teletrasporto', 'supervelocita', 'chiaroveggenza', 'telepatia',
                                'ipnosi', 'supersensi', 'sesto_senso', 'autoipnosi', 'invisibilita'];

    public static function parametro(string $pkey): ?string
    {
        return self::PARAMETRO[$pkey] ?? null;
    }

    /**
     * Le scelte possibili per un potere, adesso: i posti dove ci si può
     * portare, o le persone presenti.
     *
     * @return list<array{valore:string, nome:string}>
     */
    public static function scelte(array $pg, string $pkey, ?int $gts = null): array
    {
        $gts ??= Orologio::lineare();
        $qui = (string) $pg['luogo'];
        return match (self::parametro($pkey)) {
            'luogo' => array_map(
                static fn (string $k): array => ['valore' => $k, 'nome' => Luoghi::nome($k)],
                $pkey === 'supervelocita' ? self::vicini($pg, $gts)
                    : ($pkey === 'chiaroveggenza' ? array_values(array_diff(self::noti((int) $pg['id']), [$qui]))
                        : self::raggiungibili($pg, $gts))
            ),
            'persona' => array_map(
                static fn (array $p): array => ['valore' => (string) $p['id'],
                    'nome' => trim((string) $p['cognome'] . ' ' . (string) $p['nome'])],
                Personaggio::presenti($qui, (int) $pg['id'])
            ),
            default => [],
        };
    }

    /** I posti in cui si è stati almeno una volta: «solo verso posti che conosci davvero». */
    public static function noti(int $pgId): array
    {
        return array_values(array_filter(
            array_column(Database::all('SELECT DISTINCT luogo FROM presenze WHERE personaggio_id = ?', [$pgId]), 'luogo'),
            static fn (string $l): bool => Luoghi::esiste($l)
        ));
    }

    /** Dove ci si può teletrasportare adesso: posti noti, aperti, e di cui si ha la porta. */
    private static function raggiungibili(array $pg, int $gts): array
    {
        return array_values(array_filter(
            self::noti((int) $pg['id']),
            static fn (string $l): bool => $l !== (string) $pg['luogo']
                && Luoghi::accessibile($l, $gts)[0]
                && Personaggio::porta($pg, $l) === null
        ));
    }

    /** I posti accanto, per chi corre a ottanta all'ora. */
    private static function vicini(array $pg, int $gts): array
    {
        return array_values(array_map(
            static fn (array $u): string => (string) $u['a'],
            array_filter(Personaggio::usciteDi($pg, (string) $pg['luogo'], $gts),
                static fn (array $u): bool => $u['aperto'] && $u['mezzo'] !== 'treno')
        ));
    }

    /**
     * Si può fare, con questa scelta? Null se sì, altrimenti il perché.
     * Si chiede PRIMA di spendere i punti: un teletrasporto verso un posto
     * sconosciuto non parte, e non costa.
     */
    public static function valida(array $pg, string $pkey, string $scelta, ?int $gts = null): ?string
    {
        $gts ??= Orologio::lineare();
        $tipo = self::parametro($pkey);
        if ($tipo === null) {
            return null;
        }
        if ($scelta === '') {
            // Senza una scelta il potere si usa lo stesso, ma non porta da
            // nessuna parte e non agisce su nessuno: e' il teletrasporto di un
            // oggetto, la telepatia a vuoto. Si vede, e costa, come prima.
            return null;
        }
        $valori = array_column(self::scelte($pg, $pkey, $gts), 'valore');
        if (!in_array($scelta, $valori, true)) {
            return $tipo === 'luogo'
                ? accorda('Lì non ci arrivi: o non ci sei mai stat{o}, o adesso è chiuso.', $pg)
                : 'Quella persona non è qui.';
        }
        if ($pkey === 'ipnosi' && self::ipnosiDiOggi((int) $pg['id'], $gts)) {
            return 'Una persona al giorno, non di più: per oggi l\'hai già fatto.';
        }
        return null;
    }

    private static function ipnosiDiOggi(int $pgId, int $gts): bool
    {
        return Database::first(
            "SELECT 1 x FROM incidenti WHERE attore_id = ? AND pkey = 'ipnosi' AND gts > ? LIMIT 1",
            [$pgId, $gts - 86400]
        ) !== null;
    }

    /** Chi l'ipnosi rende cieco a quello che sta succedendo: il bersaglio. */
    public static function nonVede(string $pkey, string $scelta): ?int
    {
        return $pkey === 'ipnosi' && ctype_digit($scelta) ? (int) $scelta : null;
    }

    /**
     * Un potere scelto a caso fra quelli che si hanno, con una scelta a caso:
     * è quello che succede a un esper con la febbre.
     *
     * @param list<array<string,mixed>> $poteri
     * @return array{0:array<string,mixed>, 1:string}|null
     */
    public static function sballa(array $pg, array $poteri, string $voluto, Rng $rng, int $gts): ?array
    {
        $altri = array_values(array_filter($poteri,
            static fn (array $p): bool => !in_array((string) $p['pkey'], [$voluto, 'sogni', 'blocco'], true)));
        if ($altri === []) {
            return null;
        }
        $p = $altri[$rng->int(0, count($altri) - 1)];
        $scelte = self::scelte($pg, (string) $p['pkey'], $gts);
        if (self::parametro((string) $p['pkey']) !== null && $scelte === []) {
            return null;
        }
        $scelta = $scelte === [] ? '' : (string) $scelte[$rng->int(0, count($scelte) - 1)]['valore'];
        return [$p, $scelta];
    }

    /**
     * L'effetto. Si chiama dopo che il potere è stato pagato e visto (o no):
     * quello che restituisce si aggiunge al racconto.
     */
    public static function effetto(array $pg, array $potere, string $scelta, int $gts): string
    {
        $primario = (int) ($potere['primario'] ?? 0) === 1;
        $id = (int) $pg['id'];
        if (self::parametro((string) $potere['pkey']) !== null && $scelta === '') {
            return '';
        }
        return match ((string) $potere['pkey']) {
            'teletrasporto', 'supervelocita' => self::sposta($pg, $scelta, $gts, (string) $potere['pkey']),
            'chiaroveggenza' => self::guardaAltrove($scelta, $id),
            'telepatia'      => self::leggi($pg, (int) $scelta, $primario),
            'ipnosi'         => self::ipnotizza($pg, (int) $scelta, $gts),
            'supersensi'     => self::origlia($pg),
            'sesto_senso'    => self::presentimento($pg),
            'autoipnosi'     => self::specchio($pg, $gts),
            'invisibilita'   => self::sparisci($pg, $gts),
            default          => '',
        };
    }

    // --- I sogni premonitori -------------------------------------------------------

    /**
     * I 予知夢: arrivano da soli, la notte, una volta su due. Lo chiama il
     * battito nelle prime ore dopo l'alba, e il sogno finisce nell'album dei
     * ricordi di chi l'ha fatto.
     *
     * Non sono mai inventati: dicono una cosa vera del giorno che viene — una
     * festa che si avvicina, la pioggia, qualcuno che ce l'ha con te, qualcuno
     * che ti tiene d'occhio — ma la dicono come la dicono i sogni, cioè storta.
     * «Si avverano davvero, ma mai come li avevi capiti.»
     *
     * @return int quanti hanno sognato
     */
    public static function sognaTutti(?int $gts = null): int
    {
        $gts ??= Orologio::lineare();
        $ora = (int) Orologio::data($gts)->format('G');
        $alba = GameConfig::int('riposo.alba_ora', 6);
        if ($ora < $alba || $ora >= $alba + 3) {
            return 0;
        }
        $n = 0;
        foreach (Database::all(
            "SELECT p.* FROM personaggi p JOIN personaggio_poteri pp ON pp.personaggio_id = p.id AND pp.pkey = 'sogni'
             WHERE p.png IS NULL AND p.user_id IS NOT NULL AND p.stato = 'attivo'"
        ) as $pg) {
            $gia = Database::first(
                "SELECT 1 x FROM ricordi WHERE personaggio_id = ? AND episodio_id IS NULL AND titolo = 'Un sogno' AND gts > ?",
                [(int) $pg['id'], $gts - 20 * 3600]
            );
            if ($gia !== null) {
                continue;
            }
            $giorno = intdiv($gts, 86400);
            $rng = Rng::for(GameConfig::int('world.seed', 19870406), 'sogno', (int) $pg['id'], $giorno);
            // Una notte su due; e la decisione e' una sola per notte, anche se
            // il battito passa dieci volte in quelle tre ore.
            $segnato = Database::first(
                "SELECT 1 x FROM stati WHERE personaggio_id = ? AND stato = 'notte_senza_sogni' AND fino_gts > ?",
                [(int) $pg['id'], $gts]);
            if ($segnato !== null) {
                continue;
            }
            if (!$rng->chance(0.5)) {
                Stati::metti((int) $pg['id'], 'notte_senza_sogni', 4 * 60, $gts);
                continue;
            }
            Database::run(
                'INSERT INTO ricordi (personaggio_id, user_id, episodio_id, titolo, testo, gts, luogo)
                 VALUES (?, ?, NULL, ?, ?, ?, ?)',
                [(int) $pg['id'], (int) $pg['user_id'], 'Un sogno', self::sogno($pg, $gts, $rng), $gts, (string) $pg['luogo']]
            );
            $n++;
        }
        return $n;
    }

    /** Il sogno di stanotte: una cosa vera, detta storta. */
    public static function sogno(array $pg, int $gts, Rng $rng): string
    {
        $id = (int) $pg['id'];
        // Qualcuno che ce l'ha con te, o che ti ha frainteso di brutto.
        $contro = Database::first(
            'SELECT p.nome FROM legami l JOIN personaggi p ON p.id = l.da_id
             WHERE l.a_id = ? AND p.stato = \'attivo\' AND (l.fraintendimento >= 30 OR l.affetto <= -15)
             ORDER BY l.fraintendimento - l.affetto DESC LIMIT 1', [$id]);
        if ($contro !== null && $rng->chance(0.6)) {
            return sprintf('Sogni %s in fondo a un corridoio lunghissimo. Ti volta le spalle e se ne va, e '
                . 'per quanto corri non riesci ad accorciare la distanza. Ti svegli col fiatone, e con la '
                . 'certezza che oggi qualcosa fra voi due andrà storto — o è già andato.', $contro['nome']);
        }
        // Qualcuno che ti tiene d'occhio.
        $occhi = Database::first('SELECT COUNT(*) n FROM anomalie WHERE soggetto_id = ?', [$id]);
        if ((int) ($occhi['n'] ?? 0) >= 2 && $rng->chance(0.6)) {
            return 'Sogni un occhio enorme, aperto in mezzo al cielo sopra il quartiere, che guarda proprio te. '
                . 'Non è cattivo: è paziente. Quando ti svegli ti accorgi di aver contato, nel sonno, le persone '
                . 'che ultimamente ti guardano un secondo di troppo.';
        }
        // Una festa che si avvicina.
        $prossima = Eventi::prossimo($gts);
        if ($prossima !== null && (int) ($prossima['fra_giorni'] ?? 99) <= 3) {
            return sprintf('Sogni %s, ma è tutto sbagliato: i colori sono quelli di un\'altra stagione e la '
                . 'gente ha facce che non conosci. In mezzo alla folla qualcuno ti chiama per nome. '
                . 'Ti svegli prima di girarti a vedere chi era.', mb_strtolower((string) $prossima['nome']));
        }
        // La pioggia.
        if (\App\Sim\Meteo::a($gts + 10 * 3600)['pioggia'] > 0) {
            return 'Sogni acqua che cade senza fare rumore, e un ombrello solo per due persone. Non sai chi '
                . 'siano. Uno dei due, forse, sei tu.';
        }
        return 'Sogni i gradini, tutti e cento, e qualcuno seduto in cima che aspetta. Non ti sembra un brutto '
            . 'sogno. Ma i sogni di questo genere, nella tua famiglia, non sono mai soltanto sogni.';
    }

    // --- Gli effetti, uno per uno ------------------------------------------------

    private static function sposta(array $pg, string $dove, int $gts, string $come): string
    {
        Personaggio::spostaSubito($pg, $dove, $gts);
        return $come === 'teletrasporto'
            ? sprintf('Un attimo dopo sei %s, e il cuore ti batte come se avessi corso.', Luoghi::dove($dove))
            : sprintf('Il vento in faccia, un muro che passa troppo vicino, ed eccoti %s.', Luoghi::dove($dove));
    }

    private static function guardaAltrove(string $dove, int $io): string
    {
        $chi = array_map(
            static fn (array $p): string => trim((string) $p['cognome'] . ' ' . (string) $p['nome']),
            Personaggio::presenti($dove, $io)
        );
        $tracce = Personaggio::tracce($dove, 1);
        $vedi = $chi === []
            ? 'non c\'è nessuno'
            : 'ci sono ' . (count($chi) === 1 ? $chi[0] : implode(', ', array_slice($chi, 0, -1)) . ' e ' . end($chi));
        return sprintf('Chiudi gli occhi e sei %s: %s.%s', Luoghi::dove($dove), $vedi,
            $tracce !== [] ? ' ' . $tracce[0]['testo'] : '');
    }

    /** Quello che una persona pensa di te, detto come lo sentiresti tu. */
    public static function cosaPensaDiTe(int $affetto, int $fraint, bool $donna): string
    {
        $o = $donna ? 'a' : 'o';
        $base = match (true) {
            $affetto >= 70 => 'pensa a te più spesso di quanto ammetterebbe',
            $affetto >= 45 => 'ti vuole bene, e comincia a non riuscire a nasconderlo',
            $affetto >= 20 => 'ti trova simpatico',
            $affetto >= 5  => 'sa chi sei, e poco di più',
            $affetto > -5  => 'non sta pensando a te per niente',
            $affetto > -25 => 'preferirebbe essere da un\'altra parte',
            $affetto > -60 => 'non ti sopporta',
            default        => 'ha con te qualcosa di rotto, e lo sa',
        };
        $coda = match (true) {
            $fraint >= 40 => ', ed è convint' . $o . ' di una cosa su di te che non è vera',
            $fraint >= 15 => ', con qualche malinteso in mezzo',
            default       => '',
        };
        return $base . $coda;
    }

    private static function leggi(array $pg, int $chi, bool $primario): string
    {
        $altro = Database::first('SELECT id, nome, cognome, sesso FROM personaggi WHERE id = ?', [$chi]);
        if ($altro === null) {
            return '';
        }
        $l = Legami::fra($chi, (int) $pg['id']);
        $donna = (string) $altro['sesso'] === 'f';
        if (!$primario) {
            // «Da secondario: solo un'idea vaga, e a caro prezzo.»
            return sprintf('Ti arriva un\'immagine sfocata, più una sensazione che un pensiero: %s %s.',
                $altro['nome'], self::cosaPensaDiTe($l['affetto'], 0, $donna));
        }
        $testo = sprintf('Senti i pensieri di %s come una radio accesa nella stanza accanto: %s.',
            $altro['nome'], self::cosaPensaDiTe($l['affetto'], $l['fraintendimento'], $donna));
        // E chi gli sta più a cuore. È la parte che non volevi sapere.
        $piu = Database::first(
            'SELECT p.nome, p.cognome FROM legami l JOIN personaggi p ON p.id = l.a_id
             WHERE l.da_id = ? AND l.a_id <> ? AND l.affetto >= 45 AND l.affetto > ?
             ORDER BY l.affetto DESC LIMIT 1',
            [$chi, (int) $pg['id'], $l['affetto']]
        );
        if ($piu !== null) {
            $testo .= sprintf(' E sotto, più forte di tutto il resto, c\'è %s %s.', $piu['nome'], $piu['cognome']);
        }
        return $testo . ' Saperlo non aiuta a capire.';
    }

    private static function ipnotizza(array $pg, int $chi, int $gts): string
    {
        $altro = Database::first('SELECT id, nome, sesso FROM personaggi WHERE id = ?', [$chi]);
        if ($altro === null) {
            return '';
        }
        // Dimentica l'ultima cosa strana che aveva visto di te.
        $ultima = Database::first(
            'SELECT id FROM anomalie WHERE osservatore_id = ? AND soggetto_id = ? ORDER BY gts DESC LIMIT 1',
            [$chi, (int) $pg['id']]
        );
        if ($ultima !== null) {
            Database::run('DELETE FROM anomalie WHERE id = ?', [(int) $ultima['id']]);
        }
        // «Chi si risveglia si ricorda abbastanza da essere furioso.»
        Legami::muovi($chi, (int) $pg['id'], -4, 6, $gts);
        return $ultima !== null
            ? sprintf('%s dice «hai» a tutto per cinque minuti buoni, e quando si riscuote non si ricorda '
                . 'più di quella cosa strana che aveva visto fare a te. Si ricorda però abbastanza da '
                . 'guardarti storto.', $altro['nome'])
            : sprintf('%s dice «hai» a tutto per cinque minuti buoni. Quando si riscuote non sa cosa sia '
                . 'successo, ma è sicur%s che sia colpa tua.', $altro['nome'], (string) $altro['sesso'] === 'f' ? 'a' : 'o');
    }

    private static function origlia(array $pg): string
    {
        foreach (Personaggio::presenti((string) $pg['luogo'], (int) $pg['id']) as $p) {
            $v = Voci::per((int) $p['id'], 1);
            if ($v !== []) {
                return sprintf('Dall\'altra parte senti %s raccontare, a voce bassa: «%s»',
                    $p['nome'], rtrim((string) $v[0]['testo'], '.') . '.»');
            }
        }
        return 'Tendi le orecchie fino a sentire il ronzio dei lampioni. Nessuno sta dicendo niente che valga la pena.';
    }

    private static function presentimento(array $pg): string
    {
        $id = (int) $pg['id'];
        $qui = (string) $pg['luogo'];
        $occhi = (int) (Database::first(
            "SELECT COUNT(DISTINCT a.osservatore_id) n FROM anomalie a JOIN personaggi p ON p.id = a.osservatore_id
             WHERE a.soggetto_id = ? AND p.luogo = ? AND p.verso IS NULL AND p.stato = 'attivo'",
            [$id, $qui]
        )['n'] ?? 0);
        $sanno = (int) (Database::first(
            "SELECT COUNT(*) n FROM sanno s JOIN personaggi p ON p.id = s.chi_sa_id
             WHERE s.esper_id = ? AND s.come <> 'confidato' AND p.luogo = ? AND p.verso IS NULL AND p.stato = 'attivo'",
            [$id, $qui]
        )['n'] ?? 0);
        return match (true) {
            $sanno > 0 => 'Ti si gela la nuca. Qualcuno, qui, sa di te più di quanto dovrebbe.',
            $occhi > 0 => 'C\'è qualcosa che non va. Qualcuno qui ti sta tenendo d\'occhio, anche se non sai chi.',
            default    => 'Niente. Per adesso, qui, niente.',
        };
    }

    private static function specchio(array $pg, int $gts): string
    {
        Database::run('UPDATE personaggi SET compostezza = LEAST(compostezza_max, compostezza + 3) WHERE id = ?',
            [(int) $pg['id']]);
        // «Con questo potere si diventa stronzi in fretta»: chi c'è se ne accorge.
        foreach (Personaggio::presenti((string) $pg['luogo'], (int) $pg['id']) as $p) {
            Legami::muovi((int) $p['id'], (int) $pg['id'], -2, 0, $gts);
        }
        return accorda('Ti ripeti la parola davanti allo specchio finché non ci credi, e ci credi. Adesso '
            . 'sei tranquill{o} come non sei mai stat{o} — e un filo antipatic{o}, a giudicare dalle facce.', $pg);
    }

    private static function sparisci(array $pg, int $gts): string
    {
        Stati::metti((int) $pg['id'], 'invisibile', GameConfig::int('poteri.invisibile_minuti', 20), $gts);
        return 'Ti guardi le mani e non ci sono. Per un po\' nessuno ti vedrà fare niente: ti sentiranno, '
            . 'però, e ci sbatteranno contro.';
    }
}
