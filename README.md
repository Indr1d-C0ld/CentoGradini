# Cento Gradini

**Un gioco di ruolo da browser, multigiocatore e persistente, ambientato nel
quartiere di *Kimagure Orange Road*.** Si gioca un ragazzo o una ragazza del
1987 in una cittadina giapponese di provincia, e il problema non è salvare il
mondo: è arrivare a fine settimana senza che nessuno abbia capito cosa sai
fare, e senza aver rovinato l'unica cosa a cui tieni per non aver detto una
frase al momento giusto.

Il quartiere va avanti anche quando non ci sei. L'orologio scorre, il tempo
cambia, la gente si sposta, e quello che hai fatto ieri davanti a tre persone
sta già circolando in una versione che non riconosceresti.

> Progetto amatoriale e senza scopo di lucro, non affiliato né autorizzato.
> *Kimagure Orange Road* è di **Izumi Matsumoto** e degli aventi diritto.

- Documento di progetto: [`docs/PROGETTO.md`](docs/PROGETTO.md)
- Registro delle fonti: [`docs/FONTI.md`](docs/FONTI.md)
- La rilettura dell'opera: [`docs/CANONE.md`](docs/CANONE.md)

> **Cosa non trovate qui.** Questo repository contiene il nostro codice e basta.
> Il regolamento del 1990 e la sua traduzione italiana, il corpus di ricerca e le
> fotografie di Izumi Matsumoto hanno altri autori: la GPL3 copre quello che
> abbiamo scritto noi e non ci autorizza a ridistribuire il lavoro di altri.
> `docs/FONTI.md` dice da dove viene ogni cosa, cosi' chi vuole puo' risalirci.

---

## Da dove viene

Nel 1990, su una BBS americana, qualcuno che si firmava «Totoro Hunter Leto II»
pubblicò *Whimsical Orange Road: The Role-Playing Game v3.0*: un gioco di ruolo
amatoriale su *Kimagure Orange Road*, battuto a macchina, con le statistiche dei
personaggi dichiarate dall'autore stesso «stime azzardate».

Questo progetto è la trasposizione di quel regolamento in un gioco da browser —
ma riscritta, non ricopiata. Quello che il 1990 aveva azzeccato l'abbiamo tenuto:
soprattutto **l'idea che i poteri siano un problema e non una risorsa**, che è
l'unico punto in cui quel gioco coincideva perfettamente con l'opera. Tutto il
resto è stato rifatto rileggendo il manga: tutti e 156 i capitoli, la FAQ storica
della serie, le voci giapponesi, due siti di appassionati. Cosa ne è uscito, e
dove le due cose divergono, sta in [`docs/CANONE.md`](docs/CANONE.md).

---

## Che gioco è

Non si combatte, non si sale di livello, non c'è niente da conquistare. Ci sono
tre cose che si accumulano — il **Controllo** sui propri poteri, i **legami**
con le persone e i **ricordi** — e una che non si accumula ma si subisce: quello
che **si dice di te**, cioè le voci, che nessuno controlla e ognuno racconta a
modo suo. E una sola cosa si può perdere davvero: il posto dove vivi.

Il gioco sta in piedi su quattro pilastri, e sono intrecciati apposta.

### 1. Il Segreto

Alla creazione si sceglie: **esper o non esper**. Non è un tiro di fortuna, ed
è una delle decisioni fondanti del progetto — Madoka e Hikaru reggono tutta
l'opera senza avere un solo potere, e un gioco che li rendesse personaggi di
serie B tradirebbe il libro.

Chi sceglie i poteri gioca il Segreto dal lato di chi ce l'ha. Usarli è facile;
usarli senza che nessuno se ne accorga, no.

Ogni volta che usi un potere il motore decide chi se n'è accorto, e la
probabilità la muovono cinque cose: la **vistosità** del potere (far sparire una
persona non è spostare una matita), l'**attenzione** di chi guarda, il **buio**,
la **pioggia**, e il tuo **Controllo** — che dimezza, ma non annulla mai.

Chi si accorge di qualcosa non capisce subito: si porta a casa un'**anomalia**,
una riga sul suo taccuino. Tre anomalie coerenti sulla stessa persona, un tiro di
Intuizione, e quella persona **ha capito**. Due persone che hanno capito — fra
quelle che abitano ancora nel quartiere — e la tua famiglia trasloca: nel manga
succede davvero, ed è la sola punizione del gioco. Non muori, te ne vai; e dopo
un paio di giorni di gioco **torni**, in stazione, *trasferito di recente*. Ti
porti dietro poteri, Controllo, carattere e l'album dei ricordi; lasci i legami,
i club, e chi aveva capito, che adesso ha solo una storia vecchia da raccontare.
E se ignori un incidente invece di coprirlo, il tempo decide per te: il silenzio
è una risposta, e il testimone si tiene la sua anomalia.

La valvola di sfogo è **confidarsi**. Dieci persone che sanno perché gliel'hai
detto tu non spostano un mobile; due che l'hanno scoperto da sole sì. Fidarsi di
qualcuno è, meccanicamente, il modo di restare.

E se nel posto dove sei c'è qualcuno che sa **spegnere i poteri**, il tuo potere
può semplicemente non partire. Nel canone lo sa fare un bambino di otto anni, ed
è il motivo per cui è il più forte della famiglia.

**E dall'altra parte?** Chi non ha poteri non gioca una versione ridotta: gioca
il lato opposto dello stesso sistema. Ha più punti abilità da distribuire e un
tratto in più, l'**Intuizione** per collegare le anomalie che ha annotato, il
**taccuino** dove segnarle, e quattro punti in più che possono finire nel
Dai-suki — l'abilità con cui le voci passano di bocca in bocca. Il non-esper è
quello che *può capire*, ed è la ragione per cui l'esper ha paura.

### 2. I legami, su due assi

Le relazioni hanno due numeri, e sono cose diverse:

| | |
|:---|:---|
| **Affetto** | da −100 a +100. Quanto A tiene a B — e non è quanto B tiene ad A |
| **Fraintendimento** | da 0 a 100. Quanto A crede di B qualcosa che non è vero |

Il fraintendimento **non nasce dalle bugie**: nasce dal fatto che i gesti hanno
più di una lettura. Quando fai un gesto verso qualcuno, il motore non aggiorna
solo il vostro legame — **distribuisce una lettura a ogni presente**. Chi è
distratto capisce male e se lo porta dietro; chi è innamorato di uno dei due ha
capito benissimo, ed è proprio per questo che gli brucia. Malinteso e gelosia
sono due meccaniche distinte, apposta.

E **il fraintendimento non decade col tempo**. Il tempo non aggiusta niente:
serve una conversazione, che si tira sul Cuore, costa Compostezza e **può
peggiorare le cose**. È l'unica
regola del gioco che non ammette eccezioni, ed è il motivo per cui il triangolo
di *Kimagure Orange Road* dura diciotto volumi invece di due.

Poi c'è la confessione, con due prove in fila — riuscire a dirlo, e la risposta —
e il **cappello di paglia rosso**, che esiste in un esemplare solo per server e
passa di mano.

### 3. Il quartiere, e le voci

Il quartiere è **ventidue luoghi** con orari, stagionalità e tempi di percorrenza
veri, disegnati su una carta. Il liceo è chiuso in agosto, la spiaggia esiste
solo d'estate, il luna park apre alle dieci, e la grande scalinata non la fa
quasi nessuno — c'è una scaletta più comoda dietro la collina, ed è per questo
che in cima si riesce sempre a stare da soli.

Ma il vero collante del multigiocatore sono le **voci**, e la scelta che le
regge è questa: **il testo di una voce non esiste a database**. Esistono un
fatto — chi, cosa, dove, quando — e tante versioni soggettive quante sono le
persone che ne hanno sentito parlare, ognuna con una *precisione* e un *tono*.
La frase si ricostruisce nel momento in cui la leggi, e a ogni passaggio di bocca
si perde qualcosa nell'ordine in cui lo perde la gente vera:

```
p=95   Kyosuke Kasuga ha spostato delle cose col pensiero davanti a tutti
       all'ABCB.
p=70   Kyosuke ha fatto una cosa che non si spiega all'ABCB.
p=45   Uno del terzo anno ha fatto qualcosa che nessuno sa spiegare in uno di
       quei posti dove si ritrovano tutti.
p=8    Un ragazzo si è comportato in un modo strano, o almeno così dicono.
```

Il nome se ne va per primo, ed è la perdita che fa più danno: da quel momento
può essere chiunque del terzo anno. Il tono non torna mai verso lo zero — si
polarizza, perché è così che funziona una scuola.

Le voci hanno bisogno di gambe, e le gambe sono i **tredici abitanti canonici**
che vivono nel quartiere per conto loro: Kyosuke, Madoka, Hikaru, le gemelle, il
Master dell'ABCB e gli altri. Non sono comparse — sono personaggi come i
giocatori, quindi vedono, annotano, ricevono gesti ed entrano nel cast degli
episodi. Ognuno ha il suo giro, che il calendario scolastico interrompe quando è
ora di lezione e il club interrompe nel pomeriggio.

I **dieci club** sono il secondo canale di propagazione, e l'unico che non passa
dalla geografia: due iscritti si parlano anche se non si incrociano mai. È così
che il quartiere raggiunge chi gioca poco.

E ci sono i due modi di scrivere agli altri, opposti per costruzione: la
**bacheca**, pubblica, legata a un luogo e a scadenza — quello che appendi lo
legge chiunque passi, e fa nascere una voce; e i **biglietti**, che *non
arrivano*: si lasciano in un posto, e se il destinatario non ci passa restano lì.
Entrambi si firmano con il nome che si vuole.

### 4. Gli episodi

Un episodio è una storia breve che il quartiere **apre da sé** quando le
condizioni ci sono: la stagione giusta, il posto giusto, l'acquazzone, una
ricorrenza del calendario, o un sospetto già acceso su qualcuno. Chi si trova lì
entra nel cast.

Poi si va a scene, e a ogni scena ciascuno sceglie. Le scelte muovono legami,
malintesi, Compostezza e il **calore** dei posti — in su, e anche in giù: certe
scelte servono a calmare le acque — cose **vere** del mondo, non punteggi finti.
Più una scelta è difficile, meno spesso riesce. Chi non si fa vivo
entro la finestra non blocca la storia: un **agente autonomo** sceglie per lui
pesando le opzioni sulla sua scheda, così un timido resta timido anche quando a
giocarlo è il motore.

Quello che resta finisce nell'**album dei ricordi**, ognuno con la sua
illustrazione generata dal luogo, dalla stagione, dall'ora e dal tempo che
faceva. E si può **portare via**: il diario completo di un personaggio si
esporta in Markdown, che si legge anche fra vent'anni senza il gioco e senza il
database.

---

## L'ambientazione

### L'«eterno 1987»

Il calendario è vero — l'anno scolastico giapponese 1987-88, dal 6 aprile al 5
aprile — ma **non finisce mai**: arrivato in fondo ricomincia. Feste nazionali,
tre trimestri, il sabato a scuola, il compleanno dell'Imperatore il 29 aprile,
ventisei giornate che valgono un episodio.

Il tempo di gioco scorre a **1:4**: un'ora reale è quattro ore nel quartiere, un
giorno reale è quattro giorni. Chi gioca mezz'ora la sera vede passare mezza
giornata, e chi manca una settimana torna un mese dopo.

Il triangolo Kyosuke/Madoka/Hikaru **non si risolve mai**. È un vincolo di
progetto, non un limite temporaneo: è il motivo per cui l'opera dura, e un gioco
persistente che lo sciogliesse si spegnerebbe da solo.

### Il meteo, e le due ore

Il tempo che fa è una **funzione pura** di seme e istante: nessuna riga a
database, nessuno stato da far avanzare, e due richieste ravvicinate vedono lo
stesso cielo. Il clima viene dalla data avvolta (giugno è sempre stagione delle
piogge) ma il rumore dall'istante lineare — *il calendario si ripete, il meteo
no*.

Lo stesso vale per i **quattordici eventi stagionali**: i ciliegi, la Golden
Week, il festival d'estate al tempio, il festival culturale, il capodanno, San
Valentino. Non inventano meccaniche — alzano la gente in giro e accelerano le
voci, e tutto il resto segue da solo.

### La scuola

Non è decorativa: **la classe determina l'anno di nascita**. In Giappone la
coorte scolastica si taglia al 2 aprile, quindi chi è nato il 20 marzo sta in
classe con chi è nato undici mesi prima. L'età non si sceglie: la si deduce dal
compleanno. Le classi giocabili sono la terza media e le tre superiori.

### Il quartiere è un posto vero

La ricerca ha portato a una scoperta che ha cambiato l'ambientazione: il
quartiere di *Kimagure Orange Road* **non è Tokyo**. È **Takaoka**, la città in
cui Izumi Matsumoto è cresciuto. La scalinata è quella del parco Takaoka Kojō,
l'ABCB è un locale che si chiamava ABAB, la stazione è Etchū-Nakagawa. Chi gioca
cammina nell'infanzia dell'autore.

Per questo c'è **`/santuario`**: una sala in memoria di Izumi Matsumoto
(1958-2020), che non è una pagina di crediti. È il motivo per cui questo
progetto esiste.

---

## Cosa c'è dentro

| | |
|---:|:---|
| **22** | luoghi, con **78** collegamenti, orari, stagionalità e vedute |
| **13** | abitanti canonici che girano per il quartiere da soli |
| **17** | poteri esper, con vistosità e Controllo |
| **24** | tratti di personalità, riscritti nel Giappone del 1987 |
| **10** | gesti fra personaggi, quasi tutti ambigui per costruzione |
| **10** | club scolastici |
| **14** | eventi stagionali di server |
| **10** | copioni di episodi |
| **51** | manopole di configurazione, tutte lette da qualcuno |
| **18** | migrazioni |
| **81** | rotte |
| **449** | verifiche su 16 file di prova (15 unitari + 1 end-to-end) |

### Le schermate

`/quartiere` la carta e dove sei · `/luogo/{x}` la scheda di un posto ·
`/personaggio` la tua scheda · `/personaggio/profilo` la fotografia e l'aspetto ·
`/chi/{id}` il profilo di una persona che conosci · `/legami` il grafo delle relazioni ·
`/comunicazioni` il filo diretto con la gestione · `/trasloco` dopo il trasloco, fino al ritorno ·
`/password-dimenticata` per rifarla, con un collegamento che vale due ore ·
`/verso/{id}` cosa provi per una persona, e cosa puoi farci ·
`/taccuino` le anomalie che hai annotato · `/incidente/{id}` coprire un potere
appena usato · `/voci` quello che ti è arrivato · `/bacheca` e `/biglietti` ·
`/club` · `/calendario` gli eventi e chi abita qui · `/episodio` quello in corso ·
`/ricordi` l'album illustrato · `/diario` l'esportazione ·
`/opera` e `/santuario` le pagine pubbliche.

Il pannello, per chi amministra: `/admin` il cruscotto · `/admin/utenti` e
`/admin/utente/{id}` gli account, i personaggi e la moderazione ·
`/admin/mappa` la carta con le presenze · `/admin/statistiche` ·
`/admin/comunicazioni` i fili con i giocatori · `/admin/fotografie` il muro delle
facce · `/admin/accessi` le provenienze · `/admin/impostazioni` le cinquantuno leve.

È anche una **PWA**: si installa, e il service worker tiene in tasca il guscio
del sito. Non mette mai in cache le pagine di gioco — il quartiere cambia ogni
minuto, e una pagina salvata è una bugia su dove si trovano gli altri.

### La faccia

Ogni personaggio può avere una **fotografia**, che il giocatore carica e centra
da sé: si sceglie un file, lo si trascina dentro un quadrato e si stringe finché
l'inquadratura è quella giusta. Compare sulla scheda, sulla pagina di chi si
incontra e nell'elenco di chi c'è in un posto.

Il riquadro manda al server il **rettangolo di ritaglio in pixel dell'immagine
originale**, non «zoom e spostamento»: quei due numeri significherebbero
qualcosa solo conoscendo la misura del riquadro sullo schermo di chi carica, e
il server non la conosce e non deve fidarsene. Un rettangolo lo sa verificare da
solo, e infatti lo riporta dentro i bordi qualunque cosa arrivi. Senza
JavaScript il modulo funziona lo stesso: il ritaglio resta a zero e il server
centra sul lato corto, un po' più in alto del centro geometrico — in un ritratto
la testa sta in alto, e tagliare dal centro decapita.

**Quello che si carica non è quello che si serve.** Il file portato da casa non
arriva mai al disco: viene riaperto con GD, ritagliato, riscalato a 320 pixel e
riscritto in WebP. Il tipo si decide guardando i byte, non l'estensione; gli SVG
non si accettano. Il nome del file è l'impronta sha256 del risultato, quindi non
è indovinabile e due fotografie identiche occupano un file solo — con la
conseguenza che toglierla a uno non la toglie all'altro.

La faccia non resta sulla propria scheda: compare nell'elenco di **chi c'è adesso** in un
luogo, sulla pagina di chi si incontra, nell'elenco dei **legami**, e su `/chi/{id}` — il
profilo di una persona. Quel profilo non è una rubrica: si apre solo su chi è **qui adesso**
oppure su chi si è **già incontrato** almeno una volta. Un elenco di tutti gli abitanti
consultabile dal divano di casa racconterebbe un quartiere diverso da questo, dove la gente
la si conosce stando negli stessi posti. E mostra quello che si vedrebbe guardando una
persona — faccia, vestito, classe, club — non le abilità e non i poteri: quelli non si
leggono in faccia a nessuno.

Le fotografie nascono **solo sull'installazione viva** — le scrive il server web
quando qualcuno ne carica una — e il deploy in andata le salta apposta, perché
una copia con `--delete` le cancellerebbe tutte a ogni pubblicazione. Al ritorno
`deploy/04-fotografie.sh` le ritira e le porta nell'albero di lavoro, perché il
backup integrale le deve contenere: sono l'unica cosa che i giocatori mettono di
loro, e un backup che le salta ha un buco esattamente lì. In questa copia
pubblica non ce ne sono e non ce ne saranno: `.gitignore` le ignora, e lo
strumento che deriva la copia pubblica dal nostro albero le esclude e si rifiuta
di procedere se ne trova anche una sola — sono immagini di persone vere,
caricate per giocare e non per finire su un repository aperto.

Chi amministra può correggere il profilo di un giocatore — fotografia e aspetto
— dalla scheda dell'utente, e ogni modifica fatta a un altro finisce nel
registro. Il muro `/admin/fotografie` le raccoglie tutte in una
schermata: moderarle una alla volta aprendo la scheda di ogni utente non è moderare, è
sperare di inciampare in quella sbagliata.

### Le comunicazioni

`/comunicazioni` è il filo diretto fra **la gestione e un giocatore**, nei due sensi.
Non passa per i biglietti e non si traveste da finzione, e a schermo si vede che è un'altra
cosa: un avvertimento di moderazione travestito da biglietto lasciato sui gradini sarebbe una
bugia raccontata proprio nel momento in cui serve essere creduti. Va in tutte e due le
direzioni perché un canale a senso unico obbliga chi ha un problema a scrivere un'e-mail e
aspettare, e quasi nessuno lo fa: il risultato pratico sarebbe che i problemi non arrivano.

Quando la gestione scrive, al giocatore parte anche un'e-mail — che **non ripete il messaggio
per intero**: letto nella posta, fuori contesto e senza poter rispondere, un avvertimento
prende quasi sempre peggio di com'era inteso. Nel pannello i fili in attesa di risposta stanno
in cima: in ordine di data sembrerebbero tutti uguali, e la domanda di tre giorni fa finirebbe
in fondo proprio perché è vecchia.

`/admin/mappa` disegna la carta del quartiere con le presenze, ed è **l'unica carta in cui i
giocatori si distinguono dagli abitanti** mossi dal motore: su quella del giocatore sarebbe
un'informazione fuori dal mondo, e una volta data non si può più togliere.

---

## Come è fedele all'opera

Ogni dato porta un livello di confidenza dichiarato in
[`docs/FONTI.md`](docs/FONTI.md): `canone` (attestato nell'opera), `documentato`
(da fonti secondarie curate), `regolamento1990`, `ricostruita` (nostra). La
distinzione non è pignoleria: è quello che permette di sapere cosa si può
cambiare senza tradire niente.

Quattro esempi di cosa ha prodotto la rilettura:

- **Kyosuke non ha la telepatia.** Il regolamento del 1990 gliela dava; il canone
  è esplicito nel negarla, ed è il motore della storia — se leggesse nel pensiero,
  il fumetto finirebbe al terzo capitolo.
- **Manami e Kurumi erano scambiate** nel regolamento del 1990. Manami è la
  maggiore, con gli occhiali e la testa a posto.
- **Nel manga i club scolastici non esistono.** Su 156 capitoli le sole occorrenze
  sono un invito che Madoka declina e un club fondato per scherzo. I nostri dieci
  club sono dichiaratamente una ricostruzione: servono a una funzione di gioco che
  l'opera non aveva bisogno di avere.
- **La collina era al contrario.** Avevamo il liceo in cima alla scalinata; il
  canone mette lassù la casa dei Kasuga e tutto il resto sotto. C'era persino una
  prova che difendeva l'errore.

---

## Stato

**Il gioco è finito.** Le otto fasi da F0 a F7 sono fatte e in produzione; la
tabella completa è in [`docs/PROGETTO.md`](docs/PROGETTO.md) §12, con l'elenco
di quello che è arrivato dopo — le immagini dei luoghi, il pannello in
profondità, l'uso da telefono, la fotografia del personaggio e le
comunicazioni. Dopo F7 si aggiunge dove serve, ma il motore del mondo — tempo,
meteo, Segreto, legami, episodi, voci — è quello e non cambia.

La rilettura dell'opera è completa: tutti e 156 i riassunti dei capitoli raccolti
e spogliati, e le domande aperte quasi tutte chiuse — comprese quelle a cui la
risposta è «il canone non lo dice», che valgono quanto le altre.

---

## Stack

**PHP 8.4 senza framework, MariaDB, Apache.** Front controller unico
(`index.php` via PATH_INFO), autoloader PSR-4 scritto a mano, JavaScript
vanilla e Canvas, **nessun passo di compilazione**: si clona, si migra, si
apre. Circa 16.000 righe di PHP fra `src/`, `db/`, `views/` e `bin/`.

Due principi che tornano dappertutto:

**Il mondo avanza per calcolo, non per stato.** Meteo, eventi stagionali,
posizione degli abitanti e ora di gioco sono funzioni pure dell'istante: non
c'è niente da far avanzare, e due richieste ravvicinate vedono lo stesso
quartiere. Quello che deve essere scritto — movimenti, incidenti, voci — lo
scrive un **battito** da cron ogni minuto, più un **avanzamento pigro** su
richiesta web che chiama lo stesso metodo: una strada sola, quindi i due
percorsi non possono divergere.

**Il generatore casuale è deterministico e ancorato.** Ogni tiro si ancora a
qualcosa di stabile e irripetibile — l'id di un incidente, la coppia più l'ora
di gioco — così rilanciare il battito non rimescola niente e insistere su
un'azione appena fallita non serve. Ci si è arrivati sbagliando due volte: le
lezioni 11 e 15 qui sotto.

Il core (`Config`, `Database`, `Router`, `Session`, `Csrf`, `View`, `Mailer`,
`Posta`, `RateLimiter`, `Lock`, `Auth`, `Migrator`, `Seeder`) è portato da
Atlantik e SubSpazio.

## Installazione

```bash
sudo bash deploy/00-bootstrap.sh      # directory, database, config, Apache
php bin/console.php migrate
php bin/console.php user:create 'Il Tuo Nome' tu@example.com --admin
```

Il battito va nel crontab dell'utente di sistema, non in quello di root:

```
* * * * * /usr/bin/php /var/www/orangeroad/bin/tick.php >/dev/null 2>&1
```

## Console

```
php bin/console.php            # elenco dei comandi
php bin/console.php status     # configurazione, schema, utenti, battito, posta
php bin/console.php migrate    # applica le migrazioni in attesa
php bin/console.php seed       # carica db/seed/*.php
php bin/console.php mondo:ora  # che ora, che giorno e che tempo fa adesso
php bin/console.php mondo:anno # una passata sull'anno simulato, per guardarlo
php bin/console.php mondo:luoghi
php bin/console.php bilancio --conferma   # rapporto di bilanciamento
```

Il **bilancio** risponde alle domande che una prova non sa porre. Una prova
dice «la vistosità morde»; questo dice *quanto*: genera centinaia di schede
con il codice vero, le legge, le cancella, e stampa le distribuzioni di
PF/PP/tratti/poteri, la tabella del farsi notare potere per potere, la folla
media per luogo, e la riga che conta davvero — quante volte la prudenza paga
rispetto all'imprudenza.

## Prove

```bash
bash tests/tutte.sh            # tutto in fila
php tests/test_orologio.php    # unitarie: non toccano il mondo
bash tests/e2e_accesso.sh      # end-to-end: avvia da sola un server e si ripulisce
```

**Quale installazione stai provando.** `Config::load` prende il primo file che
trova, e `/etc/orangeroad/config.php` vince sul config di progetto: la
prova end-to-end lo stampa in testa e **si rifiuta** di girare in produzione se
non glielo si chiede esplicitamente. Per provare in locale:

```bash
ORANGEROAD_CONFIG=config/config.php bash tests/tutte.sh
```

Per verificare il deploy vero (crea e cancella un utente di prova, e controlla
anche che le cartelle dei sorgenti non siano servite):

```bash
bash tests/e2e_accesso.sh https://example.org/orangeroad --anche-in-produzione
```

## Sviluppo in locale

Serve un MariaDB. Il più rapido è un container usa-e-getta:

```bash
docker run -d --name kor-prova-db \
  -e MARIADB_ROOT_PASSWORD=passwordlocale -e MARIADB_DATABASE=kor_orangeroad \
  -e MARIADB_USER=kor_orangeroad -e MARIADB_PASSWORD=passwordlocale \
  -p 127.0.0.1:33307:3306 mariadb:11
```

Poi si copia `config/config.example.php` in `config/config.php` puntando alla porta
33307 con trasporto posta `log`. Quel file è ignorato da git.

```bash
php -S 127.0.0.1:8150 -t . index.php
```

---

## Trappole già pagate

Annotate qui perché non si ripetano.

1. **Il divisore di statement delle migrazioni non può esplodere su `;`.** Un punto
   e virgola dentro una stringa SQL — anche solo in una nota descrittiva — spezzava
   lo statement a metà, e l'errore che ne usciva parlava di sintassi SQL invece che
   del vero problema. Ora `Migrator::splitStatements` scandisce carattere per
   carattere tenendo conto di apici, apici raddoppiati, barra rovesciata e commenti.
   Coperto da `tests/test_migratore.php`.
2. **Sotto `php -S` il router riceve anche i file statici.** Senza il `return false`
   in cima a `index.php` il foglio di stile torna come 404 in HTML e il sito si vede
   nudo, con un errore di MIME che non sembra affatto un problema di instradamento.
   Sotto Apache ci pensa la `RewriteCond -f`. Coperto dalla prova end-to-end.
3. **`status` deve rispondere anche prima della prima migrazione.** È il comando che
   si lancia quando qualcosa non va: se è l'unico strumento di diagnosi e anche
   l'unico che non funziona, non serve a niente.
4. **La forma breve `background` azzera `background-color`.** Con
   `background-attachment: fixed` il gradiente copre solo la finestra: su una pagina
   lunga, scorrendo oltre, compariva il bianco del browser.
5. **`.testata nav a` vince per specificità su `.bottone`.** Il richiamo nella testata
   si ritrovava testo marrone su fondo arancione, contrasto 1,1.
6. **Ogni `sed` su un file PHP va seguito da `php -l`, e `php -l` non basta.** Un
   apostrofo non protetto in `Router.php` ha fatto fallire venti prove end-to-end su
   ventitré con sintomi che sembravano di instradamento. E in senso opposto: un
   metodo mancante passa il lint indisturbato e fa cadere ogni pagina a runtime.
   Dopo una modifica al Core, una richiesta di prova vale quanto il lint.
7. **Apache abbina i `<Directory>` sul percorso col symlink NON risolto.** Se il
   DocumentRoot passa per un link simbolico — `/var/www/html` che punta a un'altra
   cartella — il blocco va scritto col percorso **non** risolto, quello che sta
   sotto il DocumentRoot. Scriverlo col percorso reale compila, supera il
   configtest, non dà alcun errore — e non si applica mai. Il sintomo inganna: la
   home funziona (ci pensa `DirectoryIndex`) mentre ogni altro indirizzo dà 404 di
   Apache, e per giunta le cartelle dei sorgenti restano servite. Sembra un
   problema di instradamento dell'applicazione, e non lo è.
   Coperto dalla prova end-to-end quando gira contro un URL vero.
8. **`pipefail` + `grep -q` = guasto intermittente.** `curl url | grep -q testo`
   funziona finché la risposta sta nel buffer del tubo; sulle pagine più grandi
   `grep` esce appena trova la corrispondenza, `curl` prende SIGPIPE e la pipeline
   restituisce 141 **nonostante il testo ci fosse**. Nella prova end-to-end si usa
   la funzione `contiene`, che raccoglie prima e cerca dopo.
9. **Il «tira ancora» del 1990 non era limitato.** Le tabelle dei tratti e dei poteri
   hanno due risultati alti che valgono «tira altre due (o tre) volte», e quei tiri
   possono ricascarci. Con i dadi veri si tirano cinque personaggi in una sera e la
   coda lunga non si vede mai; con cinquecento personaggi esce quello con **tredici
   poteri su sedici** — che non è fortunato, è un personaggio a cui il Segreto non fa
   più paura. Il compounding resta, con un tetto.
10. **Una prova che passa da sola e fallisce in suite non è un capriccio.** I tiri
   sono ancorati all'id del personaggio: se prima girano prove che ne creano cento,
   il personaggio della prova dopo ha id e tiri diversi. Le asserzioni devono
   verificare **invarianti** (il minimo, il tetto, la coerenza), non numeri esatti
   che valevano per un id fortunato.
11. **Il generatore casuale non si ancora all'istante quando l'azione può ripetersi.**
   Per il meteo è giusto (il tempo è funzione dell'istante), per un'azione no: con la
   compressione 1:4 un secondo di gioco dura un quarto di secondo vero, quindi due usi
   di potere ravvicinati cadevano nello stesso istante, condividevano lo stesso flusso
   e davano lo **stesso identico esito**. Chi non veniva notato la prima volta non
   veniva notato mai. Va ancorato a una chiave che cresce per costruzione — qui l'id
   dell'incidente.
12. **Il crawler educato non è un vezzo.** 550 richieste a mezzo secondo l'una hanno
   mandato orangeroad.it in HTTP 500 su tutto, home compresa. Per i siti amatoriali:
   una richiesta ogni quattro secondi, filtro sui binari **al momento di accodare**
   (non di scaricare, o la coda si riempie di immagini e il limite scatta prima di
   aver visto le pagine vere), e un raccoglitore mirato invece di una strisciata
   quando la struttura degli URL è già nota.
13. **Le prove che condividono uno stato condiviso vanno isolate a monte, non a memoria.**
   Nelle prove del Segreto tutti i personaggi finivano nello stesso luogo e si facevano
   da testimoni a vicenda; sgombrare «dove serve» significava che tre prove lo facevano
   e nove no, con guasti che dipendevano dall'ordine di esecuzione. Adesso si sgombra
   all'inizio di ognuna, senza eccezioni.
14. **Nel database solo istanti lineari, a schermo solo istanti avvolti.** Il
   calendario gira in tondo: se si salvasse l'istante avvolto, un viaggio iniziato
   il 5 aprile e finito il 6 avrebbe l'arrivo prima della partenza — una volta
   l'anno, con un guasto impossibile da riprodurre a comando.

15. **Una prova probabilistica va calcolata, non stimata a occhio.** Due prove del
   Segreto cadevano a caso — una su tre lanci — e sembrava un difetto del motore:
   non lo era. La prima diceva «vistosità 9 e Testa 15, la probabilità è al
   massimo» senza fissare l'orologio, ma `probabilitaNota()` moltiplica per 0,55
   di notte e per 0,6 sotto un rovescio: l'81% pieno diventava 45% la notte e 27%
   la notte sotto la pioggia, e l'esito dipendeva da che ora fosse nel gioco
   quando la lanciavi. La seconda concedeva cinque tiri al 60%, cioè falliva
   0,4^5 = 1% delle volte per pura sfortuna. Le regole che ne escono: **fissare
   l'orologio** con `Orologio::fingiEpoca()`/`fingiAdesso()` in ogni prova il cui
   esito dipende da ora, stagione o meteo, e ripristinarlo in un `finally`;
   **affiancare al conteggio un'asserzione deterministica** sulla formula, che è
   quella che dice davvero se il motore è giusto; e **ricavare la soglia dalla
   distribuzione** — media, scarto, e margine di tre scarti — invece di sceglierla
   perché sembra ragionevole. È la stessa trappola del punto 13, un piano più in
   là: lì era lo stato condiviso, qui è il tempo condiviso.

16. **Un'impronta è l'impronta del risultato, non della sorgente.** Due prove
   della fotografia fallivano dicendo che immagini diverse producevano lo stesso
   file: erano 900×600 e 500×500, e sembrava un difetto della deduplicazione. Non
   lo era. Il quadrato centrale di un'immagine metà rossa e metà blu è sempre la
   stessa metà rossa e metà blu, e dopo il ritaglio e la riscalatura le due
   uscite erano identiche **byte per byte** — quindi stessa impronta, giustamente
   un file solo. Sbagliava il materiale di prova, non il codice. La regola:
   quando si prova qualcosa che ha di mezzo una trasformazione, il materiale va
   reso distinto **dopo** la trasformazione, non prima. Con un corollario
   imbarazzante: la prima correzione limitava le tinte con un `max()`, due di
   loro finivano sullo stesso valore, e la prova tornava a fallire per lo stesso
   identico motivo.

17. **Una prova che non passa dagli header dell'applicazione non prova
   l'applicazione.** Il riquadro di ritaglio era stato verificato in un browser
   vero, con tanto di trascinamento e numeri misurati: funzionava. In produzione
   non funzionava affatto — il riquadro non compariva e restava solo «non riesco
   a mostrarti l'anteprima». La causa era la CSP: `img-src 'self' data:` senza
   `blob:`, e l'anteprima si costruisce con `URL.createObjectURL()`, che produce
   esattamente un URL `blob:`. La prova l'aveva mancata perché serviva una
   pagina statica dal server incorporato, che non passa da `Response::send()` e
   quindi non porta addosso nessuna delle intestazioni vere. La regola: quando
   si verifica un pezzo di interfaccia fuori dall'applicazione, **si riproduce
   anche il contorno** — intestazioni comprese — o si accetta che la verifica
   copra il codice e non il prodotto. Il guasto era peraltro invisibile dal
   server: la pagina torna 200, il modulo continua a funzionare (il server sa
   ritagliare da solo) e l'unica traccia sta nella console del browser. Il
   controllo che adesso lo copre guarda l'intestazione, perché è l'unico punto
   ispezionabile senza un browser.

18. **Una barra di scorrimento dentro un riquadro è un dato nascosto, non una
   soluzione.** Le tabelle del pannello sbordavano e scorrevano in orizzontale, e
   sembrava un problema delle tabelle. Non lo era: il pannello ereditava la
   colonna tarata sulla **lettura** — 42rem, la misura di riga che si legge
   senza fatica — mentre è fatto di tabelle da sei o sette colonne. Misurato
   invece che stimato: l'elenco degli utenti chiede 673 pixel e ne aveva 548.
   Allargata la colonna per le sole pagine del pannello, il problema sparisce a
   monte. Sotto i 768 pixel però nessuna larghezza salva una tabella da sette
   colonne — su un telefono restano circa 250 pixel utili — e lì l'unica
   risposta onesta è cambiare forma: ogni riga diventa una scheda e ogni cella
   una coppia «etichetta: valore», con l'intestazione che si trasferisce nelle
   celle via `data-etichetta`. Regola generale: quando un contenuto non ci sta,
   prima si guarda **perché il contenitore è largo così**, e solo dopo si tocca
   il contenuto.

19. **Un guasto che risponde 200 non lo trova nessuna prova che guardi solo il
   codice di stato.** Un audit di settembre ha trovato quattro cose che
   giravano da giorni senza che niente le segnalasse, e tutte e quattro avevano
   la stessa forma: nessuna eccezione, nessun errore, la pagina si apriva. Il
   cruscotto stampava «Array» al posto di tutti e cinquanta i valori delle
   manopole (e cento avvisi per visita, nel log che nessuno leggeva);
   `GameConfig::set()` declassava a stringa il tipo di ogni manopola toccata da
   riga di comando; una manopola letta dal codice non era in tabella e quindi
   non era regolabile, mentre tre in tabella non le leggeva nessuno — leve
   collegate a niente, che è peggio di leve assenti. Le tre cose che le hanno
   fatte saltare fuori: **esercitare ogni rotta con `error_reporting=E_ALL` e
   leggere il log**, non solo il codice HTTP; **confrontare il codice con i
   dati** (quali chiavi legge il codice, quali stanno in tabella, e l'insieme
   simmetrico delle differenze); e **far girare il mondo a vuoto per una
   settimana simulata**, che è l'unico modo di vedere se un motore produce
   qualcosa o gira a vuoto — le prove unitarie passano identiche nei due casi.

20. **Non decidere non può costare meno che decidere.** Il diagramma del Segreto
   mette «non fare niente» fra le quattro risposte a un incidente, e chi la
   sceglie lascia un'anomalia a ogni testimone. Nel codice quella via c'era, ma
   solo come **scelta**: chi chiudeva il browser invece di sceglierla non pagava
   nulla, perché l'incidente restava aperto per sempre e nessuno lo chiudeva
   mai. Dichiarare «non faccio niente» costava, andarsene no — l'opposto di
   quello che il gioco vuole insegnare, e in un gioco persistente una strategia
   dominante del genere la trova il primo giocatore attento e la insegna a tutti
   gli altri. Adesso il tempo decide al posto di chi non decide. La regola
   generale: ogni ramo di una scelta di gioco deve avere un esito, **compreso
   il ramo di chi non risponde** — ed è quello che si dimentica, perché non ha
   un bottone.

21. **Uno stato senza uscita è un difetto anche se non dà errori.** Dopo il
   trasloco il personaggio passava a `trasferito` e lì restava: invisibile agli
   altri, ma ancora capace di muoversi e usare i poteri, mentre il giocatore non
   poteva nemmeno crearne un altro («Hai già un personaggio»). Nessuna eccezione,
   nessuna pagina rotta — solo un giocatore chiuso in una stanza senza porte. Il
   progetto il ritorno lo descriveva, e il tratto «Trasferito di recente» era nei
   semi dal primo giorno: mancava soltanto la freccia che dallo stato portava
   fuori. Quando si aggiunge uno stato, si scrive **prima** come se ne esce.

22. **Un seme che gira a ogni deploy non deve riscrivere quello che il gioco
   cambia.** Il seeder, su una riga esistente, aggiornava ogni colonna tranne la
   chiave. Per i nomi dei luoghi va benissimo; per il cappello di paglia rosso —
   un esemplare per server, che passa di mano — voleva dire rimetterlo sui gradini
   a ogni pubblicazione, togliendolo a chi lo teneva. E riportare i tredici
   abitanti al punto e ai valori di partenza. Adesso un seme dichiara quali
   colonne sono **solo lo stato iniziale** (`solo_alla_nascita`), e il seeder le
   scrive una volta sola.

23. **Un numero va letto con il significato che gli dà chi lo scrive.** I copioni
   chiamavano `difficolta` il valore del tiro e lo usavano come tale — 30 per
   «stare col bar, funziona sempre», 55 per «scendere di corsa sul ghiaccio» — e
   il motore lo **sommava** alla probabilità: la mossa sicura riusciva il 42%
   delle volte, quella spericolata il 67%. Nessuna prova se n'era accorta perché
   le prove usavano opzioni senza tiro. La prova nuova non guarda la formula:
   confronta due opzioni e pretende che la più difficile riesca meno spesso.

24. **Una frase fatta di pezzi si controlla generandole tutte.** Le voci erano
   un «chi + cosa + dove» incollato, e sembravano giuste lette a campione. Lette
   tutte e 3571, dicevano di Kurumi «si è preso la briga» e «se n'è andato»,
   mettevano due soggetti con un verbo singolare, e appendevano il luogo anche
   dove sembrava una destinazione («ha cambiato scuola all'ABCB»). Adesso le frasi
   hanno segnaposto espliciti, e una prova le genera tutte — ogni tipo, fascia,
   genere e coppia — e cerca le forme sbagliate. Lo stesso valeva per i testi in
   seconda persona: il gioco parlava a una giocatrice al maschile («sei arrivato
   da poco», «ti sei rassegnato»). Si scrive `arrivat{o}` e lo si accorda nel
   momento in cui lo si mostra.

25. **Due orologi che coincidono per caso sono un orologio rotto.** Le scadenze
   le scrive `NOW()` del database e le rilegge `time()` di PHP. In produzione i
   due fusi coincidevano; sulla macchina di sviluppo il database era in UTC, e un
   collegamento per rifare la password — due ore — nasceva già scaduto. Adesso la
   sessione del database si allinea al fuso di PHP alla connessione, e una prova
   pretende che i due leggano la stessa ora.

## Licenza e diritti

Il codice di Cento Gradini è distribuito sotto **GNU General Public License v3.0
o successiva** — il testo integrale è in [`LICENSE`](LICENSE). Non c'è garanzia
di alcun tipo.

La licenza copre il codice, i dati di ambientazione scritti da noi e la
documentazione in `docs/`. Non copre — e non potrebbe — l'opera su cui il gioco
si appoggia.

*Kimagure Orange Road* è di **Izumi Matsumoto** e degli aventi diritto (Shūeisha,
Studio Pierrot). Questo è un progetto **amatoriale e senza scopo di lucro**, non
affiliato né autorizzato: non riproduce testi, tavole, fotografie o materiale
grafico delle opere originali, e viene rimosso su richiesta di chi ne detiene i
diritti.

*Whimsical Orange Road: The Role-Playing Game* v3.0 (1990) è di «Totoro Hunter
Leto II». Cento Gradini ne è una trasposizione riscritta da zero: né il
regolamento originale né la nostra traduzione sono inclusi qui.
