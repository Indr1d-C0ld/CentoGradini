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
       'ko' => 'Ridi da sol{o}, e il suono resta lì in mezzo al tavolo.',
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

// ─────────────────────────────────────────────────────────────────────────
[
 'ckey' => 'valentino',
 'titolo' => 'Quattordici febbraio. Chi lo dà a chi',
 'occhiello' => 'Il giorno più lungo dell\'anno scolastico',
 'premessa' => 'In Giappone il cioccolato di San Valentino lo regalano le ragazze, e non è '
     . 'una faccenda privata: è pubblica. Quello comprato si chiama giri-choko, cioccolato '
     . 'd\'obbligo, e si dà a tutti. Quello fatto in casa si chiama honmei-choko, e si dà a '
     . 'una persona sola. La differenza la vedono tutti, e tutti la commentano.',
 'luogo' => 'liceo',
 'condizione' => 'evento:san_valentino',
 'min_cast' => 2, 'max_cast' => 6, 'finestra' => 1800,
 'scene' => $scene(
   [
    'testo' => 'L\'aula prima dell\'appello è un mercato. Ci sono sacchetti che passano di mano '
        . 'sotto i banchi, e altri che vengono consegnati in piedi, davanti a tutti, con la '
        . 'faccia rossa. Komatsu ne ha già ricevuti quattro e li conta ad alta voce. Hatta zero, '
        . 'e finge di non aver notato. Sul tuo banco, quando torni dal corridoio, ce n\'è uno '
        . 'che non c\'era prima. Non ha un biglietto.',
    'opzioni' => [
      ['k' => 'apri', 'testo' => 'Aprirlo subito, lì, davanti a tutti',
       'prova' => 'nessuna', 'difficolta' => 0,
       'ok' => 'È fatto in casa. Si vede dalla forma, che non è una forma: è quello che viene '
           . 'quando si versa il cioccolato in uno stampo di carta. Mezza classe ha smesso di '
           . 'parlare. Tu non hai idea di chi sia stato, e adesso non hai nemmeno la possibilità '
           . 'di far finta di niente.',
       'ko' => '', 'effetti_ok' => ['compostezza' => -2, 'affetto_cast' => 1], 'effetti_ko' => [],
       'peso' => ['dai_suki' => 3, 'kakko' => 1]],
      ['k' => 'tasca', 'testo' => 'Farlo sparire in borsa senza guardarlo',
       'prova' => 'kakko', 'difficolta' => 35,
       'ok' => 'Un movimento solo, e il sacchetto non c\'è più. Nessuno ha visto niente, e per '
           . 'tutto il giorno hai addosso una cosa che non sai di chi sia.',
       'ko' => 'Ti cade. Rotola fino al piede di qualcun altro, che lo raccoglie e te lo porge '
           . 'guardandoti come si guarda una persona che ha appena mentito male.',
       'effetti_ok' => [], 'effetti_ko' => ['compostezza' => -2, 'malinteso_cast' => 4],
       'peso' => ['kakko' => 3, 'testa' => 1]],
      ['k' => 'cerca', 'testo' => 'Chiedere in giro chi lo ha messo lì',
       'prova' => 'testa', 'difficolta' => 45,
       'ok' => 'Non lo scopri, ma capisci una cosa: due persone in questa stanza sanno la '
           . 'risposta, e nessuna delle due sei tu. È già qualcosa.',
       'ko' => 'Lo chiedi alla persona sbagliata, che diventa rossa, e adesso il malinteso è due: '
           . 'quello di prima e quello che hai appena fatto.',
       'effetti_ok' => [], 'effetti_ko' => ['malinteso_cast' => 8],
       'peso' => ['testa' => 3]],
    ],
   ],
   [
    'testo' => 'Fine delle lezioni. Nel corridoio delle scarpe c\'è la coda, e la coda è lenta '
        . 'perché tutti stanno guardando tutti. Chi ha ancora un sacchetto in mano a quest\'ora '
        . 'lo ha perché non è riuscito a darlo, e lo sanno anche i muri.',
    'opzioni' => [
      ['k' => 'dallo', 'testo' => 'Darlo adesso, a chi volevi darlo, e finirla',
       'prova' => 'cuore', 'difficolta' => 40,
       'ok' => 'Glielo metti in mano e dici una frase che non avevi preparato, e proprio per '
           . 'questo suona vera. Quello che succede dopo non lo decidi tu, ma la parte tua l\'hai fatta.',
       'ko' => 'Arrivi a mezzo metro e dici che fa freddo. Poi che domani c\'è compito. Poi ciao. '
           . 'Il sacchetto è ancora in borsa.',
       'effetti_ok' => ['affetto_cast' => 8, 'compostezza' => -2],
       'effetti_ko' => ['compostezza' => -3],
       'peso' => ['cuore' => 4]],
      ['k' => 'obbligo', 'testo' => 'Darlo a tutti, così non vuol dire niente',
       'prova' => 'dai_suki', 'difficolta' => 30,
       'ok' => 'Ne dai uno a testa, ridendo, e il sacchetto che contava sparisce dentro il mucchio. '
           . 'Nessuno capisce niente, che era esattamente lo scopo.',
       'ko' => 'Li distribuisci, ma ne resta uno, e si vede che era diverso dagli altri. '
           . 'Adesso lo sanno tutti tranne la persona giusta.',
       'effetti_ok' => ['affetto_cast' => 2],
       'effetti_ko' => ['malinteso_cast' => 10, 'affetto_cast' => 1],
       'peso' => ['dai_suki' => 4]],
      ['k' => 'tieni', 'testo' => 'Tenerlo. Un altro anno',
       'prova' => 'nessuna', 'difficolta' => 0,
       'ok' => 'Esci con il sacchetto in borsa. Non è successo niente, e non succederà niente, '
           . 'e lo sai già mentre cammini verso casa. C\'è sempre il prossimo febbraio.',
       'ko' => '', 'effetti_ok' => ['compostezza' => 1, 'affetto_cast' => -1], 'effetti_ko' => [],
       'peso' => ['cuore' => -2, 'testa' => 1]],
    ],
   ],
 ),
],

// ─────────────────────────────────────────────────────────────────────────
[
 'ckey' => 'fuochi',
 'titolo' => 'La sera dei fuochi',
 'occhiello' => 'Festival d\'estate al tempio',
 'premessa' => 'Lanterne di carta lungo tutta la salita, bancarelle che friggono, e alle nove '
     . 'i fuochi sopra il fiume. È la sera dell\'anno in cui il quartiere sta tutto nello '
     . 'stesso posto: ci sono i compagni di classe, i professori in borghese, la gente dei '
     . 'negozi, e chiunque tu stia evitando da tre settimane.',
 'luogo' => 'tempio',
 'condizione' => 'evento:festival_estate',
 'min_cast' => 2, 'max_cast' => 6, 'finestra' => 1800,
 'scene' => $scene(
   [
    'testo' => 'La salita è piena. Si cammina a passi di dieci centimetri, spalla contro spalla, '
        . 'e ogni tanto la folla si muove tutta insieme e ti porta dove non volevi andare. '
        . 'Qualcuno del gruppo è già rimasto indietro. Con lo yukata non si corre.',
    'opzioni' => [
      ['k' => 'mano', 'testo' => 'Prendere per mano chi hai vicino, per non perdervi',
       'prova' => 'cuore', 'difficolta' => 35,
       'ok' => 'Lo fai come se fosse una cosa pratica, perché lo è. Resta una cosa pratica per '
           . 'circa quattro secondi.',
       'ko' => 'Allunghi la mano e afferri una manica. Della persona sbagliata. Che si gira.',
       'effetti_ok' => ['affetto_cast' => 5],
       'effetti_ko' => ['malinteso_cast' => 7, 'compostezza' => -1],
       'peso' => ['cuore' => 3]],
      ['k' => 'grida', 'testo' => 'Metterti a gridare il punto di ritrovo sopra la folla',
       'prova' => 'dai_suki', 'difficolta' => 30,
       'ok' => 'Urli tre volte «al torii!» e si girano cinquanta persone, ma il gruppo ha capito. '
           . 'Sei rosso, funziona.',
       'ko' => 'Urli, e la tua voce sparisce dentro il tamburo che ha appena cominciato. '
           . 'Nessuno ti ha sentito, e adesso siete sparpagliati.',
       'effetti_ok' => ['affetto_cast' => 2, 'compostezza' => -1],
       'effetti_ko' => ['affetto_cast' => -1],
       'peso' => ['dai_suki' => 4]],
      ['k' => 'lato', 'testo' => 'Sgusciare fuori dalla calca per il sentiero laterale',
       'prova' => 'kakko', 'difficolta' => 40,
       'ok' => 'Conosci un passaggio dietro le bancarelle. Sbuchi sopra la salita con il fiato '
           . 'a posto mentre gli altri arrancano, e per un momento sei l\'unico che sa dov\'è tutti.',
       'ko' => 'Il sentiero è chiuso da un furgone delle bancarelle. Torni indietro contro '
           . 'corrente, e contro corrente con lo yukata è un\'impresa.',
       'effetti_ok' => ['affetto_cast' => 1],
       'effetti_ko' => ['pf' => -1, 'compostezza' => -1],
       'peso' => ['kakko' => 3, 'testa' => 2]],
    ],
   ],
   [
    'testo' => 'Sopra, il piazzale del tempio è più largo e si respira. Cominciano i fuochi. '
        . 'Fra uno scoppio e l\'altro c\'è un silenzio lungo in cui si sente solo la gente che '
        . 'fa oh, e in quel silenzio la persona accanto a te dice qualcosa che non capisci '
        . 'perché arriva il secondo colpo.',
    'opzioni' => [
      ['k' => 'richiedi', 'testo' => 'Chiederle di ripetere',
       'prova' => 'nessuna', 'difficolta' => 0,
       'ok' => 'Lei scuote la testa e dice «niente, niente». E guarda in alto. Ma non era niente, '
           . 'e lo sapete tutti e due.',
       'ko' => '', 'effetti_ok' => ['affetto_cast' => 2, 'malinteso_cast' => 3], 'effetti_ko' => [],
       'peso' => ['testa' => 2, 'cuore' => 1]],
      ['k' => 'rispondi', 'testo' => 'Rispondere come se avessi capito',
       'prova' => 'kakko', 'difficolta' => 50,
       'ok' => 'Dici una cosa abbastanza generica da andare bene per qualunque frase, e per '
           . 'fortuna va bene anche per quella. Lei sorride.',
       'ko' => 'Rispondi a una domanda che non era una domanda. Adesso lei pensa una cosa che tu '
           . 'non hai detto, e tu non sai nemmeno quale.',
       'effetti_ok' => ['affetto_cast' => 4],
       'effetti_ko' => ['malinteso_cast' => 12],
       'peso' => ['kakko' => 4]],
      ['k' => 'guarda', 'testo' => 'Stare zitto e guardare i fuochi',
       'prova' => 'nessuna', 'difficolta' => 0,
       'ok' => 'Ce ne sono ancora per dieci minuti. Nessuno dei due dice più niente, e non è '
           . 'imbarazzante: è una delle poche volte in cui non parlare è la cosa giusta.',
       'ko' => '', 'effetti_ok' => ['affetto_cast' => 3, 'compostezza' => 2], 'effetti_ko' => [],
       'peso' => ['cuore' => 2, 'candore' => -1]],
    ],
   ],
 ),
],

// ─────────────────────────────────────────────────────────────────────────
[
 'ckey' => 'culturale',
 'titolo' => 'Il festival culturale, e la classe che non sa cosa fare',
 'occhiello' => 'Due giorni, e la scuola aperta a chiunque',
 'premessa' => 'Ogni classe deve montare qualcosa: un bar, una casa stregata, uno spettacolo. '
     . 'La riunione per decidere cosa fare è cominciata quaranta minuti fa e non ha ancora '
     . 'prodotto niente, perché ci sono tre proposte e nessuna maggioranza. Il tempo per '
     . 'costruire è quello che è.',
 'luogo' => 'liceo',
 'condizione' => 'evento:culturale',
 'min_cast' => 2, 'max_cast' => 6, 'finestra' => 1800,
 'scene' => $scene(
   [
    'testo' => 'Alla lavagna ci sono tre parole: BAR, FANTASMI, BAND. Chi ha proposto il bar dice '
        . 'che è sicuro. Chi ha proposto i fantasmi dice che è l\'unico che si ricorderà '
        . 'qualcuno. Chi ha proposto la band non ha proposto niente: ha solo detto che sa '
        . 'suonare, e adesso guarda fuori dalla finestra come se la cosa non lo riguardasse.',
    'opzioni' => [
      ['k' => 'bar', 'testo' => 'Stare col bar: funziona sempre e non si fa figuracce',
       'prova' => 'testa', 'difficolta' => 30,
       'ok' => 'Fai due conti sul retro di un quaderno — costi, turni, quanti caffè servono — e '
           . 'la sala si convince perché i numeri non litigano con nessuno.',
       'ko' => 'Parli di numeri a gente che voleva parlare di idee. Ti ascoltano e poi riprendono '
           . 'da dove erano.',
       'effetti_ok' => ['affetto_cast' => 2], 'effetti_ko' => ['compostezza' => -1],
       'peso' => ['testa' => 4]],
      ['k' => 'fantasmi', 'testo' => 'Spingere per la casa stregata',
       'prova' => 'dai_suki', 'difficolta' => 40,
       'ok' => 'Descrivi il corridoio al buio, la mano che esce dal muro, la ragazza che urla. '
           . 'A metà del discorso hanno già cominciato a dividersi i compiti.',
       'ko' => 'Ti entusiasmi da sol{o} per tre minuti. Quando finisci, qualcuno chiede se si '
           . 'torna al bar.',
       'effetti_ok' => ['affetto_cast' => 3, 'compostezza' => -1],
       'effetti_ko' => ['compostezza' => -2],
       'peso' => ['dai_suki' => 4, 'kakko' => 1]],
      ['k' => 'band', 'testo' => 'Dire che chi sa suonare dovrebbe suonare',
       'prova' => 'cuore', 'difficolta' => 45,
       'ok' => 'Lo dici guardando la finestra, non la classe. Lei si gira. Non dice di sì, ma '
           . 'non dice nemmeno di no, e in questa scuola è la stessa cosa.',
       'ko' => 'Lo dici, e lei risponde che non se ne parla. Il tono chiude la faccenda e apre '
           . 'qualcos\'altro, che non sai bene cosa sia.',
       'effetti_ok' => ['affetto_cast' => 6],
       'effetti_ko' => ['malinteso_cast' => 6, 'affetto_cast' => -1],
       'peso' => ['cuore' => 4, 'musica' => 2]],
    ],
   ],
   [
    'testo' => 'Secondo giorno, pomeriggio. Quello che avete montato è in piedi, più o meno, e '
        . 'la gente entra. Poi entra anche un gruppo di un\'altra scuola che ha deciso che la '
        . 'cosa divertente è rovinarla: spostano una cosa, ridono, ne spostano un\'altra. '
        . 'Il professore è dall\'altra parte dell\'edificio.',
    'opzioni' => [
      ['k' => 'affronta', 'testo' => 'Andare lì e dirgli di smettere',
       'prova' => 'rissa', 'difficolta' => 45,
       'ok' => 'Non serve alzare le mani: basta arrivare vicino, restare fermo, e non abbassare '
           . 'gli occhi per il tempo che serve. Se ne vanno borbottando.',
       'ko' => 'Uno ti dà una spinta, tu ne dai una indietro, e adesso c\'è un capannello. '
           . 'Finisce in niente, ma ti fa male una spalla e il banchetto è per terra.',
       'effetti_ok' => ['affetto_cast' => 5],
       'effetti_ko' => ['pf' => -3, 'affetto_cast' => 2, 'compostezza' => -2],
       'peso' => ['rissa' => 4]],
      ['k' => 'professore', 'testo' => 'Andare a chiamare il professore',
       'prova' => 'nessuna', 'difficolta' => 0,
       'ok' => 'Ci metti quattro minuti. Quando tornate se ne sono andati, e resta da rimettere '
           . 'a posto. Nessuno si è fatto male, e nessuno si ricorderà che sei stat{o} tu a '
           . 'risolverla.',
       'ko' => '', 'effetti_ok' => ['affetto_cast' => 1], 'effetti_ko' => [],
       'peso' => ['testa' => 3, 'rissa' => -2]],
      ['k' => 'accogli', 'testo' => 'Trattarli da clienti e metterli a lavorare',
       'prova' => 'kakko', 'difficolta' => 55,
       'ok' => 'Dai a quello più grosso un grembiule e gli dici che serve uno all\'ingresso. '
           . 'Ci casca. Fanno il turno, si divertono, e a fine giornata uno chiede se domani '
           . 'può tornare.',
       'ko' => 'Provi a fare lo spiritoso e passi per quello che li prende in giro. '
           . 'Peggiora tutto.',
       'effetti_ok' => ['affetto_cast' => 7, 'compostezza' => 1],
       'effetti_ko' => ['pf' => -2, 'compostezza' => -2],
       'peso' => ['kakko' => 4, 'dai_suki' => 2]],
    ],
   ],
 ),
],

// ─────────────────────────────────────────────────────────────────────────
[
 'ckey' => 'capodanno',
 'titolo' => 'La prima visita dell\'anno',
 'occhiello' => 'Hatsumōde, all\'una di notte',
 'premessa' => 'Si va al tempio la notte del primo gennaio, e ci va tutto il quartiere insieme. '
     . 'La fila scende per tutta la salita e avanza di un metro ogni due minuti. Fa freddo, si '
     . 'vede il fiato, e c\'è un chiosco che vende amazake caldo a metà strada. Alla fine si '
     . 'tira la corda, si battono le mani due volte, e si chiede una cosa sola.',
 'luogo' => 'tempio',
 'condizione' => 'evento:capodanno',
 'min_cast' => 2, 'max_cast' => 6, 'finestra' => 1800,
 'scene' => $scene(
   [
    'testo' => 'Quaranta minuti di fila. A quest\'ora e con questo freddo la gente parla di cose '
        . 'di cui non parlerebbe mai alle tre del pomeriggio: propositi, paure, quello che è '
        . 'andato storto quest\'anno. Poi arriva il tuo turno davanti alla campana.',
    'opzioni' => [
      ['k' => 'chiedi', 'testo' => 'Chiedere la cosa che vuoi davvero',
       'prova' => 'cuore', 'difficolta' => 30,
       'ok' => 'Tiri la corda, batti le mani, e per mezzo secondo la formuli anche a te stess{o}, '
           . 'che è la parte difficile. Esci sapendo una cosa in più di quando sei entrat{o}.',
       'ko' => 'Tiri la corda, batti le mani, e ti accorgi che non sai cosa chiedere. Chiedi '
           . 'salute per la famiglia, che va sempre bene e non impegna nessuno.',
       'effetti_ok' => ['compostezza' => 2], 'effetti_ko' => [],
       'peso' => ['cuore' => 3]],
      ['k' => 'omikuji', 'testo' => 'Tirare l\'omikuji, il foglietto della sorte',
       'prova' => 'nessuna', 'difficolta' => 0,
       'ok' => 'Esce **piccola fortuna**. Il foglietto dice che quello che aspetti arriverà, ma '
           . 'non da dove lo stai guardando. Lo leggi tre volte e poi lo leghi al ramo con gli '
           . 'altri, come si fa con le cose che non si vogliono portare a casa.',
       'ko' => '', 'effetti_ok' => ['affetto_cast' => 1], 'effetti_ko' => [],
       'peso' => ['candore' => 2, 'dai_suki' => 1]],
      ['k' => 'amazake', 'testo' => 'Saltare la fila e andare a prendere l\'amazake per tutti',
       'prova' => 'dai_suki', 'difficolta' => 35,
       'ok' => 'Torni con cinque bicchieri di carta in equilibrio e non ne rovesci nemmeno uno. '
           . 'Per dieci minuti sei la persona più popolare del tempio.',
       'ko' => 'Torni che la fila è avanzata, il gruppo non c\'è più, e tu hai cinque bicchieri '
           . 'e nessuno a cui darli.',
       'effetti_ok' => ['affetto_cast' => 4, 'pf' => 1],
       'effetti_ko' => ['affetto_cast' => -1],
       'peso' => ['dai_suki' => 4]],
    ],
   ],
 ),
],

// ─────────────────────────────────────────────────────────────────────────
[
 'ckey' => 'chiacchiere',
 'titolo' => 'Quello che dicono di te',
 'occhiello' => 'Una storia che ha fatto troppa strada',
 'premessa' => 'Da qualche giorno gira una storia. Non si sa chi l\'abbia messa in giro e non '
     . 'somiglia più a quello che è successo — se è successo. Il problema delle voci è che a '
     . 'un certo punto smettono di avere un\'origine: le sanno tutti, e quindi sono vere.',
 'luogo' => null,
 'condizione' => 'pettegolezzo',
 'min_cast' => 2, 'max_cast' => 5, 'finestra' => 1500,
 'scene' => $scene(
   [
    'testo' => 'Te ne accorgi da come si interrompono quando arrivi. Non è ostilità: è '
        . 'curiosità, che è peggio, perché l\'ostilità almeno si affronta. Uno del gruppo ti '
        . 'guarda e ti chiede, con tono leggero, se è vero. Non specifica cosa.',
    'opzioni' => [
      ['k' => 'smentisci', 'testo' => 'Smentire, punto e basta',
       'prova' => 'kakko', 'difficolta' => 45,
       'ok' => 'Dici di no con la faccia di chi trova la domanda noiosa, e la noia è l\'unica '
           . 'cosa che uccide un pettegolezzo. Cambiano argomento da soli.',
       'ko' => 'Neghi troppo in fretta e troppo forte. Adesso ci credono anche quelli che non '
           . 'ci credevano.',
       'effetti_ok' => ['calore' => -8],
       'effetti_ko' => ['malinteso_cast' => 10, 'calore' => 6],
       'peso' => ['kakko' => 4]],
      ['k' => 'origine', 'testo' => 'Chiedere a chi l\'ha sentita da chi',
       'prova' => 'testa', 'difficolta' => 40,
       'ok' => 'Risali tre passaggi e arrivi a una persona che dice di averla sentita da te. '
           . 'Non è vero, e a questo punto è quasi divertente.',
       'ko' => 'Ognuno l\'ha sentita da un altro e nessuno si ricorda da chi. È così che '
           . 'funziona, e adesso lo sai.',
       'effetti_ok' => ['calore' => -4, 'affetto_cast' => 2],
       'effetti_ko' => ['compostezza' => -1],
       'peso' => ['testa' => 4]],
      ['k' => 'peggiora', 'testo' => 'Raccontarla tu, ma molto peggio, finché non regge più',
       'prova' => 'dai_suki', 'difficolta' => 50,
       'ok' => 'Ci aggiungi un elicottero e una banda di motociclisti. Quando finisci ridono '
           . 'tutti, e la versione seria è morta.',
       'ko' => 'La tua versione esagerata piace, e comincia a girare **anche quella**. '
           . 'Adesso ce ne sono due.',
       'effetti_ok' => ['calore' => -10, 'affetto_cast' => 3],
       'effetti_ko' => ['calore' => 10, 'malinteso_cast' => 6],
       'peso' => ['dai_suki' => 4, 'kakko' => 2]],
    ],
   ],
   [
    'testo' => 'Più tardi trovi la persona da cui, secondo tre passaggi di corridoio, sarebbe '
        . 'partita. È seduta da sola e non sta scappando. Quando ti vede arrivare non si alza.',
    'opzioni' => [
      ['k' => 'ascolta', 'testo' => 'Sederti e lasciarla parlare per prima',
       'prova' => 'cuore', 'difficolta' => 35,
       'ok' => 'Racconta una cosa che aveva visto per metà, e che ripetendo è diventata un\'altra. '
           . 'Non lo ha fatto per cattiveria. Quasi mai è per cattiveria.',
       'ko' => 'Non parla. Il silenzio dura abbastanza da diventare una risposta, e la risposta '
           . 'non ti piace.',
       'effetti_ok' => ['affetto_cast' => 6, 'calore' => -5],
       'effetti_ko' => ['malinteso_cast' => 5],
       'peso' => ['cuore' => 4, 'testa' => 1]],
      ['k' => 'accusa', 'testo' => 'Dirle in faccia che è stata lei',
       'prova' => 'rissa', 'difficolta' => 40,
       'ok' => 'Ammette. Si scusa, pure, e la cosa finisce lì — ma per un po\' non vi parlerete, '
           . 'e lo sapete tutti e due.',
       'ko' => 'Non era stata lei. Adesso c\'è una persona in più che ha una buona ragione per '
           . 'parlare male di te.',
       'effetti_ok' => ['calore' => -6, 'affetto_cast' => -4],
       'effetti_ko' => ['affetto_cast' => -8, 'malinteso_cast' => 12],
       'peso' => ['rissa' => 4]],
      ['k' => 'lascia', 'testo' => 'Girare i tacchi e lasciar perdere',
       'prova' => 'nessuna', 'difficolta' => 0,
       'ok' => 'Fra due settimane nessuno se ne ricorderà, perché ci sarà una storia nuova. '
           . 'È vero, e non consola granché.',
       'ko' => '', 'effetti_ok' => ['compostezza' => 1], 'effetti_ko' => [],
       'peso' => ['testa' => 2, 'cuore' => -1]],
    ],
   ],
 ),
],

// ─────────────────────────────────────────────────────────────────────────
[
 'ckey' => 'neve',
 'titolo' => 'La prima neve',
 'occhiello' => 'Il quartiere zitto',
 'premessa' => 'Ha cominciato di notte e nessuno se n\'è accorto. La mattina il quartiere è '
     . 'un\'altra cosa: tutto più chiaro, tutto più basso, e soprattutto zitto — la neve si '
     . 'porta via i rumori. I treni vanno a rilento, le strade sono mezze vuote, e per qualche '
     . 'ora sembra lecito non fare niente.',
 'luogo' => null,
 'condizione' => 'neve',
 'min_cast' => 2, 'max_cast' => 5, 'finestra' => 1500,
 'scene' => $scene(
   [
    'testo' => 'Nessuno ha voglia di andare dove doveva andare. Ci si ferma, si guarda in alto '
        . 'con la bocca aperta come si fa a sei anni, e si sta lì. Poi qualcuno raccoglie della '
        . 'neve e la stringe in mano, senza ancora aver deciso cosa farne.',
    'opzioni' => [
      ['k' => 'tira', 'testo' => 'Tirargliela addosso per primo',
       'prova' => 'sport', 'difficolta' => 30,
       'ok' => 'Centro in pieno, sulla spalla. Quello che segue è una guerra di dieci minuti in '
           . 'cui nessuno ha più diciassette anni.',
       'ko' => 'Manchi, e la palla finisce nel cappuccio di uno che passava. Che si gira. '
           . 'E che adesso è dalla parte degli altri.',
       'effetti_ok' => ['affetto_cast' => 5, 'pf' => -1],
       'effetti_ko' => ['affetto_cast' => 2, 'pf' => -2, 'compostezza' => -1],
       'peso' => ['sport' => 3, 'dai_suki' => 3]],
      ['k' => 'pupazzo', 'testo' => 'Metterti a fare un pupazzo, serissimo',
       'prova' => 'nessuna', 'difficolta' => 0,
       'ok' => 'Ci si mettono in tre. Viene brutto e storto, ha un sasso per occhio e una '
           . 'sciarpa che qualcuno rivorrà indietro. Resterà lì fino a giovedì.',
       'ko' => '', 'effetti_ok' => ['affetto_cast' => 4], 'effetti_ko' => [],
       'peso' => ['candore' => 3, 'cuore' => 2]],
      ['k' => 'guarda', 'testo' => 'Restare fermo a guardare, senza toccare niente',
       'prova' => 'nessuna', 'difficolta' => 0,
       'ok' => 'Il quartiere così non lo hai mai visto e non lo rivedrai uguale, perché domani '
           . 'sarà fango. Uno del gruppo si ferma accanto a te e guarda dove guardi tu, e non '
           . 'chiede niente.',
       'ko' => '', 'effetti_ok' => ['affetto_cast' => 3, 'compostezza' => 2], 'effetti_ko' => [],
       'peso' => ['cuore' => 3, 'dai_suki' => -1]],
    ],
   ],
   [
    'testo' => 'Verso sera comincia a gelare, e la discesa davanti alla stazione diventa una '
        . 'pista. Una signora con le buste è ferma in cima e non se la sente. Dietro di lei si '
        . 'sta formando una piccola coda di gente che non se la sente neanche loro.',
    'opzioni' => [
      ['k' => 'accompagna', 'testo' => 'Darle un braccio e scendere piano',
       'prova' => 'nessuna', 'difficolta' => 0,
       'ok' => 'Venti metri in tre minuti. In fondo ti ringrazia e ti mette in mano un mandarino, '
           . 'perché è il genere di signora che ha sempre dei mandarini.',
       'ko' => '', 'effetti_ok' => ['affetto_cast' => 3, 'pf' => 1], 'effetti_ko' => [],
       'peso' => ['cuore' => 4]],
      ['k' => 'sabbia', 'testo' => 'Andare a cercare sabbia o cenere da buttare sul ghiaccio',
       'prova' => 'testa', 'difficolta' => 35,
       'ok' => 'Dal cantiere dietro l\'angolo. Ne spargete due secchi e la discesa torna una '
           . 'discesa. Nessuno vi ha visto, e va benissimo così.',
       'ko' => 'Non trovi niente, e quando torni la coda si è sciolta da sola perché qualcuno '
           . 'ha avuto un\'idea migliore.',
       'effetti_ok' => ['affetto_cast' => 2], 'effetti_ko' => [],
       'peso' => ['testa' => 4]],
      ['k' => 'scivola', 'testo' => 'Scendere di corsa per dimostrare che si può',
       'prova' => 'sport', 'difficolta' => 55,
       'ok' => 'Arrivi in fondo in piedi, con le braccia larghe, e ti giri come se niente fosse. '
           . 'Applausi di quattro persone, ma quattro persone sono quattro persone.',
       'ko' => 'Il primo metro va bene. Poi il mondo gira di novanta gradi e tu sei sedut{o} in '
           . 'fondo alla discesa con la neve nei pantaloni.',
       'effetti_ok' => ['affetto_cast' => 3, 'compostezza' => 1],
       'effetti_ko' => ['pf' => -3, 'compostezza' => -3],
       'peso' => ['kakko' => 3, 'sport' => 2]],
    ],
   ],
 ),
],

// ─────────────────────────────────────────────────────────────────────────
[
 'ckey' => 'addio',
 'titolo' => 'Ai giardini, quando c\'è da dire una cosa',
 'occhiello' => 'Il posto dove si viene apposta',
 'premessa' => 'Ai giardini pensili non ci si capita: ci si viene. Sono quattro spiazzi '
     . 'quadrati a terrazze sul fianco della discesa, due panchine rivolte a sud, e da quaggiù '
     . 'si sente la gente parlare in fondo alla scalinata senza vederla. È il posto dove '
     . 'Madoka, nel capitolo quarantacinque, dice a Kyosuke che se ne va in America. Chi '
     . 'convoca qualcuno qui ha già deciso cosa dirgli. Il problema è dirlo.',
 'luogo' => 'giardini',
 'condizione' => 'sempre',
 'min_cast' => 2, 'max_cast' => 4, 'finestra' => 1800,
 'scene' => $scene(
   [
    'testo' => 'Siete sulla seconda terrazza, quella con il muretto. Non c\'è nessun altro e '
        . 'non passerà nessuno: è il motivo per cui si viene qui. Uno di voi ha chiesto agli '
        . 'altri di venire, e adesso che ci siete tutti nessuno comincia. Da sotto arriva il '
        . 'rumore di una saracinesca che si chiude.',
    'opzioni' => [
      ['k' => 'comincia', 'testo' => 'Cominciare tu, senza girarci intorno',
       'prova' => 'cuore', 'difficolta' => 35,
       'ok' => 'Lo dici in cinque parole e poi stai zitto. È il modo giusto: le cose difficili '
           . 'dette in fretta lasciano il tempo di reggere il colpo. Nessuno ti interrompe.',
       'ko' => 'Cominci, e a metà della seconda frase ti accorgi che stai spiegando il contesto '
           . 'invece di dire la cosa. Ti fermi. Adesso sanno che c\'è qualcosa e non sanno cosa.',
       'effetti_ok' => ['affetto_cast' => 5, 'compostezza' => -2],
       'effetti_ko' => ['malinteso_cast' => 8, 'compostezza' => -2],
       'peso' => ['cuore' => 4]],
      ['k' => 'aspetta', 'testo' => 'Aspettare che parli chi vi ha chiamati',
       'prova' => 'nessuna', 'difficolta' => 0,
       'ok' => 'Il silenzio dura più di quanto sia comodo. Poi qualcuno si siede sul muretto, e '
           . 'quando comincia a parlare lo fa guardando i tetti, non voi. Si capisce subito che '
           . 'ci ha pensato per giorni.',
       'ko' => '', 'effetti_ok' => ['affetto_cast' => 2], 'effetti_ko' => [],
       'peso' => ['testa' => 3, 'candore' => -1]],
      ['k' => 'leggero', 'testo' => 'Buttarla sul ridere, per abbassare la tensione',
       'prova' => 'dai_suki', 'difficolta' => 45,
       'ok' => 'Dici una scemenza sul muretto, sulle panchine, su chiunque abbia deciso di '
           . 'metterle rivolte a sud. Ridono. E quando si smette di ridere si riesce a parlare.',
       'ko' => 'La battuta cade in mezzo a un silenzio che non la voleva. Qualcuno sorride per '
           . 'educazione e la cosa diventa più difficile di prima.',
       'effetti_ok' => ['affetto_cast' => 3, 'compostezza' => 1],
       'effetti_ko' => ['affetto_cast' => -2, 'compostezza' => -1],
       'peso' => ['dai_suki' => 4]],
    ],
   ],
   [
    'testo' => 'Adesso è detta. Non importa quale fosse: qui le cose che si dicono sono sempre '
        . 'la stessa — che qualcosa sta per finire, o che è già finito e non ve ne eravate '
        . 'accorti. Il sole è sceso sotto il livello del muretto e comincia a fare freddo. '
        . 'Nessuno si alza per primo.',
    'opzioni' => [
      ['k' => 'resta', 'testo' => 'Restare seduti finché non fa buio',
       'prova' => 'nessuna', 'difficolta' => 0,
       'ok' => 'Non si aggiunge altro. Si guardano i tetti, si sente qualcuno che chiama un cane '
           . 'in fondo alla discesa, e si resta. Fra dieci anni di questo pomeriggio ricorderete '
           . 'il freddo sul muretto e non le parole.',
       'ko' => '', 'effetti_ok' => ['affetto_cast' => 6, 'compostezza' => 2], 'effetti_ko' => [],
       'peso' => ['cuore' => 3, 'candore' => 1]],
      ['k' => 'convinci', 'testo' => 'Provare a far cambiare idea',
       'prova' => 'dai_suki', 'difficolta' => 55,
       'ok' => 'Non cambi niente di quello che succederà, ma trovi la frase giusta, e quella '
           . 'resta. A volte è tutto quello che si può fare, e non è poco.',
       'ko' => 'Insisti una volta di troppo. La risposta arriva più dura di quanto fosse voluta, '
           . 'e adesso oltre alla cosa c\'è pure come ve la siete detta.',
       'effetti_ok' => ['affetto_cast' => 7],
       'effetti_ko' => ['affetto_cast' => -5, 'malinteso_cast' => 10, 'compostezza' => -2],
       'peso' => ['dai_suki' => 3, 'cuore' => 2]],
      ['k' => 'scendi', 'testo' => 'Alzarti e scendere per primo',
       'prova' => 'kakko', 'difficolta' => 40,
       'ok' => 'Ti alzi, dici che si fa tardi, e scendi senza voltarti. È una cosa da vigliacchi '
           . 'e insieme una gentilezza: gli altri non devono decidere quando finirla.',
       'ko' => 'Ti alzi e ti accorgi, dal modo in cui ti guardano, che andartene adesso è la cosa '
           . 'peggiore che potevi fare. Ma ormai sei in piedi.',
       'effetti_ok' => ['compostezza' => 1],
       'effetti_ko' => ['affetto_cast' => -6, 'malinteso_cast' => 6],
       'peso' => ['kakko' => 3, 'cuore' => -2]],
    ],
   ],
 ),
],

    ],
];
