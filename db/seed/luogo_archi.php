<?php

declare(strict_types=1);

/**
 * Come si passa da un luogo all'altro.
 *
 * I minuti sono minuti di **gioco**: con la compressione 1:4 un tragitto di
 * otto minuti costa due minuti reali di attesa. Sono tarati a passo di
 * studente con la cartella, non a passo di marcia.
 *
 * Gli archi sono orientati. Quasi tutti valgono nei due sensi e sono generati
 * in coppia qui sotto, ma la scalinata fa eccezione: scenderla costa meno che
 * salirla, ed e' giusto che il modello lo sappia dire.
 */

$biDirezionali = [
    // da, a, minuti
    ['stazione',    'passaggio',   3],
    ['passaggio',   'commerciale', 4],
    ['commerciale', 'dischi',      2],
    ['commerciale', 'sala_giochi', 2],
    ['dischi',      'sala_giochi', 3],
    ['commerciale', 'abcb',        6],
    ['commerciale', 'parco',       5],
    ['abcb',        'viale',       4],
    ['abcb',        'gradini',     5],
    ['abcb',        'parco',       6],
    ['viale',       'liceo',       7],
    ['viale',       'gradini',     3],
    ['casa_kasuga', 'gradini',     4],
    ['casa_kasuga', 'parco',       5],
    ['casa_kasuga', 'abcb',        6],
    ['casa_ayukawa','gradini',     6],
    ['casa_ayukawa','tempio',      5],
    ['casa_ayukawa','argine',      8],
    ['parco',       'argine',      7],
    ['parco',       'albero',      3],
    ['albero',      'argine',      6],
    ['argine',      'luna_park',   13],
    ['liceo',       'tempio',      9],
    ['tempio',      'luna_park',   11],
];

/** Tratte in treno: partono tutte dalla stazione, e sono lunghe apposta. */
$treno = [
    ['stazione', 'spiaggia', 115],
    ['stazione', 'montagna', 190],
];

$righe = [];
$aggiungi = static function (string $da, string $a, int $minuti, string $mezzo) use (&$righe): void {
    $righe[] = [
        'akey'   => $da . '>' . $a,
        'da'     => $da,
        'a'      => $a,
        'minuti' => $minuti,
        'mezzo'  => $mezzo,
    ];
};

foreach ($biDirezionali as [$da, $a, $m]) {
    $aggiungi($da, $a, $m, 'piedi');
    $aggiungi($a, $da, $m, 'piedi');
}
foreach ($treno as [$da, $a, $m]) {
    $aggiungi($da, $a, $m, 'treno');
    $aggiungi($a, $da, $m, 'treno');
}

// La scalinata: cento gradini in salita sono un'altra cosa che cento in discesa.
$aggiungi('gradini', 'liceo', 6, 'piedi');   // su
$aggiungi('liceo', 'gradini', 4, 'piedi');   // giu'

return [
    'tabella'     => 'luogo_archi',
    'chiave'      => 'akey',
    'descrizione' => 'collegamenti fra i luoghi',
    'righe'       => $righe,
];
