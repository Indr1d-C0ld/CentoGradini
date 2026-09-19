<?php

declare(strict_types=1);

namespace App\Game;

use App\Core\Database;
use App\Core\GameConfig;
use App\Sim\Rng;
use App\Sim\Scuola;

/**
 * La scheda del personaggio: come si tira, come si completa, come si legge.
 *
 * **I tiri sono deterministici.** Nascono da `Rng::for(seme del mondo, 'pg',
 * id)`, quindi ricaricare la pagina non ritira niente: chi ha avuto tre di
 * RISSA se lo tiene. Nel gioco da tavolo del 1990 questo lo garantiva la
 * presenza fisica degli amici; qui lo deve garantire il motore, perche'
 * altrimenti in mezz'ora tutti hanno quindici dappertutto e il sistema non
 * dice piu' niente.
 *
 * **La creazione ha due tempi.** Prima il motore tira (abilita', tratti,
 * poteri), poi il giocatore distribuisce dei punti liberi. In mezzo il
 * personaggio esiste gia' e sta in piedi, con `scheda = 'abbozzo'`: e' lo
 * stesso stato in cui si trovano i personaggi nati in F1, quando la scheda
 * non c'era ancora. Una strada sola per due situazioni, invece di un
 * completamento retroattivo scritto a parte che si dimentica un campo.
 */
final class Scheda
{
    /**
     * Le bande del tiro per il potere **primario**.
     *
     * Il regolamento del 1990 dava: telecinesi 01-35, teletrasporto 36-59,
     * **telepatia 60-91**. Cioe' un esper su tre nasceva telepate. Il canone
     * dice l'opposto: la FAQ storica di KOR, alla domanda «chi detiene quale
     * potere», scrive che Kazuya e' **il solo telepate della famiglia** — e
     * la voce giapponese insiste che Kyosuke, che ha tutto il resto, la
     * telepatia non ce l'ha. Un potere che nell'opera e' unico non puo'
     * toccare a un terzo dei giocatori.
     *
     * @var array<string,array{0:int,1:int}>
     */
    public const BANDE_PRIMARIE = [
        'telecinesi'    => [1, 52],
        'teletrasporto' => [53, 92],
        'telepatia'     => [93, 98],
        // 99-100: si ritira.
    ];

    public const ABILITA = ['rissa', 'testa', 'dai_suki', 'cuore'];
    public const SECONDARIE = ['inglese', 'sport', 'guida', 'kakko', 'nuoto', 'musica', 'cucina', 'candore'];

    public const NOMI = [
        'rissa'    => 'Rissa',    'testa'   => 'Testa',   'dai_suki' => 'Dai-suki', 'cuore' => 'Cuore',
        'inglese'  => 'Inglese',  'sport'   => 'Sport',   'guida'    => 'Guida',    'kakko' => 'Kakko',
        'nuoto'    => 'Nuoto',    'musica'  => 'Musica',  'cucina'   => 'Cucina',   'candore' => 'Candore',
    ];

    public const SPIEGAZIONI = [
        'rissa'    => 'Prontezza fisica e coraggio del corpo. Serve di rado, e quando serve serve tutta.',
        'testa'    => 'Intelligenza, studio, inventiva. È anche la scusa che riesci a inventarti sul momento.',
        'dai_suki' => 'Aspetto, carisma, l\'impressione che fai entrando in una stanza.',
        'cuore'    => 'La capacità di dire quello che provi. È la statistica di cui questa storia parla.',
        'inglese'  => 'Sei anni di scuola e nessuno che lo parli davvero.',
        'sport'    => 'Quanto te la cavi in palestra e al festival sportivo.',
        'guida'    => 'Bicicletta e motorino. La patente vera è lontana.',
        'kakko'    => 'Lo stile: come vieni fuori quando provi a sembrare uno tosto.',
        'nuoto'    => 'Il mare d\'agosto e la piscina di luglio.',
        'musica'   => 'Cantare, suonare, tenere il tempo. Madoka è professionale; quasi nessun altro lo è.',
        'cucina'   => 'Il bentō è una dichiarazione. Saperlo fare non è un dettaglio.',
        'candore'  => 'Quanto ti smonta l\'imbarazzo. Alto: ti travolge. Basso: resti di sasso, e nessuno capisce cosa provi.',
    ];

    // --- Lettura ---------------------------------------------------------------

    /** @return list<array<string,mixed>> */
    public static function tratti(int $pgId): array
    {
        return Database::all(
            'SELECT t.* FROM personaggio_tratti pt JOIN tratti t ON t.tkey = pt.tkey
             WHERE pt.personaggio_id = ? ORDER BY t.da',
            [$pgId]
        );
    }

    /** @return list<array<string,mixed>> */
    public static function poteri(int $pgId): array
    {
        return Database::all(
            'SELECT p.*, pp.primario, pp.controllo, pp.usi
             FROM personaggio_poteri pp JOIN poteri p ON p.pkey = pp.pkey
             WHERE pp.personaggio_id = ? ORDER BY pp.primario DESC, p.nome',
            [$pgId]
        );
    }

    /** Il tiro di intuizione: 30 + TESTA×2 per cento. È il verbo del non-esper. */
    public static function intuizione(array $pg): int
    {
        return min(95, 30 + (int) $pg['testa'] * 2);
    }

    /** Costo in PP di un potere per questo personaggio, Controllo compreso. */
    public static function costo(array $potere): int
    {
        $base = (int) $potere['primario'] === 1
            ? (int) ($potere['costo_primario'] ?? $potere['costo_secondario'])
            : (int) $potere['costo_secondario'];
        // Il Controllo è l'unica cosa che cresce: a 40 e a 80 si risparmia un
        // punto, e non si scende mai sotto zero.
        $sconto = ((int) $potere['controllo'] >= 40 ? 1 : 0) + ((int) $potere['controllo'] >= 80 ? 1 : 0);
        return max(0, $base - $sconto);
    }

    // --- Il tiro ---------------------------------------------------------------

    /**
     * Assicura che il personaggio abbia i tiri fatti.
     *
     * È il punto di completamento retroattivo: i personaggi nati in F1 hanno
     * le abilità a zero e passano di qui esattamente come quelli nuovi. Il
     * generatore è ancorato all'id, quindi la funzione è idempotente in senso
     * pieno — chiamarla due volte non cambia una virgola.
     *
     * @param array<string,mixed> $pg
     * @return array<string,mixed> il personaggio aggiornato
     */
    public static function assicuraTiri(array $pg): array
    {
        $id = (int) $pg['id'];
        $pg = self::riparaCompleanno($pg);
        if ((int) $pg['rissa'] > 0) {
            return $pg;      // già tirato
        }

        $rng   = Rng::for(GameConfig::int('world.seed', 19870406), 'pg', $id);
        $esper = (bool) $pg['esper'];

        // 1. Le quattro abilità. Il tiro sotto il 3 si ripete: è la regola
        //    «Col cazzo» del 1990, ripulita del suo nome.
        $ab = [];
        foreach (self::ABILITA as $a) {
            do { $v = $rng->int(1, 10); } while ($v < 3);
            $ab[$a] = $v;
        }

        // 2. I tratti, con i modificatori addosso alle abilità.
        $tratti = self::tiraTratti($rng, (string) $pg['sesso'],
            GameConfig::int($esper ? 'pg.tratti' : 'pg.tratti_umano', $esper ? 3 : 4));
        $modTratti = self::modificatori($tratti);
        foreach (self::ABILITA as $a) {
            $ab[$a] = max(1, min(15, $ab[$a] + ($modTratti[$a] ?? 0)));
        }

        // 3. I poteri: solo per chi è della stirpe.
        $poteri = $esper ? self::tiraPoteri($rng) : [];

        Database::run(
            'UPDATE personaggi SET rissa = ?, testa = ?, dai_suki = ?, cuore = ? WHERE id = ?',
            [$ab['rissa'], $ab['testa'], $ab['dai_suki'], $ab['cuore'], $id]
        );
        Database::run('DELETE FROM personaggio_tratti WHERE personaggio_id = ?', [$id]);
        foreach ($tratti as $t) {
            Database::run('INSERT IGNORE INTO personaggio_tratti (personaggio_id, tkey) VALUES (?, ?)',
                [$id, $t['tkey']]);
        }
        Database::run('DELETE FROM personaggio_poteri WHERE personaggio_id = ?', [$id]);
        foreach ($poteri as $p) {
            Database::run(
                'INSERT IGNORE INTO personaggio_poteri (personaggio_id, pkey, primario) VALUES (?, ?, ?)',
                [$id, $p['pkey'], $p['primario']]
            );
        }

        return Database::first('SELECT * FROM personaggi WHERE id = ?', [$id]) ?? $pg;
    }

    /**
     * Ripara il compleanno dei personaggi nati in F1.
     *
     * Quando la scheda non esisteva il compleanno non si chiedeva, e la
     * migrazione ha messo a tutti il 2 aprile con una coorte di comodo. Un
     * quartiere in cui tutti sono nati lo stesso giorno non è un difetto
     * estetico: i compleanni sono un fatto di gioco, e se cadono tutti
     * insieme non valgono niente.
     *
     * Il segno che una riga è vecchia è che l'anno di nascita **non** è
     * quello che la sua classe comporta: la coorte si ricava dalla classe,
     * quindi una riga coerente non può sbagliarlo. La data nuova è estratta
     * dal seme del mondo e dall'id, quindi è stabile.
     *
     * @param array<string,mixed> $pg
     * @return array<string,mixed>
     */
    private static function riparaCompleanno(array $pg): array
    {
        $atteso = Scuola::annoDiNascita(
            (string) $pg['sezione'], (int) $pg['anno'],
            (int) $pg['nato_mese'], (int) $pg['nato_giorno']
        );
        if ((int) $pg['anno_nascita'] === $atteso) {
            return $pg;
        }

        $rng   = Rng::for(GameConfig::int('world.seed', 19870406), 'compleanno', (int) $pg['id']);
        $mese  = $rng->int(1, 12);
        $giorni = (int) (new \DateTimeImmutable(sprintf('1988-%02d-01', $mese)))->format('t');
        $giorno = $rng->int(1, $giorni);
        $nascita = Scuola::annoDiNascita((string) $pg['sezione'], (int) $pg['anno'], $mese, $giorno);

        Database::run(
            'UPDATE personaggi SET nato_mese = ?, nato_giorno = ?, anno_nascita = ? WHERE id = ?',
            [$mese, $giorno, $nascita, (int) $pg['id']]
        );
        $pg['nato_mese'] = $mese;
        $pg['nato_giorno'] = $giorno;
        $pg['anno_nascita'] = $nascita;
        return $pg;
    }

    /**
     * Tira i tratti.
     *
     * I due risultati alti della tabella del 1990 (97-98 e 99-00) non sono
     * tratti: sono «tira altre due volte» e «altre tre volte». Restano, perché
     * sono il motivo per cui ogni tanto esce un personaggio assurdo, e i
     * personaggi assurdi sono metà del divertimento — ma con un tetto di tre
     * tratti in più del previsto. Il compounding non era limitato nel 1990 e
     * non se ne accorgeva nessuno: con i dadi veri si tirano cinque
     * personaggi in una sera, non cinquecento.
     *
     * @return list<array<string,mixed>>
     */
    private static function tiraTratti(Rng $rng, string $sesso, int $quanti): array
    {
        $tabella = Database::all('SELECT * FROM tratti ORDER BY da');
        $presi   = [];
        $daFare  = $quanti;
        $giri    = 0;
        $tetto   = $quanti + 3;

        while ($daFare > 0 && $giri < 60 && count($presi) < $tetto) {
            $giri++;
            $d = $rng->int(1, 100);
            if ($d >= 99) { $daFare += 3; continue; }
            if ($d >= 97) { $daFare += 2; continue; }
            foreach ($tabella as $t) {
                if ($d < (int) $t['da'] || $d > (int) $t['a']) { continue; }
                // Si ritira se il tratto c'è già o se non vale per questo sesso.
                if (isset($presi[$t['tkey']])) { break; }
                if ($t['sesso'] !== 'MF' && $t['sesso'] !== strtoupper($sesso)) { break; }
                $presi[$t['tkey']] = $t;
                $daFare--;
                break;
            }
        }
        return array_values($presi);
    }

    /**
     * Tira i poteri: uno primario e tre secondari.
     *
     * **La telepatia è rarissima**, e lo è per canone: nella famiglia di
     * Kyosuke ce l'ha una persona sola. Non c'è nessun divieto che la leghi
     * agli altri poteri — ci avevo provato e le fonti dicono di no — c'è solo
     * che esce poco: sei possibilità su cento come primario, tre come
     * secondario.
     *
     * @return list<array{pkey:string,primario:int}>
     */
    private static function tiraPoteri(Rng $rng): array
    {
        $primari   = Database::all('SELECT * FROM poteri WHERE costo_primario IS NOT NULL ORDER BY da');
        $secondari = Database::all('SELECT * FROM poteri ORDER BY da');

        // Primario: 01-35 telecinesi, 36-59 teletrasporto, 60-91 telepatia,
        // 92-99 si sceglie (qui: si ritira).
        $perChiave = [];
        foreach ($primari as $p) {
            $perChiave[(string) $p['pkey']] = $p;
        }
        $primario = null;
        for ($i = 0; $i < 40 && $primario === null; $i++) {
            $d = $rng->int(1, 100);
            foreach (self::BANDE_PRIMARIE as $k => [$da, $a]) {
                if ($d >= $da && $d <= $a && isset($perChiave[$k])) { $primario = $perChiave[$k]; break; }
            }
        }
        $primario ??= $perChiave['telecinesi'] ?? $primari[0];

        // **Regola ritirata il 19/09/2026.** Avevo escluso a vicenda
        // teletrasporto e telepatia, fondandomi su una parentesi dubitativa
        // della voce giapponese («nel manga sembra che Kazuya non possa
        // teletrasportarsi»). La FAQ storica di KOR dice il contrario a
        // chiare lettere: Kazuya si teletrasporta *e* e' il solo telepate
        // della famiglia. Quello su cui le due fonti concordano non e' che i
        // due poteri si escludano — e' che la **telepatia sia rarissima**, e
        // quello si ottiene con le bande del tiro, non con un divieto.

        $presi  = [(string) $primario['pkey'] => ['pkey' => (string) $primario['pkey'], 'primario' => 1]];
        $daFare = GameConfig::int('pg.poteri_secondari', 3);
        $giri   = 0;

        // Primario + i secondari previsti + al massimo tre di regalo: sette.
        $massimo = 1 + $daFare + 3;

        while ($daFare > 0 && $giri < 80 && count($presi) < $massimo) {
            $giri++;
            $d = $rng->int(1, 100);
            // Come per i tratti, i due risultati alti valgono «tira ancora»:
            // è il motivo per cui ogni tanto nasce un esper più carico degli
            // altri, e va tenuto. Ma **con un tetto**, che nel 1990 non c'era
            // e non serviva: con i dadi veri si tiravano cinque personaggi in
            // una sera e la coda lunga non si vedeva mai. Qui i personaggi
            // sono centinaia, e senza limite ne esce uno con tredici poteri
            // su sedici — che non è un personaggio fortunato, è un
            // personaggio a cui il Segreto non fa più paura, e il Segreto è
            // tutto il gioco.
            if ($d >= 99) { $daFare += 3; continue; }
            if ($d >= 97) { $daFare += 2; continue; }
            foreach ($secondari as $p) {
                if ($d < (int) $p['da'] || $d > (int) $p['a']) { continue; }
                $k = (string) $p['pkey'];
                if (isset($presi[$k])) { break; }
                $presi[$k] = ['pkey' => $k, 'primario' => 0];
                $daFare--;
                break;
            }
        }
        return array_values($presi);
    }

    /**
     * Somma dei modificatori dei tratti.
     * @param list<array<string,mixed>> $tratti
     * @return array<string,int>
     */
    public static function modificatori(array $tratti): array
    {
        $out = [];
        foreach ($tratti as $t) {
            /** @var array<string,int> $m */
            $m = json_decode((string) ($t['modificatori'] ?? '{}'), true) ?: [];
            foreach ($m as $k => $v) {
                $out[$k] = ($out[$k] ?? 0) + (int) $v;
            }
        }
        return $out;
    }

    // --- La chiusura ------------------------------------------------------------

    public static function puntiDaDistribuire(array $pg): int
    {
        return (bool) $pg['esper']
            ? GameConfig::int('pg.punti_abilita', 10)
            : GameConfig::int('pg.punti_abilita_umano', 14);
    }

    /**
     * Chiude la scheda: applica la distribuzione dei punti, calcola tutto il
     * derivato e mette `scheda = 'completa'`.
     *
     * @param array<string,int> $punti  quanti punti su ciascuna abilità
     * @return array{ok:bool, error?:string}
     */
    public static function finalizza(array $pg, array $punti): array
    {
        $id  = (int) $pg['id'];
        $max = GameConfig::int('pg.abilita_max', 15);
        $disponibili = self::puntiDaDistribuire($pg);

        $somma = 0;
        foreach (self::ABILITA as $a) {
            $v = max(0, (int) ($punti[$a] ?? 0));
            $somma += $v;
            if ((int) $pg[$a] + $v > $max) {
                return ['ok' => false, 'error' => 'Nessuna abilità può superare ' . $max . ': '
                    . self::NOMI[$a] . ' arriverebbe a ' . ((int) $pg[$a] + $v) . '.'];
            }
            $punti[$a] = $v;
        }
        if ($somma > $disponibili) {
            return ['ok' => false, 'error' => "Hai distribuito {$somma} punti su {$disponibili}."];
        }
        if ($somma < $disponibili) {
            return ['ok' => false, 'error' => 'Ti restano ' . ($disponibili - $somma)
                . ' punti da distribuire: non si lasciano per dopo.'];
        }

        $ab = [];
        foreach (self::ABILITA as $a) {
            $ab[$a] = (int) $pg[$a] + $punti[$a];
        }

        $rng    = Rng::for(GameConfig::int('world.seed', 19870406), 'pg-derivato', $id);
        $mod    = self::modificatori(self::tratti($id));
        $esper  = (bool) $pg['esper'];
        $sec    = self::secondarie($ab, $mod, $rng);
        $pf     = self::puntiFerita($ab['rissa'], $mod, $rng);
        // «Roll 1d10/2+6. The range is therefore 7-11», dice il regolamento.
        // Con la divisione troncata un 1 darebbe 0, e quindi 6: il minimo
        // dichiarato non tornerebbe. Il mezzo punto vale uno.
        $pp     = $esper ? max(1, (int) floor($rng->int(1, 10) / 2)) + 6 : 0;
        $compMax = max(2, min(10, 12 - $sec['candore']));

        Database::run(
            'UPDATE personaggi SET rissa = ?, testa = ?, dai_suki = ?, cuore = ?,
                    inglese = ?, sport = ?, guida = ?, kakko = ?, nuoto = ?, musica = ?, cucina = ?, candore = ?,
                    pf_max = ?, pf = ?, pp_max = ?, pp = ?, compostezza_max = ?, compostezza = ?,
                    scheda = ?
               WHERE id = ?',
            [
                $ab['rissa'], $ab['testa'], $ab['dai_suki'], $ab['cuore'],
                $sec['inglese'], $sec['sport'], $sec['guida'], $sec['kakko'],
                $sec['nuoto'], $sec['musica'], $sec['cucina'], $sec['candore'],
                $pf, $pf, $pp, $pp, $compMax, $compMax,
                'completa', $id,
            ]
        );
        return ['ok' => true];
    }

    /**
     * Le otto abilità secondarie.
     *
     * Le prime cinque hanno le formule del 1990, con un solo adattamento:
     * GUIDA li' riguardava l'automobile e la patente americana a sedici anni.
     * Qui siamo in Giappone nel 1987, dove l'auto arriva a diciotto: copre la
     * bicicletta e il motorino, che sono quello che nell'opera si guida
     * davvero — Kyosuke spinge la bici con la telecinesi, non l'automobile.
     *
     * @param array<string,int> $ab
     * @param array<string,int> $mod
     * @return array<string,int>
     */
    public static function secondarie(array $ab, array $mod, Rng $rng): array
    {
        $s = [];
        $s['inglese'] = (int) floor($rng->int(1, 10) / 2) + (int) floor($ab['testa'] / 2);
        $s['sport']   = $rng->int(1, 10) - 2 + ($ab['rissa'] > 11 ? 1 : 0) + ($ab['dai_suki'] > 11 ? 1 : 0);
        $s['guida']   = $rng->int(1, 10) - 3;
        $s['kakko']   = (int) floor($rng->int(1, 10) / 2) + (int) floor($ab['dai_suki'] / 2);
        $s['musica']  = $rng->int(1, 10) - 3;
        $s['cucina']  = $rng->int(1, 10) - 3;
        // Il candore non discende dalle altre: è quanto ti smonta l'imbarazzo,
        // e non c'entra con quanto sei bravo o bello.
        $s['candore'] = $rng->int(1, 10);

        foreach ($s as $k => $v) {
            $s[$k] = max(1, min(10, $v + ($mod[$k] ?? 0)));
        }
        // Il nuoto si calcola dopo, perché dipende dallo sport già aggiustato.
        $s['nuoto'] = max(1, min(10,
            (int) floor(($s['sport'] + $rng->int(1, 10) / 2) / 3) + ($mod['nuoto'] ?? 0)));
        return $s;
    }

    /** @param array<string,int> $mod */
    public static function puntiFerita(int $rissa, array $mod, Rng $rng): int
    {
        $pf = $rng->int(1, 10) + 4;
        if ($rissa === 15)      { $pf += 3; }
        elseif ($rissa > 12)    { $pf += 2; }
        elseif ($rissa > 8)     { $pf += 1; }
        return max(1, $pf + ($mod['pf'] ?? 0));
    }

    /** Riassunto in una riga: «3ª superiore · 17 anni · esper». */
    public static function riga(array $pg): string
    {
        $pezzi = [Scuola::nomeClasse((string) $pg['sezione'], (int) $pg['anno'])];
        $pezzi[] = Scuola::eta((int) $pg['anno_nascita'], (int) $pg['nato_mese'], (int) $pg['nato_giorno']) . ' anni';
        $pezzi[] = (bool) $pg['esper'] ? 'della stirpe' : 'nessun potere';
        return implode(' · ', $pezzi);
    }
}
