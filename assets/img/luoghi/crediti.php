<?php

declare(strict_types=1);

/**
 * Le immagini dei luoghi, e che cosa sono.
 *
 * **Il campo `tipo` è la ragione per cui questo file esiste.** Ci sono due
 * nature diverse qui dentro, e questo progetto ha passato giorni a separare
 * quello che l'opera attesta da quello che abbiamo ricostruito noi: mostrare
 * una fotografia vera e un'immagine inventata con lo stesso aspetto, senza
 * dirlo, disferebbe quel lavoro in silenzio.
 *
 *   `fotografia`  una fotografia vera di un posto vero, presa da Wikimedia
 *                 Commons con licenza libera. Porta autore, licenza e
 *                 origine, perché la licenza lo pretende e perché è giusto.
 *   `generata`    un'immagine generata, nello stile della fotografia su
 *                 pellicola degli anni Ottanta. Non ritrae un posto
 *                 esistente: i cartelli che si leggono — «BAR ABCB»,
 *                 高陵高等学校, 高岡遊園地 — sono nomi del gioco, non
 *                 insegne del mondo. Sono belle e sono giuste per il
 *                 racconto, ma non documentano niente.
 *
 * Le immagini generate possono stare anche nella copia pubblica: non c'è un
 * terzo che ne rivendichi i diritti. È la differenza fra queste e le tavole
 * del manga, che restano fuori.
 *
 * @return array<string, array{tipo:string, file:string, didascalia:string,
 *                             autore?:string, licenza?:string,
 *                             licenza_url?:string, origine?:string}>
 */

$foto = static fn (string $file, string $autore, string $licenza, string $url,
                   string $origine, string $did): array => [
    'tipo' => 'fotografia', 'file' => $file, 'autore' => $autore,
    'licenza' => $licenza, 'licenza_url' => $url, 'origine' => $origine,
    'didascalia' => $did,
];

$gen = static fn (string $file, string $did): array => [
    'tipo' => 'generata', 'file' => $file, 'didascalia' => $did,
];

return [
    // --- Fotografie vere di posti veri ---------------------------------------
    'stazione' => $foto('stazione.jpg', 'Kansai-good', 'CC BY-SA 4.0',
        'https://creativecommons.org/licenses/by-sa/4.0/',
        'https://commons.wikimedia.org/wiki/File:Etchu-Nakagawa-Station-building.jpg',
        'La stazione di Etchū-Nakagawa, sulla linea Himi, oggi. È la stazione che Matsumoto '
        . 'ha disegnato: il murale è stato aggiunto molti anni dopo.'),

    'gradini' => $foto('gradini.jpg', 'SilverHaze', 'CC BY-SA 3.0',
        'https://creativecommons.org/licenses/by-sa/3.0/',
        'https://commons.wikimedia.org/wiki/File:%E9%AB%98%E5%B2%A1%E5%8F%A4%E5%9F%8E%E5%85%AC%E5%9C%92_-_panoramio.jpg',
        'Takaoka vista dall\'alto: i tetti bassi, e in mezzo al verde il parco Kojō col suo '
        . 'fossato. La scalinata che è diventata i Cento Gradini è lì dentro — di lei, su '
        . 'Commons, non esiste una fotografia libera.'),

    'commerciale' => $foto('commerciale.jpg', 'Phillip Capper', 'CC BY 2.0',
        'https://creativecommons.org/licenses/by/2.0/',
        'https://commons.wikimedia.org/wiki/File:Suburban_street_Tokyo_1987.jpg',
        'Una via commerciale di periferia sotto la pioggia, nel 1987. È Tokyo, non Takaoka: '
        . 'non è la nostra strada, ma è com\'era una strada come la nostra nell\'anno in cui '
        . 'il gioco è ambientato.'),

    // --- Immagini generate ----------------------------------------------------
    'abcb' => $gen('abcb.jpg',
        'Il bar all\'angolo, con l\'insegna sopra la porta e le tendine alla vetrina. '
        . 'Dentro c\'è il bancone lungo, e dietro il bancone c\'è sempre qualcuno.'),

    'liceo' => $gen('liceo.jpg',
        'Tre piani di cemento e finestre, il cancello aperto sul cortile di terra battuta, '
        . 'il pino all\'ingresso e la pietra con il nome della scuola.'),

    'passaggio' => $gen('passaggio.jpg',
        'Le sbarre abbassate, il semaforo rosso, le biciclette in fila ad aspettare e il '
        . 'convoglio che passa. Si sta fermi un minuto, e in un minuto succedono le cose.'),

    'sala_giochi' => $gen('sala_giochi.jpg',
        'Cabinati in fila lungo la parete, schermi accesi nel buio, qualcuno in piedi dietro '
        . 'chi gioca. Cento yen alla volta.'),

    'viale' => $gen('viale.jpg',
        'La strada di casa: i ginkgo sui due lati, le saracinesche, qualcuno in bicicletta. '
        . 'È lunga, e ci si pensa parecchio mentre la si fa.'),

    'scaletta' => $gen('scaletta.jpg',
        'La discesa stretta fra le case di legno, con la città che si apre in fondo. '
        . 'È la strada che fa tutto il quartiere, e infatti c\'è sempre gente.'),

    'area_giochi' => $gen('area_giochi.jpg',
        'Lo scivolo, la giostra, le panchine e il cartello del parco. Di giorno ci sono i '
        . 'bambini; dopo cena non c\'è nessuno.'),

    'giardini' => $gen('giardini.jpg',
        'Gli spiazzi a terrazze sul fianco della discesa, con le passerelle che li collegano '
        . 'e il verde che si mangia tutto. Da quaggiù si sente la gente senza vederla.'),

    'parco' => $gen('parco.jpg',
        'Il laghetto, il ponte, i sentieri sotto gli alberi e le biciclette appoggiate. '
        . 'Grande abbastanza da poterci non incontrare nessuno.'),

    'albero' => $gen('albero.jpg',
        'L\'albero in cima al prato, solo, con la città distesa sotto. Nel tronco c\'è '
        . 'un\'incisione, e accanto una data.'),

    'argine' => $gen('argine.jpg',
        'Il sentiero sull\'argine, l\'acqua ferma, l\'erba alta e nessuno per centinaia di '
        . 'metri. Ci si viene apposta per quello.'),

    'tempio' => $gen('tempio.jpg',
        'Il cortile di ghiaia, le lanterne di pietra, il tetto curvo e le canfore molto '
        . 'vecchie. A capodanno la fila arriva fino in fondo alla salita.'),

    'luna_park' => $gen('luna_park.jpg',
        'La ruota panoramica, le montagne russe, le bancarelle accese e la gente che cammina '
        . 'in mezzo. Un posto rumoroso dove si può parlare senza essere sentiti.'),

    'spiaggia' => $gen('spiaggia.jpg',
        'Ombrelloni fino al promontorio, i teli, i gonfiabili, l\'acqua bassa piena di gente. '
        . 'Due ore di treno, e un\'altra estate.'),

    'montagna' => $gen('montagna.jpg',
        'La frazione sotto la neve, le case di legno, le montagne dietro e la strada battuta. '
        . 'Qui abitano i nonni.'),

    'casa_kasuga' => $gen('casa_kasuga.jpg',
        'La palazzina verde di cinque piani in cima alla collina, coi ballatoi e le biciclette '
        . 'sotto. Secondo piano, la porta in fondo.'),

    'casa_ayukawa' => $gen('casa_ayukawa.jpg',
        'Il soggiorno: il divano, i manifesti alle pareti, la chitarra appoggiata, la vetrata '
        . 'sul balcone. Grande, ordinato, e quasi sempre vuoto.'),
];
