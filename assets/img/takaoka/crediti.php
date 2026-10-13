<?php

declare(strict_types=1);

/**
 * La Takaoka vera: le fotografie della sala dedicata a Izumi Matsumoto.
 *
 * Sono tutte da Wikimedia Commons, con licenza libera o di pubblico dominio,
 * verificate una per una sui metadati di Commons il 9 ottobre 2026. Le licenze
 * CC BY e CC BY-SA chiedono tre cose, e la pagina le fa tutte: il nome
 * dell'autore, la licenza con il suo collegamento, e il collegamento alla
 * fonte — più l'indicazione che l'immagine e' stata modificata (qui:
 * ridimensionata e convertita in WebP). Le CC BY-SA si ridistribuiscono con
 * la stessa licenza: per questo, a differenza delle foto del maestro e delle
 * tavole dell'opera, queste possono stare anche nel repository pubblico.
 *
 * Nessuna di queste fotografie mostra «la» scalinata dei cento gradini: le
 * fonti dicono che e' una gradinata del parco Kojo, ma nessuna dice quale, e
 * un appassionato che ci e' andato apposta ha sbagliato scalinata. Una foto
 * scelta a caso e spacciata per quella sarebbe esattamente l'errore che
 * questa sala non deve fare.
 *
 * @return list<array{file:string, didascalia:string, autore:string, licenza:string,
 *                    licenza_url:string, origine:string, anno:string}>
 */

$f = static fn (string $file, string $didascalia, string $autore, string $licenza,
                string $licenzaUrl, string $origine, string $anno): array => [
    'file' => $file, 'didascalia' => $didascalia, 'autore' => $autore, 'licenza' => $licenza,
    'licenza_url' => $licenzaUrl, 'origine' => $origine, 'anno' => $anno,
];

return [
    $f('kojo-fossato.webp',
       'Il fossato del castello, dal ponte rosso del parco Kojō. Il parco è nato nel 1875 sulle '
       . 'rovine del castello di Takaoka, e una delle sue scalinate di pietra — quale, nessuna fonte '
       . 'lo dice — è quella da cui viene la scalinata del manga.',
       'SYM50cc EMR', 'CC BY 3.0', 'https://creativecommons.org/licenses/by/3.0/',
       'https://commons.wikimedia.org/wiki/File:%E9%AB%98%E5%B2%A1%E5%8F%A4%E5%9F%8E%E5%85%AC%E5%9C%92%EF%BC%92_-_panoramio.jpg',
       '2009'),
    $f('kojo-1909.webp',
       'Lo stesso parco nel 1909, quando aveva trentaquattro anni: i pini vecchi, l\'acqua del '
       . 'fossato tutto intorno.',
       'Prefettura di Toyama', 'Pubblico dominio', '',
       'https://commons.wikimedia.org/wiki/File:%E6%98%8E%E6%B2%BB%E6%99%82%E4%BB%A3%E3%81%AE%E9%AB%98%E5%B2%A1%E5%85%AC%E5%9C%92.jpg',
       '1909'),
    $f('kojo-stagno-taisho.webp',
       'Barche sullo stagno del parco, negli anni Venti.',
       'autore sconosciuto', 'Pubblico dominio', '',
       'https://commons.wikimedia.org/wiki/File:%E9%AB%98%E5%B2%A1%E5%8F%A4%E5%9F%8E%E5%85%AC%E5%9C%92%E3%81%AE%E6%B1%A0.png',
       'anni Venti'),
    $f('kojo-ciliegi.webp',
       'Un ciliegio in fiore nel parco Kojō, a metà aprile: è la settimana in cui, nel gioco, '
       . 'tutto il quartiere stende i teli sotto gli alberi.',
       'kahusi', 'CC BY-SA 4.0', 'https://creativecommons.org/licenses/by-sa/4.0/',
       'https://commons.wikimedia.org/wiki/File:Prunus_sp_in_Takaoka_Kojo_Park_01.jpg',
       '2005'),
    $f('koryo.webp',
       'La scuola media Kōryō di Takaoka. Si dice che sia il modello del liceo dell\'opera, che '
       . 'porta lo stesso nome, e che fosse la scuola dell\'autore.',
       'XF10', 'CC BY-SA 4.0', 'https://creativecommons.org/licenses/by-sa/4.0/',
       'https://commons.wikimedia.org/wiki/File:Koryo-junior-high-school_takaoka-city_2018.jpg',
       '2018'),
    $f('nakagawa-stazione.webp',
       'La stazione di Etchū-Nakagawa, sulla linea Himi, con il suo treno: si dice che sia lei la '
       . 'stazione da cui si arriva nel quartiere.',
       'tab2_dawa', 'CC BY 2.0', 'https://creativecommons.org/licenses/by/2.0/',
       'https://commons.wikimedia.org/wiki/File:Etch%C5%AB-Nakagawa_Station(1)_-_flickr.jpg',
       '2013'),
    $f('nakagawa-binario.webp',
       'Il binario di Etchū-Nakagawa, sotto la pensilina.',
       'MaedaAkihiko', 'CC BY-SA 4.0', 'https://creativecommons.org/licenses/by-sa/4.0/',
       'https://commons.wikimedia.org/wiki/File:Etchu-Nakagawa-STA_Home.jpg',
       '2022'),
    $f('takaoka-1987.webp',
       'Aprile 1987, stazione di Takaoka: un treno per Himi fermo al binario. È il mese e l\'anno '
       . 'in cui comincia il gioco, e quello in cui la serie televisiva andava in onda per la prima '
       . 'volta.',
       'Suikotei', 'CC BY-SA 4.0', 'https://creativecommons.org/licenses/by-sa/4.0/',
       'https://commons.wikimedia.org/wiki/File:Kiha_35_196_at_Takaoka_Station.jpg',
       '1987'),
    $f('amaharashi-treno.webp',
       'La linea Himi corre lungo la costa di Amaharashi, davanti allo scoglio di Onna-iwa: è il '
       . 'mare più vicino a casa sua.',
       'Niiryoku2910', 'CC BY-SA 4.0', 'https://creativecommons.org/licenses/by-sa/4.0/',
       'https://commons.wikimedia.org/wiki/File:Kiha_40_at_Amaharashi_coast.jpg',
       '2026'),
    $f('amaharashi-tateyama.webp',
       'D\'inverno, dalla stessa costa, si vedono i monti Tateyama al di là della baia.',
       'くろふね', 'CC BY 4.0', 'https://creativecommons.org/licenses/by/4.0/',
       'https://commons.wikimedia.org/wiki/File:%E9%9B%A8%E6%99%B4%E6%B5%B7%E5%B2%B8%EF%BC%88%E6%B0%B7%E8%A6%8B%E7%B7%9A%E3%83%BB%E7%AB%8B%E5%B1%B1%E9%80%A3%E5%B3%B0%EF%BC%8920250215-IMG_2861.jpg',
       '2025'),
];
