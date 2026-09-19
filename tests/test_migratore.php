<?php

declare(strict_types=1);

/**
 * Prove del divisore di statement del Migratore.
 *
 * Non tocca il database: verifica solo che un file .sql venga spezzato nei
 * punti giusti. La versione ingenua (esplodi su ';') si e' rotta sulla prima
 * migrazione reale di questo progetto, perche' una nota descrittiva conteneva
 * un punto e virgola. Queste prove servono a non ripetere l'errore.
 *
 *   php tests/test_migratore.php
 */

require __DIR__ . '/_prova.php';

use App\Cli\Migrator;

$dividi = static function (string $sql): array {
    $m = new Migrator(__DIR__);
    $r = new ReflectionMethod($m, 'splitStatements');
    $r->setAccessible(true);
    return $r->invoke($m, $sql);
};

prova('statement semplici', function () use ($dividi) {
    $r = $dividi("SELECT 1;\nSELECT 2;\n");
    uguale(2, count($r));
    uguale('SELECT 1', $r[0]);
});

prova('punto e virgola dentro una stringa', function () use ($dividi) {
    $r = $dividi("INSERT INTO t (a) VALUES ('uno; due');");
    uguale(1, count($r), 'il ; dentro gli apici non divide');
    vero(str_contains($r[0], 'uno; due'));
});

prova('apice raddoppiato', function () use ($dividi) {
    $r = $dividi("INSERT INTO t (a) VALUES ('l''anno; scolastico');\nSELECT 1;");
    uguale(2, count($r));
    vero(str_contains($r[0], "l''anno; scolastico"));
});

prova('virgolette doppie', function () use ($dividi) {
    $r = $dividi('INSERT INTO t (a) VALUES ("a; b");');
    uguale(1, count($r));
});

prova('barra rovesciata', function () use ($dividi) {
    $r = $dividi("INSERT INTO t (a) VALUES ('c\\'e; qui');\nSELECT 9;");
    uguale(2, count($r));
});

prova('commento di riga con punto e virgola', function () use ($dividi) {
    $r = $dividi("-- questo; e' un commento\nSELECT 1;");
    uguale(1, count($r));
    uguale('SELECT 1', $r[0]);
});

prova('commento con cancelletto', function () use ($dividi) {
    $r = $dividi("# nota; varia\nSELECT 1;");
    uguale(1, count($r));
});

prova('commento a blocco', function () use ($dividi) {
    $r = $dividi("/* a; b */ SELECT 1; /* c */ SELECT 2;");
    uguale(2, count($r));
    uguale('SELECT 1', trim($r[0]));
});

prova('ultimo statement senza punto e virgola', function () use ($dividi) {
    $r = $dividi("SELECT 1;\nSELECT 2");
    uguale(2, count($r));
});

prova('file vuoto o di soli commenti', function () use ($dividi) {
    uguale(0, count($dividi("-- niente\n\n")));
    uguale(0, count($dividi('')));
});

prova('le migrazioni vere si dividono', function () use ($dividi) {
    foreach (glob(dirname(__DIR__) . '/db/migrations/*.sql') ?: [] as $f) {
        $st = $dividi((string) file_get_contents($f));
        vero($st !== [], basename($f) . ': almeno uno statement');
        foreach ($st as $s) {
            // Uno statement che finisce con una virgola o una parentesi aperta
            // e' quasi certamente un troncone di una divisione sbagliata.
            vero(!str_ends_with(rtrim($s), ','), basename($f) . ': statement troncato su virgola');
        }
    }
});

riepilogo();
