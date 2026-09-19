<?php

declare(strict_types=1);

/**
 * I copioni degli episodi.
 *
 * Un copione è una **scaletta**, non un racconto: dice cosa succede e quali
 * strade ci sono, e il resto lo scrivono le scelte del cast. Ogni scena
 * propone a ciascuno le stesse opzioni, e il fatto che quattro persone
 * scelgano cose diverse nello stesso momento è il motivo per cui vale la pena
 * giocarli insieme.
 *
 * Ogni opzione porta:
 *   `prova`      su quale abilità si tira (o `nessuna`: certe cose riescono e basta)
 *   `difficolta` quanto serve superare, prima del bonus dell'abilità
 *   `ok` / `ko`  cosa si racconta nei due casi
 *   `effetti_*`  cosa cambia nel mondo
 *   `peso`       quanto quella scelta somiglia a un certo tipo di persona:
 *                serve all'**agente autonomo**, che deve scegliere per chi non
 *                c'è e deve farlo in carattere, non a caso.
 *
 * Il vocabolario degli effetti è volutamente corto — affetto, malinteso,
 * compostezza, punti ferita, calore, ricordo — perché un copione deve restare
 * una cosa che si legge, non un programma.
 */

$scene = static fn (array ...$s): string => json_encode($s, JSON_UNESCAPED_UNICODE);

return [
    'tabella'     => 'copioni',
    'chiave'      => 'ckey',
    'descrizione' => 'copioni degli episodi',
    'righe' => [

// ─────────────────────────────────────────────────────────────────────────
[
 'ckey' => 'funghi',
 'titolo' => 'Funghi al cento per cento! Nessuno sa più mentire',
 'occhiello' => 'Un pomeriggio all\'ABCB',
 'premessa' => 'Il Master ha ricevuto in regalo un cesto di funghi da un cliente che li ha '
     . 'raccolti lui stesso in montagna, e ha deciso di farci una zuppa. Il cliente ha anche '
     . 'detto una frase che il Master non ha sentito bene, qualcosa su «quelli che fanno dire '
     . 'la verità». Probabilmente era una battuta.',
 'luogo' => 'abcb',
 'condizione' => 'autunno',
 'min_cast' => 2, 'max_cast' => 5, 'finestra' => 1500,
 'scene' => $scene(
   [
    'testo' => 'La zuppa arriva fumante, in ciotole di coccio, e profuma di bosco. Il Master vi '
        . 'guarda con l\'aria di chi ha appena fatto una cosa gentile e si aspetta che qualcuno '
        . 'gliela riconosca. La prima cucchiaiata è buonissima.',
    'opzioni' => [
      ['k' => 'mangia', 'testo' => 'Mangiarne, e pure con gusto',
       'prova' => 'nessuna', 'difficolta' => 0,
       'ok' => 'Finisci la ciotola. È davvero buona, e per un quarto d\'ora non succede niente.',
       'ko' => '', 'effetti_ok' => ['affetto_cast' => 2], 'effetti_ko' => [],
       'peso' => ['cuore' => 1]],
      ['k' => 'assaggia', 'testo' => 'Assaggiare appena, per educazione',
       'prova' => 'testa', 'difficolta' => 35,
       'ok' => 'Ne prendi due cucchiai e sposti il resto con il cucchiaio, come si fa. Nessuno se ne accorge.',
       'ko' => 'Ci provi, ma il Master ti riempie di nuovo la ciotola con aria felice. Non puoi dirgli di no.',
       'effetti_ok' => [], 'effetti_ko' => ['affetto_cast' => 1], 'peso' => ['testa' => 2]],
      ['k' => 'avvisa', 'testo' => 'Dire ad alta voce che quei funghi non vi convincono',
       'prova' => 'dai_suki', 'difficolta' => 45,
       'ok' => 'Lo dici ridendo e metà tavolo posa il cucchiaio. Il Master si offende un pochino.',
       'ko' => 'Lo dici, e ti rispondono che sei il solito. Mangiano tutti, tu compreso.',
       'effetti_ok' => ['affetto_cast' => -1], 'effetti_ko' => ['compostezza' => -1],
       'peso' => ['dai_suki' => 2]],
    ],
   ],
   [
    'testo' => 'Non è che diventiate sinceri: è che smettete di riuscire a **non** esserlo. '
        . 'Komatsu, da un altro tavolo, sta spiegando a nessuno in particolare quanto gli faccia '
        . 'paura il futuro. Il Master asciuga un bicchiere e dice, senza che nessuno gli abbia '
        . 'chiesto niente, che una volta ha rovinato tutto con una persona per non aver parlato '
        . 'in tempo. Poi il silenzio arriva al vostro tavolo.',
    'opzioni' => [
      ['k' => 'dillo', 'testo' => 'Lasciar uscire quello che pensi davvero',
       'prova' => 'cuore', 'difficolta' => 30,
       'ok' => 'Esce, e non è nemmeno la cosa terribile che temevi. Per un momento vi guardate tutti come se vi vedeste.',
       'ko' => 'Esce la cosa sbagliata, quella che pensavi di aver messo via. Cala un gelo che si potrebbe tagliare.',
       'effetti_ok' => ['affetto_cast' => 8, 'malinteso_cast' => -12],
       'effetti_ko' => ['malinteso_cast' => 10, 'compostezza' => -1],
       'peso' => ['cuore' => 3]],
      ['k' => 'mordi', 'testo' => 'Mordersi la lingua con tutte le forze',
       'prova' => 'testa', 'difficolta' => 50,
       'ok' => 'Ti concentri sul fondo della ciotola e non dici niente. È faticosissimo e funziona.',
       'ko' => 'Ti scappa una mezza frase, la più imbarazzante di tutte, e poi ti fermi. Peggio che averla finita.',
       'effetti_ok' => ['compostezza' => -2],
       'effetti_ko' => ['malinteso_cast' => 8, 'compostezza' => -2],
       'peso' => ['testa' => 2, 'candore' => -1]],
      ['k' => 'sposta', 'testo' => 'Far parlare qualcun altro',
       'prova' => 'dai_suki', 'difficolta' => 40,
       'ok' => 'Fai una domanda innocente alla persona giusta e il tavolo si volta da quella parte. Salvo.',
       'ko' => 'La domanda innocente apre una faccenda che non andava aperta, e adesso guardano te.',
       'effetti_ok' => ['affetto_cast' => 2],
       'effetti_ko' => ['malinteso_cast' => 6],
       'peso' => ['dai_suki' => 3]],
      ['k' => 'esci', 'testo' => 'Alzarti e uscire a prendere aria',
       'prova' => 'nessuna', 'difficolta' => 0,
       'ok' => 'Esci. Fuori è quasi buio e si sta bene. Quando rientri, il peggio è passato — per gli altri.',
       'ko' => '', 'effetti_ok' => ['affetto_cast' => -3], 'effetti_ko' => [],
       'peso' => ['cuore' => -2]],
    ],
   ],
   [
    'testo' => 'L\'effetto passa come è venuto. Restano sul tavolo le ciotole vuote e un certo '
        . 'numero di cose che sono state dette e che non si possono rimandare indietro. Il Master '
        . 'ha già portato via il pentolone, e non commenta.',
    'opzioni' => [
      ['k' => 'chiarisci', 'testo' => 'Rimettere a posto quello che hai detto',
       'prova' => 'cuore', 'difficolta' => 35,
       'ok' => 'Ci metti la faccia. Chi ti ascolta capisce, e la cosa si chiude bene.',
       'ko' => 'Più spieghi e peggio è. A un certo punto smetti.',
       'effetti_ok' => ['malinteso_cast' => -15, 'affetto_cast' => 4],
       'effetti_ko' => ['malinteso_cast' => 6],
       'peso' => ['cuore' => 3]],
      ['k' => 'ridi', 'testo' => 'Buttarla sul ridere',
       'prova' => 'dai_suki', 'difficolta' => 35,
       'ok' => 'Ridi per primo e ridono tutti. Non è risolto niente, però si respira.',
       'ko' => 'Ridi da solo, e il suono resta lì in mezzo al tavolo.',
       'effetti_ok' => ['affetto_cast' => 3],
       'effetti_ko' => ['affetto_cast' => -2, 'compostezza' => -1],
       'peso' => ['dai_suki' => 3, 'kakko' => 1]],
      ['k' => 'niente', 'testo' => 'Non dire più niente, mai',
       'prova' => 'nessuna', 'difficolta' => 0,
       'ok' => 'Non ne parlate più. Nessuno ne parlerà più. Resterà lì.',
       'ko' => '', 'effetti_ok' => ['malinteso_cast' => 5], 'effetti_ko' => [],
       'peso' => ['cuore' => -3]],
    ],
   ],
 ),
],

// ─────────────────────────────────────────────────────────────────────────
[
 'ckey' => 'temporale',
 'titolo' => 'Acquazzone! Sotto la stessa tettoia',
 'occhiello' => 'Dieci minuti che durano un\'ora',
 'premessa' => 'Il cielo si è chiuso in cinque minuti e adesso viene giù un muro d\'acqua. '
     . 'Siete finiti tutti sotto la stessa tettoia, quella troppo piccola davanti al negozio '
     . 'chiuso, e non si può fare altro che aspettare.',
 'luogo' => null,
 'condizione' => 'rovescio',
 'min_cast' => 2, 'max_cast' => 4, 'finestra' => 1200,
 'scene' => $scene(
   [
    'testo' => 'La tettoia copre un metro quadro scarso. Chi sta ai bordi si bagna una spalla. '
        . 'L\'acqua fa un rumore assurdo sul metallo e per parlare bisogna alzare la voce o '
        . 'avvicinarsi molto.',
    'opzioni' => [
      ['k' => 'stringi', 'testo' => 'Stringerti per fare posto agli altri',
       'prova' => 'nessuna', 'difficolta' => 0,
       'ok' => 'Ti sposti verso l\'esterno e prendi acqua. Nessuno dice grazie e tutti se ne accorgono.',
       'ko' => '', 'effetti_ok' => ['affetto_cast' => 5, 'pf' => -1], 'effetti_ko' => [],
       'peso' => ['cuore' => 2]],
      ['k' => 'parla', 'testo' => 'Riempire il silenzio',
       'prova' => 'dai_suki', 'difficolta' => 30,
       'ok' => 'Attacchi a parlare di niente e funziona: dieci minuti passano in fretta.',
       'ko' => 'Parli troppo e si vede che è per non stare zitto. Qualcuno guarda altrove.',
       'effetti_ok' => ['affetto_cast' => 4],
       'effetti_ko' => ['affetto_cast' => -1, 'compostezza' => -1],
       'peso' => ['dai_suki' => 3]],
      ['k' => 'zitto', 'testo' => 'Stare zitto e guardare la pioggia',
       'prova' => 'nessuna', 'difficolta' => 0,
       'ok' => 'Guardi l\'acqua che rimbalza sull\'asfalto. È una cosa che si può fare anche in compagnia.',
       'ko' => '', 'effetti_ok' => ['compostezza' => 1], 'effetti_ko' => [],
       'peso' => ['candore' => -2, 'testa' => 1]],
    ],
   ],
   [
    'testo' => 'Passano venti minuti e non accenna a smettere. Fa freddo, siete tutti mezzi '
        . 'bagnati, e qualcuno ha starnutito. In fondo alla strada si vede l\'insegna del bar, '
        . 'accesa. Sono cinquanta metri di diluvio.',
    'opzioni' => [
      ['k' => 'corri', 'testo' => 'Correre fino al bar, tutti insieme',
       'prova' => 'rissa', 'difficolta' => 30,
       'ok' => 'Partite di corsa ridendo come scemi e arrivate fradici e felici.',
       'ko' => 'Scivoli a metà strada e arrivi peggio degli altri, con la divisa da buttare.',
       'effetti_ok' => ['affetto_cast' => 6],
       'effetti_ko' => ['pf' => -2, 'affetto_cast' => 3, 'compostezza' => -1],
       'peso' => ['rissa' => 3]],
      ['k' => 'ombrello', 'testo' => 'Tirare fuori l\'ombrello che avevi nella borsa',
       'prova' => 'nessuna', 'difficolta' => 0,
       'ok' => 'Lo apri. Ci sta una persona, forse due. Decidere chi è la parte difficile.',
       'ko' => '', 'effetti_ok' => ['affetto_cast' => 7, 'malinteso_cast' => 6], 'effetti_ko' => [],
       'peso' => ['testa' => 2]],
      ['k' => 'resta', 'testo' => 'Restare lì finché non passa',
       'prova' => 'nessuna', 'difficolta' => 0,
       'ok' => 'Restate. Si sta scomodi e non se ne va nessuno, il che a pensarci è una cosa.',
       'ko' => '', 'effetti_ok' => ['affetto_cast' => 3, 'pf' => -1], 'effetti_ko' => [],
       'peso' => ['cuore' => 1]],
    ],
   ],
 ),
],

// ─────────────────────────────────────────────────────────────────────────
[
 'ckey' => 'sospetti',
 'titolo' => 'Sospetti in aula! Qualcuno ha cominciato a guardare',
 'occhiello' => 'E adesso?',
 'premessa' => 'Non è successo niente di clamoroso. È che una persona, negli ultimi giorni, si '
     . 'gira sempre un attimo prima che tu faccia qualcosa, e ti fa domande che sembrano '
     . 'casuali e non lo sono. Oggi vi ha chiesto di fermarvi dopo le lezioni.',
 'luogo' => 'liceo',
 'condizione' => 'sospetto',
 'min_cast' => 2, 'max_cast' => 4, 'finestra' => 1500,
 'scene' => $scene(
   [
    'testo' => 'L\'aula si svuota. Restate in pochi, e quella persona non ha nessuna intenzione '
        . 'di andarsene. Comincia con una frase innocua su una cosa successa la settimana scorsa, '
        . 'e poi resta in silenzio ad aspettare la risposta.',
    'opzioni' => [
      ['k' => 'nega', 'testo' => 'Negare tutto, con faccia tosta',
       'prova' => 'kakko', 'difficolta' => 45,
       'ok' => 'Lo dici con una tale sicurezza che per un momento ci credi anche tu. Molla la presa.',
       'ko' => 'Neghi una frazione di secondo troppo in fretta, e si vede.',
       'effetti_ok' => ['calore' => -5],
       'effetti_ko' => ['malinteso_cast' => 10, 'calore' => 8],
       'peso' => ['kakko' => 3]],
      ['k' => 'spiega', 'testo' => 'Costruire una spiegazione che stia in piedi',
       'prova' => 'testa', 'difficolta' => 40,
       'ok' => 'Metti insieme una versione ragionevole di tutto. Non ci crede del tutto, ma non ha come contestarla.',
       'ko' => 'La versione ha un buco, e lo trova subito.',
       'effetti_ok' => [], 'effetti_ko' => ['calore' => 6, 'compostezza' => -1],
       'peso' => ['testa' => 3]],
      ['k' => 'chiedi', 'testo' => 'Chiedergli perché ci tiene tanto',
       'prova' => 'cuore', 'difficolta' => 35,
       'ok' => 'Rovesci la conversazione. Ti risponde, e quello che dice non te lo aspettavi.',
       'ko' => 'Ti risponde che lo sai benissimo perché. E cala il silenzio.',
       'effetti_ok' => ['affetto_cast' => 6, 'malinteso_cast' => -8],
       'effetti_ko' => ['compostezza' => -1],
       'peso' => ['cuore' => 3]],
    ],
   ],
   [
    'testo' => 'Comunque sia andata, adesso siete in tre o quattro in un\'aula vuota, con una '
        . 'cosa non detta sospesa in mezzo. Fuori dalla finestra il cortile è deserto. Uno di voi '
        . 'dovrà decidere come si esce da qui.',
    'opzioni' => [
      ['k' => 'verita', 'testo' => 'Dire la verità. Tutta.',
       'prova' => 'cuore', 'difficolta' => 45,
       'ok' => 'La dici. Non ti crede, poi ti crede, e poi non sa cosa fare della faccia. Da adesso sa, e lo sa perché gliel\'hai detto tu.',
       'ko' => 'Cominci e non riesci a finire. Resta a metà, che è il posto peggiore dove lasciare una cosa così.',
       'effetti_ok' => ['affetto_cast' => 12, 'calore' => -10],
       'effetti_ko' => ['malinteso_cast' => 14, 'compostezza' => -2],
       'peso' => ['cuore' => 4]],
      ['k' => 'ridicolo', 'testo' => 'Rendere la cosa talmente assurda da non poterci credere',
       'prova' => 'dai_suki', 'difficolta' => 45,
       'ok' => 'Ci ricami sopra fino a farla diventare una barzelletta. Alla fine ride anche lui.',
       'ko' => 'Esageri, e l\'esagerazione suona come una difesa. Che è quello che è.',
       'effetti_ok' => ['calore' => -6, 'affetto_cast' => 3],
       'effetti_ko' => ['calore' => 8],
       'peso' => ['dai_suki' => 3, 'kakko' => 2]],
      ['k' => 'vattene', 'testo' => 'Prendere la borsa e andartene',
       'prova' => 'nessuna', 'difficolta' => 0,
       'ok' => 'Esci senza salutare. Non hai risolto niente e non hai peggiorato niente. Per oggi.',
       'ko' => '', 'effetti_ok' => ['affetto_cast' => -4, 'calore' => 3], 'effetti_ko' => [],
       'peso' => ['cuore' => -3]],
    ],
   ],
 ),
],

    ],
];
