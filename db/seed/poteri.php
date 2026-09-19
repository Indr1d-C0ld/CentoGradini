<?php

declare(strict_types=1);

/**
 * I poteri della stirpe.
 *
 * I limiti numerici vengono dal regolamento del 1990, che su questo è la
 * fonte migliore che abbiamo: li aveva ricavati dal manga citando volume e
 * pagina. I **nomi** invece sono quelli del canone giapponese, che usa i
 * termini della parapsicologia — e in un caso il nome cambia la sostanza:
 * quella che il regolamento chiama «vista a raggi X» nel canone è
 * クレヤボヤンス, chiaroveggenza, cioè vedere *un altro luogo*, non vedere
 * *attraverso* le cose. Il regolamento ci aveva costruito sopra una gag sulle
 * emorragie nasali; noi teniamo il potere vero.
 *
 * Tre poteri del 1990 non ci sono, e ognuno per un motivo suo:
 *
 *  - **Viaggio nel tempo**: il canone dice che Kyosuke non lo comanda, si
 *    attiva da solo («自分の意志通りには使えず、偶発的に発動される»). Resta
 *    nel mondo come fenomeno del luogo — i Timeslip della scalinata — e come
 *    potere del nonno, che ha sessant'anni e non è un personaggio giocante.
 *  - **Conversione di energia potenziale**: è il potere di Akira. L'autore
 *    del 1990 scrive lui stesso che «è qui solo perché ci facciate una
 *    risata, e probabilmente non andrebbe giocato affatto». Siamo d'accordo.
 *  - **Scambio di corpo minore**: esisteva solo per sanare un'incoerenza
 *    della serie, e il regolamento lo marcava «questo potere fa schifo».
 *    Assorbito nello scambio di corpo normale.
 *
 * `costo_primario` / `costo_secondario`: PP per uso. Null dove il potere non
 * può essere primario — i primari della stirpe sono tre soli, ed è canone.
 */

/**
 * **Gli intervalli `da`-`a` valgono per il tiro dei SECONDARI.** I tre poteri
 * che possono essere primari hanno una tabella propria, in
 * `Scheda::BANDE_PRIMARIE`: il regolamento del 1990 dava alla telepatia il
 * 32% come potere principale, e il canone dice il contrario — nella famiglia
 * di Kyosuke il telepate e' uno solo, ed e' un bambino di otto anni.
 *
 * `vistosita` 0-10: quanto e' difficile NON farsi notare mentre lo si usa.
 * E' il numero attorno a cui gira tutta la fase F3, ed e' anche una lettura
 * dell'opera: i poteri che in Kimagure Orange Road creano guai sono quelli
 * che si vedono. La telepatia non si vede e non ha mai messo Kazuya nei
 * pasticci; il teletrasporto fa sparire una persona davanti a testimoni, e
 * infatti e' il motivo per cui i Kasuga traslocano. A vistosita' 0 il potere
 * non e' osservabile affatto: i sogni premonitori e il sesto senso succedono
 * dentro la testa.
 */
$p = static fn (
    string $k, string $nome, string $jp, ?int $primario, int $secondario,
    int $da, int $a, int $vistosita, string $desc, string $limiti
): array => [
    'pkey'             => $k,
    'nome'             => $nome,
    'nome_jp'          => $jp,
    'costo_primario'   => $primario,
    'costo_secondario' => $secondario,
    'da'               => $da,
    'a'                => $a,
    'vistosita'        => $vistosita,
    'descrizione'      => $desc,
    'limiti'           => $limiti,
];

return [
    'tabella'     => 'poteri',
    'chiave'      => 'pkey',
    'descrizione' => 'poteri esper',
    'righe' => [

$p('telecinesi', 'Telecinesi', 'サイコキネシス', 1, 3, 1, 17, 7,
  'Il potere di base della stirpe, e quello che quasi tutti hanno. Un braccio mentale che '
  . 'solleva, sposta, trattiene. Serve per tenere il gatto in casa, per spingere la bicicletta '
  . 'in salita, per non cadere — e, una volta ogni tanto, per qualcosa di enorme.',
  'Fino a 110 kg, e solo su cose che vedi o che conosci benissimo: un letto di casa tua a '
  . 'sedici chilometri sì, la serratura che hai davanti no, perché il meccanismo non lo vedi. '
  . 'Autolevitazione lenta sotto i sessanta metri. Scudo a pochi centimetri dal corpo: ferma '
  . 'i pugni, devia i proiettili, non i laser.'),

$p('teletrasporto', 'Teletrasporto', 'テレポーテーション', 2, 2, 18, 25, 9,
  'Sparire da qui e comparire là. Chi ce l\'ha come potere primario porta con sé anche altre '
  . 'persone; chi ce l\'ha come secondario sposta oggetti, e solo in un\'emergenza vera riesce '
  . 'a spostare sé stesso.',
  'Solo verso posti che conosci davvero. Una persona in più costa un punto in più. Come '
  . 'secondario: oggetti sì, persone no — tranne te stesso, a cinque punti, e una volta sola '
  . 'per disperazione.'),

$p('telepatia', 'Telepatia', 'テレパシー', 2, 4, 26, 28, 1,
  'Leggere nel pensiero e parlare senza voce. Non è un potere passivo: va acceso, costa '
  . 'fatica, e quello che arriva è spesso un\'immagine invece di una frase — con tutti i '
  . 'fraintendimenti che ne seguono. È il più raro dei tre: nella famiglia di Kyosuke ce '
  . 'l\'ha una persona sola, ed è un bambino di otto anni.',
  'Da primario: leggi facilmente e agganci la posizione di qualcuno entro un chilometro e '
  . 'mezzo. Da secondario: solo un\'idea vaga, e a caro prezzo. Chi ce l\'ha si accorge '
  . 'presto che sapere cosa pensano gli altri non aiuta a capirli.'),

$p('supervelocita', 'Supervelocità', '超速', null, 3, 29, 31, 8,
  'Ottanta all\'ora di corsa, e tre attacchi nel tempo in cui gli altri ne fanno uno. '
  . 'Spettacolare, rumoroso, impossibile da nascondere.',
  'Un punto al minuto. Una persona al traino, non di più. Un quarto delle volte sfondi '
  . 'qualcosa: un muro, una siepe, una porta.'),

$p('supersensi', 'Supersensi', '超感覚', null, 1, 32, 37, 1,
  'Sentire e vedere a cento metri come se fossi a due. Utile per sapere cosa stanno dicendo '
  . 'di te dall\'altra parte del cortile — e per scoprire che non volevi saperlo.',
  'Cento metri. Devi vedere chi parla o almeno sapere da che parte sta. Va acceso di '
  . 'proposito: non è sempre attivo.'),

$p('chiaroveggenza', 'Chiaroveggenza', 'クレヤボヤンス', null, 2, 38, 43, 2,
  'Vedere un altro luogo. Non attraverso i muri: **altrove**. Chiudi gli occhi e sei in una '
  . 'stanza dove non sei. È il potere che Kyosuke usa per cercare qualcuno, ed è anche il '
  . 'modo più rapido per vedere una cosa che avresti preferito non vedere.',
  'Serve un posto che conosci o una persona a cui tieni. L\'immagine è muta e a volte fuori '
  . 'tempo di qualche minuto.'),

$p('sogni', 'Sogni premonitori', 'プレコグニション', null, 0, 44, 49, 0,
  'Gli 予知夢, i sogni che si avverano. Brutti, quasi sempre: qualcuno che muore, qualcuno che '
  . 'ti volta le spalle, qualcuno che se ne va. E si avverano davvero, ma mai come li avevi '
  . 'capiti.',
  'Non costa niente e non si comanda. Arriva quando vuole, di solito la notte prima. '
  . 'L\'interpretazione è tua, ed è lì che sbagli.'),

$p('scambio_corpo', 'Scambio di corpo', '入れ替わり', null, 0, 50, 56, 6,
  'Le teste si scontrano e ci si ritrova nel corpo dell\'altro. I poteri restano a chi li '
  . 'aveva: tu nel corpo suo usi i tuoi, non i suoi.',
  'Serve un urto vero, testa contro testa. Con chi non è della stirpe, funziona metà delle '
  . 'volte. Si torna indietro allo stesso modo, prima o poi.'),

$p('cambio_identita', 'Cambio d\'identità', '変身', null, 3, 57, 61, 5,
  'Apparire come un\'altra persona. È il potere di Akane, ed è più sottile di come sembra: '
  . 'non cambi davvero, cambi **quello che vede una persona sola**. Tutti gli altri continuano '
  . 'a vedere te, ed è lì che nascono i guai.',
  'Un bersaglio per volta. Chi sa che hai questo potere può accorgersene: tira sotto '
  . '30+(TESTA×2) su cento. Se sei spaventato o malato, una volta su due sbagli forma.'),

$p('fantasmi', 'Proiezione di fantasmi', '幻影', null, 2, 62, 67, 8,
  'Piegare la luce e disegnarci qualcosa: un UFO nel cielo, una scena sul muro della camera '
  . 'oscura, un\'ombra che non c\'è. Kurumi ci ha fatto credere a mezzo quartiere che fossero '
  . 'arrivati gli alieni.',
  'Immagini piatte, senza spessore. Serve una superficie o della foschia. Le persone non si '
  . 'possono imitare. Due punti valgono due minuti.'),

$p('ipnosi', 'Ipnosi', '催眠術', null, 3, 68, 74, 6,
  'Una persona al giorno fa quello che le dici, e dice «hai» a tutto. È il potere di Kurumi, '
  . 'che lo usa quasi sempre su suo fratello e quasi mai per il suo bene.',
  'Una persona al giorno, non di più. L\'effetto passa da solo quando il divertimento è '
  . 'finito. Chi si risveglia si ricorda abbastanza da essere furioso.'),

$p('autoipnosi', 'Autoipnosi', 'ヒュプノシス', null, 2, 75, 79, 3,
  'Fissarti allo specchio e ripetere una parola finché non diventi quella cosa: più forte, '
  . 'più sicuro, meno imbranato. Funziona. È il problema.',
  'Si disfa allo specchio, con uno schiaffo, o con un secchio d\'acqua. Finché dura sei '
  . 'un\'altra persona, e non sempre una migliore: con questo potere si diventa stronzi in '
  . 'fretta.'),

$p('natura', 'Comunicare con la natura', '自然との対話', null, 1, 80, 84, 4,
  'Era il potere di Akemi, la madre di Kyosuke. Parlare con gli animali, e con il resto. Non '
  . 'si comandano: si chiedono le cose, e metà delle volte ti ascoltano.',
  'Animali sì, piante no. Mai un ordine: solo una richiesta, accolta una volta su due. '
  . 'Chi ha questo potere ha un rapporto con i posti che gli altri non capiscono.'),

$p('invisibilita', 'Invisibilità', '透明化', null, 2, 85, 88, 7,
  'Non ti si vede. Ti si sente, ti si annusa, ci si sbatte contro.',
  'Una probabilità su dieci al minuto che qualcuno se ne accorga per caso; una su due se ti '
  . 'sta cercando e sa del potere. Due punti all\'attivazione e altri due ogni cinque minuti.'),

$p('sesto_senso', 'Sesto senso', '第六感', null, 1, 89, 92, 0,
  'Quando sta per succedere qualcosa di brutto, lo senti. Non sai cosa, non sai dove, non sai '
  . 'quando. Lo senti e basta, e non ti sbagli mai.',
  'Vago per costruzione. Dice che c\'è un pericolo, non quale. Chi ce l\'ha impara a fidarsi, '
  . 'e a non riuscire a spiegarlo a nessuno.'),

$p('voce', 'Manipolazione della voce', '声色', null, 2, 93, 96, 4,
  'Buttare la voce dall\'altra parte della stanza, e cambiarla fino a farla diventare quella '
  . 'di qualcun altro. Nel regolamento del 1990 era descritta e poi dimenticata fuori dalla '
  . 'tabella: qui c\'è.',
  'La voce sì, il resto no: chi ti guarda vede te. Funziona al telefono, dietro una porta, '
  . 'al buio.'),

    ],
];
