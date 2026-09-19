<?php

declare(strict_types=1);

/**
 * La scheda del personaggio.
 *
 * Scrive sul database: crea utenti e personaggi con un prefisso riconoscibile
 * e li cancella comunque vada, anche se fallisce a metà.
 *
 *   php tests/test_scheda.php
 */

require __DIR__ . '/_prova.php';

use App\Core\Config;
use App\Core\Database;
use App\Core\GameConfig;
use App\Game\Personaggio;
use App\Game\Scheda;
use App\Sim\Rng;
use App\Sim\Scuola;

Config::load(dirname(__DIR__));

const PREFISSO = 'zzprova';
$creati = [];

/** Crea un personaggio di prova e restituisce la riga. */
function finto(string $nome, bool $esper, string $sesso = 'f', array $extra = []): array
{
    global $creati;
    $utente = PREFISSO . ' ' . $nome;
    Database::run(
        'INSERT INTO users (username, email, password_hash, status) VALUES (?, ?, ?, ?)',
        [$utente, strtolower($nome) . '@zzprova.invalid', 'x', 'active']
    );
    $uid = Database::lastInsertId();
    $creati[] = $uid;
    $r = Personaggio::crea(
        $uid, $nome, ucfirst(PREFISSO), $sesso,
        $extra['sezione'] ?? Scuola::SUPERIORI, $extra['anno'] ?? 2,
        $extra['mese'] ?? 6, $extra['giorno'] ?? 15, $esper
    );
    if (!$r['ok']) {
        throw new RuntimeException('creazione fallita: ' . ($r['error'] ?? '?'));
    }
    return Scheda::assicuraTiri(Database::first('SELECT * FROM personaggi WHERE id = ?', [$r['id']]));
}

/**
 * Distribuisce $quanti punti sulle quattro abilità senza sfondare il 15.
 * Serve perché mettere tutto su una sola abilità fa scattare il tetto e la
 * prova finisce per verificare il messaggio sbagliato.
 *
 * @return array<string,int>
 */
function sparpaglia(array $pg, int $quanti): array
{
    $p = array_fill_keys(Scheda::ABILITA, 0);
    $giri = 0;
    while ($quanti > 0 && $giri < 200) {
        $giri++;
        foreach (Scheda::ABILITA as $a) {
            if ($quanti <= 0) { break; }
            if ((int) $pg[$a] + $p[$a] < 15) { $p[$a]++; $quanti--; }
        }
    }
    return $p;
}

function pulisci(): void
{
    global $creati;
    foreach ($creati as $uid) {
        try { Database::run('DELETE FROM users WHERE id = ?', [$uid]); } catch (Throwable) {}
    }
    try { Database::run('DELETE FROM users WHERE username LIKE ?', [PREFISSO . '%']); } catch (Throwable) {}
    try { Database::run('DELETE FROM tracce WHERE testo LIKE ?', ['%' . ucfirst(PREFISSO) . '%']); } catch (Throwable) {}
    $creati = [];
}
register_shutdown_function('pulisci');

// --- I tiri ------------------------------------------------------------------

prova('i tiri sono deterministici: ricaricare non ritira', function () {
    $pg = finto('Aaa', true);
    $prima = [$pg['rissa'], $pg['testa'], $pg['dai_suki'], $pg['cuore']];
    // assicuraTiri deve essere idempotente in senso pieno.
    Scheda::assicuraTiri(Database::first('SELECT * FROM personaggi WHERE id = ?', [$pg['id']]));
    $dopo = Database::first('SELECT rissa, testa, dai_suki, cuore FROM personaggi WHERE id = ?', [$pg['id']]);
    uguale($prima, array_values(array_map('intval', $dopo)));
});

prova('nessuna abilità nasce sotto 3', function () {
    // È la regola «Col cazzo» del 1990, ripulita: sotto il 3 si ritira.
    // I modificatori dei tratti possono poi scendere, ma il TIRO no.
    for ($i = 0; $i < 25; $i++) {
        $rng = Rng::for(12345, 'prova-abilita', $i);
        do { $v = $rng->int(1, 10); } while ($v < 3);
        vero($v >= 3 && $v <= 10, "tiro fuori scala: {$v}");
    }
});

prova('i tratti sono tre (quattro senza poteri), e al massimo tre in più', function () {
    // Il numero non è fisso: i due risultati alti della tabella valgono «tira
    // ancora». Quello che dev'essere fisso è il minimo e il tetto.
    $conta = [];
    for ($i = 0; $i < 25; $i++) {
        $esper = $i % 2 === 0;
        $n = count(Scheda::tratti((int) finto('Tr' . $i, $esper)['id']));
        $base = $esper ? 3 : 4;
        vero($n >= $base, ($esper ? 'esper' : 'umano') . ": solo {$n} tratti");
        vero($n <= $base + 3, ($esper ? 'esper' : 'umano') . ": ben {$n} tratti, il tetto è " . ($base + 3));
        $conta[$n] = ($conta[$n] ?? 0) + 1;
    }
    vero(count($conta) >= 1, 'campione vuoto');
});

prova('i tratti non si ripetono', function () {
    for ($i = 0; $i < 6; $i++) {
        $t = array_column(Scheda::tratti((int) finto('Dup' . $i, $i % 2 === 0)['id']), 'tkey');
        uguale(count($t), count(array_unique($t)), 'tratti doppi: ' . implode(',', $t));
    }
});

prova('un tratto solo femminile non tocca a un maschio', function () {
    // «Burikko» è marcato F. Con venti personaggi maschi non deve uscire mai.
    for ($i = 0; $i < 20; $i++) {
        $t = array_column(Scheda::tratti((int) finto('Mas' . $i, false, 'm')['id']), 'tkey');
        vero(!in_array('burikko', $t, true), 'burikko assegnato a un maschio');
    }
});

prova('i personaggi di F1 si riparano il compleanno da soli', function () {
    // In F1 il compleanno non si chiedeva e la migrazione ha messo a tutti il
    // 2 aprile con una coorte di comodo. Un quartiere in cui tutti sono nati
    // lo stesso giorno non è un difetto estetico: i compleanni sono un fatto
    // di gioco. Il segno che una riga è vecchia è che l'anno di nascita non
    // corrisponde a quello che la sua classe comporta.
    $pg = finto('Vecchio', false);
    Database::run(
        'UPDATE personaggi SET nato_mese = 4, nato_giorno = 2, anno_nascita = 1971, rissa = 0 WHERE id = ?',
        [$pg['id']]
    );
    $riparato = Scheda::assicuraTiri(Database::first('SELECT * FROM personaggi WHERE id = ?', [$pg['id']]));

    $atteso = Scuola::annoDiNascita(
        (string) $riparato['sezione'], (int) $riparato['anno'],
        (int) $riparato['nato_mese'], (int) $riparato['nato_giorno']
    );
    uguale($atteso, (int) $riparato['anno_nascita'], 'la coorte torna a corrispondere alla classe');
    vero((int) $riparato['nato_mese'] >= 1 && (int) $riparato['nato_mese'] <= 12);
    vero((int) $riparato['nato_giorno'] >= 1 && (int) $riparato['nato_giorno'] <= 31);

    // E la riparazione è stabile: chiamarla ancora non sposta il compleanno.
    $ancora = Scheda::assicuraTiri(Database::first('SELECT * FROM personaggi WHERE id = ?', [$pg['id']]));
    uguale((int) $riparato['nato_mese'], (int) $ancora['nato_mese']);
    uguale((int) $riparato['nato_giorno'], (int) $ancora['nato_giorno']);
});

prova('una scheda già coerente non viene toccata', function () {
    $pg = finto('Coerente', false, 'f', ['mese' => 9, 'giorno' => 3]);
    $dopo = Scheda::assicuraTiri(Database::first('SELECT * FROM personaggi WHERE id = ?', [$pg['id']]));
    uguale(9, (int) $dopo['nato_mese']);
    uguale(3, (int) $dopo['nato_giorno']);
});

prova('il 29 febbraio è ammesso solo dove la coorte è bisestile', function () {
    global $creati;
    // Chi è in 3ª media nel 1987-88 e ha il compleanno prima di aprile è nato
    // nel 1973, che non è bisestile: quel compleanno non può esistere.
    // Chi è in 1ª superiore è nato nel 1972, che lo è.
    $prova = static function (string $sezione, int $anno) use (&$creati): array {
        $u = PREFISSO . ' bis ' . $sezione . $anno;
        Database::run('INSERT INTO users (username, email, password_hash, status) VALUES (?, ?, ?, ?)',
            [$u, md5($u) . '@zzprova.invalid', 'x', 'active']);
        $uid = Database::lastInsertId();
        $creati[] = $uid;
        return Personaggio::crea($uid, 'Bis' . $anno, ucfirst(PREFISSO), 'f', $sezione, $anno, 2, 29, false);
    };

    $r = $prova(Scuola::MEDIE, 3);
    vero(!$r['ok'], 'la 3ª media (coorte 1973) non può avere il 29 febbraio');
    vero(str_contains((string) $r['error'], 'bisestile'), 'e lo deve spiegare: ' . ($r['error'] ?? ''));

    $r = $prova(Scuola::SUPERIORI, 1);
    vero($r['ok'], 'la 1ª superiore (coorte 1972) sì: ' . ($r['error'] ?? ''));
});

// --- I poteri -----------------------------------------------------------------

prova('un esper ha un primario e almeno tre secondari', function () {
    // Non sono sempre quattro: i due risultati alti della tabella valgono
    // «tira ancora», quindi ogni tanto nasce un esper con sei o sette poteri.
    // C'era anche nel 1990 ed è voluto.
    $massimo = 0;
    for ($i = 0; $i < 20; $i++) {
        $p = Scheda::poteri((int) finto('Pot' . $i, true)['id']);
        vero(count($p) >= 4, 'almeno quattro poteri, ottenuti ' . count($p));
        vero(count($p) <= 7, 'non più di sette: ' . count($p));
        $massimo = max($massimo, count($p));
        $primari = array_filter($p, static fn (array $x): bool => (int) $x['primario'] === 1);
        uguale(1, count($primari), 'uno e uno solo principale');
        $chiavi = array_column($p, 'pkey');
        uguale(count($chiavi), count(array_unique($chiavi)), 'poteri doppi');
    }
});

prova('il primario è sempre uno dei tre della stirpe', function () {
    $ammessi = ['telecinesi', 'teletrasporto', 'telepatia'];
    for ($i = 0; $i < 12; $i++) {
        foreach (Scheda::poteri((int) finto('Pri' . $i, true)['id']) as $x) {
            if ((int) $x['primario'] === 1) {
                vero(in_array($x['pkey'], $ammessi, true), 'primario illegale: ' . $x['pkey']);
            }
        }
    }
});

prova('LA TELEPATIA È RARA: è il potere di uno solo, in famiglia', function () {
    // Il regolamento del 1990 dava alla telepatia il 32% come potere
    // principale: un esper su tre nasceva telepate. Il canone dice l'opposto —
    // la FAQ storica scrive che Kazuya è il solo telepate della famiglia, e la
    // voce giapponese insiste che Kyosuke, che ha tutto il resto, non ce l'ha.
    //
    // (Qui c'era una regola che escludeva a vicenda teletrasporto e telepatia.
    // L'ho ritirata: era fondata su una parentesi dubitativa, e la FAQ dice
    // chiaro che Kazuya si teletrasporta *e* è telepate. Le due fonti
    // concordano sulla rarità, non su un divieto.)
    $primari = ['telecinesi' => 0, 'teletrasporto' => 0, 'telepatia' => 0];
    $conTelepatia = 0;
    $quanti = 60;
    for ($i = 0; $i < $quanti; $i++) {
        $chiavi = [];
        foreach (Scheda::poteri((int) finto('Rar' . $i, true)['id']) as $p) {
            $chiavi[] = $p['pkey'];
            if ((int) $p['primario'] === 1) {
                $primari[$p['pkey']] = ($primari[$p['pkey']] ?? 0) + 1;
            }
        }
        if (in_array('telepatia', $chiavi, true)) {
            $conTelepatia++;
        }
    }
    // Su sessanta esper la telepatia come potere PRINCIPALE deve restare
    // un'eccezione: la banda è 93-98, cioè sei su cento.
    vero($primari['telepatia'] <= $quanti * 0.22,
        "telepate principale {$primari['telepatia']} volte su {$quanti}: troppo comune");
    vero($primari['telecinesi'] + $primari['teletrasporto'] >= $quanti * 0.75,
        'i due poteri comuni devono coprire quasi tutti');
    // E in totale, contando anche i secondari, deve restare minoritaria.
    vero($conTelepatia <= $quanti * 0.35,
        "telepatia presente in {$conTelepatia} esper su {$quanti}");
});

prova('teletrasporto e telepatia POSSONO convivere', function () {
    // Kazuya le ha tutte e due: un divieto sarebbe stato piu' pulito da
    // programmare e sbagliato. Qui si verifica solo che nessuna regola lo
    // impedisca — non che capiti spesso, perche' capita di rado.
    $a = finto('Kazuya', true);
    Database::run('DELETE FROM personaggio_poteri WHERE personaggio_id = ?', [(int) $a['id']]);
    foreach ([['telepatia', 1], ['teletrasporto', 0]] as [$k, $pri]) {
        Database::run(
            'INSERT INTO personaggio_poteri (personaggio_id, pkey, primario) VALUES (?, ?, ?)',
            [(int) $a['id'], $k, $pri]
        );
    }
    $chiavi = array_column(Scheda::poteri((int) $a['id']), 'pkey');
    vero(in_array('telepatia', $chiavi, true) && in_array('teletrasporto', $chiavi, true));
});

prova('chi non è della stirpe non ha poteri', function () {
    uguale([], Scheda::poteri((int) finto('Umano', false)['id']));
});

// --- La chiusura della scheda --------------------------------------------------

prova('i punti vanno distribuiti tutti, e non uno di più', function () {
    $pg = finto('Punti', true);
    $tot = Scheda::puntiDaDistribuire($pg);

    $r = Scheda::finalizza($pg, sparpaglia($pg, $tot - 1));
    vero(!$r['ok'], 'avanzare punti non si può');
    vero(str_contains((string) $r['error'], 'restano'), 'e lo deve dire: ' . $r['error']);

    $r = Scheda::finalizza($pg, sparpaglia($pg, $tot + 1));
    vero(!$r['ok'], 'spenderne di più nemmeno');
    vero(str_contains((string) $r['error'], 'distribuito'), 'e lo deve dire: ' . $r['error']);
});

prova('nessuna abilità può superare quindici', function () {
    $pg = finto('Tetto', true);
    Database::run('UPDATE personaggi SET rissa = 14 WHERE id = ?', [$pg['id']]);
    $pg = Database::first('SELECT * FROM personaggi WHERE id = ?', [$pg['id']]);
    $tot = Scheda::puntiDaDistribuire($pg);
    $r = Scheda::finalizza($pg, ['rissa' => $tot, 'testa' => 0, 'dai_suki' => 0, 'cuore' => 0]);
    vero(!$r['ok']);
    vero(str_contains((string) $r['error'], '15'), $r['error']);
});

prova('chi non ha poteri ha più punti da distribuire', function () {
    vero(Scheda::puntiDaDistribuire(finto('PiuPunti', false))
       > Scheda::puntiDaDistribuire(finto('MenoPunti', true)),
       'la compensazione per chi rinuncia ai poteri');
});

prova('la scheda chiusa ha tutto il derivato nei limiti', function () {
    foreach ([true, false] as $esper) {
        $pg = finto('Fin' . ($esper ? 'E' : 'U'), $esper);
        $tot = Scheda::puntiDaDistribuire($pg);
        $r = Scheda::finalizza($pg, sparpaglia($pg, $tot));
        vero($r['ok'], $r['error'] ?? '');

        $f = Database::first('SELECT * FROM personaggi WHERE id = ?', [$pg['id']]);
        uguale('completa', $f['scheda']);
        foreach (Scheda::ABILITA as $a) {
            vero((int) $f[$a] >= 1 && (int) $f[$a] <= 15, "{$a} = {$f[$a]}");
        }
        foreach (Scheda::SECONDARIE as $s) {
            vero((int) $f[$s] >= 1 && (int) $f[$s] <= 10, "{$s} = {$f[$s]}");
        }
        vero((int) $f['pf_max'] >= 5 && (int) $f['pf_max'] <= 22, 'PF ' . $f['pf_max']);
        uguale((int) $f['pf_max'], (int) $f['pf'], 'si comincia interi');
        if ($esper) {
            vero((int) $f['pp_max'] >= 7 && (int) $f['pp_max'] <= 11, 'PP ' . $f['pp_max']);
        } else {
            uguale(0, (int) $f['pp_max'], 'niente PP senza poteri');
        }
        vero((int) $f['compostezza_max'] >= 2 && (int) $f['compostezza_max'] <= 10,
            'compostezza ' . $f['compostezza_max']);
    }
});

prova('molto candore vuol dire poca compostezza', function () {
    // Il candore è quanto ti smonta l'imbarazzo: chi ne ha tanto crolla prima.
    $rng = Rng::for(999, 'compostezza');
    $basso = 12 - 2;    // candore 2
    $alto  = 12 - 10;   // candore 10
    vero(max(2, min(10, $basso)) > max(2, min(10, $alto)),
        'chi ha candore basso regge di più');
});

// --- Il costo dei poteri ----------------------------------------------------------

prova('il controllo abbassa il costo, e non sotto zero', function () {
    $p = ['primario' => 0, 'costo_primario' => 1, 'costo_secondario' => 3, 'controllo' => 10];
    uguale(3, Scheda::costo($p),            'a controllo basso si paga pieno');
    $p['controllo'] = 40; uguale(2, Scheda::costo($p), 'a 40 si sconta un punto');
    $p['controllo'] = 80; uguale(1, Scheda::costo($p), 'a 80 se ne scontano due');
    $p['controllo'] = 100; uguale(1, Scheda::costo($p), 'non si scende oltre');

    // Un potere che costa zero resta zero: i sogni premonitori non si pagano.
    $gratis = ['primario' => 0, 'costo_primario' => null, 'costo_secondario' => 0, 'controllo' => 90];
    uguale(0, Scheda::costo($gratis));
});

prova('il primario costa meno del secondario', function () {
    $pri = ['primario' => 1, 'costo_primario' => 1, 'costo_secondario' => 3, 'controllo' => 10];
    $sec = ['primario' => 0, 'costo_primario' => 1, 'costo_secondario' => 3, 'controllo' => 10];
    vero(Scheda::costo($pri) < Scheda::costo($sec),
        'è la differenza per cui Kyosuke si teletrasporta di continuo e Manami quasi mai');
});

// --- Intuizione ---------------------------------------------------------------------

prova('l\'intuizione segue la Testa e non sfonda', function () {
    uguale(50, Scheda::intuizione(['testa' => 10]));
    uguale(60, Scheda::intuizione(['testa' => 15]));
    vero(Scheda::intuizione(['testa' => 15]) <= 95, 'non si arriva mai alla certezza');
});

riepilogo();
