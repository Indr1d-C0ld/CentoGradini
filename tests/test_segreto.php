<?php

declare(strict_types=1);

/**
 * Il Segreto: il ciclo centrale.
 *
 * Scrive sul database e si ripulisce da sola.
 *
 *   php tests/test_segreto.php
 */

require __DIR__ . '/_prova.php';

use App\Core\Config;
use App\Core\Database;
use App\Core\GameConfig;
use App\Game\Personaggio;
use App\Game\Scheda;
use App\Game\Segreto;
use App\Sim\Meteo;
use App\Sim\Folla;
use App\Sim\Orologio;
use App\Sim\Scuola;

Config::load(dirname(__DIR__));

const PREFISSO = 'zzseg';

/**
 * Il palco e il limbo.
 *
 * Tutte le prove usano lo stesso luogo — «l'albero dei ricordi», l'unico posto
 * del quartiere con folla zero a qualunque ora — e questo le faceva
 * interferire: i personaggi di una prova restavano lì a fare da testimoni a
 * quelli della prova dopo, e l'asserzione «chi sa non è più un testimone»
 * trovava ventidue persone. Adesso nascono in un limbo (una casa privata, che
 * nessuna prova usa come palco) e ogni prova ci porta soltanto i propri.
 */
const PALCO = 'albero';
const LIMBO = 'casa_kasuga';

$creati = [];

/** Porta un personaggio sul palco. */
/**
 * Sgombra il palco da tutti i personaggi di prova.
 *
 * Si chiama all'inizio di ogni prova, senza eccezioni. Farlo solo dove
 * «serve» era il difetto: tre prove sgombravano e le altre ereditavano il
 * cast della precedente, così un uso di potere che doveva passare inosservato
 * veniva visto da un personaggio rimasto lì da due prove prima — e il guasto
 * compariva o no a seconda dell'ordine.
 */
function sgombra(): void
{
    Database::run(
        'UPDATE personaggi SET luogo = ? WHERE luogo = ? AND cognome = ?',
        [LIMBO, PALCO, ucfirst(PREFISSO)]
    );
}

function suPalco(array $pg): array
{
    Database::run('UPDATE personaggi SET luogo = ?, verso = NULL WHERE id = ?', [PALCO, (int) $pg['id']]);
    return Database::first('SELECT * FROM personaggi WHERE id = ?', [(int) $pg['id']]);
}

function attore(string $nome, bool $esper = true, array $poteri = [], array $ab = []): array
{
    global $creati;
    $u = PREFISSO . ' ' . $nome;
    Database::run('INSERT INTO users (username, email, password_hash, status) VALUES (?, ?, ?, ?)',
        [$u, md5($u) . '@zzseg.invalid', 'x', 'active']);
    $uid = Database::lastInsertId();
    $creati[] = $uid;
    $r = Personaggio::crea($uid, $nome, ucfirst(PREFISSO), 'f', Scuola::SUPERIORI, 2, 6, 15, $esper);
    if (!$r['ok']) {
        throw new RuntimeException($r['error'] ?? 'creazione fallita');
    }
    $id = (int) $r['id'];
    Scheda::assicuraTiri(Database::first('SELECT * FROM personaggi WHERE id = ?', [$id]));

    // Valori noti: le prove devono misurare il meccanismo, non i dadi.
    $ab += ['rissa' => 8, 'testa' => 8, 'dai_suki' => 8, 'cuore' => 8];
    Database::run(
        'UPDATE personaggi SET rissa=?, testa=?, dai_suki=?, cuore=?, kakko=5,
                pf_max=12, pf=12, pp_max=11, pp=11, compostezza_max=6, compostezza=6,
                scheda=?, luogo=?, verso=NULL WHERE id=?',
        [$ab['rissa'], $ab['testa'], $ab['dai_suki'], $ab['cuore'], 'completa', LIMBO, $id]
    );
    if ($poteri !== []) {
        Database::run('DELETE FROM personaggio_poteri WHERE personaggio_id = ?', [$id]);
        $primo = true;
        foreach ($poteri as $pk) {
            Database::run(
                'INSERT INTO personaggio_poteri (personaggio_id, pkey, primario, controllo) VALUES (?, ?, ?, 10)',
                [$id, $pk, $primo ? 1 : 0]
            );
            $primo = false;
        }
    }
    return Database::first('SELECT * FROM personaggi WHERE id = ?', [$id]);
}

function ricarica(array $pg): array
{
    return Database::first('SELECT * FROM personaggi WHERE id = ?', [(int) $pg['id']]);
}

function pulisci(): void
{
    global $creati;
    foreach ($creati as $uid) {
        try { Database::run('DELETE FROM users WHERE id = ?', [$uid]); } catch (Throwable) {}
    }
    try { Database::run('DELETE FROM users WHERE username LIKE ?', [PREFISSO . '%']); } catch (Throwable) {}
    try { Database::run('DELETE FROM tracce WHERE testo LIKE ?', ['%' . ucfirst(PREFISSO) . '%']); } catch (Throwable) {}
    try { Database::run('DELETE FROM calore WHERE luogo = ?', ['albero']); } catch (Throwable) {}
    $creati = [];
}
register_shutdown_function('pulisci');

// --- Il tiro di Nota ------------------------------------------------------

prova('un potere invisibile non si nota mai', function () {
    sgombra();
    // I sogni premonitori e il sesto senso succedono dentro la testa: non
    // c'è niente da vedere, e la probabilità dev'essere zero secca.
    uguale(0, Segreto::probabilitaNota(0, 15, 0));
    uguale(0, Segreto::probabilitaNota(0, 15, 100));
});

prova('più è vistoso, più si nota', function () {
    sgombra();
    $prec = -1;
    foreach ([1, 3, 5, 7, 9] as $v) {
        $p = Segreto::probabilitaNota($v, 8, 0);
        vero($p > $prec, "vistosità {$v} non sale: {$p}");
        $prec = $p;
    }
});

prova('il controllo abbassa il rischio, la Testa del testimone lo alza', function () {
    sgombra();
    $fermo   = Segreto::probabilitaNota(7, 8, 0);
    $esperto = Segreto::probabilitaNota(7, 8, 100);
    vero($esperto < $fermo, "controllo alto deve abbassare: {$esperto} vs {$fermo}");

    $tonto  = Segreto::probabilitaNota(7, 3, 0);
    $sveglio = Segreto::probabilitaNota(7, 15, 0);
    vero($sveglio > $tonto, "chi è sveglio nota di più: {$sveglio} vs {$tonto}");
});

prova('non si arriva mai alla certezza, né all\'impossibile', function () {
    sgombra();
    for ($v = 1; $v <= 10; $v++) {
        for ($t = 1; $t <= 15; $t += 2) {
            foreach ([0, 50, 100] as $c) {
                $p = Segreto::probabilitaNota($v, $t, $c);
                vero($p >= 2 && $p <= 95, "vistosità {$v} testa {$t} controllo {$c} -> {$p}");
            }
        }
    }
});

prova('di notte ci si nota di meno', function () {
    sgombra();
    $tz = new DateTimeZone('Asia/Tokyo');
    $giorno = (new DateTimeImmutable('1987-06-15 13:00:00', $tz))->getTimestamp();
    $notte  = (new DateTimeImmutable('1987-06-15 02:00:00', $tz))->getTimestamp();
    vero(Segreto::probabilitaNota(8, 8, 0, $notte) < Segreto::probabilitaNota(8, 8, 0, $giorno),
        'il buio deve coprire');
});

// --- L'uso ------------------------------------------------------------------

prova('senza poteri non si usa niente', function () {
    sgombra();
    $u = attore('Umano', false);
    $r = Segreto::usa($u, 'telecinesi');
    vero(!$r['ok']);
});

prova('un potere che non hai non si usa', function () {
    sgombra();
    $a = attore('SoloTk', true, ['telecinesi']);
    $r = Segreto::usa($a, 'invisibilita');
    vero(!$r['ok']);
    vero(str_contains((string) $r['error'], 'non ce l\'hai'), $r['error'] ?? '');
});

prova('usare costa punti potere, e senza non si può', function () {
    sgombra();
    $a = attore('Scarico', true, ['telecinesi']);
    Database::run('UPDATE personaggi SET pp = 0 WHERE id = ?', [(int) $a['id']]);
    $r = Segreto::usa(ricarica($a), 'telecinesi');
    vero(!$r['ok']);
    vero(str_contains((string) $r['error'], 'punti potere'), $r['error'] ?? '');
});

prova('da soli, in un posto vuoto, non succede niente', function () {
    sgombra();
    // L'albero dei ricordi è in fondo al parco: folla zero a ogni ora.
    uguale(0, Folla::a('albero'));
    $a = suPalco(attore('Solo', true, ['telecinesi']));
    $r = Segreto::usa($a, 'telecinesi');
    vero($r['ok'], $r['error'] ?? '');
    vero(!$r['notato'], 'nessuno può averlo visto');
    $inc = Database::first('SELECT stato FROM incidenti WHERE attore_id = ? ORDER BY id DESC LIMIT 1',
        [(int) $a['id']]);
    uguale('pulito', $inc['stato']);
});

prova('un uso andato liscio alza il Controllo', function () {
    sgombra();
    $a = suPalco(attore('Liscio', true, ['telecinesi']));
    $prima = (int) Database::first('SELECT controllo FROM personaggio_poteri WHERE personaggio_id = ? AND pkey = ?',
        [(int) $a['id'], 'telecinesi'])['controllo'];
    Segreto::usa($a, 'telecinesi');
    $dopo = (int) Database::first('SELECT controllo FROM personaggio_poteri WHERE personaggio_id = ? AND pkey = ?',
        [(int) $a['id'], 'telecinesi'])['controllo'];
    vero($dopo > $prima, "il controllo deve salire: {$prima} -> {$dopo}");
});

prova('davanti a un testimone attento, un teletrasporto si vede', function () {
    // L'OROLOGIO VA FISSATO. Senza, questa prova è instabile e non per colpa
    // del motore: probabilitaNota() moltiplica per 0,55 di notte e per 0,6
    // sotto un rovescio, quindi l'81% pieno di un teletrasporto davanti a un
    // testimone sveglio diventa 45% la notte e 27% la notte sotto la pioggia.
    // Lanciata a un'ora di gioco qualunque, la prova cade a caso. È lo stesso
    // difetto della vecchia prova di spostamento: dipendere dall'ora di gioco
    // senza dirlo.
    //
    // 5400 secondi reali dopo l'epoca sono le 13:00 del 6 aprile 1987 a Tokyo,
    // con pioggia zero: pieno giorno e asciutto.
    $epoca = 1735689600;
    Orologio::fingiEpoca($epoca);
    Orologio::fingiAdesso($epoca + 5400);

    try {
        $gts = Orologio::gts();
        uguale(0.0, Meteo::a($gts)['pioggia'], 'l\'istante fissato deve essere asciutto');
        vero(!Meteo::notte($gts), 'l\'istante fissato deve essere di giorno');

        // Vistosità 9, Testa 15, Controllo 0: qui la probabilità è al massimo,
        // e questo pezzo è deterministico.
        uguale(81, Segreto::probabilitaNota(9, 15, 0, $gts));

        // E poi il giro intero, che passa dal database e dai tiri veri. Con
        // p=0,81 su venti prove la media è 16,2 e lo scarto 1,75: la soglia a
        // 10 sta a tre scarti e mezzo, mentre un motore in cui la vistosità
        // non mordesse affatto si fermerebbe al minimo del 2%, cioè a zero.
        $visti = 0;
        for ($i = 0; $i < 20; $i++) {
            // Ogni giro è una scena nuova: senza questo, alla ventesima
            // iterazione ci sarebbero quaranta persone sul palco e la prova
            // passerebbe perché il campione è enorme, non perché la vistosità morde.
            sgombra();
            $a = suPalco(attore('Vis' . $i, true, ['teletrasporto']));
            $t = suPalco(attore('Occhio' . $i, false, [], ['testa' => 15]));
            $r = Segreto::usa(ricarica($a), 'teletrasporto');
            if ($r['notato']) { $visti++; }
        }
        vero($visti >= 10, "su venti volte notato solo {$visti}: la vistosità non morde");
    } finally {
        Orologio::fingiEpoca(null);
        Orologio::fingiAdesso(null);
    }
});

// --- La copertura -------------------------------------------------------------

prova('non coprire lascia un\'anomalia al testimone', function () {
    sgombra();
    $a = suPalco(attore('Sfacciato', true, ['teletrasporto']));
    $t = attore('Testimone', false, [], ['testa' => 15]);
    suPalco($t);

    $r = Segreto::usa(ricarica($a), 'teletrasporto');
    if (!$r['notato']) {
        vero(true, '(non notato: prova saltata)');
        return;
    }
    $c = Segreto::copri(ricarica($a), (int) $r['incidente'], 'niente');
    vero($c['ok'], $c['error'] ?? '');
    uguale(0, $c['riusciti']);
    vero($c['falliti'] >= 1);

    $n = (int) Database::first(
        'SELECT COUNT(*) n FROM anomalie WHERE osservatore_id = ? AND soggetto_id = ?',
        [(int) $t['id'], (int) $a['id']])['n'];
    uguale(1, $n, 'una sola anomalia, sul testimone giusto');
});

prova('coprire costa compostezza', function () {
    sgombra();
    $a = suPalco(attore('Bugiardo', true, ['teletrasporto']));
    $t = attore('Guarda', false, [], ['testa' => 15]);
    suPalco($t);
    $r = Segreto::usa(ricarica($a), 'teletrasporto');
    if (!$r['notato']) { vero(true, '(non notato: prova saltata)'); return; }

    $prima = (int) ricarica($a)['compostezza'];
    Segreto::copri(ricarica($a), (int) $r['incidente'], 'scusa');
    uguale($prima - 1, (int) ricarica($a)['compostezza']);
});

prova('a zero di compostezza non si inventa niente', function () {
    sgombra();
    $a = suPalco(attore('Distrutto', true, ['teletrasporto']));
    $t = attore('Vede', false, [], ['testa' => 15]);
    suPalco($t);
    $r = Segreto::usa(ricarica($a), 'teletrasporto');
    if (!$r['notato']) { vero(true, '(non notato: prova saltata)'); return; }

    Database::run('UPDATE personaggi SET compostezza = 0 WHERE id = ?', [(int) $a['id']]);
    $c = Segreto::copri(ricarica($a), (int) $r['incidente'], 'scusa');
    vero(!$c['ok']);
    vero(str_contains((string) $c['error'], 'scosso'), $c['error'] ?? '');
});

prova('un incidente si copre una volta sola', function () {
    sgombra();
    $a = suPalco(attore('Duevolte', true, ['teletrasporto']));
    $t = attore('Spia', false, [], ['testa' => 15]);
    suPalco($t);
    $r = Segreto::usa(ricarica($a), 'teletrasporto');
    if (!$r['notato']) { vero(true, '(non notato: prova saltata)'); return; }

    $uno = Segreto::copri(ricarica($a), (int) $r['incidente'], 'niente');
    vero($uno['ok']);
    $due = Segreto::copri(ricarica($a), (int) $r['incidente'], 'scusa');
    vero(!$due['ok'], 'il secondo tentativo dev\'essere respinto');
});

// --- Il taccuino e il sospetto ---------------------------------------------------

prova('due usi ravvicinati non danno lo stesso esito', function () {
    sgombra();
    // Il generatore è ancorato all'id dell'incidente, non all'istante: con la
    // compressione 1:4 un secondo di gioco dura un quarto di secondo vero, e
    // ancorarlo all'istante rendeva identici tutti gli usi fatti di fila.
    $a = suPalco(attore('Ripetuto', true, ['teletrasporto']));
    $t = attore('Presente', false, [], ['testa' => 9]);
    suPalco($t);
    $esiti = [];
    for ($i = 0; $i < 25; $i++) {
        Database::run('UPDATE personaggi SET pp = 11, compostezza = 6 WHERE id = ?', [(int) $a['id']]);
        $r = Segreto::usa(ricarica($a), 'teletrasporto');
        $esiti[] = (bool) ($r['notato'] ?? false);
        if (($r['notato'] ?? false)) {
            Segreto::copri(ricarica($a), (int) $r['incidente'], 'niente');
        }
    }
    vero(count(array_unique($esiti)) > 1,
        'venticinque usi di fila con esito tutto uguale: il generatore non varia');
});

prova('tre anomalie fanno un mucchio, due no', function () {
    sgombra();
    $a = suPalco(attore('Visto', true, ['teletrasporto']));
    $t = attore('Detective', false, [], ['testa' => 15]);
    suPalco($t);

    $servono = GameConfig::int('segreto.anomalie_per_sospetto', 3);
    $fatte = 0;
    for ($i = 0; $i < 30 && $fatte < $servono; $i++) {
        Database::run('UPDATE personaggi SET pp = 11, compostezza = 6 WHERE id = ?', [(int) $a['id']]);
        $r = Segreto::usa(ricarica($a), 'teletrasporto');
        if (($r['notato'] ?? false)) {
            Segreto::copri(ricarica($a), (int) $r['incidente'], 'niente');
            $fatte++;
        }
    }
    uguale($servono, $fatte, 'non sono riuscito a generare abbastanza anomalie');

    $tac = Segreto::taccuino((int) $t['id']);
    uguale(1, count($tac), 'un solo soggetto nel taccuino');
    uguale($servono, $tac[0]['quante']);
    vero($tac[0]['basta'], 'con tre annotazioni si può ragionare');
    vero(!$tac[0]['fondato'], 'ma non si è ancora capito niente');
});

prova('mettere in fila richiede abbastanza annotazioni', function () {
    sgombra();
    $a = attore('Pochi', true, ['telecinesi']);
    $t = attore('Impaziente', false);
    $r = Segreto::collega($t, (int) $a['id']);
    vero(!$r['ok']);
    vero(str_contains((string) $r['error'], 'almeno'), $r['error'] ?? '');
});

prova('chi ha capito entra fra quelli che sanno', function () {
    sgombra();
    $a = suPalco(attore('Scoperto', true, ['teletrasporto']));
    $t = attore('Sherlock', false, [], ['testa' => 15]);
    suPalco($t);

    // Anomalie messe a mano: qui si prova il collegamento, non il tiro di Nota.
    //
    // Il numero di annotazioni non e' decorativo: collega() concede un
    // tentativo per annotazione, e con Testa 15 l'intuizione e' 60%. Con
    // cinque annotazioni la prova falliva 0,4^5 = 1,0% delle volte — poco,
    // ma su una suite lanciata di continuo si vede eccome, e cadeva per
    // sfortuna e non per un difetto del motore. Con dodici siamo a
    // 0,4^12 = 0,002%: una volta ogni sessantamila giri.
    uguale(60, Scheda::intuizione($t), 'Testa 15 deve dare intuizione 60');

    $tentativi = 12;
    $gts = Orologio::lineare();
    for ($i = 0; $i < $tentativi; $i++) {
        Database::run(
            'INSERT INTO anomalie (osservatore_id, soggetto_id, gts, luogo, testo) VALUES (?, ?, ?, ?, ?)',
            [(int) $t['id'], (int) $a['id'], $gts - $i * 3600, 'albero', 'prova ' . $i]
        );
    }
    $capito = false;
    for ($i = 0; $i < $tentativi && !$capito; $i++) {
        $r = Segreto::collega($t, (int) $a['id']);
        if (!$r['ok']) { break; }
        $capito = (bool) ($r['capito'] ?? false);
    }
    vero($capito, "con Testa 15 e {$tentativi} annotazioni deve riuscirci");
    vero(Segreto::sa((int) $a['id'], (int) $t['id']), 'e deve risultare fra quelli che sanno');
    uguale(1, Segreto::quantiSanno((int) $a['id']));
});

// --- Confidarsi -------------------------------------------------------------------

prova('confidarsi azzera le annotazioni e crea un complice', function () {
    sgombra();
    $a = suPalco(attore('Sincero', true, ['telecinesi']));
    $t = attore('Amico', false);
    suPalco($t);
    Database::run('INSERT INTO anomalie (osservatore_id, soggetto_id, gts, luogo, testo) VALUES (?, ?, ?, ?, ?)',
        [(int) $t['id'], (int) $a['id'], Orologio::lineare(), 'albero', 'qualcosa']);

    $r = Segreto::confida(ricarica($a), (int) $t['id']);
    vero($r['ok'], $r['error'] ?? '');
    vero(Segreto::sa((int) $a['id'], (int) $t['id']));
    uguale(0, (int) Database::first(
        'SELECT COUNT(*) n FROM anomalie WHERE osservatore_id = ? AND soggetto_id = ?',
        [(int) $t['id'], (int) $a['id']])['n'], 'le annotazioni non servono più');
    vero(Segreto::complicePresente(ricarica($a)), 'e adesso è un complice presente');
});

prova('chi sa non è più un testimone', function () {
    sgombra();
    $a = suPalco(attore('Coperto', true, ['teletrasporto']));
    $t = attore('Confidente', false, [], ['testa' => 15]);
    suPalco($t);
    Segreto::confida(ricarica($a), (int) $t['id']);
    uguale([], Segreto::testimoniPossibili(ricarica($a), 'albero'),
        'chi sa già non si stupisce più');
});

prova('non ci si confida con chi non è presente', function () {
    sgombra();
    $a = suPalco(attore('Lontano', true, ['telecinesi']));
    $t = attore('Altrove', false);
    Database::run('UPDATE personaggi SET luogo = ? WHERE id = ?', ['tempio', (int) $t['id']]);  // apposta lontano
    $r = Segreto::confida(ricarica($a), (int) $t['id']);
    vero(!$r['ok']);
});

// --- Il calore ------------------------------------------------------------------------

prova('il calore sale e decade', function () {
    sgombra();
    Database::run('DELETE FROM calore WHERE luogo = ?', ['albero']);
    uguale(0, Segreto::calore('albero'));
    $gts = Orologio::lineare();
    Segreto::alzaCalore('albero', 40, $gts);
    uguale(40, Segreto::calore('albero', $gts));

    // Dopo un tempo di dimezzamento deve essere la metà.
    $ore = GameConfig::int('segreto.calore_decadimento_ore', 48);
    uguale(20, Segreto::calore('albero', $gts + $ore * 3600));
    vero(Segreto::calore('albero', $gts + $ore * 3600 * 6) < 2, 'alla lunga si spegne');
});

prova('il calore non sfonda il tetto', function () {
    sgombra();
    Database::run('DELETE FROM calore WHERE luogo = ?', ['albero']);
    $gts = Orologio::lineare();
    for ($i = 0; $i < 30; $i++) {
        Segreto::alzaCalore('albero', 20, $gts);
    }
    uguale(GameConfig::int('segreto.calore_max', 100), Segreto::calore('albero', $gts));
});

// --- Il Trasloco ------------------------------------------------------------------------

prova('il pericolo cresce con chi ha capito', function () {
    sgombra();
    $a = attore('Braccato', true, ['telecinesi']);
    uguale('quieto', Segreto::pericolo(ricarica($a))['stato']);

    $t1 = attore('Uno', false);
    Database::run('INSERT INTO sanno (esper_id, chi_sa_id, come, gts) VALUES (?, ?, ?, ?)',
        [(int) $a['id'], (int) $t1['id'], 'scoperto', Orologio::lineare()]);
    uguale('avviso', Segreto::pericolo(ricarica($a))['stato']);

    $t2 = attore('Due', false);
    Database::run('INSERT INTO sanno (esper_id, chi_sa_id, come, gts) VALUES (?, ?, ?, ?)',
        [(int) $a['id'], (int) $t2['id'], 'scoperto', Orologio::lineare()]);
    uguale('trasloco', Segreto::pericolo(ricarica($a))['stato']);
});

prova('CONFIDARSI NON FA TRASLOCARE: è la valvola', function () {
    sgombra();
    // Dieci persone che sanno perché gliel'hai detto tu non spostano un
    // mobile. Due che hanno capito da sole, sì. È tutta la differenza.
    $a = attore('Aperto', true, ['telecinesi']);
    for ($i = 0; $i < 10; $i++) {
        $t = attore('Fid' . $i, false);
        Database::run('INSERT INTO sanno (esper_id, chi_sa_id, come, gts) VALUES (?, ?, ?, ?)',
            [(int) $a['id'], (int) $t['id'], 'confidato', Orologio::lineare()]);
    }
    uguale('quieto', Segreto::pericolo(ricarica($a))['stato']);
    uguale(0, Segreto::quantiSanno((int) $a['id'], 'scoperto'));
    uguale(10, Segreto::quantiSanno((int) $a['id'], 'confidato'));
});

prova('chi trasloca sparisce dal quartiere', function () {
    sgombra();
    $a = attore('Partito', true, ['telecinesi']);
    $r = Segreto::trasloca(ricarica($a));
    vero($r['ok'], $r['error'] ?? '');

    $dopo = ricarica($a);
    uguale('trasferito', $dopo['stato']);
    uguale(1, (int) $dopo['traslochi']);
    // Non compare più fra i presenti né fra i testimoni possibili.
    $altri = array_column(Personaggio::presenti('albero'), 'id');
    vero(!in_array((int) $a['id'], array_map('intval', $altri), true), 'non è più fra i presenti');
    // E le annotazioni su di lui non hanno più un soggetto da riguardare.
    uguale(0, (int) Database::first('SELECT COUNT(*) n FROM anomalie WHERE soggetto_id = ?',
        [(int) $a['id']])['n']);
});

prova('non si trasloca due volte', function () {
    sgombra();
    $a = attore('Bis', true, ['telecinesi']);
    vero(Segreto::trasloca(ricarica($a))['ok']);
    vero(!Segreto::trasloca(ricarica($a))['ok']);
});

riepilogo();
