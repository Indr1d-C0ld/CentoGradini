# Il canone
## Rilettura sistematica dell'opera originale, e cosa cambia nel gioco

> Prima passata — 19 settembre 2026.
> Serve a sostituire con dati verificati le statistiche del regolamento del 1990,
> che il suo stesso autore dichiara «stime azzardate» (*wild guesses*).

---

## 0. Metodo

Il criterio è quello già usato per le altre ricostruzioni: **enumerare prima tutte le fonti
rilevanti, poi leggerle per intero**, invece di fermarsi ai primi risultati di una ricerca.
Dove serviva il testo esatto ho scaricato direttamente e ripulito l'HTML, senza passare da
sintesi automatiche.

Tre cose che questa disciplina ha già evitato:

1. **`kor.fandom.com` non è il wiki di Orange Road.** Ha il nome giusto e 151 pagine, e parla
   di tutt'altro (un'ambientazione fantasy). Chi si fosse fidato del dominio avrebbe importato
   dati di un'altra opera.
2. **Trentadue pagine scaricate erano tutte "Just a moment…"** — la protezione anti-bot di
   Cloudflare restituisce una pagina di attesa con codice 200. Pesavano tutte 5816 byte, il che
   è stato l'indizio; l'API del wiki invece risponde, e da lì sono arrivate le pagine vere.
3. **Il primo lettore dell'indice dei capitoli ne perdeva cinque su 156**, perché i numeri doppi
   di *Jump* (`1985年1・2号`) e l'ultima puntata hanno una forma diversa dalle altre. Se ne è
   accorto solo il controllo di completezza sulla sequenza 1-156.

---

## 1. Le fonti

| Fonte | Cosa dà | Affidabilità |
|:---|:---|:---|
| [Wikipedia giapponese, きまぐれオレンジ☆ロード](https://ja.wikipedia.org/wiki/きまぐれオレンジ☆ロード) (159 KB) | Personaggi, poteri, luoghi reali, 18 volumi, episodi, film, OAV | `documentato`, con note a piè di pagina su volume e pagina |
| [Wikipedia giapponese, 鮎川まどか](https://ja.wikipedia.org/wiki/鮎川まどか) | Cronologia puntuale di Madoka, abilità con **riferimento al capitolo** | `documentato` |
| [ジャジャン研 — dati di pubblicazione su *Jump*](https://www.jajanken.net/sakuhins/PGJnODg84L) | **Tutti e 156 i capitoli**: numero, anno, numero di rivista, titolo, posizione in classifica | `documentato` — la pagina dichiara come fonte la *Media Arts Database* dell'Agenzia per gli Affari Culturali giapponese |
| [orangeroad.fandom.com (ja)](https://orangeroad.fandom.com/ja/), 32 pagine | Schede dei personaggi; in gran parte ricalca Wikipedia, ma aggiunge dettagli | `documentato` (fan wiki: più debole) |
| [Wikipedia inglese](https://en.wikipedia.org/wiki/Kimagure_Orange_Road) | Elenco episodi TV e OAV, dati editoriali occidentali | `documentato` |
| [orangeroad.it](https://www.orangeroad.it/), 550 pagine | 59 sezioni: riassunti capitolo per capitolo, luoghi, cronologia, simbolismo, censure, interviste | `documentato` (sito di appassionati, curato e con riferimenti) |
| [madoka.ayukawa.free.fr](http://madoka.ayukawa.free.fr/kor.htm), 467 pagine | 131 «Réflexions» analitiche, e soprattutto la **FAQ storica di KOR** | `documentato` |
| **La FAQ di KOR**, versione francese 3.17 (13 marzo 2025) | Novantadue domande e risposte su tutta l'opera. Tradotta dall'originale americano e ampliata da CyberFred e TCV | `documentato`, ed è la fonte secondaria più densa che abbiamo trovato |

Materiale archiviato in `fonti/canone/` e `fonti/capitoli_manga.tsv` (156 righe).

---

## 2. Correzioni al regolamento del 1990

Le più pesanti sono quattro, e tre toccano il cuore del sistema.

### 2.1 Kyosuke **non ha** la telepatia

Il regolamento gliela assegna fra i poteri secondari. Il canone è esplicito e ci insiste:

> «このようにさまざまな超能力が使えるが、テレパシー（精神遠隔感応）は使えず、他人の心を
> 読むことはできない» — *sa usare tutti questi poteri, ma la telepatia no: non può leggere
> nel pensiero degli altri.*

E la scheda del wiki dedicato lo chiama proprio il punto della faccenda: «テレパシーは駄目
なのがミソ» — *il bello è che la telepatia non gli riesce*.

**Perché conta.** Non è un dettaglio da schedario: è il motore della storia. Kyosuke passa
diciotto volumi a non sapere cosa prova Madoka, potendo teletrasportarsi. Un protagonista che
legge nel pensiero non ha trama. Nel nostro gioco questo diventa una regola di progetto:
**telecinesi e telepatia non stanno bene insieme** su uno stesso personaggio, e chi ha molti
poteri pratici ha comunque un buco proprio dove servirebbe.

### 2.2 Il viaggio nel tempo **non si comanda**

> «タイムリープ（過去、未来、場合によってはパラレルワールドへ移動。ただ自分の意志通りには
> 使えず、偶発的に発動される）» — *time-leap (verso il passato, il futuro, a volte mondi
> paralleli. Però **non si usa a volontà**: si attiva per caso.)*

Il nonno invece lo padroneggia («恭介がコントロールできない時間移動も自由に使いこなす»).

**Perché conta.** Conferma indipendente della decisione presa il 19/09: il viaggio nel tempo
non è un potere giocabile, è un fenomeno del luogo. Il canone dice esattamente questo.

### 2.3 Kyosuke è **scarso** a scuola e nello sport

> «勉強もスポーツも苦手な方である» — *è dalla parte degli scarsi sia nello studio sia
> nello sport.*

Il regolamento gli dà RISSA 9 e TESTA 9 su 15, cioè sopra la media. È sbagliato, e in modo
interessante: Kyosuke è il personaggio più potente del suo mondo e **un ragazzo mediocre**.
È tutta la sua caratterizzazione.

### 2.4 Manami e Kurumi erano scambiate

Il regolamento inverte le gemelle in due punti. Il canone:

- **Manami** è la gemella **maggiore**, coda di cavallo e **occhiali**, giudiziosa, fa tutte le
  faccende di casa, fa i compiti delle vacanze subito. Tifa per Madoka.
- **Kurumi** è la **minore**, capelli semilunghi, usa i poteri d'istinto, **ipnosi** (soprattutto
  su Kyosuke), cucina disastrosa, scarsa a scuola (fa gli esami di recupero insieme a Hikaru),
  ed è **lei la causa dell'ultimo trasloco della famiglia**. Tifa per Hikaru.

Del «personaggio grasso» che il regolamento attribuisce a Kurumi non c'è traccia nel canone.

---

## 3. I poteri, ricostruiti

Nomenclatura canonica (il testo giapponese usa i termini della parapsicologia, non quelli del
regolamento americano):

| Canone | Chi | Note |
|:---|:---|:---|
| サイコキネシス — psicocinesi | Kyosuke, Manami, Kurumi, nonni, Kazuya | Il potere di base della stirpe |
| テレポーテーション — teletrasporto (persone **e** oggetti) | Kyosuke, nonni. **Kazuya nel manga sembra non averlo** | |
| クレヤボヤンス — chiaroveggenza a distanza | Kyosuke | **Non** è «vista a raggi X»: è vedere *un altro luogo*, non attraverso le cose |
| プレコグニション — sogni premonitori | Kyosuke | |
| ヒュプノシス — **auto**ipnosi | Kyosuke | Su di sé |
| 催眠術 — ipnosi su altri | Kurumi | Soprattutto su Kyosuke |
| タイムリープ — salto temporale | Kyosuke (**involontario**), nonno (a volontà) | |
| テレパシー — telepatia | **Kazuya**, e non Kyosuke | |
| 変身 — apparire come un'altra persona | Akane | **Un solo bersaglio per volta** nel manga; l'OAV ne fa due ed è un errore |

Il nonno è descritto come «ほぼ全能» — quasi onnipotente — e la nonna come **custode
dell'isola** dove sono nascosti tutti i segreti della stirpe, con il compito di tenerne
lontani gli estranei.

**Kazuya è più forte di Kyosuke**: «その他の超能力の威力も恭介よりかなり強く、恭介いわく
「大超能力者」» — *gli altri poteri li ha anche parecchio più forti di Kyosuke, che lo chiama
«grande esper»*. Un bambino di otto anni è il personaggio più potente in circolazione, il che
è perfettamente in tono.

---

## 3-bis. Chi ha quale potere, secondo la FAQ

La FAQ storica, alla domanda «chi detiene quale potere», dà l'elenco completo. È la fonte
più esplicita che abbiamo trovato, e su tre punti corregge quello che avevamo.

| Personaggio | Poteri secondo la FAQ |
|:---|:---|
| **Kyosuke** | Teletrasporto, telecinesi, premonizione, *lampi* telepatici, scambio di corpo, viaggio nel tempo, autoipnosi — più la scarica distruttiva dell'episodio 48 e dell'OAV di Akane |
| **Manami** | Teletrasporto, telecinesi |
| **Kurumi** | Teletrasporto, telecinesi, ipnosi |
| **Akane** | Teletrasporto, illusioni che agiscono sulla mente |
| **Kazuya** | Teletrasporto, telecinesi, scambio di corpo, **bloccare i poteri altrui**, ed è **il solo telepate della famiglia** |
| **I nonni** | Praticamente qualunque cosa, più una collezione di oggetti: un orologio che ferma il tempo, una corda che scambia le menti, un pappagallo telepate |

### La regola che ho dovuto ritirare

Avevo introdotto una **regola della stirpe**: chi ha il teletrasporto non ha la telepatia e
viceversa. La fondavo su una parentesi della voce giapponese — «nel manga sembra che Kazuya
non possa teletrasportarsi» — che è dubitativa, e che la FAQ contraddice apertamente: Kazuya
si teletrasporta *e* è telepate.

Le due fonti però concordano su qualcos'altro, e su quello si può costruire: **la telepatia è
rarissima**. Nella famiglia ce l'ha una persona sola, e Kyosuke — che ha tutto il resto — non
ce l'ha. Quindi il divieto è sparito e al suo posto c'è una banda del tiro: la telepatia come
potere principale è passata dal **32%** del regolamento del 1990 al **6%**. Misurata su
duemila tiri: telecinesi 53,5%, teletrasporto 40,9%, telepatia 5,7%.

Un divieto sarebbe stato più pulito da programmare e sarebbe stato sbagliato.

### Tre risposte che valgono una meccanica

- **L'ipnosi si impara** (FAQ 42). Kurumi riesce a ipnotizzare perché il nonno le ha detto
  «dov'è il punto debole». Kyosuke ci prova con Madoka nell'episodio 39 e fallisce, perché
  nessuno gliel'ha insegnato: *ha* il potere e non sa usarlo. È una distinzione che il nostro
  Controllo non fa ancora — da noi un potere posseduto è un potere utilizzabile.
- **Lo scambio di corpo funziona anche sui non-esper** (FAQ 43): Kyosuke con il cantante
  Hayakawa, Kazuya con il gatto Jingoro nell'episodio 29.
- **I poteri fanno cilecca e partono da soli** (FAQ 45): Kyosuke si è teletrasportato dalla
  vasca da bagno dentro l'automobile di Umao e Ushiko. Conferma le condizioni che li sballano.

### E un potere che non abbiamo

Kazuya sa **impedire agli altri di usare i propri poteri**. Non è nella nostra tabella, e in
un gioco costruito attorno al Segreto sarebbe un potere enorme: è il modo di impedire a
qualcun altro di combinare il guaio che poi tocca coprire a tutti. Da valutare per F5.

---

## 3-ter. Due domande vecchie, chiuse

**I gradini sono cento.** La FAQ (domanda 12) racconta che nell'episodio 1 Kyosuke ne conta
100 e Madoka sostiene che siano 99; finiscono per accordarsi su novantanove e mezzo. Nell'OAV
*Message in Rouge* Madoka li ricontrolla e sono cento. In `FONTI.md` il conteggio passa da
`ricostruita` a `canone` — e il compromesso a novantanove e mezzo è troppo bello per non
finire nel gioco.

**I Kasuga traslocano quando i loro poteri vengono scoperti pubblicamente** (FAQ 26). Una
riga secca, e conferma esattamente il meccanismo del Trasloco: non è una persona che sospetta,
è il diventare di pubblico dominio.

**Manami è la maggiore, e la madre morì dando alla luce Kurumi** (FAQ 47). La voce giapponese
diceva solo «morì quando nacquero le gemelle», con un punto interrogativo. Resta una fonte
secondaria, ma è più precisa.

---

## 4. Madoka, con i riferimenti ai capitoli

La voce dedicata cita capitolo per capitolo. Contro le «stime azzardate» del 1990:

| Abilità | Attestazione |
|:---|:---|
| Sci | cap. 44 «スノー・スケッチ», cap. 93 «冬山恐怖伝説», anime 46, OAV *White Lovers* |
| Tennis | cap. 30, 60, 109, 157, anime 20 |
| Surf | anime 18 |
| Atletica | cap. 101, 116 |
| Musica | Canto, chitarra, tastiere a livello professionale; nell'anime anche sax e pianoforte |
| Cucina, cucito, equitazione, inglese | Voce principale |
| **Punto debole** | Storie di fantasmi e horror: si aggrappa a chi ha vicino |

I riferimenti sono stati **verificati incrociando l'indice dei 156 capitoli**: i titoli dei
capitoli 30, 44, 60, 93, 101, 109 e 116 corrispondono esattamente. La fonte regge.

Altri dati fermi: nata il **25 maggio 1969** (nel volume 8 era 25 novembre, corretto
dall'autore sulla copertina del volume 9); seconda figlia; genitori musicisti di fama
mondiale quasi sempre all'estero; vive **sola** in una grande casa in stile occidentale da
quando la sorella si è sposata; smette di fumare perché Kyosuke le dice una frase brutale; da
quel momento i voti le salgono di colpo ai primi della classe (vol. 18, p. 132). Il modello
dichiarato dall'autore è **Akina Nakamori** all'epoca di *Shōjo A*.

---

## 5. La scoperta che ribalta un'assunzione: **Takaoka, non Setagaya**

Avevo costruito il quartiere su Umegaoka, Gōtokuji e Shimokitazawa, nel municipio di Setagaya
a Tokyo, seguendo la Wikipedia inglese. La Wikipedia giapponese, citando il **Kitanippon
Shimbun** (2021), dice un'altra cosa: i modelli reali sono a **Takaoka, prefettura di Toyama**
— la città di Izumi Matsumoto.

| Luogo dell'opera | Modello reale |
|:---|:---|
| **100段階段**, la scalinata dei cento gradini | La scalinata di pietra del **parco Takaoka Kojō** |
| **高陵学園** Kōryō Gakuen | La **scuola media Kōryō** di Takaoka |
| **abcb** (あばかぶ) | Il ristorante **ABAB** di Takaoka |
| **中川駅**, la stazione | **Etchū-Nakagawa**, linea Himi delle JR West |

Le due affermazioni non si escludono: il quartiere *narrato* è periferia di Tokyo, i luoghi
*disegnati* vengono dalla città natale dell'autore. Ma tre conseguenze pratiche:

1. **La scalinata dei cento gradini è il nome canonico.** «Cento Gradini» non è una nostra
   invenzione poetica: è come si chiama nell'opera. Il titolo del gioco è a posto.
2. **La stazione ha un nome**: si chiama **Nakagawa**, e il nostro `stazione` va rinominato.
3. **Manca un luogo, ed è importante.**

### Il luogo che manca: 想い出の樹, l'albero dei ricordi

Un albero nel parco, con inciso «6年後にまたここで…まどかへ» — *fra sei anni ancora qui…
per Madoka*. È dove il Kyosuke saltato nel passato incontra la Madoka bambina, la salva mentre
sta per cadere, e dove lei lo bacia. È dove lascia il messaggio che verrà mantenuto. Nel
finale della serie TV il bacio fra i due avviene sotto questo albero.

Dopo il cappello di paglia rosso è l'oggetto più carico dell'opera, e nel quartiere non c'è.
Va aggiunto.

---

## 6. La scuola: il modello è sbagliato

Il canone è preciso e il nostro modello no.

- All'inizio Kyosuke, Madoka **e Hikaru** sono al **中等部**, la sezione **media** del Kōryō
  Gakuen: loro al terzo anno, Hikaru (due anni sotto, con Manami e Kurumi) al primo.
- **Aprile 1985**: passano al 高等部, le superiori.
- Sugi Hiromi si trasferisce nella loro classe «nell'autunno del primo anno di superiori».

Quindi Kōryō Gakuen è **un istituto con medie e superiori insieme**, e in gioco convivono
ragazzi dagli undici ai diciotto anni. Il nostro `anno_scuola` 1-3 di «liceo» copre metà
della scuola che l'opera racconta.

### E l'«eterno 1987» regge — meglio di come l'avevo giustificato

La cronologia canonica di Madoka:

| Data | Evento |
|:---|:---|
| aprile 1984 | Incontra Kyosuke. Smette di fumare. |
| aprile 1985 | Passa alle superiori. |
| 1986 | I genitori spingono per l'America: li convince a lasciar perdere. |
| aprile 1987 | Partecipa al talent show e lo vince. |
| settembre 1987 | Viene notata, decide di partire. **Kyosuke le si dichiara.** |
| 20 settembre 1987 | Parte per l'America. Hikaru le restituisce il cappello. |
| 1988 | Torna. |

Avevo scelto il **6 aprile 1987** come primo giorno del ciclo per ragioni di calendario. Si
scopre che è la data giusta per una ragione molto migliore: **è l'inizio dell'ultimo anno
scolastico, quello in cui tutto si scioglie**. Ad aprile 1987 Madoka non è ancora stata
notata, Kyosuke non si è ancora dichiarato, il cappello è ancora suo, e mancano cinque mesi
alla partenza.

L'eterno 1987 non è un anno qualunque congelato: **è l'ultimo anno, tenuto aperto**. Il
calendario si ferma un passo prima della fine, e ricomincia. Questo va scritto nel documento
di progetto, perché è la differenza fra una scelta tecnica e una scelta di senso.

---

## 7. Le prime schede rifatte

Scala 1-15 del regolamento, ma i numeri ora rispondono al canone. `CUORE` è nostra e non ha
attestazione diretta: si deduce dal comportamento, ed è marcata come tale.

| Personaggio | RISSA | TESTA | DAI-SUKI | CUORE | Fondamento |
|:---|:---:|:---:|:---:|:---:|:---|
| **Kyosuke** | 6 | 6 | 10 | 3 | «scarso sia a scuola sia nello sport»; piacevole ma non appariscente; l'indecisione è il suo tratto |
| **Madoka** | 13 | 14 | 15 | 4 | Atletica completa e sa battersi; voti ai primi della classe; «nemmeno fra le celebrità»; ma non dice mai quello che prova |
| **Hikaru** | 5 | 4 | 9 | 13 | Scarsa a scuola quanto Kurumi, esami di recupero; solare e magnetica; **dice tutto subito**, ed è l'unica |
| **Manami** | 5 | 11 | 9 | 8 | Giudiziosa, manda avanti la casa |
| **Kurumi** | 6 | 4 | 8 | 10 | Pessima a scuola, impulsiva, dice quello che pensa |
| **Yūsaku** | 13 | 6 | 8 | 4 | Karate secondo dan; davanti a Hikaru diventa un coniglio |
| **Kazuya** | 5 | 12 | 11 | 11 | Sveglio oltre l'età; la telepatia gli toglie ogni pudore |
| **Akane** | 11 | 11 | 12 | 7 | Mascolina, diretta, ma sul suo sentimento per Madoka tace |
| **Il Master** | 7 | 13 | 9 | 12 | Sa tutto di tutti e sceglie quando parlare |

Rispetto al 1990: Kyosuke scende parecchio, Madoka sale, Hikaru resta bassa dove il 1990 la
metteva bassa — ma con CUORE 13 diventa il personaggio più *forte* del gruppo nell'unica
statistica che in questa storia decide qualcosa.

**Il triangolo in una riga:** i tre protagonisti hanno DAI-SUKI 10, 15 e 9 — molto diversi — e
CUORE 3, 4 e 13. Chi ha il coraggio di parlare è quella che non può essere ricambiata. La
storia dura diciotto volumi per questo, e adesso il sistema lo dice con i numeri.

---

## 8. Cosa resta da fare

1. **I 156 capitoli ci sono, i loro contenuti no.** `fonti/capitoli_manga.tsv` ha numero, anno,
   numero di *Jump*, titolo e posizione in classifica di tutti. Manca l'associazione ai 18
   volumi: dedurla dai titoli dei volumi non funziona (il titolo di un volume non è sempre il
   suo primo capitolo — sei su diciotto non corrispondono a nessun capitolo con quella grafia).
   Serve la *Media Arts Database* dell'Agenzia per gli Affari Culturali.
2. **Il censimento degli usi dei poteri**, capitolo per capitolo, con peso, distanza e durata.
   L'impalcatura c'è: `bin/censimento_poteri.php` legge i riassunti in `fonti/riassunti/`, li
   incrocia con l'indice dei 156 capitoli e produce `fonti/censimento_poteri.tsv`. Mancano
   **144 riassunti su 156**: orangeroad.it è andato in HTTP 500 mentre li scaricavo (vedi
   `FONTI.md`) e `fonti/siti/riprendi_capitoli.py` aspetta che torni su.
3. **Quante volte i Kasuga hanno traslocato.** Il *perché* è chiuso (FAQ 26: quando i poteri
   diventano di dominio pubblico); resta da trovare il numero esatto, che è il parametro che
   tara la soglia.
4. **Gli artefatti canonici**: l'orologio che ferma il tempo (cap. 109 «フシギな時計»), la
   macchina fotografica dei pensieri, il pappagallo del nonno.
5. **L'isola della stirpe**, custodita dalla nonna: nel regolamento del 1990 comparivano «due
   isole solenni» vicino a un porto. Il canone ne conferma una, e le dà una funzione.
6. **Le edizioni successive**: l'*aizōban* e il *bunkobon* in 10 volumi hanno testo aggiunto
   (il volume 18 ha 45 pagine in più rispetto alla rivista, aggiunte «per Hikaru-chan» per
   esplicita dichiarazione dell'autore in copertina).
