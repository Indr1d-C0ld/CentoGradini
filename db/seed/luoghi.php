<?php

declare(strict_types=1);

/**
 * I luoghi del quartiere.
 *
 * Non e' una riproduzione topografica di Setagaya: e' una mappa di gioco.
 * L'ispirazione dichiarata dall'opera sono Umegaoka, Gotokuji e Shimokitazawa,
 * e da li' vengono la ferrovia che taglia il quartiere da ovest, il passaggio
 * a livello, la strada commerciale coperta e il viale alberato. Il resto sono
 * luoghi del manga messi dove servono al gioco.
 *
 * Coordinate su una tela di 1000x700. Gli orari sono in minuti dalla
 * mezzanotte (NULL = sempre accessibile).
 *
 * Le coordinate non sono libere: sulla carta ogni luogo porta il proprio nome
 * scritto sotto o sopra il pallino, e due nomi che si accavallano rendono la
 * carta illeggibile in un punto solo ma proprio dove serve. La prova
 * `tests/test_luoghi.php` misura le etichette e non lascia passare né le
 * sovrapposizioni né i nomi che escono dal bordo: se si sposta un luogo o gli
 * si cambia nome, va rilanciata.
 */

return [
    'tabella'     => 'luoghi',
    'chiave'      => 'lkey',
    'descrizione' => 'luoghi del quartiere',
    'righe' => [

        // --- L'asse della ferrovia, a ovest ---------------------------------
        [
            'lkey' => 'stazione', 'dove' => 'alla stazione', 'nome' => 'Nakagawa',
            'sottotitolo' => 'La stazione: da qui si parte e qui si torna',
            'descrizione' => 'Due binari, una pensilina di lamiera e il tabellone degli orari che '
                . 'nessuno guarda perché il treno passa sempre alla stessa ora. È il posto dove '
                . 'si saluta la gente che se ne va, e dove si aspetta quella che torna. Ci si '
                . 'incontra per caso più che altrove, perché prima o poi ci passano tutti.',
            'tipo' => 'strada', 'x' => 118, 'y' => 300,
            'apre' => 300, 'chiude' => 1450, 'ordine' => 10,
        ],
        [
            'lkey' => 'passaggio', 'dove' => 'al passaggio a livello', 'nome' => 'Il passaggio a livello',
            'sottotitolo' => 'Le sbarre si abbassano sempre al momento sbagliato',
            'descrizione' => 'Il campanello comincia, le sbarre scendono, e per un minuto e mezzo '
                . 'non si va da nessuna parte. Un minuto e mezzo è lunghissimo se dall\'altra parte '
                . 'c\'è qualcuno che stavi rincorrendo, o se accanto a te c\'è qualcuno a cui non '
                . 'sai cosa dire.',
            'tipo' => 'strada', 'x' => 272, 'y' => 300, 'ordine' => 20,
        ],

        // --- La strada commerciale -------------------------------------------
        [
            'lkey' => 'commerciale', 'dove' => 'in via commerciale', 'nome' => 'La via commerciale',
            'sottotitolo' => 'Tettoia di plastica ondulata, musichetta in filodiffusione',
            'descrizione' => 'Il fruttivendolo che grida i prezzi, la tintoria, il negozio di '
                . 'croquette con la coda all\'uscita da scuola. Tutti conoscono tutti, il che è '
                . 'comodo per farsi prestare cento yen e pessimo per qualunque altra cosa.',
            'tipo' => 'ritrovo', 'x' => 340, 'y' => 238,
            'apre' => 540, 'chiude' => 1200, 'ordine' => 30,
        ],
        [
            'lkey' => 'dischi', 'dove' => 'al negozio di dischi', 'nome' => 'Il negozio di dischi',
            'sottotitolo' => 'Vinili, cassette e un poster sbiadito in vetrina',
            'descrizione' => 'Stretto e lungo, con i contenitori di legno dove si sfogliano le '
                . 'copertine in piedi per mezz\'ora. Il proprietario mette quello che gli pare e '
                . 'non chiede mai a nessuno se gli piace. Si viene qui anche solo per sentire '
                . 'qualcosa che a casa non c\'è.',
            'tipo' => 'ritrovo', 'x' => 296, 'y' => 146,
            'apre' => 660, 'chiude' => 1230, 'ordine' => 40,
        ],
        [
            'lkey' => 'sala_giochi', 'dove' => 'in sala giochi', 'nome' => 'La sala giochi',
            'sottotitolo' => 'Buio, rumore e monetine da cento yen',
            'descrizione' => 'Cabinati in fila, fumo che non dovrebbe esserci e il tizio del terzo '
                . 'anno che tiene il primato su tutto da sei mesi. È il posto dove finiscono i '
                . 'pomeriggi che non avevano un programma, ed è anche il posto dove circolano per '
                . 'primi tutti i pettegolezzi della scuola.',
            'tipo' => 'ritrovo', 'x' => 404, 'y' => 196,
            'apre' => 600, 'chiude' => 1320, 'ordine' => 50,
        ],

        // --- Orange Road ------------------------------------------------------
        [
            'lkey' => 'abcb', 'dove' => 'all\'ABCB', 'nome' => 'ABCB',
            'sottotitolo' => 'Il bar sulla strada arancione',
            'descrizione' => 'Legno scuro, luce bassa, un bancone lungo e il padrone che asciuga '
                . 'bicchieri senza mai chiedere niente a nessuno. È il posto più neutrale del '
                . 'quartiere: ci si incontrano persone che altrove non si parlerebbero. Il Master '
                . 'sa molte più cose di quante ne dica, e questo lo sanno tutti tranne chi lo crede '
                . 'distratto.',
            'tipo' => 'ritrovo', 'x' => 472, 'y' => 300,
            'apre' => 660, 'chiude' => 1380, 'ordine' => 60,
        ],
        [
            'lkey' => 'viale', 'dove' => 'sul viale degli alberi', 'nome' => 'Il viale degli alberi',
            'sottotitolo' => 'La strada di casa, quella lunga',
            'descrizione' => 'Un doppio filare che copre tutta la carreggiata: verde tenero in '
                . 'aprile, ombra fitta d\'estate, arancione a novembre. È la strada che si fa due '
                . 'volte al giorno e che, se si ha qualcosa da dire a qualcuno, si allunga apposta.',
            'tipo' => 'strada', 'x' => 566, 'y' => 244, 'ordine' => 70,
        ],

        // --- Il centro di tutto ---------------------------------------------------
        [
            'lkey' => 'gradini', 'dove' => 'sui Cento Gradini', 'nome' => 'I Cento Gradini',
            'sottotitolo' => 'Dove tutto è cominciato',
            'descrizione' => 'Una scalinata di pietra fra due muri di cinta, che sale dal quartiere '
                . 'basso fino alla collina della scuola. In cima ci si ferma sempre un momento a '
                . 'riprendere fiato, e da lì si vede tutto: i tetti, la ferrovia, il cielo. Di tanto '
                . 'in tanto qui succede qualcosa che non dovrebbe: non spesso, e mai quando lo si '
                . 'cerca.',
            'tipo' => 'strada', 'x' => 604, 'y' => 358, 'ordine' => 80,
        ],
        [
            'lkey' => 'liceo', 'dove' => 'al Kōryō', 'nome' => 'Liceo Kōryō',
            'sottotitolo' => 'Aule, corridoi, tetto, e il retro della palestra',
            'descrizione' => 'Tre piani di cemento e finestre, un cortile di terra battuta e una '
                . 'palestra che d\'inverno è più fredda di fuori. Sul tetto si mangia il bentō e si '
                . 'guarda lontano; dietro la palestra si va per le cose che non si fanno davanti '
                . 'agli altri. Le scarpe da esterno restano negli armadietti all\'ingresso, e le '
                . 'lettere pure.',
            'tipo' => 'scuola', 'x' => 710, 'y' => 196,
            'apre' => 450, 'chiude' => 1140, 'ordine' => 90,
        ],

        // --- Le case ------------------------------------------------------------------
        [
            'lkey' => 'casa_kasuga', 'dove' => 'dai Kasuga', 'nome' => 'La palazzina dei Kasuga',
            'sottotitolo' => 'La «Green Castle», in cima alla collina',
            'descrizione' => 'Cinque piani tirati su nel 1982, in cima alla collina, e dalla porta '
                . 'principale si esce dritti verso la scalinata. Dentro, un appartamento piccolo per '
                . 'quattro persone, con la camera oscura del padre ricavata in uno sgabuzzino e un '
                . 'gatto che tenta la fuga ogni volta che si apre la porta. Ci si entra solo se '
                . 'invitati, e chi ci entra si accorge presto che in questa casa ci sono delle '
                . 'regole che nessuno spiega.',
            'tipo' => 'casa', 'x' => 520, 'y' => 434, 'privato' => 1, 'ordine' => 100,
        ],
        [
            'lkey' => 'casa_hiyama', 'dove' => 'da Hikaru', 'nome' => 'Casa Hiyama',
            'sottotitolo' => 'Rumorosa, e la porta non è mai chiusa',
            'descrizione' => 'Una casa bassa in fondo alla discesa, con la bicicletta appoggiata al '
                . 'muro e sempre qualcuno che entra o esce. Ci si festeggia tutto: la festa delle '
                . 'bambole a marzo, il capodanno, i compleanni, e qualunque altra scusa. Chi ci è '
                . 'stato una volta ci torna, perché nessuno gli ha mai chiesto di annunciarsi.',
            'tipo' => 'casa', 'x' => 766, 'y' => 296,
            'apre' => null, 'chiude' => null, 'ordine' => 115,
        ],

        [
            'lkey' => 'casa_ayukawa', 'dove' => 'da Ayukawa', 'nome' => 'Casa Ayukawa',
            'sottotitolo' => 'Grande, ordinata e quasi sempre vuota',
            'descrizione' => 'Una casa con il giardino e il cancello, in cui vive una persona sola. '
                . 'Di sera è illuminata una finestra soltanto. Non ci entra quasi nessuno, e non '
                . 'perché sia proibito: perché nessuno ha mai osato chiedere.',
            'tipo' => 'casa', 'x' => 664, 'y' => 470, 'privato' => 1, 'ordine' => 110,
        ],

        // --- Gli spazi aperti ----------------------------------------------------------------
        [
            'lkey' => 'parco', 'dove' => 'al parco', 'nome' => 'Il parco',
            'sottotitolo' => 'Altalene, una fontanella e tre ciliegi',
            'descrizione' => 'Di giorno ci sono i bambini e le madri; dopo il tramonto resta solo la '
                . 'luce arancione di un lampione e le altalene che si muovono da sole. È il posto '
                . 'dove si va quando si deve dire una cosa e non si vuole essere interrotti — e '
                . 'anche dove si va quando quella cosa è andata male.',
            'tipo' => 'natura', 'x' => 386, 'y' => 452, 'ordine' => 120,
        ],
        [
            'lkey' => 'albero', 'dove' => 'sotto l\'albero dei ricordi', 'nome' => 'L\'albero dei ricordi',
            'sottotitolo' => 'Con un\'incisione nel tronco, e una data',
            'descrizione' => 'In fondo al parco, dove non passa quasi nessuno, c\'è un albero '
                . 'grande con qualcosa inciso nella corteccia a mezza altezza: una frase e un '
                . 'nome, scritti da una mano che non si è preoccupata di essere elegante. '
                . 'L\'incisione è vecchia di anni e il legno l\'ha cicatrizzata ai bordi, ma si '
                . 'legge ancora. Chi la trova ci resta un momento più del previsto, anche senza '
                . 'sapere di chi parla.',
            'tipo' => 'natura', 'x' => 300, 'y' => 540, 'ordine' => 125,
        ],
        [
            'lkey' => 'argine', 'dove' => 'sull\'argine del fiume', 'nome' => 'L\'argine del fiume',
            'sottotitolo' => 'Erba, cemento e nessuno per centinaia di metri',
            'descrizione' => 'Un terrapieno lungo, con il fiume sotto e la città dall\'altra parte. '
                . 'Ci si viene a correre, a provare uno strumento, a tirare sassi nell\'acqua, o a '
                . 'stare per conto proprio senza che nessuno chieda perché. Al tramonto diventa '
                . 'tutto arancione, e non è un modo di dire.',
            'tipo' => 'natura', 'x' => 432, 'y' => 600, 'ordine' => 130,
        ],
        [
            'lkey' => 'tempio', 'dove' => 'al tempio', 'nome' => 'Il tempio',
            'sottotitolo' => 'Un cortile di ghiaia e due canfore molto vecchie',
            'descrizione' => 'Fuori mano, silenzioso trecentosessanta giorni l\'anno e affollatissimo '
                . 'negli altri cinque. Si sale una scalinata più corta di quella dei Cento Gradini e '
                . 'ci si trova in un posto dove la città non si sente. Ci vivono un gatto e un vecchio '
                . 'che non dice mai come si chiama.',
            'tipo' => 'natura', 'x' => 792, 'y' => 398, 'ordine' => 140,
        ],
        [
            'lkey' => 'luna_park', 'dove' => 'al luna park', 'nome' => 'Il luna park',
            'sottotitolo' => 'Montagne russe, ruota panoramica, zucchero filato',
            'descrizione' => 'Ai margini del quartiere, aperto solo il pomeriggio. Le montagne russe '
                . 'fanno un giro completo con la testa in giù, e c\'è chi giura che in quel momento '
                . 'preciso, ogni tanto, qualcosa si sposti. Nessuno lo dice ad alta voce perché '
                . 'suona idiota.',
            'tipo' => 'ritrovo', 'x' => 884, 'y' => 556,
            'apre' => 600, 'chiude' => 1230, 'ordine' => 150,
        ],

        // --- Fuori dal quartiere ---------------------------------------------------------------
        [
            'lkey' => 'spiaggia', 'dove' => 'in spiaggia', 'nome' => 'La spiaggia',
            'sottotitolo' => 'Due ore di treno e un\'altra estate',
            'descrizione' => 'Sabbia scura, capanni di legno, il mare che non è mai freddo abbastanza. '
                . 'Ci si va in comitiva, con i bagagli e i costumi comprati apposta, e si torna la sera '
                . 'con la pelle che brucia e qualcosa di diverso fra due persone che la mattina '
                . 'ancora non c\'era.',
            'tipo' => 'fuori', 'x' => 940, 'y' => 130,
            'stagione' => 'estate', 'fuori' => 1, 'ordine' => 160,
        ],
        [
            'lkey' => 'montagna', 'dove' => 'in montagna', 'nome' => 'La Montagna d\'Inverno',
            'sottotitolo' => 'La casa dei nonni, e la vetta accanto',
            'descrizione' => 'Una casa di legno in mezzo ai boschi, con la stufa accesa e i nonni che '
                . 'trattano i poteri come una cosa di tutti i giorni. Accanto c\'è una montagna più '
                . 'alta, con la neve che non si scioglie mai: il nonno la usa nel bicchiere al posto '
                . 'del ghiaccio, e ride se glielo si fa notare.',
            'tipo' => 'fuori', 'x' => 120, 'y' => 96, 'fuori' => 1, 'ordine' => 170,
        ],
    ],
];
