<?php

declare(strict_types=1);

/**
 * I gesti.
 *
 * Sono i verbi delle relazioni, e la cosa che li rende interessanti non è
 * quanto affetto danno: è che quasi tutti sono **ambigui per costruzione**.
 * Un gesto ha una lettura vera — quella che intendeva chi lo ha fatto — e una
 * lettura sbagliata, che è quella che si porta a casa chi passava di lì. In
 * *Kimagure Orange Road* non succede quasi mai che qualcuno menta: succede in
 * continuazione che qualcuno veda metà di una scena.
 *
 * `richiede` lega il gesto al mondo: l'ombrello si divide solo se piove, il
 * bentō si dà all'ora di pranzo. Senza questo vincolo i gesti sarebbero
 * pulsanti; con questo, il giocatore comincia a guardare il meteo.
 *
 * I segnaposto nei testi: `%A` chi agisce, `%B` chi riceve.
 */

$g = static fn (
    string $k, string $nome, int $affetto, int $cuore, ?string $richiede,
    bool $ambiguo, string $desc, string $vera, ?string $falsa, int $ordine
): array => [
    'gkey'          => $k,
    'nome'          => $nome,
    'affetto'       => $affetto,
    'costo_cuore'   => $cuore,
    'richiede'      => $richiede,
    'ambiguo'       => $ambiguo ? 1 : 0,
    'descrizione'   => $desc,
    'lettura_vera'  => $vera,
    'lettura_falsa' => $falsa,
    'ordine'        => $ordine,
];

return [
    'tabella'     => 'gesti',
    'chiave'      => 'gkey',
    'descrizione' => 'gesti fra personaggi',
    'righe' => [

$g('parlare', 'Scambiare due parole', 2, 0, null, false,
  'Il meteo, i compiti, quello che ha detto il professore. Non è niente, e serve a farsi '
  . 'l\'abitudine l\'uno dell\'altro.',
  '%A e %B si sono fermati a parlare.', null, 10),

$g('ascoltare', 'Stare a sentire', 5, 0, null, false,
  'Lasciare che l\'altro dica quello che ha da dire fino in fondo, senza infilarci dentro '
  . 'la propria opinione. È più raro e più difficile di quanto sembri.',
  '%A ha ascoltato %B per un pezzo, senza interrompere.', null, 20),

$g('accompagnare', 'Accompagnare a casa', 7, 0, null, true,
  'Fare la strada insieme fino al portone. Volendo, si allunga il giro.',
  '%A ha accompagnato %B fino a casa.',
  '%A e %B se ne sono andati insieme, e non avevano fretta di arrivare.', 30),

$g('ombrello', 'Dividere l\'ombrello', 10, 0, 'pioggia', true,
  'Un ombrello per due vuol dire camminare vicini e bagnarsi una spalla ciascuno. In questa '
  . 'storia è più eloquente di mezza dichiarazione.',
  '%A ha diviso l\'ombrello con %B.',
  '%A e %B erano sotto lo stesso ombrello, stretti, e ridevano.', 40),

$g('bento', 'Preparare un bentō', 12, 1, 'pranzo', true,
  'Alzarsi prima, cucinare per qualcun altro, portarlo a scuola in una borsa e trovare il '
  . 'coraggio di tirarlo fuori. Non è un pranzo: è una dichiarazione con il riso dentro.',
  '%A ha preparato un bentō per %B.',
  '%A cucina per %B tutte le mattine, a quanto pare.', 50),

$g('regalo', 'Fare un regalo', 8, 1, null, true,
  'Qualcosa di piccolo, scelto pensando a quella persona. Il problema dei regali è che '
  . 'dicono per quanto tempo ci hai pensato.',
  '%A ha dato qualcosa a %B.',
  '%A fa regali a %B. Chissà per quale occasione.', 60),

$g('invitare', 'Invitare da qualche parte', 6, 1, null, true,
  'Al cinema, al luna park, a studiare insieme. Il contenuto conta meno dell\'aver chiesto.',
  '%A ha invitato %B da qualche parte.',
  '%A e %B si vedono anche fuori da scuola.', 70),

$g('difendere', 'Difendere davanti agli altri', 11, 1, null, true,
  'Mettersi in mezzo quando qualcuno parla male di quella persona, e farlo davanti a tutti. '
  . 'La domanda che resta agli altri non è se avevi ragione: è perché ci tenevi tanto.',
  '%A ha difeso %B davanti a tutti.',
  '%A si è scaldato parecchio per %B. Troppo, per uno che non c\'entra niente.', 80),

$g('bottone', 'Chiedere il secondo bottone', 18, 3, 'diploma', true,
  'Il secondo bottone della divisa è quello sopra il cuore, e il giorno del diploma si dà '
  . 'alla persona che si ama. Chiederlo è una dichiarazione che non ha bisogno di parole — '
  . 'e proprio per questo si fa in mezzo a tutta la scuola.',
  '%B ha dato a %A il secondo bottone della divisa.',
  '%A ha avuto il bottone di %B. Lo hanno visto tutti.', 90),

$g('evitare', 'Evitare', -8, 0, null, true,
  'Cambiare strada, fare finta di non aver sentito, trovarsi sempre occupato. Non si litiga: '
  . 'ci si allontana, che fa più male e si nota di meno.',
  '%A ha evitato %B.',
  '%A e %B hanno litigato per qualcosa, si direbbe.', 100),

    ],
];
