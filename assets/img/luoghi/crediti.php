<?php

declare(strict_types=1);

/**
 * Le fotografie dei luoghi, e a chi appartengono.
 *
 * **Solo posti che sono davvero quel posto.** Non foto generiche di scenari
 * giapponesi: un passaggio a livello qualunque non è *il* passaggio a livello,
 * e spacciarlo per tale sarebbe la stessa disonestà che altrove abbiamo
 * evitato con cura. Dove non c'è una fotografia vera, la scheda del luogo usa
 * l'illustrazione generata — che non pretende di essere una fotografia.
 *
 * Le immagini vengono da **Wikimedia Commons** con licenze libere, e proprio
 * per questo possono stare anche nella copia pubblica: la licenza le
 * accompagna, l'attribuzione è in pagina e qui dentro. È la differenza fra
 * queste e le tavole del manga, che invece restano fuori.
 *
 * `didascalia` dice cosa si sta guardando **e in che rapporto sta col gioco**:
 * sono fotografie di Takaoka com'è adesso, non del 1987, e la pagina non deve
 * far finta del contrario.
 *
 * @return array<string, array{file:string, autore:string, licenza:string,
 *                             licenza_url:string, origine:string, didascalia:string}>
 */
return [
    'stazione' => [
        'file'        => 'stazione.jpg',
        'autore'      => 'Kansai-good',
        'licenza'     => 'CC BY-SA 4.0',
        'licenza_url' => 'https://creativecommons.org/licenses/by-sa/4.0/',
        'origine'     => 'https://commons.wikimedia.org/wiki/File:Etchu-Nakagawa-Station-building.jpg',
        'didascalia'  => 'La stazione di Etchū-Nakagawa, sulla linea Himi, oggi. È la stazione '
            . 'che Matsumoto ha disegnato: il murale è stato aggiunto molti anni dopo.',
    ],
    'gradini' => [
        'file'        => 'gradini.jpg',
        'autore'      => 'SilverHaze',
        'licenza'     => 'CC BY-SA 3.0',
        'licenza_url' => 'https://creativecommons.org/licenses/by-sa/3.0/',
        'origine'     => 'https://commons.wikimedia.org/wiki/File:%E9%AB%98%E5%B2%A1%E5%8F%A4%E5%9F%8E%E5%85%AC%E5%9C%92_-_panoramio.jpg',
        'didascalia'  => 'Takaoka vista dall\'alto: i tetti bassi, e in mezzo al verde il parco '
            . 'Kojō col suo fossato. La scalinata che è diventata i Cento Gradini è lì dentro — '
            . 'di lei, su Commons, non esiste una fotografia libera.',
    ],
];
