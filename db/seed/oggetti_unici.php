<?php

declare(strict_types=1);

/**
 * Gli oggetti unici del quartiere. Uno solo per server, e nessuno di questi
 * migliora una statistica: sono modi di dire una cosa che non si riesce a
 * dire a parole.
 */

return [
    'tabella'     => 'oggetti_unici',
    'chiave'      => 'okey',
    'descrizione' => 'oggetti unici',
    // Chi lo tiene e dove sta sono del gioco: il seme li decide solo la prima
    // volta. Senza questa riga ogni deploy rimetteva il cappello sui gradini.
    'solo_alla_nascita' => ['detentore_id', 'luogo', 'gts'],
    'righe' => [
        [
            'okey' => 'cappello',
            'nome' => 'Un cappello di paglia rosso',
            'descrizione' => 'Di paglia, tinto di un rosso che il sole ha scolorito ai bordi, con '
                . 'un nastro attorno alla cupola. È vecchio di anni e tenuto bene, come si tiene '
                . 'una cosa che è stata regalata. Chi lo trova in cima ai gradini se lo ritrova in '
                . 'mano senza aver deciso di raccoglierlo. Non serve a niente, e chi lo dà a '
                . 'qualcuno sta dicendo qualcosa che non ha il coraggio di dire a voce.',
            'detentore_id' => null,
            'luogo' => 'gradini',
            'gts' => 0,
            'dimora' => 'gradini',
        ],

        // --- Gli oggetti del nonno, e quello di Akemi (9 ottobre 2026) --------
        // Il regolamento del 1990 li chiama «artefatti»: cose cariche di
        // energia esper. Due sono del nonno (capp. 107, 109-110; TV41), uno
        // della madre di Kyosuke (capp. 150-151). Nessuno migliora una
        // statistica; tutti cambiano una scena.
        [
            'okey' => 'orologio',
            'nome' => 'L\'orologio da taschino del nonno',
            'descrizione' => 'Argento annerito, il coperchio che si apre con uno scatto, e dentro '
                . 'le lancette ferme su un\'ora qualunque. Se lo premi, il tempo si ferma per qualche '
                . 'minuto per tutti tranne che per te: nessuno vede quello che fai. Poi va '
                . 'ricaricato, e ci vogliono giorni. Il nonno l\'ha perso al tempio, e non se n\'è '
                . 'ancora accorto.',
            'detentore_id' => null,
            'luogo' => 'tempio',
            'gts' => 0,
            'dimora' => 'tempio',
        ],
        [
            'okey' => 'pappagallo',
            'nome' => 'Il pappagallo del nonno',
            'descrizione' => 'Verde, con una gabbietta che si porta a mano, e il vizio di gridare ad '
                . 'alta voce quello che pensa chi gli sta vicino. Con lui accanto non c\'è gesto che '
                . 'si possa fraintendere: lo dice lui, a tutti, cosa volevi dire. Il nonno l\'ha '
                . 'lasciato in custodia al parco, e il parco non lo voleva.',
            'detentore_id' => null,
            'luogo' => 'parco',
            'gts' => 0,
            'dimora' => 'parco',
        ],
        [
            'okey' => 'ciondolo',
            'nome' => 'Il ciondolo di Akemi',
            'descrizione' => 'Un ciondolo semplice, d\'argento: il regalo del padre di Kyosuke alla '
                . 'madre, che il nonno conserva perché vada a una persona sola, quella che si '
                . 'sceglie (capp. 150-151). Come il '
                . 'cappello, non serve a niente; diversamente dal cappello, chi lo riceve sa '
                . 'benissimo cosa vuol dire.',
            'detentore_id' => null,
            'luogo' => 'albero',
            'gts' => 0,
            'dimora' => 'albero',
        ],
    ],
];
