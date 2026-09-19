# Cento Gradini

Gioco di ruolo multigiocatore persistente nell'universo di **Kimagure Orange Road**
di Izumi Matsumoto. Progetto amatoriale, senza scopo di lucro, non affiliato agli
aventi diritto.

- Documento di progetto: [`docs/PROGETTO.md`](docs/PROGETTO.md)
- Registro delle fonti: [`docs/FONTI.md`](docs/FONTI.md)
- La rilettura dell'opera: [`docs/CANONE.md`](docs/CANONE.md)

> **Cosa non trovate qui.** Questo repository contiene il nostro codice e basta.
> Il regolamento del 1990 e la sua traduzione italiana, il corpus di ricerca e le
> fotografie di Izumi Matsumoto hanno altri autori: la GPL3 copre quello che
> abbiamo scritto noi e non ci autorizza a ridistribuire il lavoro di altri.
> `docs/FONTI.md` dice da dove viene ogni cosa, cosi' chi vuole puo' risalirci.

## Stato

**F0 — fondamenta: fatta.** Ci si iscrive, si conferma l'indirizzo, si entra.

**F1 — il mondo: fatta.** Orologio ciclico dell'«eterno 1987» (compressione 1:4),
calendario scolastico giapponese 1987-88 con feste, vacanze e ventisei giornate
che valgono un episodio, meteo deterministico tarato sulle medie di Tokyo,
diciassette luoghi con orari e stagionalità, carta su tela, spostamento con
tempi di percorrenza, presenze e tracce.

**F2 — il personaggio: fatta.** Modello scolastico canonico (il Kōryō Gakuen ha
medie e superiori, e l'età non si sceglie: la determina il compleanno tramite la
coorte), quattro abilità con Cuore, 24 tratti riscritti nel Giappone del 1987,
otto abilità secondarie, esper e non-esper con schede diverse, 16 poteri con
nomi canonici e Controllo. Le schede nate in F1 si completano da sole.

**F3 — il Segreto: fatta.** È il ciclo centrale: usi un potere, il motore decide
chi se n'è accorto (vistosità del potere, attenzione del testimone, buio, pioggia,
il tuo Controllo), tu provi a coprire con una scusa, un diversivo o la faccia di
bronzo, e chi non si beve la storia si porta dietro un'anomalia. Tre anomalie
coerenti, un tiro di Intuizione, e quella persona ha capito. Due persone che hanno
capito e la famiglia trasloca. La valvola è **confidarsi**: dieci persone che sanno
perché gliel'hai detto tu non spostano un mobile, due che l'hanno capito da sole sì.

**F4 — le relazioni: fatta.** Legami orientati su due assi — **Affetto** e
**Fraintendimento** — e dieci gesti, quasi tutti ambigui per costruzione. Un gesto
fra A e B viene *letto* da chi passa di lì: chi è distratto capisce male e si porta
a casa un malinteso; chi è innamorato ha capito benissimo, ed è proprio per questo
che gli brucia. Il fraintendimento **non decade col tempo** — solo un chiarimento
lo scioglie, costa Cuore e può peggiorare le cose. E c'è la confessione, con due
prove in fila: riuscire a dirlo, e la risposta. Più il cappello di paglia rosso,
uno solo per server.

**F5 — gli episodi: fatta.** Un episodio è una storia breve che il quartiere
apre da sé quando le condizioni ci sono: la stagione giusta, il posto giusto, il
tempo giusto, o un sospetto già acceso su qualcuno. Chi si trova lì entra nel
cast. Poi si va a scene, e a ogni scena si sceglie: le scelte muovono legami,
Compostezza, Copertura e sospetti veri, non punteggi finti. Chi non si fa vivo
entro la finestra non blocca la storia — un **agente autonomo** sceglie per lui,
pesando le opzioni sulla sua scheda, così un timido resta timido anche quando a
giocarlo è il motore. Quello che resta finisce nei **ricordi**, in `/ricordi`.
Tre copioni per cominciare: i funghi che tolgono la voglia di mentire,
l'acquazzone sotto la stessa tettoia, e i sospetti in aula.

**La sala del maestro**: `/santuario`, in memoria di Izumi Matsumoto.

La tabella completa delle fasi è in [`docs/PROGETTO.md`](docs/PROGETTO.md), e la
rilettura dell'opera in [`docs/CANONE.md`](docs/CANONE.md).

## Stack

PHP 8.4 senza framework, MariaDB, Apache. Front controller unico (`index.php`),
autoloader PSR-4 scritto a mano, nessun passo di compilazione lato browser.
Battito da cron ogni minuto + avanzamento pigro su richiesta. Core portato da
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
```

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
