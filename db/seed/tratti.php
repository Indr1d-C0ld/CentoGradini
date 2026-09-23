<?php

declare(strict_types=1);

/**
 * I ventiquattro tratti.
 *
 * Il regolamento del 1990 ne aveva ventiquattro e li tirava su d100 tre volte.
 * Teniamo la struttura — è buona: tre tratti casuali fanno un personaggio più
 * interessante di tre tratti scelti — e rifacciamo il contenuto.
 *
 * Quelli che cadono sono i tratti politici da Guerra Fredda americana (il
 * rambo dell'NRA, il comunista con la maglietta tie-dye, il nazionalista
 * antiamericano): erano battute fra amici di San Diego e nell'opera non
 * esiste niente del genere. Al loro posto vanno cose che nel Giappone del
 * 1987 c'erano davvero e che in *Kimagure Orange Road* si vedono: il senpai
 * che pesa, il club che si mangia i pomeriggi, il figlio del negozio della
 * strada commerciale, chi studia per il jūken.
 *
 * Ogni tratto porta tre cose: i **modificatori**, un **obiettivo personale**
 * (che nel 1990 era la trovata migliore del sistema — gli scopi non
 * dichiarati generano trama) e una **scena** che il motore può usare.
 *
 * `sesso`: 'MF' per tutti, 'F' o 'M' se il tratto ha senso solo per uno.
 * I modificatori si applicano dopo il tiro delle abilità; quelli sulle
 * abilità secondarie dopo il loro calcolo.
 */

$t = static fn (
    string $k, int $da, int $a, string $nome, string $sesso,
    array $mod, string $desc, string $obiettivo
): array => [
    'tkey'      => $k,
    'da'        => $da,
    'a'         => $a,
    'nome'      => $nome,
    'sesso'     => $sesso,
    'modificatori' => json_encode($mod, JSON_UNESCAPED_UNICODE),
    'descrizione'  => $desc,
    'obiettivo'    => $obiettivo,
];

return [
    'tabella'     => 'tratti',
    'chiave'      => 'tkey',
    'descrizione' => 'tratti di personalità e background',
    'righe' => [

$t('combinaguai', 1, 7, 'Combinaguai', 'MF', [],
  'Sei quell{o} che ci prova sempre e la prende sempre. Sai a memoria chi esce con chi in '
  . 'tutta la scuola, hai una teoria su ogni ragazza e nessuna funziona. Nei guai ci finisci '
  . 'per curiosità, non per cattiveria, e ne esci con un bernoccolo e nessuna lezione imparata. '
  . 'Gli altri ti vogliono bene esattamente per questo.',
  'Farti prendere sul serio da qualcuno, almeno una volta.'),

$t('miope', 8, 11, 'Miope', 'MF', ['testa' => 1],
  'Senza occhiali o lenti non riconosci le persone nemmeno da vicino. È già successo che tu '
  . 'salutassi con calore uno sconosciuto, e che passassi accanto a chi ti stava aspettando. '
  . 'Il giorno che li togli, qualcuno ti guarda in un modo diverso e non te ne accorgi.',
  'Farti vedere per come sei davvero, non per come ti vesti addosso la miopia.'),

$t('cuore_deciso', 12, 15, 'Cuore deciso', 'MF', ['rissa' => 1, 'cuore' => 1],
  'Parli chiaro, cammini svelto, non ti scusi per esistere. Porti i pantaloni quando gli '
  . 'altri si aspettano una gonna, o il contrario, e non è una dichiarazione: è comodo. Sai '
  . 'benissimo di chi sei innamorat{o}. Quello che non sai è come dirglielo senza rovinare tutto.',
  'Dire a quella persona quello che provi, prima che lo faccia qualcun altro.'),

$t('motorino', 16, 21, 'Il motorino', 'MF', ['dai_suki' => 2, 'kakko' => 2, 'guida' => 9],
  'Cinquanta di cilindrata, lucidato ogni domenica, e un giubbotto che tieni addosso anche '
  . 'quando fa caldo. Il rumore lo riconoscono da due isolati. Portare qualcuno dietro è una '
  . 'cosa seria: ci si deve tenere, e chi si tiene lo decide da sé.',
  'Portare su quella sella la persona giusta, e fare la strada lunga.'),

$t('senpai', 22, 25, 'Senpai pesante', 'MF', ['kakko' => 1],
  'Sei un anno o due avanti agli altri e lo fai pesare. Nel club decidi tu chi lava le divise '
  . 'e chi entra in campo, e ti chiamano tutti «senpai» con quel tono a metà fra rispetto e '
  . 'paura. È un potere vero, in una scuola giapponese, e tu lo sai.',
  'Scoprire se qualcuno ti segue perché ti stima o perché deve.'),

$t('club', 26, 29, 'Anima del club', 'MF', ['sport' => 2],
  'Allenamento tutti i giorni, ritiro d\'estate, domenica compresa. Il club ti ha preso i '
  . 'pomeriggi, le vacanze e mezza adolescenza, e tu lo difenderesti a costo di litigare con '
  . 'chiunque. Solo che ogni tanto passi davanti al bar e vedi gli altri seduti dentro.',
  'Decidere se il club è quello che vuoi o solo quello a cui ti sei abituat{o}.'),

$t('negozio', 30, 34, 'Figlio del negozio', 'MF', ['cucina' => 2],
  'La tua famiglia ha una bottega sulla strada commerciale, e dopo la scuola c\'è il turno. '
  . 'Conosci tutti per nome, sai chi paga a fine mese e chi litiga con chi, e la gente ti '
  . 'racconta cose che non racconterebbe a un compagno di classe. Il grembiule però ti fa '
  . 'sembrare sempre un po\' più vecchio di quello che sei.',
  'Farti guardare una volta senza il grembiule addosso.'),

$t('mirror', 35, 38, 'Occhiali a specchio', 'MF', ['dai_suki' => 2, 'kakko' => 1],
  'Occhiali a specchio anche d\'inverno, una sigaretta che non accendi quasi mai e un '
  . 'soprannome di due sillabe. Hai un\'altissima considerazione di te e la reggi abbastanza '
  . 'bene da far dubitare gli altri. Dietro le lenti nessuno vede dove stai guardando, ed è '
  . 'esattamente il motivo per cui le porti.',
  'Toglierteli davanti a una persona sola.'),

$t('stazza', 39, 41, 'Stazza', 'MF', ['pf' => 2, 'dai_suki' => -1, 'rissa' => 1],
  'Più alto e più largo di tutti gli altri, da quando avevi dodici anni. Ti mettono sempre in '
  . 'fondo nelle foto di classe e ti chiedono sempre di spostare i banchi. Nessuno ti attacca '
  . 'briga, il che è comodo e anche un po\' triste.',
  'Che qualcuno ti chieda qualcosa che non richieda forza.'),

$t('cartone', 42, 45, 'Faccia da cartone', 'MF', [],
  'Hai una faccia che non somiglia a nessuno: sproporzionata, buffa, disegnata da un\'altra '
  . 'mano. La cosa straordinaria è che nessuno ci fa caso — nelle foto, in classe, per strada, '
  . 'tutti ti trattano come se fossi normale. Solo tu, allo specchio, ogni tanto ti chiedi.',
  'Capire se sei tu a vederti storto o loro a non guardare.'),

$t('yankee', 46, 50, 'Yankee', 'MF', ['rissa' => 1, 'dai_suki' => -1, 'kakko' => 2],
  'Divisa portata male, sigarette dietro la palestra, un rapporto pessimo con gli insegnanti '
  . 'e una fama che ti precede nei corridoi. Nella tua fama c\'è più leggenda che verità, ma '
  . 'non ti conviene dirlo. È il punto da cui parte Madoka, e da cui si può tornare indietro.',
  'Farti conoscere da qualcuno prima che gli arrivi la tua reputazione.'),

$t('passato_oscuro', 51, 54, 'Passato oscuro', 'MF', [],
  'C\'è una cosa che hai fatto, o che ti è successa, e di cui in questo quartiere non sa '
  . 'niente nessuno. Una persona di prima, un giro di gente sbagliata, un anno che non '
  . 'racconti. Finché resta là dietro va tutto bene.',
  'Che non venga fuori. Oppure: raccontarlo tu, prima che lo faccia qualcun altro.'),

$t('gemello', 55, 59, 'Gemello', 'MF', [],
  'Avete la stessa faccia e caratteri opposti, come Manami e Kurumi. Vi confondono da sempre '
  . 'e nessuno dei due lo sopporta più. Il vantaggio è che potete scambiarvi di posto; lo '
  . 'svantaggio è che quando uno combina qualcosa, la colpa arriva a metà.',
  'Farti riconoscere al primo sguardo da almeno una persona.'),

$t('precoce', 60, 62, 'Precoce', 'MF', ['testa' => 1, 'candore' => -2],
  'Capisci le cose degli adulti con qualche anno d\'anticipo e non hai la delicatezza di '
  . 'fingere di no. Dici ad alta voce quello che tutti hanno capito e nessuno voleva dire, e '
  . 'ti diverti a guardare le facce. Combini disastri con la migliore delle intenzioni.',
  'Far succedere qualcosa fra due persone che non si decidono.'),

$t('trasferito', 63, 66, 'Trasferito di recente', 'MF', [],
  'Sei arrivat{o} da poco e conosci sì e no due persone. Nel posto di prima è successo qualcosa '
  . 'che ha reso necessario andarsene — e se sei della stirpe, sai benissimo cosa. Ricominciare '
  . 'da capo è una seconda occasione e una condanna insieme.',
  'Costruirti qui qualcosa che valga la pena di non perdere di nuovo.'),

$t('burikko', 67, 69, 'Burikko', 'F', ['dai_suki' => 1, 'kakko' => -1],
  'Voce di mezzo tono più alta, «kawaiiii» a ogni cucciolo, e una versione di te che tiri '
  . 'fuori quando c\'è qualcuno che conta. Funziona benissimo con i ragazzi e pessimamente con '
  . 'le ragazze, che non ci cascano mai. Il problema è che ormai non sai più quale delle due '
  . 'sei davvero.',
  'Farti piacere da qualcuno mentre sei quell\'altra.'),

$t('secchione', 70, 73, 'Secchione', 'MF', ['testa' => 2, 'dai_suki' => -1, 'inglese' => 2],
  'Corso serale, libri di preparazione, tabella di marcia appesa sopra la scrivania. Il jūken '
  . 'è fra un anno o due e in casa tua non si parla d\'altro. Gli altri ti chiedono i compiti e '
  . 'tu glieli dai, perché è l\'unico momento in cui ti cercano.',
  'Passare un pomeriggio senza pensare all\'esame, e che sia con qualcuno.'),

$t('solo_in_casa', 74, 76, 'Solo in casa', 'MF', ['cucina' => 2, 'cuore' => -1],
  'I tuoi sono all\'estero, o lavorano lontano, o semplicemente non ci sono. Hai le chiavi, '
  . 'l\'orario che vuoi e una casa in cui di sera è accesa una finestra sola. Tutti pensano '
  . 'che sia una fortuna. È anche una fortuna.',
  'Invitare qualcuno, una volta, senza un motivo particolare.'),

$t('arti_marziali', 77, 80, 'Arti marziali', 'MF', ['rissa' => 2, 'sport' => 1],
  'Cintura nera, o quasi. Ti alleni da quando eri piccol{o} e in genere per un motivo preciso: '
  . 'qualcuno che volevi proteggere, o a cui volevi dimostrare qualcosa. Sai fermare un pugno '
  . 'e non sai cosa dire a quella persona quando ti sta davanti.',
  'Che quella persona si accorga che sei diventat{o} forte.'),

$t('doppia_vita', 81, 83, 'Doppia vita', 'MF', ['testa' => 1, 'kakko' => 1],
  'Hai un impegno di cui a scuola non sa niente nessuno: un lavoro, una band, un dovere di '
  . 'famiglia. In Giappone nel 1987 un lavoretto dopo la scuola è formalmente vietato agli '
  . 'studenti, e questo non ferma nessuno — ferma solo il raccontarlo.',
  'Tenere separate le due vite. O farle incontrare senza che crolli tutto.'),

$t('buona_famiglia', 84, 86, 'Buona famiglia', 'MF', ['musica' => 2, 'inglese' => 2, 'dai_suki' => 1],
  'Casa grande, lezioni di pianoforte da quando avevi cinque anni, inglese che parli davvero '
  . 'e un cognome che in certi ambienti significa qualcosa. Nessuno ti ha mai chiesto se '
  . 'volevi. Ai compagni di classe questa roba non la racconti: verrebbe fuori sbagliata.',
  'Fare una cosa che hai scelto tu, e vedere se ti riesce lo stesso.'),

$t('super_gentile', 87, 90, 'Super-gentile', 'MF', ['dai_suki' => 2, 'cuore' => -1, 'candore' => 2],
  'Sei una brava persona e questo, contrariamente a quello che si dice, è un problema. Non '
  . 'riesci a dire di no, non riesci a ferire nessuno, e a forza di non scegliere lasci che '
  . 'siano le situazioni a scegliere per te. Kyosuke è fatto così, e guarda com\'è finita.',
  'Prendere una decisione che farà stare male qualcuno, e prenderla lo stesso.'),

$t('acqua', 91, 93, 'Sfortuna con l\'acqua', 'MF', ['nuoto' => 2],
  'Finisci in acqua con una regolarità che ha dell\'assurdo: fontane, pioggia improvvisa, '
  . 'secchi rovesciati dalle finestre, il fiume. Ti sei rassegnat{o} a tenere un asciugamano '
  . 'nella borsa. Il raffreddore te lo prendi sempre, e ogni volta qualcuno si offre di '
  . 'accompagnarti a casa.',
  'Accorgerti che non è sempre sfortuna.'),

$t('capelli', 94, 96, 'Capelli impossibili', 'MF', ['dai_suki' => 1, 'kakko' => 1],
  'Il tuo colore di capelli non esiste in natura da queste parti — arancione, verde acqua, un '
  . 'viola che al sole diventa blu — e la cosa surreale è che nessuno te lo fa notare. Non i '
  . 'professori, non i vicini, nessuno. Quando lo dici tu, ti guardano come se avessi detto '
  . 'una banalità.',
  'Trovare una persona che se ne accorga, e chiederle come fa.'),

    ],
];
