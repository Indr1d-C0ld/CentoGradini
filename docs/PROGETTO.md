# Cento Gradini
## Gioco di ruolo multigiocatore persistente nell'universo di *Kimagure Orange Road*

> Documento di progetto — versione 0.1, 19 settembre 2026
> Base di partenza: *Whimsical Orange Road: The RPG* v3.0 (1990) di «Totoro Hunter Leto II».
> Il regolamento originale e la nostra traduzione italiana non sono inclusi in questo
> repository: hanno un altro autore. Vedi [`FONTI.md`](FONTI.md).
> Opera di riferimento: *Kimagure Orange Road* di **Izumi Matsumoto** (manga, 1984-1987) e
> la serie animata di **Studio Pierrot** con character design di **Akemi Takada** (1987-1988).

---

## 0. Il nome

Il titolo di lavoro è **Cento Gradini**: la scalinata che Kyosuke sale nella prima pagina del
manga, dove incontra Madoka e riceve il cappello di paglia rosso, e che è anche il *timeslip*
del volume 5. È il luogo dove nella nostra ambientazione comincia ogni personaggio, ed è
l'unica cosa del quartiere che non cambia mai.

Il percorso tecnico resta `orangeroad`. Il nome pubblico si cambia con una riga di
configurazione, quindi non è vincolante.

---

## 1. Le quattro decisioni fondanti

Prese dall'utente il 19/09/2026, prima di scrivere una riga di codice.

1. **Onomastica originale giapponese.** Kyosuke Kasuga, Madoka Ayukawa, Hikaru Hiyama, Manami
   e Kurumi Kasuga, Yusaku Hino, Akane e Kazuya, il Master dell'ABCB, Jingoro. Niente Johnny e
   Sabrina: fedeltà a Matsumoto. (La tabella nomi resta comunque normalizzata a DB, quindi un
   eventuale alias si può aggiungere dopo senza rifattorizzare nulla.)

2. **Esper e non-esper con ruoli asimmetrici.** Si può giocare un parente del clan *oppure* un
   essere umano perfettamente normale. Non è una scelta fra «forte» e «debole»: sono due
   mestieri diversi, con schermate diverse e verbi diversi. In *Kimagure Orange Road* le due
   persone che reggono l'intera storia — Madoka e Hikaru — non hanno alcun potere.

3. **«Eterno 1987»: il quartiere congelato.** Il calendario scorre, le stagioni si susseguono,
   ma l'anno resta il 1987 e alla fine dell'anno scolastico ricomincia. Kyosuke, Madoka e
   Hikaru esistono, si incontrano, si parlano — e il loro triangolo **non si risolve mai**: è lo
   sfondo immobile su cui i giocatori scrivono la propria storia. Nessun giocatore può sposare
   Madoka, e nessuno può far confessare Kyosuke. Questo è un vincolo di progetto, non una
   limitazione temporanea.

   > **Nota aggiunta dopo la rilettura del canone.** Il 6 aprile 1987 era stato scelto per
   > ragioni di calendario. Si è poi scoperto che è **l'inizio dell'ultimo anno scolastico**:
   > Madoka viene notata a settembre 1987, Kyosuke le si dichiara, e lei parte il 20 settembre.
   > L'eterno 1987 non è quindi un anno qualunque congelato — è l'ultimo anno, tenuto aperto.
   > Il calendario si ferma cinque mesi prima della fine e ricomincia. Vedi
   > [`CANONE.md`](CANONE.md) §6.

4. **Quartiere persistente asincrono + episodi cooperativi.** Il mondo gira sempre; i giocatori
   si incrociano in modo asincrono e si lasciano tracce; su certe condizioni si apre un
   «episodio» a turni con finestra reale, dove un gruppo gioca insieme una storia chiusa.

---

## 2. Fedeltà: cosa teniamo, cosa cambiamo, perché

Il regolamento del 1990 è la base. Ma è un documento scritto da ventenni americani per giocare
una sera fra amici, e va trattato come tale: ha tre idee eccellenti e una quantità di cose che
in un gioco persistente aperto a estranei non stanno in piedi, né per contenuto né per design.

### 2.1 Quello che teniamo integralmente

| Elemento originale | Perché resta |
|:---|:---|
| **Il segreto come obiettivo unificante** | È l'unica meccanica del 1990 che coincide perfettamente con l'opera. Tutta la tensione di KOR nasce dal fatto che i poteri sono un problema, non una risorsa. Diventa il **motore principale** del nostro gioco. |
| **Danno comico, non letale** | A 0 PF si è «pestati», compaiono bende dal nulla e i graffi svaniscono in 15 minuti. Nessuna morte. Manteniamo la tabella delle bende (1d10) quasi alla lettera: è una gag visiva perfetta per il web. |
| **Obiettivi individuali segreti** | Ogni personaggio ha uno scopo suo, non dichiarato, spesso in conflitto con quello degli altri. È la struttura letterale del triangolo. |
| **Le tre abilità RISSA / TESTA / DAI-SUKI** | Iconiche, con i numeri già calibrati sui personaggi canonici (Madoka D15, Kyosuke D11, Hikaru D8). Ci danno una scala ancorata. |
| **Il costo in PP differenziato primario/secondario** | Semplice, elegante, e giustifica meccanicamente perché Kyosuke si teletrasporta di continuo e Manami quasi mai. |
| **Le liste di poteri e i loro limiti puntuali** | 110 kg di telecinesi, 60 m di levitazione, «solo cose a vista o familiari», niente serrature, niente laser: sono limiti *ricavati dal manga*, con riferimenti a volume e pagina. Oro colato. |
| **I Timeslip, gli artefatti, gli universi paralleli** | Materiale d'avventura direttamente canonico. |
| **Il tiro di intuizione 30+(TESTA×2)%** | Diventa la meccanica centrale del giocatore **non-esper**. |

### 2.2 Quello che riprogettiamo, e con cosa

| Originale | Sostituzione | Motivo |
|:---|:---|:---|
| **VERGINITÀ** (statistica, su quindicenni) | **CANDORE** | Stessa funzione meccanica esatta — governa quanto il personaggio va in pezzi per l'imbarazzo — senza mettere a bilancio il corpo di un minorenne. |
| **Tiri di emorragia nasale** e **tiro «mune»** | **Tiri di COMPOSTEZZA** | Nell'opera l'emorragia nasale *rappresenta* il crollo del contegno. Teniamo l'effetto (perdi l'azione, prendi una penalità sociale, parte la gag) e cambiamo la causa: qualunque situazione che ti smonti, non solo quella sessuale. Il fumetto stesso usa molto di più l'imbarazzo che il sangue dal naso. |
| **PERVERTITO** con obiettivo sessuale | **COMBINAGUAI** (archetipo Komatsu/Hatta) | Resta il personaggio comico che ci prova, sbaglia tutto e viene punito dalla situazione. Cade l'obiettivo di gioco «andare a letto con qualcuno». |
| **MASCHIACCIO** (caricatura del 1990) | **CUORE DECISO** (archetipo Akane) | Akane nel manga è innamorata di Madoka e gelosa di Kyosuke: la teniamo esattamente così. Cade lo stereotipo. |
| Tratti politici da Guerra Fredda americana (Rambo NRA, comunista tie-dye, nazionalista antiamericano) | **Tratti radicati nel Giappone del 1987** | Nell'opera non c'è nulla del genere; erano battute interne fra amici. Al loro posto: il *senpai* gravoso, l'iscritto al club sportivo che non ha vita, la ragazza che studia per il *jūken*, il figlio del negozio del quartiere, il trasferito da Osaka con l'accento sbagliato. |
| **Armi da fuoco, laser, automatiche** nella tabella degli attacchi | Rimosse | Lo dice l'autore stesso due paragrafi sotto: «questo gioco è ambientato nel Giappone contemporaneo, quindi oggetti come spade e pistole sono generalmente assenti». Restano mazze, plettri, skateboard, palle da bowling, bilboquet. |
| Violenza sessuale come movente negli episodi 4 e 7 | Rimossa | Convenzione dell'action anni '80 estranea a Matsumoto. Gli episodi vengono riscritti mantenendo la struttura (mondo parallelo, banda, salvataggio). |
| **«Il Master è Dio»** | **Regole scritte e leggibili** | In un gioco senza arbitro umano il posto del Master lo prende il motore: ogni tiro è mostrato, ogni probabilità è spiegata nel diario. Niente numeri nascosti al giocatore che riguardano il suo personaggio. |
| **Nessuna crescita** («non puoi acquisire capacità più avanti») | **Nessuna crescita dei poteri, ma crescita del CONTROLLO** | L'autore ha ragione: se i poteri crescessero, il segreto diventerebbe irrilevante. Ma nel manga Kyosuke *migliora nella mira*. Quindi: il numero dei poteri non cambia mai, la loro precisione sì. |

### 2.3 Quello che aggiungiamo (e che nell'originale manca del tutto)

Il regolamento del 1990 modella benissimo i **poteri** e malissimo i **sentimenti**, che sono
l'argomento dell'opera. Tre aggiunte, tutte ricavate dal manga:

1. **CUORE**, quarta abilità. Non è coraggio fisico né fascino: è la capacità di dire quello che
   si prova. Kyosuke ha DAI-SUKI 11 e CUORE 3. Madoka ha DAI-SUKI 15 e CUORE 4. Hikaru ha
   DAI-SUKI 8 e CUORE 12 — ed è il motivo per cui è lei a muovere tutta la trama. La statistica
   più bassa di tutti i protagonisti è la stessa, ed è questa: per quello la storia dura
   diciotto volumi.

2. **Il FRAINTENDIMENTO come risorsa di gioco**, accanto all'Affetto. In KOR le relazioni non si
   rompono per antipatia: si ingarbugliano per equivoci mai chiariti. Modellarlo come una
   seconda dimensione, con proprie regole di accumulo e di scioglimento, è la cosa più fedele
   che si possa fare.

3. **Il TRASLOCO** al posto della morte. La famiglia di Kyosuke si è già trasferita sette volte
   prima dell'inizio della storia. Se il Sospetto sul tuo conto supera la soglia, non muori:
   trasloca la tua famiglia. Perdi il quartiere, i legami locali, la reputazione — tieni i
   poteri, il carattere e i ricordi. È una perdita vera e reversibile, tonalmente perfetta.

---

## 3. I quattro pilastri del gioco

> **Un gioco di ruolo in cui il potere è un problema, l'imbarazzo è una risorsa,
> il tempo non passa mai davvero e gli altri giocatori sono soprattutto voci.**

### Pilastro 1 — Il Segreto
Ogni uso di un potere in presenza di qualcuno che non sa genera un **incidente**. Gli incidenti
si coprono, non si evitano: è il gioco. Chi ti ha visto? Quanto bene? Ci crederà, alla tua
spiegazione? E soprattutto: quanto ha parlato in giro, poi?

### Pilastro 2 — I sentimenti come sistema
Relazioni direzionali su due assi (Affetto, Fraintendimento). Gesti ambigui che si possono
leggere in due modi. Conversazioni che costano CUORE. Gelosia che nasce da informazioni
*parziali*, mai da informazioni false.

### Pilastro 3 — Il quartiere come personaggio
Sedici luoghi, ciascuno con orari, stagionalità, avventori abituali e memoria. Il quartiere
ricorda chi ci passa. Le voci si propagano di luogo in luogo e si deformano.

### Pilastro 4 — L'episodio
Ogni tanto la vita quotidiana si organizza in una storia con un inizio e una fine — come una
puntata. È il momento cooperativo, sincrono, con un cast, un problema e un finale che entra
nell'album dei ricordi.

---

## 4. Il mondo

### 4.1 Il tempo — «eterno 1987»

- **Compressione 1:4** — un giorno reale vale quattro giorni di gioco. Una settimana scolastica
  passa in ~42 ore reali, un mese di gioco in ~7,5 giorni reali, l'anno scolastico completo in
  ~3 mesi reali. Poi **ricomincia**: aprile, di nuovo il primo giorno di scuola.
  Configurabile in `game_config` (`clock.compression`).
- Conseguenza voluta: a ogni accesso trovi un'ora del giorno diversa. Nessuno è costretto a
  giocare la mattina per vedere la scuola.
- **Fase episodio a 1:1**, con il resto del mondo che continua a 1:4 e il personaggio che
  «recupera» alla chiusura — è esattamente il meccanismo dell'incontro tattico di Atlantik, già
  collaudato.
- Il calendario è ciclico ma **completo**: passa per tutti i momenti che in KOR contano.

| Periodo | Evento di ambientazione |
|:---|:---|
| Aprile | Inizio dell'anno, ciliegi, cerimonia d'ingresso, reclutamento dei club |
| Maggio | Esami di metà periodo, gita scolastica |
| Giugno | Stagione delle piogge, ombrelli condivisi |
| Luglio-Agosto | Vacanze, mare, festival estivo con gli *yukata*, fuochi d'artificio, ritiri dei club, montagna |
| Settembre | Rientro, festival culturale, festival sportivo |
| Ottobre-Novembre | Foglie rosse, esami, il freddo che arriva |
| Dicembre | Natale (l'episodio più importante della serie), neve, sci sulla Montagna d'Inverno |
| Gennaio | Capodanno, primo sogno dell'anno, visita al tempio |
| Febbraio | San Valentino (il *giri-choco* e l'*honmei-choco*), esami d'ammissione |
| Marzo | *White Day*, diplomi, addii — poi il ciclo riparte |

- **Meteo**: campo continuo deterministico, funzione di (seme, istante, posizione) — nessuna
  cella salvata a DB. È il modello già scritto per Atlantik, riusabile quasi tale e quale. Serve
  perché in KOR la pioggia è un evento narrativo (ombrello condiviso, raffreddore, Sindrome di
  Ranma).

### 4.2 La mappa

Il quartiere è ispirato — come l'originale — a **Umegaoka, Gotokuji e Shimokitazawa**, nel
municipio di Setagaya a Tokyo. Non è una riproduzione topografica: è una mappa di gioco a
luoghi collegati, disegnata su Canvas nello stile delle tavole di Matsumoto.

| Luogo | Ruolo di gioco |
|:---|:---|
| **I Cento Gradini** | Punto di partenza di ogni personaggio. Timeslip attivo in condizioni rare. Il posto dove si incontra la gente per caso. |
| **Liceo Kōryō** | Aule, corridoio, tetto, palestra, cortile, dietro la palestra, aule dei club. Cuore della vita sociale diurna. |
| **ABCB** | Il bar del Master. Madoka ci lavora. Luogo neutrale dove tutti si incontrano e dove il Master *sa* delle cose. |
| **Palazzina Kasuga** | Casa di Kyosuke e delle gemelle. Accessibile solo se invitati. |
| **Casa Ayukawa** | Madoka ci vive sola. Quasi mai accessibile: è un premio narrativo. |
| **Il viale degli alberi** (Yurinoki Dōri) | Percorso casa-scuola. Il posto delle conversazioni a due. |
| **Il passaggio a livello** | Attese, separazioni, il treno che passa nel momento sbagliato. |
| **La stazione** | Partenze e arrivi, gite fuori quartiere. |
| **Il parco** | Panchine, altalene, fontana. Confessioni e litigi. |
| **L'argine del fiume** | Il posto dove si va a pensare. |
| **Il tempio** | Capodanno, festival, il gatto. |
| **La sala giochi / il negozio di dischi** | Ritrovo dei ragazzi, informazioni, voci. |
| **Il supermercato / la strada commerciale** | Cucina, regali, incontri banali che diventano importanti. |
| **Il luna park** | Montagne russe: il veicolo canonico degli universi paralleli. |
| **La spiaggia** | Solo d'estate. Via treno. |
| **La Montagna d'Inverno** | Casa dei nonni. Neve tutto l'anno sulla vetta accanto. Viaggio, non spostamento. |

### 4.3 Le voci

Il collante del multigiocatore asincrono. Non è una chat: è un modello di propagazione.

- Ogni azione osservabile genera un **fatto** legato a (luogo, istante, protagonisti).
- Ogni personaggio presente ne acquisisce una **versione soggettiva**, con una precisione che
  dipende da distanza, luce, attenzione, TESTA.
- Le versioni si propagano quando due personaggi si parlano, **degradandosi a ogni passaggio**:
  i dettagli si perdono, i nomi si sostituiscono con «uno del secondo anno», e la valutazione si
  polarizza.
- Dopo tre o quattro passaggi una voce non è più informazione: è pettegolezzo. Ed è esattamente
  così che funziona una scuola.
- Ne discendono, senza dover programmare nulla di specifico: la gelosia, il malinteso sul conto
  di un giocatore che non c'era, e la caccia all'esper.

---

## 5. Il personaggio

### 5.1 Abilità principali (1-15, 15 è il massimo umano)

| Abilità | Copre | Nota |
|:---|:---|:---|
| **RISSA** | Prontezza fisica, coraggio del corpo, cavarsela in una zuffa | Ereditata dall'originale. Il combattimento è raro e comico. |
| **TESTA** | Intelligenza, studio, inventiva, scuse credibili | Governa il tiro di intuizione e la **Copertura**. |
| **DAI-SUKI** | Aspetto, carisma, impressione che si fa | Ereditata. Alimenta KAKKO. |
| **CUORE** | Capacità di dire ciò che si prova, sincerità sotto pressione | **Aggiunta nostra.** Governa confessioni, chiarimenti, resistenza al Fraintendimento. |

Creazione: 1d10 per ciascuna (con ritiro sotto 3, erede della regola «Col cazzo» ripulita),
più 10 punti da distribuire, massimo 15 per abilità.

### 5.2 Abilità secondarie (1-10, oppure sì / così così / no)

`INGLESE` · `SPORT` · `GUIDA` · `KAKKO` · `NUOTO` · **`MUSICA`** · **`CUCINA`** · **`CANDORE`**

Le prime cinque vengono dall'originale con le stesse formule. Le ultime tre sono nostre:
- **MUSICA** — Madoka suona sax e chitarra, Hikaru balla, c'è una band e un talent show. Senza
  questa abilità metà dell'opera è ingiocabile.
- **CUCINA** — il *bentō* è un atto d'amore dichiarato e un'arma sociale. Manami cucina per
  tenere insieme la famiglia; Kurumi cucina per distruggerla.
- **CANDORE** (ex VERGINITÀ) — quanto ti smonta l'imbarazzo. Alto = ti travolge, con tutte le
  gag del caso; basso = resti di sasso, e nessuno capisce cosa provi.

### 5.3 Risorse

| Risorsa | Intervallo | Recupero |
|:---|:---|:---|
| **PF** (punti ferita) | 1d10+4, +1/+2/+3 per RISSA alta | 1/3 in 20 min di riposo, +1 ogni 2 ore, tutto con una notte di sonno |
| **PP** (punti potere) | 1d10/2+6 → 7-11. Solo esper. | 1 ogni 30 min di riposo, 1 ogni ora di stress lieve, 1 ogni 2 ore di stress forte, tutti con una notte |
| **COMPOSTEZZA** | 0-10, deriva da CANDORE e dalla situazione | Si ricostituisce lentamente; a 0 si combina un disastro pubblico |

Sotto 1/3 dei PP: stanchezza, 25% di addormentarsi. Regola dell'originale, tenuta.

### 5.4 Esper e non-esper: due mestieri

**L'esper** ha un potere primario e tre secondari (tabelle del 1990, con gli intervalli
sovrapposti corretti). Verbi propri: *usare, coprire, nascondere, teletrasportare, leggere*.
Il suo problema è che ogni soluzione che ha a disposizione lo espone.

**Il non-esper** non ha poteri e non è un personaggio di serie B. Ha:
- **Intuizione** (30+TESTA×2%): il tiro con cui *capisce*. È l'unico che può accorgersi di
  qualcosa.
- **Il Taccuino**: raccoglie anomalie osservate. Tre anomalie coerenti sullo stesso soggetto
  aprono un **sospetto fondato**, che è una vera meccanica di gioco con conseguenze pesanti.
- **Peso sociale**: reputazione, club, amicizie. Può muovere le voci molto meglio di un esper,
  che deve stare basso.
- **Presenza**: alcuni luoghi e alcune scene sono accessibili solo a chi non ha nulla da
  nascondere.

E soprattutto: un non-esper **può diventare un confidente**. Un esper che rivela
volontariamente il proprio segreto a un non-esper trasforma la minaccia più grande nell'alleato
più forte — è letteralmente l'arco di Hikaru nel primo episodio, ed è la mossa più rischiosa e
più bella del gioco.

### 5.5 Tratti

Tre tratti tirati alla creazione su una tabella riscritta (24 voci, come l'originale, ma
radicate nel Giappone del 1987). Ogni tratto porta modificatori, un **obiettivo personale** e
almeno una scena dedicata.

---

## 6. I poteri

Le tabelle e i limiti sono quelli dell'originale — che sono, ricordiamolo, *ricavati dal manga
con riferimento a volume e pagina*. Tre modifiche di sistema:

1. **Intervalli corretti.** L'originale ha due sovrapposizioni (29-32/32-37 nei secondari,
   69-70/70-74 nei tratti). Corretti, e la correzione è annotata in `FONTI.md`.
2. **MANIPOLAZIONE DELLA VOCE** è descritta nel testo ma manca dalla tabella dei secondari:
   reinserita.
3. **CONVERSIONE ENERGIA POTENZIALE-CINETICA** non è giocabile. L'autore stesso scrive che «è
   qui solo perché ci facciate una risata». Resta nel mondo come **fenomeno**, non come potere
   di un personaggio giocante.

### 6.1 Il Controllo

Ogni potere posseduto ha un valore di **Controllo** 0-100, che parte basso e sale con l'uso
riuscito. Il Controllo, e **solo** il Controllo, migliora nel tempo:

- riduce la probabilità di **incidente** (il potere fa qualcosa di leggermente diverso da quello
  che volevi — teletrasporti mezzo metro più in là, sollevi anche la sedia accanto);
- riduce di 1 il costo in PP alle soglie 40 e 80;
- alza la soglia di peso/distanza entro i limiti canonici, mai oltre.

Il numero dei poteri non cambia mai. Questo rispetta la regola esplicita dell'originale e tiene
in piedi il Segreto.

### 6.2 Condizioni che sballano i poteri

Direttamente dal regolamento: **malattia** (teletrasporti casuali, telecinesi che sbaglia
bersaglio), **ubriachezza** (poteri che partono da soli), **agitazione o paura** (50% di errore
sul Cambio d'Identità), **PP sotto un terzo**. Queste sono le condizioni che *generano le
storie*, e infatti nel manga è sempre così.

---

## 7. Il Segreto: il ciclo di gioco centrale

```
            usi un potere
                  │
                  ▼
      ┌───── chi era presente? ─────┐
      │   (luogo, ora, luce, folla) │
      └──────────────┬──────────────┘
                     ▼
        per ogni testimone: tiro di NOTA
        (attenzione · distanza · TESTA · quanto era vistoso)
                     │
        ┌────────────┴────────────┐
        ▼                         ▼
   non ha notato            ha notato → INCIDENTE APERTO
        │                         │
        │            ┌────────────┼────────────┬───────────────┐
        │            ▼            ▼            ▼               ▼
        │       SCUSA         DIVERSIVO    COMPLICE      NON FARE NULLA
        │      (TESTA)       (DAI-SUKI)   (un altro PG)   (Kurumi style)
        │            │            │            │               │
        │            └────────────┴─────┬──────┴───────────────┘
        │                               ▼
        │                     riuscita? l'incidente si chiude
        │                     fallita?  il testimone acquisisce
        │                               un'ANOMALIA sul tuo conto
        ▼                                        │
    nessuna traccia                              ▼
                                      3 anomalie coerenti
                                               │
                                               ▼
                                     SOSPETTO FONDATO
                                               │
                              ┌────────────────┼────────────────┐
                              ▼                ▼                ▼
                        ti affronta      lo dice in giro    ti osserva
                       (occasione di      (VOCI, calore      in silenzio
                        confidarti)        del quartiere)   (il peggiore)
```

- **Calore del quartiere**: come i settori di Atlantik. Sale dove si fanno cose vistose, decade
  nel tempo. Alto = più gente che guarda, più giornalisti, più curiosi, più probabilità che
  qualcuno con la macchina fotografica sia nel posto sbagliato (episodio 36 della serie).
- **Trasloco**: se il Sospetto fondato su di te supera la soglia e non lo disinneschi, la tua
  famiglia si trasferisce. Perdi luoghi, legami locali e reputazione; tieni poteri, tratti,
  Controllo e l'album dei ricordi. Rientri nel quartiere come **trasferito di recente** — che è
  un tratto del regolamento originale, e chiude il cerchio.
- **Confidarsi** è la valvola: rivelare volontariamente a un non-esper azzera le sue anomalie e
  crea un legame di categoria diversa. Ma se quel legame si guasta, il rischio torna, moltiplicato.

---

## 8. Le relazioni

Grafo direzionale. Per ogni coppia ordinata (A→B) due valori indipendenti:

- **Affetto** (-100 … +100) — quanto A tiene a B.
- **Fraintendimento** (0 … 100) — quanto A crede di B qualcosa che non è vero.

### Regole di sistema

1. Il Fraintendimento **non nasce dalle bugie**: nasce dalle informazioni parziali. Vedere
   qualcuno uscire dal bar con un'altra persona è vero; il significato che gli dai è tuo.
2. Il Fraintendimento **non decade da solo** con il tempo. Decade solo con una **conversazione
   di chiarimento**, che costa CUORE e che può fallire.
3. Un Affetto alto **con** un Fraintendimento alto è lo stato più instabile e più fecondo del
   gioco: è dove vive tutta *Kimagure Orange Road*.
4. I gesti sono **ambigui per costruzione**. Ogni azione relazionale ha una lettura prevista e
   una o più letture alternative, che scattano a seconda di chi guarda e di cosa sa già.

### Verbi relazionali
Parlare · Ascoltare · Accompagnare a casa · Condividere l'ombrello · Preparare un *bentō* ·
Fare un regalo · Invitare · Difendere · Evitare · Mentire · Chiarire · **Confessarsi**

### Il Cappello Rosso
Un oggetto unico per server. Non è un potenziamento: è un **simbolo**. Passa di mano solo con
una scena di significato e resta nell'album dei ricordi di chi l'ha avuto. È un modo di dire una
cosa che non si riesce a dire a parole — cioè esattamente la sua funzione nel manga.

---

## 9. Gli episodi

Un **episodio** è una storia chiusa, cooperativa, a turni, con finestra reale di 20-40 minuti,
2-5 giocatori e un cast di PNG.

- **Si aprono da condizioni del mondo**, non da un pulsante: un Sospetto che matura, una stagione
  che arriva, un oggetto trovato, una voce che raggiunge la persona sbagliata, un *yochimu*
  (sogno profetico) di un giocatore.
- Hanno **titolo in stile KOR** — due segmenti e un punto esclamativo. Il motore lo genera dai
  fatti: *«Sospetti in aula! Il taccuino di Hino»*.
- Hanno un **finale valutato**, non vinto: il segreto è salvo? i legami dove sono finiti? chi ha
  pagato il prezzo? Il risultato entra nell'**album dei ricordi** come una scheda illustrata.
- **Se manchi**, il tuo personaggio è comunque nella scena e agisce secondo il suo carattere e i
  suoi tratti — come l'I.WO in Atlantik. Non blocca gli altri.

### Catalogo iniziale
- **I 7 episodi del regolamento del 1990**, riscritti: il rapimento della cuginetta da parte
  dell'agenzia straniera; il cugino rinnegato; il timeslip della squadra di tennis; il mondo
  parallelo dei motociclisti; e — spostati in un archivio «fuori canone» perché rompono
  l'«eterno 1987» — i tre episodi post-atomici e quello di Akira.
- **Episodi ricavati dai 48 canonici**, non come rifacimenti ma come *situazioni*: il malinteso
  del matrimonio (ep. 11), i funghi della verità (ep. 33), l'orologio che ferma il tempo
  (ep. 41), il video che riprende un potere (ep. 36), il Natale che si ripete (ep. 38), la
  ricerca del primo amore (ep. 47).
- **Episodi nuovi**, generati dal motore a partire dallo stato reale del quartiere. Sono il
  contenuto a lungo termine.

---

## 10. Progressione

Non ci sono livelli, punti esperienza né potenziamenti. Si accumulano quattro cose:

1. **Controllo** sui propri poteri (solo esper).
2. **Legami** — il grafo delle relazioni è il vero punteggio.
3. **Reputazione** per zona e per gruppo (la classe, il club, i clienti dell'ABCB, i teppisti).
4. **Ricordi** — album di scene concluse, illustrate, permanenti. Sopravvivono al Trasloco. È la
   collezione, ed è anche il diario narrativo del personaggio, esportabile in testo.

---

## 11. Tecnica

Stessa impalcatura di [SubSpazio] e [Atlantik], che su questa macchina è collaudata.

- **PHP 8.4 senza framework**, front controller unico via `PATH_INFO` (su `/var/www/`
  `AllowOverride` è `None`: niente `.htaccess`).
- **MariaDB**, database e utente `kor_orangeroad`, segreti in
  `/etc/orangeroad/config.php`, fuori dal DocumentRoot.
- **Percorso** `/var/www/orangeroad`, servito come `/orangeroad/`. Materiale preparato in
  `/srv/orangeroad/` e spostato dopo il bootstrap.
- **Core portato** da Atlantik: `Config`, `Database`, `Router`, `Session`, `Csrf`, `View`,
  `Mailer`, `RateLimiter`. Namespace `App\`.
- **Mail** via Brevo, riusando il mittente verificato del forum.
- **Tick** da cron ogni minuto + **avanzamento pigro deterministico** su richiesta web. Nessun
  avanzamento che dipenda dal fatto che il cron abbia girato.
- **Rng xorshift64**, mai moltiplicazioni a 64 bit — in PHP traboccano in float e rompono il
  determinismo. (Trappola già pagata in Atlantik.)
- **Front-end**: JS vanilla + Canvas, nessuno *build step*. PWA con guscio in cache.
- **Rotte in italiano**: `/accesso`, `/iscrizione`, `/quartiere`, `/scuola`, `/abcb`,
  `/personaggio`, `/legami`, `/taccuino`, `/voci`, `/episodio/{id}`, `/ricordi`, `/albo`.
- **Estetica**: la tavolozza di **Akemi Takada** — arancioni caldi, azzurri pallidi, rosa
  polvere, bianco panna; niente nero puro. Tratto sottile, molta aria. Un tema «tramonto» e uno
  «notte» commutabili come l'illuminazione rossa notturna di Atlantik.
- **Determinismo verificabile**: ogni modulo di simulazione è una funzione pura con prova
  unitaria. Gli stessi criteri di rigore già usati per Atlantik.

---

## 12. Roadmap

| Fase | Contenuto | Esito verificabile |
|:---|:---|:---|
| **F0** ✔ | Fondamenta: core portato, front controller, autenticazione con conferma e-mail, `bin/console.php`, migrazioni, estetica Takada, prova e2e di autenticazione | Ci si registra, si conferma, si entra |
| **F1** ✔ | Mondo: orologio ciclico «eterno 1987», calendario e stagioni, meteo deterministico, mappa Canvas del quartiere, spostamento, presenza e tracce | Il quartiere gira da solo e si può attraversare |
| **F2** ✔ | Personaggio: modello scolastico canonico (medie + superiori, età dedotta dal compleanno), 4 abilità, 24 tratti riscritti, 8 abilità secondarie, PF/PP/Compostezza, esper vs non-esper, 16 poteri e Controllo | **Fatta.** Due personaggi molto diversi giocano schermate diverse |
| **F3** ✔ | **Il Segreto**: uso dei poteri, testimoni, tiri di Nota, Copertura, anomalie, Taccuino, Sospetto fondato, Voci, calore, Trasloco | **Fatta.** Un esper imprudente si fa scoprire e trasloca davvero |
| **F4** ✔ | Relazioni: Affetto/Fraintendimento, verbi relazionali, gesti ambigui, chiarimenti, gelosia, confessione, Cappello Rosso | **Fatta.** Si può rovinare un'amicizia per un equivoco e poi ricucirla |
| **F5** ✔ | Episodi: motore, cast, turni, finestra reale, agente autonomo per gli assenti, tre copioni seminati (funghi, acquazzone, sospetti in aula), album dei ricordi | **Fatta.** Tre giocatori giocano un episodio insieme dall'inizio alla fine, e chi non si fa vivo lo gioca il motore |
| **F6** ✔ | Multigiocatore pieno: le **voci** con precisione e tono che si degradano di bocca in bocca, 11 **abitanti canonici** che girano per il quartiere da soli, 10 **club** come secondo canale di propagazione, **bacheca** e **biglietti**, 14 **eventi stagionali** di server | **Fatta.** Il quartiere è pieno di gente che non hai mai incontrato ma di cui hai sentito parlare |
| **F7** ✔ | Rifinitura: album dei ricordi **illustrato** (SVG generato da luogo, stagione, ora e meteo), **esportazione del diario** in Markdown, **PWA** con service worker, **`/admin`**, comando **`bilancio`**, suoni opt-in generati con WebAudio | **Fatta.** Il gioco è finito |

Dopo F7 si aggiunge contenuto e comodità, non impalcatura. In ordine di arrivo:

- **22 fotografie dei luoghi** (3 fotografie vere sotto CC BY-SA, 19 immagini generate,
  distinte una per una in `assets/img/luoghi/crediti.php` — nessuna delle due cosa si
  spaccia per l'altra).
- **`/admin` in profondità**: utenti, moderazione con motivo obbligatorio, accessi e
  provenienze, 49 manopole raggruppate per famiglia, mappa della situazione, statistiche.
- **Uso da telefono e da tablet**, senza toccare la vista da monitor: la carta si
  ridimensiona sullo spazio disponibile e si trascina solo quando serve davvero.
- **La fotografia del personaggio** (migrazione `0015`): caricamento con ritaglio e
  centratura manuale, riscrittura in WebP a 320 pixel, nome del file uguale all'impronta
  del contenuto, e la stessa rotta per l'amministratore sul profilo di un giocatore.

---

## 12-bis. La sala dedicata a Izumi Matsumoto

*Nota dell'utente, 19 settembre 2026. Da costruire.*

Una sezione del sito — non una pagina di crediti, una **sala** — dedicata al maestro. Non è
decorazione: è il motivo per cui questo progetto esiste, e va fatta con la stessa cura del
resto.

### Quello che già sappiamo di lui, e che va raccontato

**Izumi Matsumoto** (まつもと泉), nato il **13 ottobre 1958 a Takaoka**, in provincia di
Toyama; morto a Tokyo il **6 ottobre 2020**, a sessantun anni, una settimana prima del suo
compleanno.

Tre cose che questa sala deve dire, e che la ricerca ha già trovato.

**Il quartiere è casa sua.** La scalinata dei cento gradini è quella del parco Takaoka Kojō;
il liceo Kōryō è la scuola media Kōryō; l'ABCB è un ristorante che si chiamava ABAB; la
stazione è Etchū-Nakagawa sulla linea Himi. Matsumoto non ha inventato un quartiere di
periferia: ha disegnato la città in cui era cresciuto e l'ha chiamata Tokyo. Chi gioca a
*Cento Gradini* cammina nella sua infanzia. (Fra l'altro, a Takaoka era nato anche Fujiko F.
Fujio, di cui Matsumoto fu compagno di scuola più giovane.)

**Voleva fare il musicista.** Era salito a Tokyo per suonare, e aveva lasciato perdere perché
non sapeva leggere gli spartiti. Quella sensibilità è finita tutta dentro l'opera — Madoka
che suona, la band, il talent show, i titoli dei capitoli che sono titoli di canzoni — e
c'è un brano delle colonne sonore in cui è lui a suonare la batteria.

**La malattia.** Per anni ha sofferto di una perdita di liquido cerebrospinale, riconosciuta
dai medici molto tardi, che gli ha impedito di lavorare per lunghi periodi. Ha scritto di
avere combattuto per cinque anni contro una malattia che non riusciva a vincere, e di volere
comunque tornare al tavolo da disegno — perché non riusciva a starne lontano. È la parte della
sua storia che l'utente ha chiamato «il suo amore per la sua opera», ed è esatto.

### Come farla

- Prosa nostra, non copiata: le fonti di appassionati sono protette quanto l'opera.
- Materiale già in casa: `fonti/canone/` (voce giapponese su di lui), la **Réflexion 36** del
  sito francese — carriera e bibliografia, più interviste tradotte da CyberFred (Tokyo 1993
  per *Kappa Magazine*; Barcellona 2010) — e la sezione *Autori* di orangeroad.it.
- **La fotografia è il punto aperto.** Una foto di una persona reale ha un autore e dei
  diritti: non si prende da un sito di appassionati. Servono un'immagine con licenza chiara,
  o il permesso di chi la detiene. Finché non c'è, la sala si apre lo stesso — con il suo
  nome, le sue date e le sue parole raccontate da noi — perché una pagina onesta senza
  ritratto vale più di una con un ritratto rubato.
- Tono: né agiografia né necrologio. Si racconta un ragazzo di Takaoka che voleva suonare,
  ha disegnato la sua città, e ci ha lasciato dentro una storia che dopo quarant'anni la
  gente rilegge ancora.

**Fatta il 19 settembre 2026**, in anticipo sulla rifinitura: `/santuario`, raggiungibile
dal piede di ogni pagina e da `/opera`. Le fotografie si trovano da sole in
`assets/img/maestro/` — se ne possono aggiungere o togliere senza toccare il codice, e
finché non ce n'è nessuna la pagina si apre lo stesso.

---

## 13. Registro delle fonti

Vedi [`FONTI.md`](FONTI.md). Criterio, ereditato da Atlantik: ogni dato di ambientazione ha un
campo **fonte**; i valori incerti sono dati come intervallo con un campo **confidence**; le
ricostruzioni sono marcate come tali e non spacciate per canone.

---

## 14. Questioni ancora aperte

1. **Le statistiche canoniche di Madoka e Kyosuke** (D15, D11 eccetera) vengono dal regolamento
   del 1990, che dichiara apertamente che sono «stime azzardate». Vanno rivalutate leggendo
   l'opera, non accettate come canone.
2. **I nomi dei tratti riscritti** sono in `db/seed/tratti.php` e si cambiano modificando il
   file e rilanciando `seed`: sono contenuto d'ambientazione, non implementazione, e vanno
   guardati con quell'occhio.
3. **Il tratto «Sindrome di Ranma»** è una citazione esterna. Va deciso se tenere le citazioni
   crossover del 1990 (Ranma, A-ko, Akira, Dirty Pair) come strato ironico o se ripulire tutto
   in favore del solo universo di Matsumoto. Propendo per tenerle **solo** dove l'opera stessa
   fa autoironia — e KOR ne fa parecchia.
4. **Il potere «Viaggio nel Tempo»** — deciso il 19/09/2026: **non è giocabile**. Resta come
   fenomeno del luogo (i Timeslip della scalinata) e degli artefatti. Il canone conferma: quello
   di Kyosuke è involontario, si attiva da solo.
