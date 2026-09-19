<?php

declare(strict_types=1);

namespace App\Game;

use App\Core\Database;
use App\Core\GameConfig;
use App\Sim\Calendario;
use App\Sim\Luoghi;
use App\Sim\Meteo;
use App\Sim\Orologio;
use App\Sim\Rng;

/**
 * Le relazioni, su due assi.
 *
 *   AFFETTO           -100..+100   quanto A tiene a B
 *   FRAINTENDIMENTO      0..100    quanto A crede di B qualcosa che non è vero
 *
 * **Il fraintendimento non nasce dalle bugie.** Nasce dal fatto che i gesti
 * hanno più di una lettura, e che chi passa di lì ne vede metà. Vedere due
 * persone sotto lo stesso ombrello è un'informazione vera; il significato che
 * ci si mette è di chi guarda. Per questo il motore, quando qualcuno compie
 * un gesto, non aggiorna solo il legame fra i due: distribuisce una
 * **lettura** a ogni presente, e chi legge male se la porta dietro.
 *
 * **Il fraintendimento non decade.** Il tempo non aggiusta niente: serve una
 * conversazione, che costa Cuore e che può peggiorare le cose. È la sola
 * regola di questo modulo che non ammette eccezioni, ed è il motivo per cui
 * il triangolo di Kimagure Orange Road dura diciotto volumi invece di due.
 */
final class Legami
{
    // --- Lettura -------------------------------------------------------------

    /**
     * Il legame orientato da A verso B. Se non esiste, ne restituisce uno a
     * zero senza scriverlo: due persone che non si conoscono non hanno una
     * riga a database, hanno solo un legame vuoto.
     *
     * @return array{affetto:int, fraintendimento:int, esiste:bool}
     */
    public static function fra(int $da, int $a): array
    {
        $r = Database::first(
            'SELECT affetto, fraintendimento FROM legami WHERE da_id = ? AND a_id = ?', [$da, $a]
        );
        return $r === null
            ? ['affetto' => 0, 'fraintendimento' => 0, 'esiste' => false]
            : ['affetto' => (int) $r['affetto'], 'fraintendimento' => (int) $r['fraintendimento'], 'esiste' => true];
    }

    /** Quello che questo personaggio prova per gli altri. @return list<array<string,mixed>> */
    public static function miei(int $pgId): array
    {
        $righe = Database::all(
            'SELECT l.*, p.nome, p.cognome, p.sezione, p.anno, p.stato
             FROM legami l JOIN personaggi p ON p.id = l.a_id
             WHERE l.da_id = ?
             ORDER BY ABS(l.affetto) DESC, l.fraintendimento DESC',
            [$pgId]
        );
        foreach ($righe as &$r) {
            $verso = self::fra((int) $r['a_id'], $pgId);
            $r['loro_affetto'] = $verso['affetto'];
            $r['loro_fraintendimento'] = $verso['fraintendimento'];
            $r['dice'] = self::descrizione((int) $r['affetto'], (int) $r['fraintendimento']);
        }
        return $righe;
    }

    /**
     * Come si dice a parole. L'incrocio dei due assi è tutto il punto: un
     * affetto alto con un fraintendimento alto non è «gli voglio bene», è
     * quella cosa lì che non si sa come chiamare.
     */
    public static function descrizione(int $affetto, int $fraint): string
    {
        $base = match (true) {
            $affetto >= 70  => 'Ci tieni più di quanto ammetteresti',
            $affetto >= 45  => 'Ci tieni, e comincia a vedersi',
            $affetto >= 20  => 'Ti sta simpatico',
            $affetto >= 5   => 'Vi conoscete',
            $affetto > -5   => 'Vi salutate e basta',
            $affetto > -25  => 'Preferiresti evitarlo',
            $affetto > -60  => 'Non lo sopporti',
            default         => 'Fra voi c\'è qualcosa di rotto',
        };
        $coda = match (true) {
            $fraint >= 70 => ', ma sei convinto di una cosa su di lui che ti avvelena tutto il resto.',
            $fraint >= 40 => ', però c\'è una faccenda in mezzo che non vi siete mai chiariti.',
            $fraint >= 15 => ', con qualche malinteso che gira.',
            default       => '.',
        };
        return $base . $coda;
    }

    // --- I gesti ----------------------------------------------------------------

    /**
     * I gesti che si possono fare adesso, verso questa persona.
     *
     * @return list<array<string,mixed>>
     */
    public static function disponibili(array $pg, ?int $gts = null): array
    {
        $gts ??= Orologio::lineare();
        $cal = Calendario::stato($gts);
        $met = Meteo::a($gts);

        $out = [];
        foreach (Database::all('SELECT * FROM gesti ORDER BY ordine') as $g) {
            [$ok, $perche] = self::condizione((string) ($g['richiede'] ?? ''), $cal, $met);
            $g['possibile'] = $ok && (int) $pg['compostezza'] >= (int) $g['costo_cuore'];
            $g['motivo']    = !$ok ? $perche
                : ((int) $pg['compostezza'] < (int) $g['costo_cuore']
                    ? 'Non te la senti: ti serve un po\' di compostezza.' : null);
            $out[] = $g;
        }
        return $out;
    }

    /** @return array{0:bool,1:?string} */
    private static function condizione(string $richiede, array $cal, array $met): array
    {
        return match ($richiede) {
            ''         => [true, null],
            'pioggia'  => [$met['pioggia'] > 0, 'Non sta piovendo.'],
            'neve'     => [$met['neve'], 'Non sta nevicando.'],
            'pranzo'   => [$cal['fase'] === 'pranzo', 'Si fa alla pausa pranzo.'],
            'sera'     => [in_array($cal['fase'], ['sera', 'notte'], true), 'Si fa di sera.'],
            'diploma'  => [$cal['md'] === '03-15', 'Si fa il giorno del diploma, e basta.'],
            default    => [true, null],
        };
    }

    /**
     * Compiere un gesto.
     *
     * @return array{ok:bool, error?:string, racconto?:string, visto_da?:int, fraintesi?:int}
     */
    public static function compi(array $pg, int $versoId, string $gkey): array
    {
        $g = Database::first('SELECT * FROM gesti WHERE gkey = ?', [$gkey]);
        if ($g === null) {
            return ['ok' => false, 'error' => 'Non so cosa vorresti fare.'];
        }
        if ($versoId === (int) $pg['id']) {
            return ['ok' => false, 'error' => 'Da solo no.'];
        }
        $altro = Database::first(
            'SELECT * FROM personaggi WHERE id = ? AND luogo = ? AND verso IS NULL AND stato = ?',
            [$versoId, (string) $pg['luogo'], 'attivo']
        );
        if ($altro === null) {
            return ['ok' => false, 'error' => 'Quella persona non è qui.'];
        }

        $gts = Orologio::lineare();
        [$ok, $perche] = self::condizione((string) ($g['richiede'] ?? ''),
            Calendario::stato($gts), Meteo::a($gts));
        if (!$ok) {
            return ['ok' => false, 'error' => $perche ?? 'Non adesso.'];
        }
        $costo = (int) $g['costo_cuore'];
        if ((int) $pg['compostezza'] < $costo) {
            return ['ok' => false, 'error' => 'Non te la senti: ti serve un po\' di compostezza.'];
        }

        // --- Rendimento calante -------------------------------------------
        // Ripetere lo stesso gesto verso la stessa persona rende sempre meno.
        // Senza questo, il modo ottimale di volersi bene sarebbe premere lo
        // stesso pulsante duecento volte, che è il contrario di quello che
        // questo gioco racconta.
        $recenti = (int) (Database::first(
            'SELECT COUNT(*) n FROM gesti_fatti
             WHERE attore_id = ? AND verso_id = ? AND gkey = ? AND gts >= ?',
            [(int) $pg['id'], $versoId, $gkey, $gts - 7 * 86400]
        )['n'] ?? 0);
        $resa = (int) round((int) $g['affetto'] * pow(0.55, $recenti));
        if ((int) $g['affetto'] > 0 && $resa < 1) {
            $resa = 0;
        }

        Database::run(
            'INSERT INTO gesti_fatti (gkey, attore_id, verso_id, luogo, gts) VALUES (?, ?, ?, ?, ?)',
            [$gkey, (int) $pg['id'], $versoId, (string) $pg['luogo'], $gts]
        );
        $gid = Database::lastInsertId();

        // Chi riceve sente tutto; chi fa sente un terzo — fare qualcosa per
        // qualcuno avvicina anche chi lo fa.
        self::muovi($versoId, (int) $pg['id'], $resa, 0, $gts);
        self::muovi((int) $pg['id'], $versoId, (int) round($resa / 3), 0, $gts);

        if ($costo > 0) {
            Database::run('UPDATE personaggi SET compostezza = GREATEST(0, compostezza - ?) WHERE id = ?',
                [$costo, (int) $pg['id']]);
        }

        // --- Chi ha guardato ------------------------------------------------
        $fraintesi = self::distribuisciLetture($pg, $altro, $g, $gid, $gts);

        Personaggio::traccia((string) $pg['luogo'], (int) $pg['id'], 'gesto',
            self::riempi((string) $g['lettura_vera'], $pg, $altro), $gts, 60);

        // Il gesto diventa una voce, e i presenti la sanno di prima mano
        // (F6). Un gesto ambiguo la fa nascere come «insieme» invece che
        // come «gesto»: chi passava non ha visto un abbraccio, ha visto due
        // persone vicine — ed e' esattamente da li' che parte il guaio.
        $testimoni = self::presentiAltri((string) $pg['luogo'], (int) $pg['id'], (int) $altro['id']);
        if ($testimoni !== []) {
            Voci::nasce(
                (int) $g['ambiguo'] === 1 ? 'insieme' : 'gesto',
                (int) $pg['id'], (int) $altro['id'], (string) $pg['luogo'],
                $testimoni, $gkey, $gts
            );
        }

        return [
            'ok'        => true,
            'visto_da'  => $fraintesi['visti'],
            'fraintesi' => $fraintesi['male'],
            'racconto'  => self::riempi((string) $g['lettura_vera'], $pg, $altro)
                . ($resa === 0 && (int) $g['affetto'] > 0
                    ? ' È la stessa cosa di sempre, e comincia a non voler più dire niente.'
                    : ''),
        ];
    }

    /**
     * Una confessione detta ad alta voce davanti a qualcuno diventa una voce.
     *
     * @param list<int> $testimoni
     */
    private static function vocedellaConfessione(array $pg, int $versoId, array $testimoni, int $gts): void
    {
        if ($testimoni === []) {
            return;
        }
        Voci::nasce('confessione', (int) $pg['id'], $versoId, (string) $pg['luogo'], $testimoni, '', $gts);
    }

    /**
     * Chi c'e' a guardare, esclusi i due protagonisti.
     *
     * @return list<int>
     */
    private static function presentiAltri(string $lkey, int $a, int $b): array
    {
        return array_map(
            static fn (array $r): int => (int) $r['id'],
            Database::all(
                'SELECT id FROM personaggi WHERE luogo = ? AND verso IS NULL AND stato = ?
                   AND id NOT IN (?, ?)',
                [$lkey, 'attivo', $a, $b]
            )
        );
    }

    /**
     * Distribuisce le letture ai presenti.
     *
     * Due cose diverse, e vanno tenute distinte. La prima è il **malinteso**:
     * chi non ha capito bene si porta a casa la lettura sbagliata, e il tiro
     * è l'Intuizione — chi è sveglio capisce, chi è distratto no. La seconda
     * è la **gelosia**, e non è un malinteso affatto: chi vuole bene a uno dei
     * due ha visto benissimo, ed è proprio per questo che gli brucia.
     *
     * @return array{visti:int, male:int}
     */
    private static function distribuisciLetture(array $pg, array $altro, array $g, int $gid, int $gts): array
    {
        if ((int) $g['ambiguo'] !== 1) {
            return ['visti' => 0, 'male' => 0];
        }
        $soglia = GameConfig::int('legami.soglia_gelosia', 35);
        $rng = Rng::for(GameConfig::int('world.seed', 19870406), 'lettura', $gid);

        $presenti = Database::all(
            'SELECT id, nome, cognome, testa FROM personaggi
             WHERE luogo = ? AND verso IS NULL AND stato = ? AND id NOT IN (?, ?)',
            [(string) $pg['luogo'], 'attivo', (int) $pg['id'], (int) $altro['id']]
        );

        $visti = $male = 0;
        foreach ($presenti as $c) {
            $visti++;
            $capisce = $rng->int(1, 100) <= Scheda::intuizione($c);
            $testo = $capisce
                ? self::riempi((string) $g['lettura_vera'], $pg, $altro)
                : self::riempi((string) ($g['lettura_falsa'] ?? $g['lettura_vera']), $pg, $altro);

            Database::run(
                'INSERT IGNORE INTO gesti_letture (gesto_id, osservatore_id, giusta, testo) VALUES (?, ?, ?, ?)',
                [$gid, (int) $c['id'], $capisce ? 1 : 0, mb_substr($testo, 0, 255)]
            );

            $fr = 0;
            if (!$capisce) {
                $fr += 8;            // ha visto metà scena e l'ha completata da sé
                $male++;
            }
            // La gelosia è un'altra cosa: chi ci tiene ha visto benissimo.
            $tieneAlAttore = self::fra((int) $c['id'], (int) $pg['id'])['affetto'];
            $tieneAlAltro  = self::fra((int) $c['id'], (int) $altro['id'])['affetto'];
            $quanto = max($tieneAlAttore, $tieneAlAltro);
            if ($quanto >= $soglia && (int) $g['affetto'] > 0) {
                $fr += (int) round(($quanto - $soglia) / 6) + 3;
                // E fa male anche all'affetto, un pochino.
                self::muovi((int) $c['id'], $tieneAlAttore >= $tieneAlAltro ? (int) $pg['id'] : (int) $altro['id'],
                    -2, 0, $gts);
            }
            if ($fr > 0) {
                self::muovi((int) $c['id'], (int) $pg['id'], 0, $fr, $gts);
            }
        }

        Database::run('UPDATE gesti_fatti SET visto_da = ? WHERE id = ?', [$visti, $gid]);
        return ['visti' => $visti, 'male' => $male];
    }

    // --- Chiarire -------------------------------------------------------------------

    /**
     * Provare a chiarire un malinteso.
     *
     * Il rischio è la parte importante: tirare fuori l'argomento può
     * peggiorare le cose, e succede spesso. Un chiarimento che riesce sempre
     * renderebbe il fraintendimento una tassa invece che un problema.
     *
     * @return array{ok:bool, error?:string, riuscito?:bool, racconto?:string}
     */
    public static function chiarisci(array $pg, int $versoId): array
    {
        $altro = Database::first(
            'SELECT * FROM personaggi WHERE id = ? AND luogo = ? AND verso IS NULL AND stato = ?',
            [$versoId, (string) $pg['luogo'], 'attivo']
        );
        if ($altro === null) {
            return ['ok' => false, 'error' => 'Quella persona non è qui.'];
        }
        $loro = self::fra($versoId, (int) $pg['id']);
        if ($loro['fraintendimento'] < 5) {
            return ['ok' => false, 'error' => 'Fra voi non c\'è niente da chiarire. Per ora.'];
        }
        if ((int) $pg['compostezza'] < 1) {
            return ['ok' => false, 'error' => 'Non sei nello stato giusto per una conversazione così.'];
        }

        $gts = Orologio::lineare();
        Database::run('UPDATE personaggi SET compostezza = GREATEST(0, compostezza - 1) WHERE id = ?',
            [(int) $pg['id']]);

        $prob = 25 + (int) $pg['cuore'] * 4 + ($loro['affetto'] > 20 ? 10 : 0)
                   - (int) round($loro['fraintendimento'] / 5);
        $prob = max(5, min(90, $prob));
        $rng  = Rng::for(GameConfig::int('world.seed', 19870406), 'chiarire',
            (int) $pg['id'], $versoId, $gts);
        $riuscito = $rng->int(1, 100) <= $prob;

        if ($riuscito) {
            self::muovi($versoId, (int) $pg['id'], 3, -GameConfig::int('legami.chiarimento_calo', 35), $gts);
            return ['ok' => true, 'riuscito' => true, 'racconto' => sprintf(
                'Ci metti un po\' a cominciare e poi viene fuori tutto insieme. %s ti ascolta fino '
                . 'in fondo, e a un certo punto fa quella faccia di chi ha capito. Non vi siete '
                . 'detti niente di importante, e però adesso è a posto.', $altro['nome']
            )];
        }

        self::muovi($versoId, (int) $pg['id'], 0, GameConfig::int('legami.chiarimento_danno', 10), $gts);
        return ['ok' => true, 'riuscito' => false, 'racconto' => sprintf(
            'Cominci male e peggiori a ogni frase. %s ti guarda come se stessi confermando proprio '
            . 'la cosa che volevi smentire, e a un certo punto smetti di parlare. Era meglio tacere.',
            $altro['nome']
        )];
    }

    // --- La confessione ------------------------------------------------------------------

    /**
     * Dichiararsi.
     *
     * È il gesto verso cui tende tutto, ed è quello che nell'opera non
     * succede quasi mai — perché dirlo è difficile e perché dirlo cambia le
     * cose in modo che non si torna indietro. Qui ci sono due prove in fila:
     * riuscire a **dirlo** (Cuore, ostacolato dai malintesi) e la **risposta**,
     * che dipende da quanto l'altro tiene a te, e su cui non si può barare.
     *
     * @return array{ok:bool, error?:string, detto?:bool, esito?:string, racconto?:string}
     */
    public static function confessa(array $pg, int $versoId): array
    {
        $altro = Database::first(
            'SELECT * FROM personaggi WHERE id = ? AND luogo = ? AND verso IS NULL AND stato = ?',
            [$versoId, (string) $pg['luogo'], 'attivo']
        );
        if ($altro === null) {
            return ['ok' => false, 'error' => 'Quella persona non è qui.'];
        }
        $mio = self::fra((int) $pg['id'], $versoId);
        $soglia = GameConfig::int('legami.soglia_confessione', 45);
        if ($mio['affetto'] < $soglia) {
            return ['ok' => false, 'error' => 'Non ci siete ancora. Non sarebbe vero.'];
        }
        if ((int) $pg['compostezza'] < 2) {
            return ['ok' => false, 'error' => 'Non stasera. Non in questo stato.'];
        }

        $gts  = Orologio::lineare();
        $loro = self::fra($versoId, (int) $pg['id']);
        Database::run('UPDATE personaggi SET compostezza = GREATEST(0, compostezza - 2) WHERE id = ?',
            [(int) $pg['id']]);

        $rng  = Rng::for(GameConfig::int('world.seed', 19870406), 'confessione',
            (int) $pg['id'], $versoId, $gts);
        $prob = max(5, min(90, 20 + (int) $pg['cuore'] * 5 - (int) round($loro['fraintendimento'] / 2)));

        // Una confessione riuscita, davanti a gente, fa il giro della scuola
        // in tre giorni (F6). Quella non detta non la sa nessuno, ed e'
        // giusto cosi': e' il punto di tutta la storia.
        $testimoni = self::presentiAltri((string) $pg['luogo'], (int) $pg['id'], $versoId);

        if ($rng->int(1, 100) > $prob) {
            // Non è uscito niente. Succede, ed è la cosa più fedele di tutte.
            self::muovi($versoId, (int) $pg['id'], 2, 0, $gts);
            return ['ok' => true, 'detto' => false, 'esito' => 'muto', 'racconto' =>
                'Apri la bocca e non esce niente. Parli del tempo, di un compito, di una cosa '
                . 'che non interessa a nessuno dei due. ' . $altro['nome'] . ' aspetta un momento '
                . 'di troppo prima di rispondere, e poi la conversazione riparte da un\'altra '
                . 'parte. Ci sei andato vicino.'];
        }

        Personaggio::traccia((string) $pg['luogo'], (int) $pg['id'], 'confessione',
            sprintf('%s ha detto qualcosa a %s, a voce bassissima, e poi sono rimasti fermi tutti e due.',
                Personaggio::nomeCompleto($pg), $altro['cognome'] . ' ' . $altro['nome']), $gts, 80);

        if ($loro['affetto'] >= $soglia) {
            self::muovi($versoId, (int) $pg['id'], 15, 0, $gts);
            self::muovi((int) $pg['id'], $versoId, 15, 0, $gts);
            self::vocedellaConfessione($pg, $versoId, $testimoni, $gts);
        return ['ok' => true, 'detto' => true, 'esito' => 'accolta', 'racconto' =>
                'Lo dici. Lo dici davvero, con le parole più goffe che avresti potuto scegliere, e '
                . $altro['nome'] . ' non dice niente per un tempo che ti sembra lunghissimo. Poi '
                . 'annuisce, una volta sola. Non è successo niente di clamoroso e da adesso è tutto '
                . 'diverso.'];
        }
        if ($loro['affetto'] >= 15) {
            self::muovi($versoId, (int) $pg['id'], 5, 5, $gts);
            self::vocedellaConfessione($pg, $versoId, $testimoni, $gts);
        return ['ok' => true, 'detto' => true, 'esito' => 'sospesa', 'racconto' =>
                'Lo dici, e ' . $altro['nome'] . ' ti chiede tempo. Non è un no. È peggio di un no, '
                . 'per certi versi: adesso ci sarà una cosa non detta fra voi ogni volta che vi '
                . 'incontrate.'];
        }
        self::muovi((int) $pg['id'], $versoId, -8, 0, $gts);
        self::muovi($versoId, (int) $pg['id'], -2, 8, $gts);
        self::vocedellaConfessione($pg, $versoId, $testimoni, $gts);
        return ['ok' => true, 'detto' => true, 'esito' => 'respinta', 'racconto' =>
            'Lo dici, e ' . $altro['nome'] . ' ti risponde con gentilezza, che è il modo peggiore. '
            . 'Ti accompagna anche un pezzo di strada, parlando d\'altro, e tu cammini accanto a '
            . 'qualcuno che ti vuole bene e non ti vuole.'];
    }

    // --- Il cappello -----------------------------------------------------------------------

    /**
     * @return array{ok:bool, error?:string, racconto?:string}
     */
    public static function passaOggetto(array $pg, int $versoId, string $okey): array
    {
        $o = Database::first('SELECT * FROM oggetti_unici WHERE okey = ?', [$okey]);
        if ($o === null) {
            return ['ok' => false, 'error' => 'Quell\'oggetto non esiste.'];
        }
        if ((int) ($o['detentore_id'] ?? 0) !== (int) $pg['id']) {
            return ['ok' => false, 'error' => 'Non ce l\'hai tu.'];
        }
        $altro = Database::first(
            'SELECT * FROM personaggi WHERE id = ? AND luogo = ? AND verso IS NULL AND stato = ?',
            [$versoId, (string) $pg['luogo'], 'attivo']
        );
        if ($altro === null) {
            return ['ok' => false, 'error' => 'Quella persona non è qui.'];
        }

        $gts = Orologio::lineare();
        Database::run('UPDATE oggetti_unici SET detentore_id = ?, luogo = NULL, gts = ? WHERE okey = ?',
            [$versoId, $gts, $okey]);
        Database::run(
            'INSERT INTO oggetti_passaggi (okey, da_id, a_id, luogo, gts, nota) VALUES (?, ?, ?, ?, ?, ?)',
            [$okey, (int) $pg['id'], $versoId, (string) $pg['luogo'], $gts, null]
        );
        self::muovi($versoId, (int) $pg['id'], 20, 0, $gts);
        self::muovi((int) $pg['id'], $versoId, 10, 0, $gts);

        Personaggio::traccia((string) $pg['luogo'], (int) $pg['id'], 'cappello',
            sprintf('%s ha dato una cosa a %s senza dire niente, e quell\'altro se l\'è tenuta.',
                Personaggio::nomeCompleto($pg), $altro['cognome'] . ' ' . $altro['nome']), $gts, 90);

        return ['ok' => true, 'racconto' => 'Glielo metti in mano e non spieghi. Non c\'è niente da '
            . 'spiegare: o si capisce o non si capisce.'];
    }

    /** Raccoglierlo, se sta in giro dove sei tu. */
    public static function raccogli(array $pg, string $okey): array
    {
        $o = Database::first('SELECT * FROM oggetti_unici WHERE okey = ? AND detentore_id IS NULL AND luogo = ?',
            [$okey, (string) $pg['luogo']]);
        if ($o === null) {
            return ['ok' => false, 'error' => 'Qui non c\'è.'];
        }
        $gts = Orologio::lineare();
        Database::run('UPDATE oggetti_unici SET detentore_id = ?, luogo = NULL, gts = ? WHERE okey = ?',
            [(int) $pg['id'], $gts, $okey]);
        Database::run('INSERT INTO oggetti_passaggi (okey, da_id, a_id, luogo, gts) VALUES (?, ?, ?, ?, ?)',
            [$okey, null, (int) $pg['id'], (string) $pg['luogo'], $gts]);
        return ['ok' => true, 'racconto' => 'Te lo ritrovi in mano senza aver deciso di raccoglierlo.'];
    }

    /** @return array<string,mixed>|null */
    public static function oggettoDi(int $pgId): ?array
    {
        return Database::first('SELECT * FROM oggetti_unici WHERE detentore_id = ?', [$pgId]);
    }

    /** @return list<array<string,mixed>> */
    public static function oggettiQui(string $lkey): array
    {
        return Database::all('SELECT * FROM oggetti_unici WHERE detentore_id IS NULL AND luogo = ?', [$lkey]);
    }

    // --- Il motore -----------------------------------------------------------------------------

    /**
     * Sposta un legame. È l'unico punto in cui i due numeri cambiano, così i
     * limiti si applicano in un posto solo.
     */
    public static function muovi(int $da, int $a, int $dAffetto, int $dFraint, ?int $gts = null): void
    {
        if ($da === $a || ($dAffetto === 0 && $dFraint === 0)) {
            return;
        }
        $gts ??= Orologio::lineare();
        $max = GameConfig::int('legami.affetto_max', 100);
        $l = self::fra($da, $a);

        $affetto = max(-$max, min($max, $l['affetto'] + $dAffetto));
        $fraint  = max(0, min(100, $l['fraintendimento'] + $dFraint));

        Database::run(
            'INSERT INTO legami (da_id, a_id, affetto, fraintendimento, conosciuti_gts, ultimo_gts)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE affetto = VALUES(affetto),
                                     fraintendimento = VALUES(fraintendimento),
                                     ultimo_gts = VALUES(ultimo_gts)',
            [$da, $a, $affetto, $fraint, $gts, $gts]
        );
    }

    private static function riempi(string $modello, array $a, array $b): string
    {
        return str_replace(
            ['%A', '%B'],
            [Personaggio::nomeCompleto($a), $b['cognome'] . ' ' . $b['nome']],
            $modello
        );
    }
}
