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
    ['abcb',        'parco',       6],
    ['viale',       'liceo',       7],
    ['casa_kasuga', 'gradini',     4],
    ['casa_kasuga', 'parco',       5],
    ['casa_kasuga', 'abcb',        6],
    ['casa_ayukawa','tempio',      5],
    ['casa_hiyama', 'liceo',       4],
    ['casa_hiyama', 'abcb',        5],
    ['casa_hiyama', 'casa_ayukawa',4],

    // --- La cima della collina ---------------------------------------------
    // L'area giochi sta subito a destra arrivando in cima, e i giardini
    // pensili sono sul versante, a mezza discesa: si raggiungono dalla
    // scalinata e da nient'altro.
    ['area_giochi', 'casa_kasuga', 3],
    ['area_giochi', 'giardini',    2],
    ['giardini',    'casa_kasuga', 4],

    // La scaletta di dietro: e' la strada vera del quartiere. Parte dalla
    // palazzina, gira dall'altra parte e scende dove scendono tutti.
    ['scaletta',    'casa_kasuga', 3],
    ['scaletta',    'commerciale', 6],
    ['scaletta',    'viale',       5],
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

// --- La scalinata ------------------------------------------------------------
//
// Cento gradini in salita sono un'altra cosa che cento in discesa, e il
// modello lo dice. Ma soprattutto: **la scalinata va nel verso giusto**.
//
// Fino al 19/09/2026 la nostra mappa metteva il liceo in cima e la casa dei
// Kasuga in fondo. E' l'opposto del canone. La ricostruzione francese del
// grande escalier (Reflexion 16, ricavata dall'episodio 32 e confermata dal 6)
// dice senza ambiguita': la residenza dei Kasuga — la «Green Castle», cinque
// piani, costruita nel 1982 — sta **in cima alla collina**, e «nei quartieri
// situati in fondo ai gradini si trovano le case di Madoka, di Hikaru,
// l'ABCB e la scuola dove vanno». Kyosuke scende per andare a lezione.
//
// Quindi `gradini` e' il pianerottolo **in alto** — quello dove ha raccolto
// il cappello e dove i due si aspettano — e tutto il resto sta sotto.
$suGiu = static function (string $basso, int $giu, int $su) use ($aggiungi): void {
    $aggiungi('gradini', $basso, $giu, 'piedi');   // si scende
    $aggiungi($basso, 'gradini', $su,  'piedi');   // si risale
};
$suGiu('liceo',        5, 8);
$suGiu('abcb',         4, 7);
$suGiu('viale',        3, 5);
$suGiu('casa_ayukawa', 5, 8);
$suGiu('casa_hiyama',  5, 8);

// L'area giochi e' in cima accanto alla scalinata: nessun dislivello. I
// giardini stanno a mezza discesa, quindi un po' di salita c'e'.
$aggiungi('gradini', 'area_giochi', 1, 'piedi');
$aggiungi('area_giochi', 'gradini', 1, 'piedi');
$suGiu('giardini', 2, 3);

return [
    'tabella'     => 'luogo_archi',
    'chiave'      => 'akey',
    'descrizione' => 'collegamenti fra i luoghi',
    'righe'       => $righe,
];
