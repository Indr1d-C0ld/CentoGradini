<?php

declare(strict_types=1);

/**
 * Le storie del quartiere: i copioni presi dall'opera.
 *
 * Arrivano dal controllo del 9 ottobre 2026, dopo una rilettura completa dei
 * 156 riassunti e delle sinossi della serie: sono le situazioni che *Kimagure
 * Orange Road* racconta di continuo e che il gioco non aveva. Ognuna porta i
 * capitoli o gli episodi da cui viene. Il formato è lo stesso di
 * `copioni.php` — vedi la nota in testa a quel file — con due aggiunte:
 *
 *   `potere`  un'opzione che usa un potere davvero, con i suoi rischi: la
 *             vede solo chi ce l'ha, passa per il Segreto (chi è lì può
 *             vedere, e chi vede apre un incidente da coprire) e l'agente
 *             autonomo non la sceglie mai per chi non c'è;
 *   `brillo`, `raffreddore` fra gli effetti: gli stati del regolamento del
 *             1990, che durano il numero di minuti di gioco indicato.
 *
 * Le condizioni nuove: `sera`, `notte`, `raro` (una giornata su dieci),
 * `vigilia:<evento>` (l'evento comincia domani), e più condizioni insieme
 * con `+`.
 */

$scene = static fn (array ...$s): string => json_encode($s, JSON_UNESCAPED_UNICODE);

/** Un'opzione. `potere` vuoto vuol dire un'opzione normale. */
$o = static fn (
    string $k, string $testo, string $prova, int $difficolta, string $ok, string $ko,
    array $effOk, array $effKo, array $peso, string $potere = ''
): array => array_filter([
    'k' => $k, 'testo' => $testo, 'prova' => $prova, 'difficolta' => $difficolta,
    'ok' => $ok, 'ko' => $ko, 'effetti_ok' => $effOk, 'effetti_ko' => $effKo, 'peso' => $peso,
    'potere' => $potere,
], static fn ($v, $k): bool => $k !== 'potere' || $v !== '', ARRAY_FILTER_USE_BOTH);

return [
    'tabella'     => 'copioni',
    'chiave'      => 'ckey',
    'descrizione' => 'copioni presi dall\'opera',
    'righe' => [

// ─────────────────────────────────────────────────────────────────────────
// Il doppio appuntamento: la gag su cui si regge mezza opera (capp. 5, 9,
// 24, 33, 42, 84; TV6, TV17). Kyosuke dice sì a Madoka e a Hikaru per la
// stessa ora, e passa il pomeriggio a correre fra le due.
[
 'ckey' => 'doppio',
 'titolo' => 'Due appuntamenti alla stessa ora',
 'occhiello' => 'Un pomeriggio in via commerciale',
 'premessa' => 'La via è piena di gente, e qualcuno di voi si è appena reso conto '
     . 'di aver detto sì a due persone diverse, per lo stesso pomeriggio, a cinque minuti di '
     . 'distanza. Una aspetta davanti al negozio di dischi. L\'altra all\'ABCB. Nessuna delle due '
     . 'sa dell\'altra.',
 'luogo' => 'commerciale',
 'condizione' => 'sempre',
 'min_cast' => 2, 'max_cast' => 4, 'finestra' => 1500,
 'scene' => $scene(
   [
    'testo' => 'L\'orologio della farmacia dice che è già ora. Fra il negozio di dischi '
        . 'e l\'ABCB ci sono trecento metri e una folla di studenti. Chi è nei guai lo è fino al '
        . 'collo; chi non lo è guarda la scena con un interesse che non fa onore a nessuno.',
    'opzioni' => [
      $o('corri', 'Fare la spola fra i due posti, di corsa', 'sport', 45,
         'Corri, arrivi, sorridi, inventi una commissione, riparti. Funziona per tre giri. Sei senza fiato e ancora viv{o}.',
         'Al secondo giro inciampi davanti al fruttivendolo con mezza via a guardarti. Arrivi in ritardo da tutte e due le parti.',
         ['compostezza' => -1], ['compostezza' => -2, 'malinteso_cast' => 4], ['sport' => 3]),
      $o('confessa', 'Andare dalla prima e dirle la verità', 'cuore', 40,
         'Glielo dici. Ti guarda come si guarda un cretino, e poi ride. Non è contenta, ma è meglio di qualunque bugia.',
         'Cominci a spiegare e ti incarti. Più parli, più sembra che tu lo abbia fatto apposta.',
         ['affetto_cast' => 3, 'malinteso_cast' => -6], ['malinteso_cast' => 8], ['cuore' => 3]),
      $o('copri', 'Coprire chi è nei guai, intrattenendo una delle due', 'dai_suki', 40,
         'Ti metti a chiacchierare come se niente fosse e tieni occupata una delle due per venti minuti. Ti devono un favore enorme.',
         'Ci provi, ma ti scappa una frase di troppo e adesso lei vuole sapere dove sia l\'altra.',
         ['affetto_cast' => 4], ['malinteso_cast' => 6], ['dai_suki' => 3]),
      $o('teletrasporto', 'Essere in due posti nello stesso momento', 'nessuna', 0,
         'Un vicolo, un attimo, e sei dall\'altra parte della via. Poi di nuovo indietro. È la cosa più comoda del mondo, finché qualcuno non conta i minuti.',
         '', ['affetto_cast' => 2], [], ['dai_suki' => 1], 'teletrasporto') + ['solo_primario' => true],
    ],
   ],
   [
    'testo' => 'Tre quarti d\'ora dopo succede quello che doveva succedere: le due persone si '
        . 'incontrano davanti alla vetrina del fotografo, e si guardano, e poi guardano voi.',
    'opzioni' => [
      $o('spiega', 'Spiegare tutto, dall\'inizio', 'cuore', 45,
         'Lo spieghi male ma per intero, e alla fine una delle due ride e l\'altra scuote la testa. È andata.',
         'Lo spieghi e ognuna capisce una cosa diversa. Se ne vanno in due direzioni opposte.',
         ['malinteso_cast' => -10, 'affetto_cast' => 3], ['malinteso_cast' => 10, 'affetto_cast' => -3], ['cuore' => 3]),
      $o('inventa', 'Inventare una storia che regga per tutte e due', 'testa', 50,
         'La storia regge. È così complicata che nessuno ha voglia di contestarla. Per stavolta.',
         'La storia ha un buco, e la persona più sveglia del gruppo ci infila il dito.',
         ['compostezza' => -1], ['malinteso_cast' => 12, 'compostezza' => -1], ['testa' => 2, 'candore' => -1]),
      $o('ridi', 'Riderci sopra con tutti', 'kakko', 40,
         'Ridi per primo, con la faccia di chi non ha niente da nascondere. Ridono anche loro, più o meno.',
         'Ridi da sol{o}. Il suono resta lì, davanti alla vetrina del fotografo.',
         ['affetto_cast' => 2], ['affetto_cast' => -2, 'compostezza' => -1], ['kakko' => 3]),
    ],
   ],
 ),
],

// ─────────────────────────────────────────────────────────────────────────
// Il lavoro all'ABCB (capp. 8, 28, 47, 155; TV5): Madoka lavora al bar di
// nascosto, perché il lavoro agli studenti è vietato, e il giorno in cui la
// scuola lo scopre il Master va dal preside. Un secondo segreto, senza poteri.
[
 'ckey' => 'bancone',
 'titolo' => 'Il turno al bancone',
 'occhiello' => 'Un pomeriggio all\'ABCB',
 'premessa' => 'Il Master ha la schiena bloccata e il bar pieno. Chiede una mano a chi è lì, «solo '
     . 'per un\'ora», e porge un grembiule. Lavorare è vietato agli studenti del Kōryō, e lo sanno '
     . 'tutti. Lo sa anche il professore di educazione fisica, che è appena entrato a prendere un caffè.',
 'luogo' => 'abcb',
 'condizione' => 'sempre',
 'esclusi' => 'master',
 'min_cast' => 2, 'max_cast' => 4, 'finestra' => 1500,
 'scene' => $scene(
   [
    'testo' => 'Il grembiule è ancora caldo di chi l\'ha portato prima. Dietro il bancone c\'è un '
        . 'ordine preciso che si capisce solo lavorandoci dentro, e il Master lo spiega a gesti, '
        . 'una mano sui reni.',
    'opzioni' => [
      $o('cucina', 'Mettersi ai fornelli', 'cucina', 40,
         'Tre panini, due omelette e un curry, e nessuno se ne lamenta. Il Master alza un sopracciglio, che da lui è un applauso.',
         'Bruci il riso. L\'odore arriva fino alla porta, e il professore alza la testa dal giornale.',
         ['affetto_cast' => 3], ['compostezza' => -1, 'calore' => 2], ['cucina' => 3]),
      $o('tavoli', 'Servire ai tavoli col sorriso', 'kakko', 40,
         'Porti i vassoi come se l\'avessi sempre fatto. Due ragazze del primo anno chiedono come ti chiami.',
         'Rovesci un\'aranciata sul tavolo del professore. Lui ti guarda, e ti riconosce.',
         ['affetto_cast' => 2], ['calore' => 3, 'compostezza' => -1], ['kakko' => 3]),
      $o('rifiuta', 'Restituire il grembiule: non vale il rischio', 'nessuna', 0,
         'Lo restituisci. Il Master annuisce senza offendersi, e si arrangia. Ma te lo ricorderà.',
         '', ['affetto_cast' => -2], [], ['testa' => 2, 'cuore' => -1]),
    ],
   ],
   [
    'testo' => 'Il professore si alza, va verso la cassa, e si ferma a guardare chi c\'è dietro il '
        . 'bancone con la faccia di chi sta facendo un conto. Il Master smette di asciugare il bicchiere.',
    'opzioni' => [
      $o('cliente', 'Togliersi il grembiule e fingersi un cliente', 'testa', 45,
         'In tre secondi sei sedut{o} a un tavolo con un menù in mano. Il professore paga e se ne va senza dire niente.',
         'Il grembiule ti resta impigliato al collo. Il professore ti saluta per nome, con un sorriso che non promette niente di buono.',
         ['compostezza' => -1], ['calore' => 4, 'compostezza' => -2], ['testa' => 3]),
      $o('copri', 'Mettersi in mezzo e coprire gli altri', 'dai_suki', 45,
         'Attacchi bottone col professore su una cosa qualunque, e mentre parli gli altri spariscono in cucina. Lui non se ne accorge.',
         'Attacchi bottone, e lui ti chiede cosa ci fai dietro il bancone di un bar.',
         ['affetto_cast' => 5], ['calore' => 3], ['dai_suki' => 3, 'cuore' => 1]),
      $o('ammetti', 'Dire la verità: si dava una mano al Master', 'cuore', 50,
         'Lo dici guardandolo negli occhi. Il professore guarda il Master, guarda la sua schiena, e decide di non aver visto niente.',
         'Lo dici, e lui prende un appunto. Domani se ne parlerà in sala professori.',
         ['affetto_cast' => 4, 'calore' => -3], ['calore' => 6, 'compostezza' => -1], ['cuore' => 3]),
      $o('telecinesi', 'Far cadere una pila di piatti dall\'altra parte del bar', 'nessuna', 0,
         'Un crollo di porcellana in fondo alla sala, e tutti si voltano da quella parte. Quando il professore si rigira, dietro il bancone c\'è solo il Master.',
         '', ['calore' => 2], [], ['dai_suki' => 1], 'telecinesi'),
    ],
   ],
 ),
],

// ─────────────────────────────────────────────────────────────────────────
// La sera prima dell'esame (capp. 9, 38, 48, 68, 119, 127-129): esami da
// recuperare, compiti fatti all'ultimo, e una volta Akane che sostiene
// l'esame al posto di Madoka mentre Kazuya le passa le risposte col pensiero.
[
 'ckey' => 'vigilia',
 'titolo' => 'La sera prima dell\'esame',
 'occhiello' => 'Domani si fa sul serio',
 'premessa' => 'Domani ci sono gli esami e nessuno ha studiato abbastanza. Qualcuno ha proposto di '
     . 'ripassare insieme, il che vuol dire che si parlerà di tutto tranne che del programma. È '
     . 'già sera.',
 'luogo' => null,
 'condizione' => 'vigilia:esami+sera',
 'min_cast' => 2, 'max_cast' => 4, 'finestra' => 1500,
 'scene' => $scene(
   [
    'testo' => 'I quaderni aperti, le matite, una lattina di caffè freddo per ciascuno. Dopo dieci '
        . 'minuti la conversazione è già scivolata su un altro argomento, e qualcuno sta guardando '
        . 'qualcun altro invece del libro.',
    'opzioni' => [
      $o('studia', 'Studiare davvero, e far studiare gli altri', 'testa', 40,
         'Riporti tutti sul programma con una pazienza che non sapevi di avere. A mezzanotte, incredibilmente, avete finito.',
         'Ci provi, ma sei il primo a distrarti. Domani sarà quel che sarà.',
         ['affetto_cast' => 3], ['compostezza' => -1], ['testa' => 3]),
      $o('chiacchiera', 'Lasciar andare la serata dove vuole', 'nessuna', 0,
         'Non studiate niente e ridete parecchio. Domani pagherete, ma stasera è stata una bella sera.',
         '', ['affetto_cast' => 2, 'compostezza' => -1], [], ['dai_suki' => 3, 'testa' => -1]),
      $o('spiega', 'Spiegare a qualcuno la cosa che non ha capito', 'cuore', 35,
         'Glielo spieghi tre volte, con tre esempi diversi, e alla terza fa quella faccia. Vi guardate e sorridete.',
         'Gliela spieghi così male che adesso non la capite più in due.',
         ['affetto_cast' => 6], ['malinteso_cast' => 3], ['cuore' => 2, 'testa' => 1]),
    ],
   ],
   [
    'testo' => 'È tardi. Domani mattina il compito sarà sul banco, chiuso in una busta che il '
        . 'professore tiene nell\'armadio della sala professori. Qualcuno, a voce bassissima, dice '
        . 'che basterebbe dargli un\'occhiata.',
    'opzioni' => [
      $o('dormi', 'Andare a dormire: quel che si sa si sa', 'nessuna', 0,
         'Dormi sette ore. Domattina, in mezzo a facce verdi, sei l\'unic{o} con gli occhi aperti.',
         '', ['compostezza' => 1], [], ['testa' => 1, 'candore' => 1]),
      $o('notte', 'Ripassare fino all\'alba', 'testa', 50,
         'All\'alba sai tutto, e la testa ti ronza come un frigorifero. Ce la farai.',
         'All\'alba non sai niente in più e ti si chiudono gli occhi. È stata una pessima idea.',
         ['compostezza' => -1], ['compostezza' => -2, 'pf' => -1], ['testa' => 2]),
      $o('chiaroveggenza', 'Guardare dentro l\'armadio della sala professori', 'nessuna', 0,
         'Chiudi gli occhi e la busta è lì, aperta sul tavolo del professore che la sta finendo di preparare. Leggi la prima domanda. Poi ti vergogni un po\'.',
         '', ['compostezza' => -1], [], ['testa' => 1], 'chiaroveggenza'),
      $o('telepatia', 'Leggere nella testa di chi ha studiato', 'nessuna', 0,
         'Domani basterà sedersi vicino alla persona giusta e ascoltare. Stanotte, intanto, senti quello che pensa di te. Non è quello che credevi.',
         '', ['malinteso_cast' => 2], [], ['testa' => 1], 'telepatia'),
    ],
   ],
 ),
],

// ─────────────────────────────────────────────────────────────────────────
// La prova di coraggio (capp. 20, 70, 93-94, 124; TV18): Komatsu organizza,
// le coppie si tirano a sorte, e Madoka ha un solo punto debole dichiarato —
// le storie di fantasmi.
[
 'ckey' => 'coraggio',
 'titolo' => 'La prova di coraggio',
 'occhiello' => 'Una notte d\'estate al tempio',
 'premessa' => 'Komatsu ha organizzato una prova di coraggio dietro il tempio: si va a coppie, tirate '
     . 'a sorte con i bastoncini, fino alla lapide in fondo al sentiero e ritorno, al buio. Hatta '
     . 'giura che lassù, l\'anno scorso, ha visto una cosa bianca. Hatta giura un sacco di cose.',
 'luogo' => 'tempio',
 'condizione' => 'estate+notte',
 'esclusi' => 'komatsu,hatta',
 'min_cast' => 2, 'max_cast' => 6, 'finestra' => 1800,
 'scene' => $scene(
   [
    'testo' => 'I bastoncini escono dal pugno di Komatsu uno alla volta. Le coppie sono quelle che '
        . 'sono, e almeno una non è quella che qualcuno avrebbe voluto. Il sentiero entra nel buio '
        . 'dopo dieci passi.',
    'opzioni' => [
      $o('vai', 'Partire con chi ti è toccato, senza storie', 'cuore', 35,
         'Parti. Al terzo passo l\'altra persona ti prende la manica, senza dire niente. Non la lasci.',
         'Parti, ma vi tenete a un metro di distanza per tutto il tempo, rigidi come due pali.',
         ['affetto_cast' => 5], ['malinteso_cast' => 3], ['cuore' => 3]),
      $o('fantasma', 'Proporti come fantasma: nasconderti lungo il sentiero', 'dai_suki', 40,
         'Ti nascondi dietro un pino con un lenzuolo. Le urla che senti valgono l\'intera estate.',
         'Ti nascondi, e dopo dieci minuti al buio da sol{o} sei tu quell{o} che ha paura.',
         ['affetto_cast' => 2, 'calore' => 1], ['compostezza' => -2], ['dai_suki' => 3, 'kakko' => 1]),
      $o('defila', 'Trovare una scusa per restare al tempio', 'nessuna', 0,
         'Resti a guardare le lanterne con chi non ha voluto andare. Non succede niente, ed è piacevole lo stesso.',
         '', ['affetto_cast' => -1], [], ['cuore' => -2, 'testa' => 1]),
    ],
   ],
   [
    'testo' => 'A metà del sentiero qualcosa si muove fra gli alberi. Una cosa bianca, alta, che '
        . 'ondeggia. Potrebbe essere il lenzuolo di qualcuno. Potrebbe non esserlo.',
    'opzioni' => [
      $o('mano', 'Prendere la mano di chi ti sta accanto', 'cuore', 40,
         'La prendi, e l\'altra mano stringe forte. Arrivate alla lapide insieme. Al ritorno nessuno dei due lascia per primo.',
         'Allunghi la mano e prendi un ramo. Urli tu, e fai urlare anche l\'altro.',
         ['affetto_cast' => 7], ['compostezza' => -2, 'malinteso_cast' => 2], ['cuore' => 3]),
      $o('scappa', 'Urlare e correre giù fino al tempio', 'sport', 35,
         'Arrivi giù prima di tutti, senza fiato, e ridi di te stess{o} prima di chiunque altro.',
         'Inciampi su una radice al buio. Arrivi giù con un ginocchio sbucciato e la dignità a pezzi.',
         ['compostezza' => -1], ['pf' => -1, 'compostezza' => -2], ['sport' => 2, 'cuore' => -1]),
      $o('ridi', 'Ridere: è sicuramente Komatsu', 'kakko', 45,
         'Lo dici forte e la cosa bianca smette di muoversi. Da dietro un albero, Komatsu bestemmia a mezza voce.',
         'Lo dici forte, e la cosa bianca continua a muoversi. Komatsu, quello vero, è dietro di te.',
         ['affetto_cast' => 2], ['compostezza' => -2], ['kakko' => 3]),
      $o('fantasmi', 'Far comparire un fantasma vero, per vendetta', 'nessuna', 0,
         'Una figura pallida si alza fra i pini e fluttua verso il sentiero. Le urla arrivano fino al fiume. Domani al Kōryō non si parlerà d\'altro.',
         '', ['calore' => 4], [], ['dai_suki' => 1], 'fantasmi'),
    ],
   ],
 ),
],

// ─────────────────────────────────────────────────────────────────────────
// I giochi del gruppo: l'asso di cuori (cap. 32, lo propone Hikaru all'ABCB),
// le carte con la penitenza sotto i ciliegi (cap. 56) e la scommessa sul bacio
// (cap. 50), che sono di Komatsu. Qui l'organizzatore e' lui.
[
 'ckey' => 'asso',
 'titolo' => 'L\'asso di cuori',
 'occhiello' => 'Un gioco di carte al parco',
 'premessa' => 'Komatsu ha tirato fuori un mazzo di carte e una regola nuova: chi pesca l\'asso di '
     . 'cuori deve fare quello che dice chi ha pescato il re. Hatta ha già capito come barare, e '
     . 'Komatsu ha già capito che Hatta bara.',
 'luogo' => 'parco',
 'condizione' => 'sempre',
 'esclusi' => 'komatsu,hatta',
 'min_cast' => 3, 'max_cast' => 6, 'finestra' => 1500,
 'scene' => $scene(
   [
    'testo' => 'Il mazzo gira. Le carte coperte sul telo, una per ciascuno, e tutti che fanno finta '
        . 'di non guardare quelle degli altri.',
    'opzioni' => [
      $o('gioca', 'Giocare onestamente', 'nessuna', 0,
         'Peschi una carta qualunque. Non sei il re e non sei l\'asso: guardi gli altri soffrire.',
         '', ['affetto_cast' => 2], [], ['candore' => 2]),
      $o('bara', 'Barare, come Hatta ma meglio', 'testa', 50,
         'Ti ritrovi in mano il re senza che nessuno capisca come. Adesso decidi tu.',
         'Ti cade una carta dalla manica. Komatsu ti guarda come un fratello.',
         ['compostezza' => 1], ['malinteso_cast' => 4, 'compostezza' => -1], ['testa' => 2, 'candore' => -2]),
      $o('telecinesi', 'Girare la carta giusta col pensiero', 'nessuna', 0,
         'Un soffio di vento, dicono tutti, e la carta si gira proprio quando serviva. Hai il re.',
         '', ['compostezza' => 1], [], ['dai_suki' => 1], 'telecinesi'),
    ],
   ],
   [
    'testo' => 'Il re ha parlato: chi ha l\'asso di cuori deve dire, davanti a tutti, il nome della '
        . 'persona del gruppo che gli piace di più. Komatsu ha già la penna in mano per scriverlo.',
    'opzioni' => [
      $o('dillo', 'Dirlo, se l\'asso è toccato a te', 'cuore', 50,
         'Lo dici. Il telo diventa silenziosissimo, e poi qualcuno applaude. Non è andata male per niente.',
         'Lo dici, ma il nome che ti esce è un altro. Adesso ci sono due persone che ti guardano.',
         ['affetto_cast' => 6], ['malinteso_cast' => 10], ['cuore' => 3]),
      $o('tratta', 'Trattare la penitenza con il re', 'dai_suki', 40,
         'Contratti come al mercato, e alla fine si accontentano di una canzone. La canti malissimo, e va benissimo.',
         'Contratti, e la penitenza raddoppia.',
         ['affetto_cast' => 3], ['compostezza' => -2], ['dai_suki' => 3]),
      $o('fuga', 'Prendere la rincorsa e scappare', 'sport', 40,
         'Sei già oltre la fontanella quando Komatsu capisce. Nessuno ti inseguirà: non ne vale la pena.',
         'Ti prendono alla fontanella. La penitenza adesso è doppia, e la fanno decidere a Hatta.',
         ['affetto_cast' => -1], ['compostezza' => -2, 'malinteso_cast' => 3], ['sport' => 3]),
    ],
   ],
 ),
],

// ─────────────────────────────────────────────────────────────────────────
// La lettera finita nelle mani sbagliate (capp. 69, 82, 112, 114, 123): il
// diario di scambio, la lettera firmata S.H., il biglietto letto in classe.
[
 'ckey' => 'lettera',
 'titolo' => 'Una lettera nella scarpiera sbagliata',
 'occhiello' => 'Una mattina al Kōryō',
 'premessa' => 'Nella scarpiera all\'ingresso c\'è una busta rosa senza firma. È nel posto di '
     . 'qualcuno, ma non si capisce se sia davvero per quel qualcuno o se chi l\'ha messa abbia '
     . 'sbagliato di una fila. La campanella suona fra cinque minuti, e intorno si è già fatto un capannello.',
 'luogo' => 'liceo',
 'condizione' => 'scuola',
 'min_cast' => 2, 'max_cast' => 5, 'finestra' => 1500,
 'scene' => $scene(
   [
    'testo' => 'La busta passa di mano in mano senza che nessuno la apra, che è un modo di aprirla. '
        . 'La calligrafia è tonda e tremante. Qualcuno dice di riconoscerla.',
    'opzioni' => [
      $o('leggi', 'Aprirla e leggerla', 'testa', 40,
         'La leggi e capisci per chi è davvero, dall\'indizio nella terza riga. Te lo tieni per te.',
         'La leggi ad alta voce prima di capire cosa stai facendo. Ormai l\'hanno sentita tutti.',
         ['compostezza' => -1], ['malinteso_cast' => 8, 'calore' => 1], ['testa' => 2, 'candore' => -1]),
      $o('restituisci', 'Rimetterla al suo posto senza leggerla', 'cuore', 30,
         'La rimetti nella scarpiera e dici agli altri di lasciar perdere. Ti ascoltano, più o meno.',
         'La rimetti a posto, ma tutti hanno visto che l\'avevi in mano tu. Adesso pensano che sia tua.',
         ['affetto_cast' => 3], ['malinteso_cast' => 6], ['cuore' => 2, 'candore' => 1]),
      $o('mostra', 'Farla girare per la classe', 'dai_suki', 35,
         'Fai ridere tutti fino alla prima ora. Non è una cosa di cui andare fier{o}.',
         'Fai ridere tutti, e poi entra la persona che l\'aveva scritta, e smettono tutti insieme.',
         ['affetto_cast' => -2, 'calore' => 1], ['malinteso_cast' => 10, 'affetto_cast' => -4], ['dai_suki' => 3, 'cuore' => -2]),
    ],
   ],
   [
    'testo' => 'All\'intervallo la persona a cui era indirizzata la lettera la sta cercando. E la '
        . 'persona che l\'ha scritta sta cercando di capire chi l\'abbia letta.',
    'opzioni' => [
      $o('verita', 'Dire alla destinataria com\'è andata', 'cuore', 40,
         'Glielo dici, tutto, anche la parte in cui non fai una bella figura. Ti ringrazia, e va a cercare chi l\'ha scritta.',
         'Glielo dici, ma si convince che la lettera sia tua. Arrossisce, e scappa.',
         ['malinteso_cast' => -8, 'affetto_cast' => 3], ['malinteso_cast' => 12], ['cuore' => 3]),
      $o('proteggi', 'Coprire chi l\'ha scritta', 'dai_suki', 45,
         'Racconti una storia in cui la lettera è finita lì per errore, e nessuno ci fa più caso. Chi l\'ha scritta ti guarda grato.',
         'La storia non regge, e ora tutti guardano la persona sbagliata.',
         ['affetto_cast' => 5], ['malinteso_cast' => 8], ['dai_suki' => 3]),
      $o('taci', 'Non dire niente a nessuno', 'nessuna', 0,
         'Non dici niente. La storia si sgonfia da sola, come tutte, lasciando un malinteso o due.',
         '', ['malinteso_cast' => 3], [], ['cuore' => -2]),
    ],
   ],
 ),
],

// ─────────────────────────────────────────────────────────────────────────
// Il Natale che si ripete (cap. 40; TV38): Kyosuke rivive la vigilia più
// volte di fila. I Timeslip della scalinata sono un fenomeno del luogo, e
// questo è il loro episodio.
[
 'ckey' => 'terzo_natale',
 'titolo' => 'Il terzo Natale',
 'occhiello' => 'La vigilia, sui gradini',
 'premessa' => 'È la vigilia di Natale. O meglio: è di nuovo la vigilia di Natale. Qualcuno di voi '
     . 'ha la sensazione precisa di aver già visto quella coppia passare, di aver già sentito quel '
     . 'campanello di bicicletta, di aver già detto questa frase. In cima ai gradini fa un freddo pulito.',
 'luogo' => 'gradini',
 'condizione' => 'giorno:12-24',
 'min_cast' => 2, 'max_cast' => 4, 'finestra' => 1800,
 'scene' => $scene(
   [
    'testo' => 'Il campanello della bicicletta suona di nuovo, alla stessa ora, nella stessa curva. '
        . 'Nessuno sembra accorgersene. Tranne qualcuno.',
    'opzioni' => [
      $o('accorgiti', 'Rendersi conto che il giorno si sta ripetendo', 'testa', 45,
         'Lo capisci, e lo capisci prima di tutti. Hai un vantaggio che nessuno ha mai avuto: sai come va a finire.',
         'Ti viene un dubbio, ma lo scacci. Sarà la stanchezza.',
         ['compostezza' => 1, 'affetto_cast' => 2], [], ['testa' => 3]),
      $o('rifai', 'Dire stavolta la cosa che la prima volta non hai detto', 'cuore', 50,
         'La dici. Questa volta la dici, e la faccia di chi ascolta non è quella della prima volta.',
         'Ci riprovi e non ti esce, esattamente come la prima volta. Forse il giorno si ripete apposta.',
         ['affetto_cast' => 7, 'malinteso_cast' => -5], ['compostezza' => -2], ['cuore' => 3]),
      $o('goditi', 'Lasciare che la sera vada come deve', 'nessuna', 0,
         'La sera va com\'è andata, e non è stata brutta neanche la prima volta.',
         '', ['affetto_cast' => 2], [], ['candore' => 1]),
    ],
   ],
   [
    'testo' => 'Mezzanotte si avvicina, e con lei il momento in cui tutto ricomincia. Per uscire dal '
        . 'cerchio, dice una voce che non sai da dove venga, bisogna fare qualcosa di diverso. In '
        . 'fondo alla scalinata, i gradini luccicano di ghiaccio.',
    'opzioni' => [
      $o('rompi', 'Fare la cosa che non faresti mai', 'cuore', 55,
         'La fai. Mezzanotte passa, e domattina è il 25. Nessuno tranne te sa cosa è costato.',
         'Non trovi il coraggio. A mezzanotte il campanello suona di nuovo nella curva.',
         ['affetto_cast' => 6], ['compostezza' => -2, 'malinteso_cast' => 3], ['cuore' => 3]),
      $o('salta', 'Lasciarsi cadere dai gradini, come nel racconto del timeslip', 'sport', 50,
         'Cadi, ruzzoli, e ti rialzi la mattina di Natale, con un bernoccolo e la sensazione di aver fatto una cosa enorme.',
         'Cadi e basta. Ti fai male, e mezzanotte suona lo stesso.',
         ['pf' => -1], ['pf' => -2, 'compostezza' => -1], ['sport' => 2, 'dai_suki' => 1]),
      $o('aspetta', 'Aspettare mezzanotte tenendosi per mano', 'nessuna', 0,
         'Restate lì. Quando suona, vi accorgete che è il 25, e che non vi siete lasciati la mano.',
         '', ['affetto_cast' => 3], [], ['cuore' => 2]),
    ],
   ],
 ),
],

// ─────────────────────────────────────────────────────────────────────────
// Il mondo senza di te (cap. 63): Kyosuke cade nella nebbia e si ritrova in
// un quartiere dove Hikaru non è mai esistita. Il regolamento del 1990 lo
// cita fra gli «universi alternativi».
[
 'ckey' => 'senza_di_te',
 'titolo' => 'Il mondo senza di te',
 'occhiello' => 'Una nebbia sui gradini',
 'premessa' => 'Sale una nebbia che non c\'era. Scendete i gradini e il quartiere è lo stesso — la '
     . 'via, il bar, la scuola — ma qualcosa non torna. All\'ABCB il terzo sgabello è vuoto. Nessuno '
     . 'ha mai sentito parlare di Hikaru Hiyama.',
 'luogo' => 'gradini',
 'condizione' => 'raro',
 'esclusi' => 'hikaru,yusaku',
 'min_cast' => 2, 'max_cast' => 4, 'finestra' => 1800,
 'scene' => $scene(
   [
    'testo' => 'Chiedete in giro. Il Master non capisce di chi parlate. Yusaku non fa karate, e '
        . 'non sa perché dovrebbe. Madoka è più sola di come la ricordavate.',
    'opzioni' => [
      $o('cerca', 'Cercarla dappertutto, di corsa', 'sport', 45,
         'Giri il quartiere fino a sera. Non la trovi, ma trovi il posto dove avrebbe dovuto esserci, e capisci quanto spazio occupava.',
         'Corri finché non ti si spezza il fiato. La nebbia non si alza.',
         ['affetto_cast' => 3], ['compostezza' => -2], ['sport' => 3]),
      $o('chiedi', 'Chiedere a tutti, uno per uno', 'dai_suki', 40,
         'Nessuno se la ricorda, ma tutti, a sentirne parlare, sorridono. Come se qualcosa dentro di loro la ricordasse lo stesso.',
         'Ti guardano come si guarda chi parla di fantasmi.',
         ['affetto_cast' => 2], ['malinteso_cast' => 3], ['dai_suki' => 3]),
      $o('guarda', 'Fermarsi a guardare come sono gli altri senza di lei', 'cuore', 40,
         'Li guardi, e capisci che ognuno di loro è un po\' meno di quello che era. Anche tu.',
         'Guardi, e non capisci niente. Hai solo freddo.',
         ['affetto_cast' => 5], [], ['cuore' => 3]),
    ],
   ],
   [
    'testo' => 'La nebbia comincia a diradarsi in cima alla scalinata. Si può tornare indietro. Ma '
        . 'qui, almeno per un po\', c\'è una versione del mondo in cui certe cose non sono mai '
        . 'successe.',
    'opzioni' => [
      $o('torna', 'Risalire i gradini nella nebbia', 'nessuna', 0,
         'Sali, e in cima qualcuno grida «darling!» con una voce che conosci. Non lo dice a te, ma non sei mai stat{o} così content{o} di sentirla.',
         '', ['affetto_cast' => 4], [], ['cuore' => 2]),
      $o('resta', 'Restare ancora un po\'', 'cuore', 50,
         'Resti un\'ora, e la passi con chi è rimasto con te. Poi torni, e ve lo porterete dietro in due.',
         'Resti troppo. Quando torni, per un giorno intero ti sembra che qualcuno ti guardi come un estraneo.',
         ['affetto_cast' => 6], ['malinteso_cast' => 8], ['cuore' => 3]),
    ],
   ],
 ),
],

// ─────────────────────────────────────────────────────────────────────────
// Le gite: il mare e l'isola (capp. 18-21; TV18-19), le camere scambiate
// (capp. 44-45, 102). Kyosuke non sa nuotare (cap. 66).
[
 'ckey' => 'isola',
 'titolo' => 'L\'isola davanti alla spiaggia',
 'occhiello' => 'Una gita al mare',
 'premessa' => 'Davanti alla spiaggia c\'è un\'isoletta con un faro, a duecento metri dalla riva. '
     . 'Qualcuno ha detto che ci si arriva a nuoto. Qualcun altro non sa nuotare, e non ha nessuna '
     . 'intenzione di dirlo.',
 'luogo' => 'spiaggia',
 'condizione' => 'evento:mare',
 'min_cast' => 1, 'max_cast' => 6, 'finestra' => 1800,
 'scene' => $scene(
   [
    'testo' => 'Si parte. Chi è già in acqua chiama gli altri. Il mare è più freddo di come sembrava '
        . 'dalla sabbia, e l\'isola più lontana.',
    'opzioni' => [
      $o('nuota', 'Nuotare fino all\'isola', 'nuoto', 40,
         'Arrivi al faro per prim{o}, e ti siedi sugli scogli caldi ad aspettare gli altri. Da qui la spiaggia sembra un altro paese.',
         'A metà strada ti manca il fiato. Qualcuno ti viene a prendere, e ti riporta a riva sotto gli occhi di tutti.',
         ['affetto_cast' => 2], ['compostezza' => -2, 'affetto_cast' => 2], ['nuoto' => 3]),
      $o('riva', 'Restare a riva con chi non va', 'nessuna', 0,
         'Resti sotto l\'ombrellone con chi non nuota. Si chiacchiera di tutto, e il tempo passa meglio che sull\'isola.',
         '', ['affetto_cast' => 1], [], ['cuore' => 1, 'nuoto' => -2]),
      $o('salva', 'Andare a riprendere chi è in difficoltà', 'nuoto', 50,
         'Lo raggiungi, lo tieni a galla, lo riporti indietro. Sulla sabbia nessuno dei due dice niente per un bel pezzo.',
         'Lo raggiungi, ma siete in difficoltà in due. Vi tira fuori il bagnino, e tutta la spiaggia guarda.',
         ['affetto_cast' => 7], ['compostezza' => -2, 'raffreddore' => 720], ['nuoto' => 2, 'cuore' => 2]),
    ],
   ],
   [
    'testo' => 'La sera, alla pensione, le camere sono state assegnate a sorte, e la sorte ha un '
        . 'senso dell\'umorismo. Qualcuno ha sbagliato porta. Qualcun altro fa finta di dormire.',
    'opzioni' => [
      $o('chiarisci', 'Rimettere a posto le camere prima che si capisca male', 'cuore', 40,
         'Bussi alle porte giuste e sistemi tutto con una calma che stupisce anche te. Nessuno saprà mai niente.',
         'Bussi alla porta sbagliata nel momento sbagliato. Domattina a colazione ne parleranno tutti.',
         ['malinteso_cast' => -5], ['malinteso_cast' => 10], ['cuore' => 2, 'testa' => 1]),
      $o('dormi', 'Fingere di dormire e lasciar fare al destino', 'nessuna', 0,
         'Chiudi gli occhi. Al mattino ti svegli in una camera che non è la tua, e non sai come.',
         '', ['malinteso_cast' => 5], [], ['candore' => 1, 'cuore' => -1]),
      $o('tuffo', 'Uscire tutti per un bagno di mezzanotte', 'nuoto', 45,
         'L\'acqua di notte è nera e tiepida, e la luna ci sta dentro. È la cosa più bella della gita.',
         'L\'acqua di notte è gelida. Domattina starnutirai fino al treno.',
         ['affetto_cast' => 5], ['raffreddore' => 1440], ['nuoto' => 2, 'dai_suki' => 2]),
    ],
   ],
 ),
],

// ─────────────────────────────────────────────────────────────────────────
// La montagna: sci e funivia (capp. 44-46; OAV 1), Fuyuyama (capp. 93-94).
[
 'ckey' => 'funivia',
 'titolo' => 'La funivia ferma a metà',
 'occhiello' => 'Una giornata sulla neve',
 'premessa' => 'Si sale in funivia, a quattro per cabina, e a metà della salita la cabina si ferma, '
     . 'oscillando sopra il vuoto, mentre comincia a nevicare forte. L\'altoparlante dice qualcosa '
     . 'che nessuno capisce.',
 'luogo' => 'montagna',
 'condizione' => 'inverno',
 'min_cast' => 1, 'max_cast' => 4, 'finestra' => 1800,
 'scene' => $scene(
   [
    'testo' => 'Fuori, solo bianco. Dentro, il respiro di chi è dentro che appanna i vetri. '
        . 'Qualcuno ha paura dell\'altezza, e lo nasconde malissimo.',
    'opzioni' => [
      $o('calma', 'Tenere tutti calmi', 'testa', 40,
         'Parli piano, spieghi che le funivie si fermano sempre e ripartono sempre. Ti credono, quasi.',
         'Parli piano, ma la cabina oscilla proprio mentre dici «non si muove niente».',
         ['affetto_cast' => 3], ['compostezza' => -1], ['testa' => 2, 'cuore' => 1]),
      $o('vicino', 'Sedersi accanto a chi ha paura', 'cuore', 35,
         'Ti siedi accanto, spalla contro spalla, e non dici niente. Basta così.',
         'Ti siedi accanto, ma la cabina si inclina e finite uno addosso all\'altro. Imbarazzo a mezz\'aria.',
         ['affetto_cast' => 6], ['malinteso_cast' => 4, 'affetto_cast' => 2], ['cuore' => 3]),
      $o('storie', 'Raccontare storie per far passare il tempo', 'dai_suki', 35,
         'Racconti la storia del fantasma di Fuyuyama. Ridono tutti, tranne una persona che si è fatta pallida.',
         'Racconti una storia che nessuno trova divertente, e la funivia non riparte.',
         ['affetto_cast' => 3], ['compostezza' => -1], ['dai_suki' => 3]),
    ],
   ],
   [
    'testo' => 'Passa un\'ora. La neve fuori è diventata tormenta. Ci vuole qualcuno che faccia '
        . 'qualcosa, e tutti guardano tutti.',
    'opzioni' => [
      $o('aspetta', 'Aspettare i soccorsi stretti insieme', 'nessuna', 0,
         'Aspettate stretti, e quando la funivia riparte vi sembra un peccato.',
         '', ['affetto_cast' => 3, 'pf' => -1], [], ['cuore' => 2]),
      $o('teletrasporto', 'Portare tutti giù, al rifugio', 'nessuna', 0,
         'Chiudi gli occhi, e un attimo dopo siete nel rifugio, vicino alla stufa, con la neve ancora sulle spalle. Nessuno sa spiegare come. Quasi nessuno.',
         '', ['calore' => 3, 'affetto_cast' => 3], [], ['cuore' => 1], 'teletrasporto') + ['solo_primario' => true],
      $o('canta', 'Cantare a squarciagola per non pensarci', 'musica', 40,
         'Canti, e alla seconda strofa cantano tutti. Il tempo passa in un attimo.',
         'Canti stonatissimo. Qualcuno ride, qualcuno si tappa le orecchie, e la funivia ancora non riparte.',
         ['affetto_cast' => 4], ['compostezza' => -1], ['musica' => 3]),
    ],
   ],
 ),
],

// ─────────────────────────────────────────────────────────────────────────
// Dai nonni, per l'Obon (capp. 71-73, 107-110; TV34): il nonno con i suoi
// poteri, il pappagallo, l'orologio, e il paese di campagna.
[
 'ckey' => 'nonni',
 'titolo' => 'Il treno per il paese dei nonni',
 'occhiello' => 'L\'Obon, in stazione',
 'premessa' => 'La stazione è piena di valigie e di famiglie che tornano al paese. Sul binario c\'è '
     . 'un vecchio con un bastone e uno sguardo troppo sveglio, che sembra conoscere tutti. Dice di '
     . 'essere il nonno di qualcuno, e invita chiunque sia lì a fare il viaggio con lui.',
 'luogo' => 'stazione',
 'condizione' => 'evento:obon',
 'min_cast' => 2, 'max_cast' => 5, 'finestra' => 1800,
 'scene' => $scene(
   [
    'testo' => 'Il treno locale va piano, fra le risaie. Il vecchio racconta storie che non possono '
        . 'essere vere, e le racconta come se lo fossero.',
    'opzioni' => [
      $o('ascolta', 'Ascoltare le sue storie fino in fondo', 'testa', 35,
         'Lo ascolti, e a un certo punto capisci che non sta inventando niente. Lui ti fa l\'occhiolino.',
         'Ti addormenti a metà della terza storia. Quando ti svegli, il vecchio ti guarda divertito.',
         ['affetto_cast' => 3], [], ['testa' => 2, 'cuore' => 1]),
      $o('finestrino', 'Guardare fuori, in silenzio, con qualcuno accanto', 'cuore', 30,
         'Le risaie, un airone, un temporale lontano. Non dite niente per un\'ora, ed è un\'ora buona.',
         'Volevi dire una cosa e non la dici. Il paesaggio continua a scorrere.',
         ['affetto_cast' => 5], [], ['cuore' => 3]),
      $o('chiacchiera', 'Fare amicizia con tutto il vagone', 'dai_suki', 35,
         'Al terzo paese conosci metà vagone, e una signora ti ha dato delle prugne salate.',
         'Parli troppo, e una famiglia cambia vagone.',
         ['affetto_cast' => 3], ['compostezza' => -1], ['dai_suki' => 3]),
    ],
   ],
   [
    'testo' => 'Al paese, la casa del vecchio è piena di cose strane: un pappagallo che parla, un '
        . 'orologio da taschino che non ticchetta, una porta chiusa a chiave. «Non aprite quella '
        . 'porta», dice, e va a dormire.',
    'opzioni' => [
      $o('porta', 'Aprire la porta chiusa', 'testa', 50,
         'Dentro c\'è solo un vecchio album di fotografie: una ragazza che ride, che somiglia a qualcuno che conosci. Lo richiudi in silenzio.',
         'La porta si apre di colpo e il pappagallo comincia a strillare i pensieri di tutti. Una notte lunghissima.',
         ['affetto_cast' => 3], ['malinteso_cast' => 8, 'compostezza' => -1], ['testa' => 2, 'candore' => -1]),
      $o('obbedisci', 'Andare a dormire, come ha detto', 'nessuna', 0,
         'Dormi con le cicale fuori dalla finestra. In città non dormi mai così.',
         '', ['compostezza' => 2], [], ['candore' => 2]),
      $o('pappagallo', 'Fare amicizia col pappagallo', 'dai_suki', 40,
         'Il pappagallo ti guarda di traverso e poi ripete, ad alta voce, il nome della persona a cui stavi pensando. Per fortuna dormono tutti.',
         'Il pappagallo ripete ad alta voce il nome a cui stavi pensando. Non dormivano tutti.',
         ['affetto_cast' => 2], ['malinteso_cast' => 10], ['dai_suki' => 2]),
    ],
   ],
 ),
],

// ─────────────────────────────────────────────────────────────────────────
// Tanabata (TV14; FAQ 60): le strisce di carta coi desideri appese al bambù.
[
 'ckey' => 'tanabata',
 'titolo' => 'Le strisce di carta',
 'occhiello' => 'La sera di Tanabata',
 'premessa' => 'Lungo la via commerciale hanno legato i bambù ai lampioni, e ci sono le strisce di '
     . 'carta colorata per scrivere un desiderio. Si scrive, si appende, e si fa finta di non '
     . 'leggere quelli degli altri.',
 'luogo' => 'commerciale',
 'condizione' => 'evento:tanabata+sera',
 'min_cast' => 2, 'max_cast' => 6, 'finestra' => 1800,
 'scene' => $scene(
   [
    'testo' => 'La penna passa di mano in mano. Qualcuno scrive di fretta e piega la striscia due '
        . 'volte; qualcuno ci mette dieci minuti per tre parole.',
    'opzioni' => [
      $o('sincero', 'Scrivere il desiderio vero', 'cuore', 40,
         'Lo scrivi senza girarci intorno, e lo appendi in alto, dove non arriva nessuno. Ti senti più legger{o}.',
         'Lo scrivi, e mentre lo appendi la striscia si gira. Qualcuno l\'ha letto.',
         ['compostezza' => 1], ['malinteso_cast' => 6, 'compostezza' => -1], ['cuore' => 3]),
      $o('scherzo', 'Scrivere una cosa buffa per far ridere', 'dai_suki', 30,
         'Scrivi «che Komatsu trovi la fidanzata». Ridono tutti, e Komatsu più di tutti.',
         'Lo scrivi, e chi lo legge non lo trova affatto buffo.',
         ['affetto_cast' => 3], ['affetto_cast' => -2], ['dai_suki' => 3]),
      $o('niente', 'Lasciare la striscia bianca', 'nessuna', 0,
         'Appendi una striscia bianca. È anche quello un desiderio: che non succeda niente di troppo.',
         '', [], [], ['candore' => 1, 'cuore' => -1]),
    ],
   ],
   [
    'testo' => 'Più tardi le strisce sventolano sotto i lampioni, e qualcuno passa lentamente lungo '
        . 'i bambù leggendo quelle degli altri, come per caso. Su una c\'è un nome che conosci.',
    'opzioni' => [
      $o('leggi', 'Leggerle tutte', 'testa', 40,
         'Le leggi e capisci un sacco di cose, alcune che non volevi capire. Te le tieni.',
         'Le leggi e capisci male quella più importante. Lo scoprirai fra un mese.',
         [], ['malinteso_cast' => 8], ['testa' => 2, 'candore' => -1]),
      $o('stacca', 'Staccare la tua prima che la legga qualcuno', 'sport', 40,
         'Salti, la prendi, la infili in tasca. Nessuno l\'ha letta. Forse.',
         'Salti, la manchi, e strappi quella di un altro. Che è lì e ti guarda.',
         ['compostezza' => 1], ['malinteso_cast' => 6, 'compostezza' => -1], ['sport' => 3]),
      $o('passeggia', 'Non leggere niente e passeggiare con qualcuno', 'nessuna', 0,
         'Camminate fino in fondo alla via senza guardare i bambù. Il desiderio di qualcuno, forse, si è appena avverato.',
         '', ['affetto_cast' => 3], [], ['cuore' => 2]),
    ],
   ],
 ),
],

// ─────────────────────────────────────────────────────────────────────────
// La festa delle bambole a casa Hiyama (cap. 49): il sake dolce, e i poteri
// dei Kasuga che vanno storti — Kurumi, con un colpo al collo, fa partire a
// Kyosuke i poteri sbagliati.
[
 'ckey' => 'bambole',
 'titolo' => 'Il sake dolce',
 'occhiello' => 'La festa delle bambole, a casa Hiyama',
 'premessa' => 'A casa di Hikaru le bambole sono già sui ripiani rossi, e la porta è aperta come '
     . 'sempre. C\'è il sake dolce delle feste, quello che «non è alcol, è per le bambine». Lo dice '
     . 'la madre di Hikaru, versandone un altro bicchiere.',
 'luogo' => 'casa_hiyama',
 'condizione' => 'evento:hinamatsuri',
 'min_cast' => 2, 'max_cast' => 6, 'finestra' => 1800,
 'scene' => $scene(
   [
    'testo' => 'Il sake è dolce davvero, e caldo, e dopo il secondo bicchiere le bambole sui ripiani '
        . 'cominciano a sembrare simpaticissime.',
    'opzioni' => [
      $o('bevi', 'Berne, che è festa', 'nessuna', 0,
         'Ne bevi. È buonissimo. La stanza diventa morbida ai bordi.',
         '', ['brillo' => 180, 'affetto_cast' => 3], [], ['dai_suki' => 2, 'testa' => -1]),
      $o('rifiuta', 'Rifiutare con un sorriso', 'testa', 35,
         'Rifiuti con garbo, e resti l\'unic{o} lucid{o} della stanza. Ti tornerà utile.',
         'Rifiuti, ma la madre di Hikaru non accetta un no. Il bicchiere è pieno di nuovo.',
         [], ['brillo' => 120], ['testa' => 3]),
      $o('servi', 'Fare il giro a riempire i bicchieri degli altri', 'dai_suki', 30,
         'Servi tutti come se fossi di casa. Ti adorano, e a te non resta il tempo di bere.',
         'Servi tutti, e tutti ricambiano. Ne hai bevuti più degli altri.',
         ['affetto_cast' => 4], ['brillo' => 240], ['dai_suki' => 3]),
    ],
   ],
   [
    'testo' => 'Qualcuno ride troppo forte. Un cuscino si solleva da solo e ricade. Nessuno sembra '
        . 'accorgersene, tranne chi deve preoccuparsene.',
    'opzioni' => [
      $o('controlla', 'Tenere a bada sé stessi, e gli altri', 'testa', 45,
         'Respiri, ti concentri, e tieni tutto a terra: cuscini, bicchieri, la tua bocca. Nessuno ha visto niente.',
         'Ti concentri, e un bicchiere si alza da solo davanti a tutti. Una risata isterica salva la situazione, più o meno.',
         ['compostezza' => -1], ['calore' => 3, 'compostezza' => -2], ['testa' => 3]),
      $o('confessa', 'Dire a qualcuno una cosa che a mente lucida non diresti', 'cuore', 35,
         'La dici. Domattina ne ricorderete metà a testa, e le due metà non combaceranno. Ma è stata detta.',
         'La dici alla persona sbagliata, convint{o} che sia quella giusta.',
         ['affetto_cast' => 6], ['malinteso_cast' => 12], ['cuore' => 3]),
      $o('aria', 'Uscire a prendere aria', 'nessuna', 0,
         'Fuori è freddo e pulito. Ti siedi sui gradini di casa Hiyama finché la testa non si ferma.',
         '', ['compostezza' => 1], [], ['testa' => 1, 'cuore' => -1]),
    ],
   ],
 ),
],

// ─────────────────────────────────────────────────────────────────────────
// Il pesce d'aprile di Komatsu (cap. 104): il giorno in cui le bugie sono
// permesse, e il giorno dopo nessuno si ricorda quali.
[
 'ckey' => 'pesce_aprile',
 'titolo' => 'Il giorno delle bugie',
 'occhiello' => 'Il primo d\'aprile',
 'premessa' => 'Komatsu ha deciso che oggi si dicono solo bugie. Ha già detto a mezza via che '
     . 'Hatta si è fidanzato, a Hatta che il Master chiude l\'ABCB, e al Master che Komatsu è un '
     . 'ragazzo serio. Adesso tocca a voi.',
 'luogo' => 'commerciale',
 'condizione' => 'evento:pesce_aprile',
 'esclusi' => 'komatsu',
 'min_cast' => 2, 'max_cast' => 6, 'finestra' => 1500,
 'scene' => $scene(
   [
    'testo' => 'Le bugie girano più veloci delle voci vere, perché tutti hanno voglia di crederci. '
        . 'Komatsu vi guarda come un allenatore.',
    'opzioni' => [
      $o('enorme', 'Dirne una enorme, con la faccia più seria del mondo', 'kakko', 45,
         'La dici e ci credono tutti. Per mezz\'ora sei la persona più potente del quartiere.',
         'La dici, e ti scappa da ridere a metà frase. Nessuno ci crede, e tutti ridono di te.',
         ['affetto_cast' => 2, 'malinteso_cast' => 4], ['compostezza' => -1], ['kakko' => 3]),
      $o('credi', 'Credere a tutto quello che ti dicono', 'nessuna', 0,
         'Ci credi. A tutto. Sei il bersaglio perfetto, e anche il più divertente.',
         '', ['affetto_cast' => 3, 'malinteso_cast' => 6], [], ['candore' => 3]),
      $o('smaschera', 'Smascherare le bugie degli altri', 'testa', 40,
         'Le smonti una per una con calma. Komatsu ti guarda con rispetto, e un po\' di rancore.',
         'Smascheri una cosa che era vera. Adesso non ti crede più nessuno.',
         ['malinteso_cast' => -4], ['malinteso_cast' => 6], ['testa' => 3]),
    ],
   ],
   [
    'testo' => 'Il giorno dopo nessuno si ricorda più quali erano le bugie. Una, in particolare, '
        . 'continua a girare, e qualcuno la sta prendendo sul serio.',
    'opzioni' => [
      $o('chiarisci', 'Andare a chiarire quella che conta', 'cuore', 40,
         'Trovi chi ci ha creduto e glielo dici. Ride, sollevato. Ti abbraccia, anche.',
         'Trovi chi ci ha creduto e glielo dici, ma adesso pensa che la bugia fosse la smentita.',
         ['malinteso_cast' => -10, 'affetto_cast' => 3], ['malinteso_cast' => 6], ['cuore' => 3]),
      $o('lascia', 'Lasciarla girare: si spegnerà da sola', 'nessuna', 0,
         'La lasci girare. Fra un mese ne sarà rimasto un pezzo, come succede alle voci.',
         '', ['malinteso_cast' => 5], [], ['cuore' => -2]),
      $o('ridi', 'Farci sopra un\'altra bugia, ancora più grossa', 'dai_suki', 45,
         'La nuova bugia è così grossa che fa dimenticare la vecchia. Komatsu ti nomina suo erede.',
         'La nuova bugia si somma alla vecchia. Adesso sono due.',
         ['affetto_cast' => 2], ['malinteso_cast' => 8], ['dai_suki' => 3]),
    ],
   ],
 ),
],

// ─────────────────────────────────────────────────────────────────────────
// La Moebius e i lenti (capp. 4, 12, 34, 54): la discoteca di Madoka prima
// di Kyosuke, dove si entra solo in coppia.
[
 'ckey' => 'lenti',
 'titolo' => 'Quando mettono i lenti',
 'occhiello' => 'Una sera alla Moebius',
 'premessa' => 'La pista è piena e la musica ti arriva nello stomaco. Al bancone c\'è chi è venuto '
     . 'in coppia e chi in coppia ci è entrato e basta. Il DJ ha appena detto, al microfono, che '
     . 'fra un pezzo mette i lenti.',
 'luogo' => 'moebius',
 'condizione' => 'sera',
 'min_cast' => 2, 'max_cast' => 6, 'finestra' => 1500,
 'scene' => $scene(
   [
    'testo' => 'Luci che girano, una canzone che conoscono tutti, gente che balla male con '
        . 'convinzione. Si può ballare, o si può guardare.',
    'opzioni' => [
      $o('balla', 'Buttarsi in pista', 'sport', 35,
         'Balli, e balli bene, o almeno con abbastanza convinzione da sembrarlo. Qualcuno ti guarda.',
         'Balli, e a un certo punto pesti un piede. Il piede sbagliato.',
         ['affetto_cast' => 3], ['compostezza' => -1, 'malinteso_cast' => 2], ['sport' => 2, 'kakko' => 1]),
      $o('bancone', 'Restare al bancone a guardare', 'nessuna', 0,
         'Guardi la pista con un bicchiere in mano. Vedi chi balla con chi, e lo ricorderai.',
         '', [], [], ['testa' => 2, 'dai_suki' => -1]),
      $o('invita', 'Invitare qualcuno a ballare il prossimo', 'cuore', 40,
         'Lo chiedi ad alta voce, sopra la musica. Ti dicono di sì.',
         'Lo chiedi, ma la musica copre metà della frase. Ti rispondono «cosa?», due volte.',
         ['affetto_cast' => 5], ['compostezza' => -1], ['cuore' => 3]),
    ],
   ],
   [
    'testo' => 'Le luci si abbassano. Parte il primo lento, e la pista si svuota e si riempie di '
        . 'coppie in dieci secondi. Chi resta in mezzo da solo si nota.',
    'opzioni' => [
      $o('stretti', 'Ballare stretti, senza parlare', 'cuore', 40,
         'Tre minuti e mezzo. Non vi dite niente, e non ce n\'è bisogno.',
         'Siete rigidi come due manici di scopa, e lo sapete tutti e due. Ridete, alla fine.',
         ['affetto_cast' => 8], ['affetto_cast' => 2, 'compostezza' => -1], ['cuore' => 3]),
      $o('scambia', 'Cambiare partner a metà canzone', 'dai_suki', 40,
         'Fai girare le coppie come in una quadriglia, e tutti finiscono con chi volevano. Quasi tutti.',
         'Lo scambio finisce male: qualcuno resta senza nessuno in mezzo alla pista.',
         ['affetto_cast' => 3], ['malinteso_cast' => 10], ['dai_suki' => 3]),
      $o('fuori', 'Uscire a prendere aria', 'nessuna', 0,
         'Fuori, sulla scala, la musica arriva attutita. Qualcuno esce dopo di te, e si siede vicino.',
         '', ['affetto_cast' => 3], [], ['cuore' => 1, 'kakko' => -1]),
    ],
   ],
 ),
],

// ─────────────────────────────────────────────────────────────────────────
// Due biglietti per il cinema (capp. 6, 55, 70, 115, 119, 128, 148).
[
 'ckey' => 'due_biglietti',
 'titolo' => 'Due biglietti',
 'occhiello' => 'Un pomeriggio al cinema',
 'premessa' => 'Qualcuno aveva due biglietti e una persona in testa a cui darli. Poi i biglietti '
     . 'sono passati di mano un paio di volte, e adesso in fila davanti al cinema c\'è più gente di '
     . 'quanta ne servisse.',
 'luogo' => 'cinema',
 'condizione' => 'sempre',
 'min_cast' => 2, 'max_cast' => 4, 'finestra' => 1500,
 'scene' => $scene(
   [
    'testo' => 'Sala uno, un film d\'azione americano. Sala due, una storia d\'amore giapponese. '
        . 'Bisogna decidere, e la scelta dice già tutto.',
    'opzioni' => [
      $o('azione', 'Il film d\'azione, senza discutere', 'kakko', 30,
         'Esplosioni, inseguimenti, e nessun momento imbarazzante. Esci soddisfatt{o}.',
         'Il film è brutto e lo sanno tutti. Nessuno ha il coraggio di dirlo per primo.',
         ['affetto_cast' => 2], ['compostezza' => -1], ['kakko' => 3]),
      $o('amore', 'La storia d\'amore', 'cuore', 35,
         'La scegli e nessuno protesta. È un film che guarderete in silenzio, tutti un po\' troppo attenti.',
         'La scegli, e si capisce benissimo perché. Qualcuno sorride di lato.',
         ['affetto_cast' => 4], ['malinteso_cast' => 5], ['cuore' => 3]),
      $o('decidi', 'Lasciar decidere agli altri', 'nessuna', 0,
         'Decidono gli altri, e ti siedi dove capita. Capita accanto a qualcuno.',
         '', ['affetto_cast' => 2], [], ['candore' => 1]),
    ],
   ],
   [
    'testo' => 'Al buio, a dieci centimetri da qualcuno. Sullo schermo succede una cosa importante, '
        . 'e sul bracciolo in mezzo c\'è spazio per un gomito solo.',
    'opzioni' => [
      $o('mano', 'Lasciare la mano sul bracciolo, e aspettare', 'cuore', 45,
         'Per metà del secondo tempo, sul bracciolo, ci sono due mani. Nessuno dei due guarda.',
         'La mano resta lì da sola fino ai titoli di coda.',
         ['affetto_cast' => 7], ['compostezza' => -1], ['cuore' => 3]),
      $o('commenta', 'Commentare il film a bassa voce', 'dai_suki', 35,
         'Fai ridere tutta la fila con due battute azzeccate. Anche quelli che non conosci.',
         'Ti zittisce la signora davanti. Due volte.',
         ['affetto_cast' => 3], ['compostezza' => -1], ['dai_suki' => 3]),
      $o('dormi', 'Addormentarsi', 'nessuna', 0,
         'Ti addormenti a metà, con la testa che scivola su una spalla. Qualcuno non ti sveglia.',
         '', ['affetto_cast' => 3, 'malinteso_cast' => 3], [], ['candore' => 2, 'testa' => -1]),
    ],
   ],
 ),
],

    ],
];
