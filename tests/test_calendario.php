<?php

declare(strict_types=1);

/**
 * Il calendario scolastico giapponese 1987-88.
 *
 *   php tests/test_calendario.php
 */

require __DIR__ . '/_prova.php';

use App\Core\Config;
use App\Sim\Calendario;
use App\Sim\Orologio;

Config::load(dirname(__DIR__));

$TZ = new DateTimeZone('Asia/Tokyo');
$q  = static fn (string $s): array => Calendario::stato(
    (new DateTimeImmutable($s, $TZ))->getTimestamp()
);

prova('il 6 aprile si va a scuola', function () use ($q) {
    $s = $q('1987-04-06 09:00:00');
    vero($s['scuola']);
    uguale(1, $s['trimestre']);
    uguale('primavera', $s['stagione']);
    uguale('Cerimonia d\'ingresso', $s['evento']['titolo'] ?? null);
});

prova('la domenica non si va a scuola', function () use ($q) {
    // 12 aprile 1987 era una domenica.
    $s = $q('1987-04-12 09:00:00');
    uguale('domenica', $s['giorno']);
    vero(!$s['scuola']);
});

prova('IL SABATO SI VA A SCUOLA, e si esce a mezzogiorno', function () use ($q) {
    // 11 aprile 1987, sabato. Nel 1987 la settimana scolastica era di sei
    // giorni: toglierlo sarebbe stato comodo e sbagliato.
    $m = $q('1987-04-11 10:00:00');
    uguale('sabato', $m['giorno']);
    vero($m['scuola'], 'il sabato mattina c\'è lezione');
    uguale('lezione', $m['fase']);

    $p = $q('1987-04-11 14:00:00');
    uguale('libero_pomeriggio', $p['fase'], 'il sabato pomeriggio è libero');
});

prova('il 29 aprile è il compleanno dell\'Imperatore', function () use ($q) {
    // Hirohito è vivo nel 1987: quella festa esiste, e sparirà nel 1989.
    $s = $q('1987-04-29 10:00:00');
    uguale('Compleanno dell\'Imperatore', $s['festa']);
    vero(!$s['scuola']);
});

prova('il 23 dicembre 1987 NON è festa', function () use ($q) {
    // Lo diventerà solo dal 1989, con l'imperatore successivo.
    uguale(null, $q('1987-12-23 10:00:00')['festa']);
});

prova('la Golden Week è tutta festiva', function () use ($q) {
    foreach (['05-03', '05-04', '05-05'] as $g) {
        $s = $q("1987-{$g} 10:00:00");
        vero($s['festa'] !== null, $g . ' deve essere festa');
        vero(!$s['scuola'], $g . ' niente scuola');
    }
});

prova('le vacanze estive vanno dal 21 luglio al 31 agosto', function () use ($q) {
    vero($q('1987-07-20 10:00:00')['vacanza'] === null, 'il 20 luglio si va ancora');
    uguale('vacanze estive', $q('1987-07-21 10:00:00')['vacanza']);
    uguale('vacanze estive', $q('1987-08-31 10:00:00')['vacanza']);
    uguale(null, $q('1987-09-01 10:00:00')['vacanza'], 'il 1° settembre si ricomincia');
});

prova('le vacanze invernali scavallano il capodanno', function () use ($q) {
    // È il caso che rompe un confronto ingenuo fra date «MM-GG».
    uguale('vacanze invernali', $q('1987-12-28 10:00:00')['vacanza']);
    uguale('vacanze invernali', $q('1988-01-03 10:00:00')['vacanza']);
    uguale(null, $q('1988-01-08 10:00:00')['vacanza'], 'l\'8 gennaio si rientra');
});

prova('i tre trimestri coprono l\'anno', function () use ($q) {
    uguale(1, $q('1987-06-10 10:00:00')['trimestre']);
    uguale(2, $q('1987-10-10 10:00:00')['trimestre']);
    uguale(3, $q('1988-02-10 10:00:00')['trimestre']);
    uguale(null, $q('1987-08-10 10:00:00')['trimestre'], 'in vacanza non c\'è trimestre');
});

prova('le stagioni sono quelle giapponesi', function () use ($q) {
    // Marzo è già primavera e giugno è già estate: sfasate di un mese
    // rispetto alle nostre, ed è così che le vive chi ci abita.
    uguale('primavera', $q('1988-03-10 10:00:00')['stagione']);
    uguale('estate',    $q('1987-06-10 10:00:00')['stagione']);
    uguale('autunno',   $q('1987-09-10 10:00:00')['stagione']);
    uguale('inverno',   $q('1987-12-10 10:00:00')['stagione']);
});

prova('la giornata scolastica si divide come deve', function () use ($q) {
    // 7 aprile 1987, martedì.
    $atteso = [
        '03:00' => 'notte',
        '06:00' => 'alba',
        '08:00' => 'tragitto',
        '08:35' => 'appello',
        '09:00' => 'lezione',
        '09:40' => 'intervallo',
        '12:45' => 'pranzo',
        '14:00' => 'lezione',
        '15:20' => 'pulizie',
        '16:30' => 'club',
        '19:00' => 'sera',
        '23:30' => 'notte',
    ];
    foreach ($atteso as $ora => $fase) {
        uguale($fase, $q("1987-04-07 {$ora}:00")['fase'], "alle {$ora}");
    }
});

prova('gli intervalli cadono ogni ora', function () use ($q) {
    // Le lezioni durano cinquanta minuti e poi ci sono dieci minuti di niente.
    uguale('lezione',    $q('1987-04-07 08:50:00')['fase']);
    uguale('intervallo', $q('1987-04-07 09:36:00')['fase']);
    uguale('lezione',    $q('1987-04-07 09:50:00')['fase']);
});

prova('il 29 febbraio 1988 esiste', function () use ($q) {
    // È il giorno in cui è andato in onda l'episodio 47: il ciclo di 366
    // giorni serve anche a questo.
    $s = $q('1988-02-29 10:00:00');
    uguale('1988-02-29', $s['iso']);
    vero($s['scuola'], 'era un lunedì');
});

prova('ogni giorno dell\'anno ha uno stato valido', function () use ($TZ) {
    // Passata completa: nessun giorno deve far saltare il calcolo, e in
    // nessun giorno la fase può restare vuota.
    $g = new DateTimeImmutable('1987-04-06 12:00:00', $TZ);
    $fine = new DateTimeImmutable('1988-04-06 00:00:00', $TZ);
    $n = 0;
    while ($g < $fine) {
        $s = Calendario::stato($g->getTimestamp());
        vero($s['fase'] !== '', $s['iso'] . ': fase vuota');
        vero($s['stagione'] !== '', $s['iso'] . ': stagione vuota');
        vero($s['vacanza'] !== null || $s['trimestre'] !== null,
            $s['iso'] . ': né vacanza né trimestre');
        $g = $g->modify('+1 day');
        $n++;
    }
    uguale(366, $n, 'l\'anno scolastico dura 366 giorni');
});

prova('i prossimi eventi arrivano in ordine', function () use ($TZ) {
    $x = (new DateTimeImmutable('1987-12-20 10:00:00', $TZ))->getTimestamp();
    $e = Calendario::prossimiEventi($x, 3);
    uguale(3, count($e));
    uguale('Vigilia di Natale', $e[0]['titolo']);
    vero($e[0]['giorni'] <= $e[1]['giorni'], 'in ordine di distanza');
});

prova('gli eventi scavallano la fine del ciclo', function () use ($TZ) {
    // A fine marzo l'evento successivo è la cerimonia d'ingresso, che sta
    // dall'altra parte del giro.
    $x = (new DateTimeImmutable('1988-03-28 10:00:00', $TZ))->getTimestamp();
    $e = Calendario::prossimiEventi($x, 1);
    uguale(1, count($e));
    uguale('Cerimonia d\'ingresso', $e[0]['titolo']);
});

prova('tutte le date degli eventi sono giorni veri', function () {
    foreach (array_keys(Calendario::tuttiGliEventi()) as $md) {
        [$m, $g] = array_map('intval', explode('-', $md));
        // L'anno giusto per la data: aprile-dicembre 1987, gennaio-marzo 1988.
        $anno = $m >= 4 ? 1987 : 1988;
        vero(checkdate($m, $g, $anno), "{$md} non è una data valida nel {$anno}");
    }
});

riepilogo();
