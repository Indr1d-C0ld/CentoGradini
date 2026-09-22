<?php

declare(strict_types=1);

namespace App\Game;

use App\Core\Database;
use App\Core\GameConfig;
use App\Sim\Luoghi;
use App\Sim\Orologio;
use App\Sim\Rng;
use App\Sim\Scuola;

/**
 * Le voci: il collante del multigiocatore asincrono.
 *
 * Non è una chat e non è una bacheca. È un modello di propagazione, e la
 * scelta che lo tiene in piedi è questa: **il testo di una voce non esiste a
 * database.** Esistono un fatto — chi, cosa, dove, quando — e tante versioni
 * soggettive quante sono le persone che ne hanno sentito parlare; ogni
 * versione ha una *precisione* e un *tono*, e la frase si ricostruisce ogni
 * volta al momento di leggerla.
 *
 * Il motivo è che la stessa voce deve suonare diversa a due persone diverse.
 * Se si salvasse la frase, la deformazione verrebbe fissata al primo
 * passaggio e tutti leggerebbero lo stesso pettegolezzo: che è esattamente il
 * contrario di come funziona una scuola.
 *
 * Il degrado ha tre assi, e sono distinti apposta:
 *
 *   PRECISIONE  100 = c'era e ha visto; 0 = non sa più nemmeno di chi si
 *               parla. Cala a ogni passaggio di bocca. I nomi se ne vanno
 *               per primi e diventano «uno del secondo anno».
 *   TONO        A ogni passaggio la storia diventa più benevola o più
 *               malevola, **mai più neutra**: il pettegolezzo si polarizza.
 *   PASSAGGI    Quante bocche prima di questa. Dopo tre o quattro, una voce
 *               non è più informazione.
 *
 * Da questo discendono da soli, senza codice dedicato, la gelosia per un
 * fatto che non si è visto, il malinteso sul conto di un giocatore che non
 * c'era, e la caccia all'esper.
 */
final class Voci
{
    /** I nuclei possibili. Ognuno ha la sua scala di frasi in FRASI. */
    public const TIPI = ['potere', 'gesto', 'confessione', 'litigio', 'insieme', 'partenza', 'affisso'];

    // --- Nascita ---------------------------------------------------------------

    /**
     * Mette in giro una voce. Chi era presente la sa bene, e basta: la
     * propagazione è affare del battito.
     *
     * @param list<int> $testimoni chi c'era, e con quanta attenzione
     */
    public static function nasce(
        string $tipo,
        ?int $soggettoId,
        ?int $soggetto2Id,
        string $luogo,
        array $testimoni = [],
        string $dettaglio = '',
        ?int $gts = null
    ): int {
        $gts  = $gts ?? Orologio::lineare();
        $seme = random_int(1, 2_000_000_000);

        Database::run(
            'INSERT INTO voci (tipo, soggetto_id, soggetto2_id, luogo, gts, dettaglio, seme)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$tipo, $soggettoId, $soggetto2Id, $luogo, $gts, mb_substr($dettaglio, 0, 64), $seme]
        );
        $id = (int) Database::lastInsertId();

        foreach (array_unique($testimoni) as $t) {
            // Chi ha visto parte alto ma non a cento: anche vedere è
            // interpretare, e due testimoni dello stesso fatto non
            // raccontano mai la stessa cosa.
            $rng = Rng::for($seme, 'testimone', $id, (int) $t);
            self::scrivi($id, (int) $t, $rng->int(82, 100), $rng->int(-12, 12), 0, $gts, null);
        }
        return $id;
    }

    /** Scrive o aggiorna la versione di una persona, tenendo la migliore. */
    private static function scrivi(
        int $voceId, int $pgId, int $precisione, int $tono, int $passaggi, int $gts, ?int $daId
    ): void {
        Database::run(
            'INSERT INTO voci_versioni (voce_id, personaggio_id, precisione, tono, passaggi, gts, da_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               precisione = GREATEST(precisione, VALUES(precisione)),
               tono       = IF(VALUES(precisione) > precisione, VALUES(tono), tono),
               passaggi   = LEAST(passaggi, VALUES(passaggi)),
               gts        = VALUES(gts)',
            [$voceId, $pgId, max(0, min(100, $precisione)), max(-100, min(100, $tono)),
             max(0, min(255, $passaggi)), $gts, $daId]
        );
    }

    // --- Propagazione ----------------------------------------------------------

    /**
     * Un giro di chiacchiere. La chiama il battito.
     *
     * Chi è nello stesso posto si racconta le cose; poi, separatamente, le
     * voci passano dentro i club, che è l'unico canale che non ha bisogno
     * della geografia — ed è il motivo per cui chi gioca poco viene comunque
     * raggiunto dal quartiere.
     *
     * @return array{di_persona:int, per_club:int}
     */
    public static function giro(?int $gts = null): array
    {
        $gts     = $gts ?? Orologio::lineare();
        $tetto   = max(1, GameConfig::int('voci.per_battito', 40));
        $soglia  = GameConfig::int('voci.soglia_racconto', 12);

        return [
            'di_persona' => self::giroDiPersona($gts, $tetto, $soglia),
            'per_club'   => self::giroPerClub($gts, (int) ceil($tetto / 3), $soglia),
        ];
    }

    /** Chi si trova nello stesso luogo si racconta quello che sa. */
    private static function giroDiPersona(int $gts, int $tetto, int $soglia): int
    {
        // Gli eventi del server accelerano tutto: a San Valentino la scuola
        // non parla d'altro, e il moltiplicatore lo dice.
        $prob = GameConfig::int('voci.prob_passaggio', 35) / 100.0 * Eventi::chiacchiera($gts);

        // Le coppie candidate: A sa qualcosa, B è nello stesso posto e non lo
        // sa. Si lascia decidere al database, che sa fare i join meglio di un
        // ciclo in PHP su tutto il quartiere.
        $coppie = Database::all(
            'SELECT vv.voce_id, vv.personaggio_id AS da_id, b.id AS a_id,
                    vv.precisione, vv.tono, vv.passaggi, a.luogo,
                    a.dai_suki AS loquacita, b.testa AS attenzione
             FROM voci_versioni vv
             JOIN personaggi a ON a.id = vv.personaggio_id AND a.stato = ? AND a.verso IS NULL
             JOIN personaggi b ON b.luogo = a.luogo AND b.id <> a.id AND b.stato = ? AND b.verso IS NULL
             LEFT JOIN voci_versioni gia ON gia.voce_id = vv.voce_id AND gia.personaggio_id = b.id
             JOIN voci v ON v.id = vv.voce_id
             WHERE gia.voce_id IS NULL
               AND vv.precisione >= ?
               AND v.soggetto_id <> b.id
               AND (v.soggetto2_id IS NULL OR v.soggetto2_id <> b.id)
             ORDER BY vv.gts DESC
             LIMIT ?',
            ['attivo', 'attivo', $soglia, $tetto * 4]
        );

        $fatti = 0;
        foreach ($coppie as $c) {
            if ($fatti >= $tetto) {
                break;
            }
            // L'ancora è la coppia, più la voce, PIÙ L'ORA DI GIOCO.
            //
            // L'ora non è un dettaglio: senza, l'esito di quell'incontro era
            // deciso una volta per sempre, e due persone che una volta non
            // si erano raccontate una cosa non se la sarebbero raccontata
            // mai più, per quanti battiti passassero. Con l'ora, ogni ora
            // hanno una possibilità nuova — che è quello che succede fra
            // gente che si rivede tutti i giorni — e dentro la stessa ora il
            // battito resta idempotente, quindi rilanciarlo non rimescola
            // niente.
            $rng = Rng::for(GameConfig::int('world.seed', 19870406), 'chiacchiera',
                (int) $c['voce_id'], (int) $c['da_id'], (int) $c['a_id'], intdiv($gts, 3600));

            // Chi è socievole racconta di più; chi è sveglio capisce meglio.
            $p = $prob * (0.6 + 0.08 * (int) $c['loquacita']);
            if (!$rng->chance(min(0.95, $p))) {
                continue;
            }
            self::passa($c, $rng, $gts, 0);
            $fatti++;
        }
        return $fatti;
    }

    /**
     * Le voci dentro i club. Stesso meccanismo, ma senza la geografia e con
     * una perdita in più: quello che gira in uno spogliatoio è già di seconda
     * mano per definizione.
     */
    private static function giroPerClub(int $gts, int $tetto, int $soglia): int
    {
        $prob = GameConfig::int('voci.prob_club', 18) / 100.0 * Eventi::chiacchiera($gts);

        $coppie = Database::all(
            'SELECT vv.voce_id, vv.personaggio_id AS da_id, m2.personaggio_id AS a_id,
                    vv.precisione, vv.tono, vv.passaggi, m1.ckey,
                    a.dai_suki AS loquacita
             FROM voci_versioni vv
             JOIN club_membri m1 ON m1.personaggio_id = vv.personaggio_id
             JOIN club_membri m2 ON m2.ckey = m1.ckey AND m2.personaggio_id <> m1.personaggio_id
             JOIN personaggi a ON a.id = vv.personaggio_id AND a.stato = ?
             JOIN personaggi b ON b.id = m2.personaggio_id AND b.stato = ?
             LEFT JOIN voci_versioni gia ON gia.voce_id = vv.voce_id AND gia.personaggio_id = m2.personaggio_id
             JOIN voci v ON v.id = vv.voce_id
             WHERE gia.voce_id IS NULL
               AND vv.precisione >= ?
               AND v.soggetto_id <> b.id
               AND (v.soggetto2_id IS NULL OR v.soggetto2_id <> b.id)
             ORDER BY vv.gts DESC
             LIMIT ?',
            ['attivo', 'attivo', $soglia + 8, $tetto * 4]
        );

        $fatti = 0;
        foreach ($coppie as $c) {
            if ($fatti >= $tetto) {
                break;
            }
            $rng = Rng::for(GameConfig::int('world.seed', 19870406), 'club_voce',
                (int) $c['voce_id'], (int) $c['da_id'], (int) $c['a_id'], intdiv($gts, 3600));
            if (!$rng->chance(min(0.9, $prob * (0.6 + 0.08 * (int) $c['loquacita'])))) {
                continue;
            }
            self::passa($c, $rng, $gts, 10);   // la penalità dello spogliatoio
            $fatti++;
        }
        return $fatti;
    }

    /**
     * Il passaggio vero e proprio: qui la voce si deforma.
     *
     * @param array<string,mixed> $c
     */
    private static function passa(array $c, Rng $rng, int $gts, int $penalita): void
    {
        $perdita = GameConfig::int('voci.perdita_passaggio', 28);

        $precisione = (int) round((int) $c['precisione'] * (1.0 - $perdita / 100.0))
            - $rng->int(0, 6) - $penalita;

        // Il tono si polarizza: non torna mai verso lo zero. Se era neutro,
        // il primo che la racconta decide da che parte pende.
        $tono = (int) $c['tono'];
        $verso = $tono === 0 ? ($rng->chance(0.5) ? 1 : -1) : ($tono > 0 ? 1 : -1);
        $tono += $verso * $rng->int(6, 20);

        self::scrivi(
            (int) $c['voce_id'], (int) $c['a_id'],
            max(0, $precisione), $tono, (int) $c['passaggi'] + 1, $gts, (int) $c['da_id']
        );
    }

    // --- Lettura ---------------------------------------------------------------

    /**
     * Quello che una persona ha sentito dire, dal più fresco al più vecchio.
     *
     * @return list<array<string,mixed>>
     */
    public static function per(int $pgId, int $quante = 30): array
    {
        $durata = GameConfig::int('voci.durata_giorni', 21) * 86400;
        $adesso = Orologio::lineare();

        $righe = Database::all(
            'SELECT vv.precisione, vv.tono, vv.passaggi, vv.gts AS saputo_gts, vv.da_id,
                    v.id, v.tipo, v.luogo, v.gts, v.dettaglio, v.seme,
                    v.soggetto_id, v.soggetto2_id,
                    d.nome AS da_nome, d.cognome AS da_cognome,
                    s1.nome AS s1_nome, s1.cognome AS s1_cognome, s1.sesso AS s1_sesso,
                    s1.sezione AS s1_sezione, s1.anno AS s1_anno,
                    s2.nome AS s2_nome, s2.cognome AS s2_cognome, s2.sesso AS s2_sesso,
                    s2.sezione AS s2_sezione, s2.anno AS s2_anno
             FROM voci_versioni vv
             JOIN voci v ON v.id = vv.voce_id
             LEFT JOIN personaggi d  ON d.id  = vv.da_id
             LEFT JOIN personaggi s1 ON s1.id = v.soggetto_id
             LEFT JOIN personaggi s2 ON s2.id = v.soggetto2_id
             WHERE vv.personaggio_id = ? AND v.gts >= ?
             ORDER BY vv.gts DESC
             LIMIT ?',
            [$pgId, $adesso - $durata, $quante]
        );

        $out = [];
        foreach ($righe as $r) {
            $out[] = [
                'id'         => (int) $r['id'],
                'tipo'       => (string) $r['tipo'],
                'precisione' => (int) $r['precisione'],
                'tono'       => (int) $r['tono'],
                'passaggi'   => (int) $r['passaggi'],
                'testo'      => self::racconta($r),
                'da'         => $r['da_id'] === null
                    ? null
                    : trim((string) $r['da_nome'] . ' ' . (string) $r['da_cognome']),
                'fa_ore'     => (int) floor(($adesso - (int) $r['saputo_gts']) / 3600),
                'vista'      => $r['da_id'] === null,
            ];
        }
        return $out;
    }

    // --- Il racconto -----------------------------------------------------------
    //
    // Qui si ricostruisce la frase. Quattro fasce di precisione, e in ognuna
    // si perde qualcosa di diverso, nell'ordine in cui lo perde la gente:
    // prima il nome, poi il posto, poi il fatto, e alla fine resta solo che
    // «qualcuno ha fatto qualcosa». L'ordine non è arbitrario — un nome è
    // l'informazione più fragile che ci sia in un pettegolezzo, ed è anche
    // quella che fa più danno quando viene sostituita da un'altra.

    private const PRECISO      = 80;
    private const SFOCATO      = 55;
    private const PETTEGOLEZZO = 30;

    /** @param array<string,mixed> $r riga grezza della query di per() */
    private static function racconta(array $r): string
    {
        $p   = (int) $r['precisione'];
        $rng = Rng::for((int) $r['seme'], 'racconto', (int) $r['id'], $p);

        $chi  = self::comeSiChiama($r, '1', $p, $rng);
        $chi2 = $r['soggetto2_id'] === null ? null : self::comeSiChiama($r, '2', $p, $rng);
        $dove = self::comeSiDice((string) $r['luogo'], $p, $rng);
        $cosa = self::cheCosa((string) $r['tipo'], (string) $r['dettaglio'], $p, $rng);

        // Quando i protagonisti sono due si nominano sempre tutti e due, anche
        // a precisione bassa: il pettegolezzo perde i nomi, non il fatto che
        // fossero in due. «Uno del terzo anno e una del primo stavano insieme»
        // e' precisamente il modo in cui una scuola rovina una reputazione.
        if ($chi2 !== null && $chi === $chi2) {
            // Due descrizioni identiche non si ripetono: chi racconta dice
            // «due del terzo anno», non «uno del terzo anno e uno del terzo anno».
            $chi  = preg_replace('/^(uno|una|un ragazzo|una ragazza)\b/u', 'due', $chi) ?? $chi;
            $chi2 = null;
        }
        $frase = $chi2 !== null
            ? sprintf('%s e %s %s %s', $chi, $chi2, $cosa, $dove)
            : sprintf('%s %s %s', $chi, $cosa, $dove);

        $frase = trim(preg_replace('/\s+/', ' ', $frase) ?? $frase);
        $frase = str_replace([' ,', ' .'], [',', '.'], $frase);
        $frase = mb_strtoupper(mb_substr($frase, 0, 1)) . mb_substr($frase, 1);

        return $frase . self::coda((int) $r['tono'], (int) $r['passaggi'], $rng);
    }

    /**
     * Come viene chiamata una persona a una data precisione. È il pezzo che
     * fa più lavoro narrativo: «Kyosuke Kasuga» diventa «uno del terzo anno»,
     * e da quel momento chiunque del terzo anno può prendersi la colpa.
     *
     * @param array<string,mixed> $r
     */
    private static function comeSiChiama(array $r, string $n, int $p, Rng $rng): string
    {
        $nome    = (string) ($r["s{$n}_nome"] ?? '');
        $cognome = (string) ($r["s{$n}_cognome"] ?? '');
        if ($nome === '' && $cognome === '') {
            return 'qualcuno';
        }
        $donna = (string) ($r["s{$n}_sesso"] ?? 'm') === 'f';

        if ($p >= self::PRECISO) {
            return trim($nome . ' ' . $cognome);
        }
        if ($p >= self::SFOCATO) {
            // Si perde metà del nome: resta il battesimo o il cognome.
            return $rng->chance(0.5) && $nome !== '' ? $nome : ($cognome !== '' ? $cognome : $nome);
        }

        $sezione = (string) ($r["s{$n}_sezione"] ?? Scuola::SUPERIORI);
        $anno    = (int) ($r["s{$n}_anno"] ?? 2);
        if ($p >= self::PETTEGOLEZZO) {
            $ord = [1 => 'primo', 2 => 'secondo', 3 => 'terzo'][max(1, min(3, $anno))];
            $chi = $donna ? 'una' : 'uno';
            return $sezione === Scuola::MEDIE
                ? "{$chi} delle medie, di {$ord} anno"
                : "{$chi} del {$ord} anno";
        }
        // Niente forme spoglie («uno», «una»): da sole passano, ma in coppia
        // danno «uno e una stavano insieme», che non e' italiano.
        return (string) ($rng->pick($donna
            ? ['una ragazza', 'una del liceo', 'una di quelle del liceo']
            : ['un ragazzo', 'uno del liceo', 'uno di quelli del liceo']) ?? 'qualcuno');
    }

    /** Il posto: nome proprio, poi genere di posto, poi niente. */
    private static function comeSiDice(string $lkey, int $p, Rng $rng): string
    {
        if ($p < self::PETTEGOLEZZO) {
            return (string) ($rng->pick(['da qualche parte', 'in giro', '']) ?? '');
        }
        $l = Luoghi::uno($lkey);
        if ($l === null) {
            return 'in giro';
        }
        if ($p >= self::SFOCATO) {
            // La forma locativa e' un dato del luogo, non il nome: «Il parco»
            // diventa «al parco», e non «a Il parco». La regola sta in
            // Luoghi::dove(), una volta sola: serve anche altrove.
            return Luoghi::dove($lkey);
        }
        return match ((string) $l['tipo']) {
            'scuola'  => 'a scuola',
            'casa'    => 'a casa di qualcuno',
            'ritrovo' => 'in uno di quei posti dove si ritrovano tutti',
            'strada'  => 'per strada',
            'natura'  => 'fuori, all\'aperto',
            default   => 'fuori dal quartiere',
        };
    }

    /**
     * Il fatto. Ogni nucleo ha la sua scala, dal preciso al nebbioso, e le
     * scale sono scritte a mano: un generatore di frasi a caso, qui, farebbe
     * soltanto rumore.
     */
    private static function cheCosa(string $tipo, string $dettaglio, int $p, Rng $rng): string
    {
        $scala  = self::FRASI[$tipo] ?? self::FRASI['potere'];
        $fascia = match (true) {
            $p >= self::PRECISO      => 'preciso',
            $p >= self::SFOCATO      => 'sfocato',
            $p >= self::PETTEGOLEZZO => 'pettegolezzo',
            default                  => 'nebbia',
        };
        $frase = (string) ($rng->pick($scala[$fascia]) ?? 'ha combinato qualcosa');

        if ($fascia === 'preciso' && $dettaglio !== '' && str_contains($frase, '%s')) {
            return sprintf($frase, self::dettaglio($tipo, $dettaglio));
        }
        return str_replace('%s', 'qualcosa', $frase);
    }

    /** Il particolare vero, quando la precisione lo regge. */
    private static function dettaglio(string $tipo, string $dettaglio): string
    {
        if ($tipo === 'potere') {
            $n = Database::first('SELECT nome FROM poteri WHERE pkey = ?', [$dettaglio]);
            return $n === null ? 'qualcosa di impossibile' : mb_strtolower((string) $n['nome']);
        }
        if ($tipo === 'gesto') {
            $n = Database::first('SELECT nome FROM gesti WHERE gkey = ?', [$dettaglio]);
            return $n === null ? 'qualcosa' : mb_strtolower((string) $n['nome']);
        }
        return $dettaglio;
    }

    /**
     * La coda: il tono, e quante bocche ha attraversato. Una voce di quarta
     * mano lo dice da sola che è di quarta mano — «dicono», «pare» — ed è
     * l'unico modo onesto di far capire al giocatore quanto fidarsi.
     */
    private static function coda(int $tono, int $passaggi, Rng $rng): string
    {
        $pezzi = [];

        if ($tono <= -45) {
            $pezzi[] = (string) $rng->pick([
                'e non se ne parla bene',
                'e la cosa ha fatto arrabbiare parecchia gente',
                'ed è una brutta storia',
            ]);
        } elseif ($tono <= -15) {
            $pezzi[] = (string) $rng->pick(['e qualcuno ha storto il naso', 'e la cosa non è piaciuta']);
        } elseif ($tono >= 45) {
            $pezzi[] = (string) $rng->pick([
                'e a quanto pare è stata una bella cosa',
                'e tutti ne parlano bene',
                'ed è finita meglio di come sembrava',
            ]);
        } elseif ($tono >= 15) {
            $pezzi[] = (string) $rng->pick(['e pare sia andata bene', 'e la cosa ha fatto piacere']);
        }

        if ($passaggi >= 4) {
            $pezzi[] = 'ma ormai lo dicono tutti e nessuno sa da dove venga';
        } elseif ($passaggi === 3) {
            $pezzi[] = 'o almeno così dicono';
        }

        return $pezzi === [] ? '.' : ', ' . implode(', ', $pezzi) . '.';
    }

    /**
     * Le scale di frasi, una per nucleo.
     *
     * @var array<string, array<string, list<string>>>
     */
    private const FRASI = [
        'potere' => [
            'preciso'      => ['ha fatto %s davanti a tutti', 'ha usato %s senza nemmeno nascondersi',
                               'ha fatto %s in mezzo alla gente'],
            'sfocato'      => ['ha fatto una cosa che non si spiega', 'ha combinato qualcosa di impossibile',
                               'ha fatto muovere della roba senza toccarla'],
            'pettegolezzo' => ['ha fatto una cosa stranissima', 'ha fatto qualcosa che nessuno sa spiegare'],
            'nebbia'       => ['ha fatto una cosa strana', 'si è comportato in un modo strano'],
        ],
        'gesto' => [
            'preciso'      => ['ha fatto %s', 'si è preso la briga di %s'],
            'sfocato'      => ['ha avuto un gesto carino', 'si è fatto avanti'],
            'pettegolezzo' => ['ha fatto qualcosa', 'ci ha provato'],
            'nebbia'       => ['ha fatto qualcosa', 'c\'entrava in qualche modo'],
        ],
        'confessione' => [
            'preciso'      => ['si è dichiarato', 'ha detto quello che provava, ad alta voce'],
            'sfocato'      => ['ha detto una cosa importante', 'si è dichiarato a qualcuno'],
            'pettegolezzo' => ['ha detto qualcosa di grosso', 'si è dichiarato, pare'],
            'nebbia'       => ['ha detto qualcosa', 'ha fatto una scenata'],
        ],
        'litigio' => [
            'preciso'      => ['ha litigato di brutto', 'ha alzato la voce'],
            'sfocato'      => ['ha litigato', 'ha avuto una discussione pesante'],
            'pettegolezzo' => ['ha avuto da ridire con qualcuno', 'ha fatto una scenata'],
            'nebbia'       => ['ha fatto una scenata', 'ha combinato un guaio'],
        ],
        'insieme' => [
            'preciso'      => ['sono stati visti insieme', 'se ne stavano insieme, da soli'],
            'sfocato'      => ['erano insieme', 'li hanno visti insieme'],
            'pettegolezzo' => ['stavano insieme', 'erano in giro insieme'],
            'nebbia'       => ['stavano insieme', 'si vedevano'],
        ],
        'partenza' => [
            'preciso'      => ['se n\'è andato, e non è più tornato', 'ha traslocato di punto in bianco'],
            'sfocato'      => ['se n\'è andato', 'ha cambiato scuola'],
            'pettegolezzo' => ['non si vede più in giro', 'è sparito'],
            'nebbia'       => ['è sparito', 'non c\'è più'],
        ],
        'affisso' => [
            'preciso'      => ['ha attaccato un avviso in bacheca'],
            'sfocato'      => ['ha scritto qualcosa in bacheca'],
            'pettegolezzo' => ['ha lasciato un messaggio da qualche parte'],
            'nebbia'       => ['ha scritto qualcosa'],
        ],
    ];

    /** Quante voci non lette ha addosso una persona (per il pallino). */
    public static function quante(int $pgId): int
    {
        $durata = GameConfig::int('voci.durata_giorni', 21) * 86400;
        return (int) (Database::first(
            'SELECT COUNT(*) n FROM voci_versioni vv JOIN voci v ON v.id = vv.voce_id
             WHERE vv.personaggio_id = ? AND v.gts >= ?',
            [$pgId, Orologio::lineare() - $durata]
        )['n'] ?? 0);
    }

    /** Potatura: le voci spente non servono a nessuno. La chiama il battito. */
    public static function pota(): int
    {
        $durata = GameConfig::int('voci.durata_giorni', 21) * 86400;
        return Database::run(
            'DELETE FROM voci WHERE gts < ?', [Orologio::lineare() - $durata]
        )->rowCount();
    }
}
