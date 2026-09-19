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
        ],
    ],
];
