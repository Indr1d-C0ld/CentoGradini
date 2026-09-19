# Registro delle fonti

> **Nota per questo repository.** Il registro cita percorsi sotto `fonti/`: e' il
> nostro corpus di ricerca, che resta fuori dalla copia pubblica perche' e'
> materiale di terzi. Le voci qui sotto servono lo stesso — dicono *quale* fonte
> sostiene ogni scelta di progetto e con quanta confidenza, e chi vuole
> verificare puo' risalire agli originali.


Criterio, ereditato da Atlantik: ogni dato di ambientazione porta una **fonte**; i valori
incerti sono espressi come intervallo con un campo **confidence**; le ricostruzioni sono
marcate come tali e mai spacciate per canone.

Livelli di affidabilità usati nei seed:

| `confidence` | Significato |
|:---|:---|
| `canone` | Verificato sull'opera (manga, serie TV, OAV, film) |
| `documentato` | Verificato su fonte secondaria attendibile (enciclopedie, schede editoriali, banche dati di doppiaggio) |
| `regolamento1990` | Preso dal GdR amatoriale del 1990, che dichiara esso stesso di tirare a indovinare |
| `ricostruita` | Inferenza nostra, coerente ma non attestata |

---

## 1. Fonte primaria del sistema di gioco

**Whimsical Orange Road: The Role-Playing Game, Version 3.0**, marzo 1990.
Autore: «Totoro Hunter Leto II» (GEnie: LETO-2), San Diego, CA.
Presentato da *Robosmut International* e *Orange Roadies of San Diego*.

- Copia archiviata fuori da questo repository: 100.095 byte, 1.764 righe, 15.705
  parole. Ne esiste una traduzione italiana integrale e annotata, anch'essa non
  inclusa qui: il testo del 1990 ha un autore e non e' nostro da ridistribuire.
- Diffuso su BBS e sulla rete GEnie; risulta ripubblicato su `rec.arts.anime`.
- **Confidence:** `regolamento1990`. L'autore dichiara più volte che le statistiche dei
  personaggi canonici sono «stime azzardate» (*wild guesses*).

### Errori dell'originale rilevati e corretti nei nostri dati

| Errore | Correzione | Dove |
|:---|:---|:---|
| Intervalli sovrapposti nella tabella dei tratti: `69-70` e `70-74` | Il valore 70 è assegnato a VEDOVA/CHITOSE; KARATE KID parte da 71 | Tabella tratti |
| Intervalli sovrapposti nei poteri secondari: `29-32` (Supervelocità) e `32-37` (Supersensi) | Supervelocità 29-31, Supersensi 32-37 | Tabella poteri secondari |
| MANIPOLAZIONE DELLA VOCE descritta nel testo ma assente dalla tabella | Reinserita fra i secondari | Tabella poteri secondari |
| «Jingle» come nome del gatto | **Jingoro** | Scheda personaggio |
| Kurumi e Manami scambiate due volte (la miope con gli occhiali è Manami; Kurumi è definita «gemella di Kurumi») | Corretto secondo il canone | Schede personaggi |
| «Mitsuru Hiyakawa», rock star | **Hayakawa Mitsuru** | Scambio di corpo minore |
| «akai muri-wara boshi» | *akai mugiwara bōshi* (赤い麦わら帽子), cappello di paglia rosso | Scheda Kyosuke |
| Ojiichan: «secondari: telecinesi» quando il primario è già telecinesi | Refuso, secondari ignoti | Scheda Ojiichan |
| «Akemi Kasuga: Età IEY» | Sigla non risolta nell'originale | Scheda Akemi |

---

## 2. Opera originale

### 2.1 Manga
- **Titolo:** きまぐれオレンジ☆ロード (*Kimagure Orange Road*)
- **Autore:** Izumi Matsumoto (松本泉)
- **Rivista:** *Weekly Shōnen Jump* (Shūeisha)
- **Serializzazione:** dal n. 15 del 26 marzo 1984 al n. 42 del 28 settembre 1987
- **Capitoli:** 156 · **Volumi:** 18 tankōbon (imprint Jump Comics)
- **Capitoli speciali:** *Panic in the Bathhouse!* (Super Jump n. 10, 1996); speciale su
  *Weekly Playboy* n. 44, 1999
- **Tiratura:** oltre 20 milioni di copie
- **Confidence:** `documentato` — [Wikipedia EN, *Kimagure Orange Road*](https://en.wikipedia.org/wiki/Kimagure_Orange_Road)

### 2.2 Serie televisiva
- **Emittente:** Nippon Television (NTV) · **Studio:** Studio Pierrot
- **Regia:** Osamu Kobayashi · **Sceneggiature:** Kenji Terada
- **Character design:** **Akemi Takada** · **Musiche:** Shirō Sagisu
- **Trasmissione:** 6 aprile 1987 – 7 marzo 1988 · **Episodi:** 48
- **Sigle di apertura:** *Night of Summer Side* (Masanori Ikeda, ep. 1-19); *Orange Mystery*
  (Hideyuki Nagashima, ep. 20-36); *Kagami no Naka no Actress* (Meiko Nakahara, ep. 37-48)
- **Sigle di chiusura:** *Natsu no Mirage* e *Kanashii Heart wa Moete-iru* (Kanako Wada);
  *Dance in the Memories* (Meiko Nakahara)
- **Confidence:** `documentato`

L'elenco completo dei 48 titoli, in giapponese e traslitterazione, con data di messa in onda,
è archiviato in `fonti/episodi_tv.tsv`. È la base del catalogo
degli episodi di gioco (§9 del progetto).

### 2.3 OAV
Otto episodi, Studio Pierrot, 1 marzo 1989 – 18 gennaio 1991:
*White Lovers* · *Hawaiian Suspense* · *I Was a Cat, I Was a Fish* · *Hurricane! Akane the
Shape-changing Girl* · *Stage of Love = Heart on Fire! Spring is for Idols* · *…Birth of a
Star* · *An Unexpected Situation* · *Message in Rouge*. **Confidence:** `documentato`

### 2.4 Film
| Anno | Titolo | Regia | Durata | Nota |
|:---|:---|:---|:---|:---|
| 1985 | *Shōnen Jump Special: Kimagure Orange Road* | Tomomi Mochizuki (supervisione Osamu Kobayashi) | 25 min | Considerato l'episodio pilota; trama riusata nell'ep. 46 |
| 1988 | *Ano hi ni kaeritai* (*Voglio tornare a quel giorno*) | Tomomi Mochizuki | 69 min | **Omette del tutto i poteri esper** |
| 1996 | *Shin Kimagure Orange Road: Soshite, ano natsu no hajimari* | Kunihiko Yuyama | 95 min | Musiche di Yuki Kajiura; accoglienza negativa |

**Confidence:** `documentato`

### 2.5 Ambientazione reale — **due risposte, non una**

Le due Wikipedia dicono cose diverse, ed è una differenza che vale la pena tenere.

**Wikipedia inglese**: il quartiere è ispirato a **Umegaoka**, **Gōtokuji** e
**Shimokitazawa**, municipio di **Setagaya**, Tokyo; il bar ABCB a un locale chiamato *Gensō
Katsudō Shashin Kan*; **Yurinoki Dōri** e il passaggio a livello presso la stazione di
**Yamashita** (linea Tōkyū Setagaya) corrispondono a scene della serie.
**Confidence:** `documentato`.

**Wikipedia giapponese**, che cita il quotidiano **Kitanippon Shimbun** (2021): i modelli
reali sono a **Takaoka, prefettura di Toyama**, città natale di Izumi Matsumoto.

| Luogo dell'opera | Modello secondo la fonte giapponese |
|:---|:---|
| 100段階段, la scalinata dei cento gradini | La scalinata di pietra del parco **Takaoka Kojō** |
| 高陵学園 Kōryō Gakuen | La **scuola media Kōryō** di Takaoka |
| abcb (あばかぶ) | Il ristorante **ABAB** di Takaoka |
| 中川駅, la stazione | **Etchū-Nakagawa**, linea Himi delle JR West |

**Confidence:** `documentato` per Takaoka (fonte giornalistica citata); `documentato` ma più
debole per la scuola e la stazione, che la voce giapponese marca «要出典» (citazione
necessaria).

**Come le teniamo insieme.** Non si escludono: il quartiere *narrato* è periferia di Tokyo,
i luoghi *disegnati* vengono da Takaoka. Il gioco usa la geografia narrativa (una periferia
senza nome) e i **nomi canonici** dei luoghi, che sono quelli giapponesi: la stazione si
chiama **Nakagawa**, la scuola **Kōryō Gakuen**, il bar **abcb**.

- La **scalinata dei cento gradini** — 100段階段 — è il nome usato nell'opera, non una nostra
  invenzione, **e i gradini sono davvero cento**: nell'episodio 1 Kyosuke ne conta 100 e
  Madoka sostiene che siano 99, si accordano su novantanove e mezzo, e nell'OAV *Message in
  Rouge* Madoka li ricontrolla arrivando a cento (FAQ di KOR, domanda 12).
  **Confidence:** `canone`.
- **想い出の樹**, l'albero dei ricordi, con inciso «fra sei anni ancora qui… per Madoka».
  **Confidence:** `canone`.
- **Kōryō Gakuen** ha sia le medie (中等部) sia le superiori (高等部): all'inizio della storia
  i protagonisti sono al terzo anno delle **medie**. **Confidence:** `canone`.

### 2.6 Cronologia interna
Dal regolamento del 1990 (§ *Cronologia generale*), da verificare sull'opera:
1981 viaggio a «sei anni fa» · 1984 inizio della serie · 1986 talent show · 1987 finale.
Nell'universo animato tutto slitta di un anno (1982 / 1988). **Confidence:** `regolamento1990`.
La scelta dell'**«eterno 1987»** come anno-contenitore è **nostra** e non pretende di essere
canone: è l'ultimo anno in cui il triangolo esiste ancora intatto in entrambe le versioni.

---

## 3. Edizione italiana (non usata per l'onomastica, archiviata per completezza)

Decisione dell'utente del 19/09/2026: **onomastica originale giapponese**. Questi dati restano
qui perché un alias italiano commutabile è un'aggiunta possibile a costo basso.

**Edizione televisiva Mediaset** — «È quasi magia Johnny», Italia 1 dal 24 gennaio 1989.
Doppiaggio Deneb Film, Milano; dialoghi di Cristina Robustelli; direzione di Donatella Fanfani.
Sigla di **Cristina D'Avena** (testo di Alessandra Valeri Manera, musica di Ninni Carucci).

| Originale | Nome italiano Mediaset |
|:---|:---|
| Kyōsuke Kasuga | Johnny |
| Madoka Ayukawa | Sabrina |
| Hikaru Hiyama | Tinetta |
| Yūsaku Hino | Renato |
| Manami Kasuga | Manuela |
| Kurumi Kasuga | Simona |
| Takashi Kasuga | Sergei |
| Jingoro (il gatto) | Ercole |
| Seiji Komatsu | Michael |
| Kazuya Hatta | Carlo |
| Master dell'ABCB | Luigi |
| Kazuya | Paolo |
| Ushiko | Lucilla |
| Umao | Aldo |
| Sumire Hoshi | Susanna |

L'edizione televisiva è **tagliata**, con gli episodi 35 e 37 eliminati per intero. Esistono un
ridoppiaggio integrale Dynamic Italia (VHS, 1996) e l'edizione Yamato Video (DVD, 2003, con i
nomi originali), più i remaster Yamato del 2019-2020.

**Confidence:** `documentato` —
[Il mondo dei doppiatori, scheda «È quasi magia Johnny»](https://www.antoniogenna.net/doppiaggio/anim/equasimagiajohnny.htm)

---

## 4. Fonti consultate

- [Wikipedia EN — *Kimagure Orange Road*](https://en.wikipedia.org/wiki/Kimagure_Orange_Road)
- [Wikipedia EN — *List of Kimagure Orange Road episodes*](https://en.wikipedia.org/wiki/List_of_Kimagure_Orange_Road_episodes)
- [Il mondo dei doppiatori — «È quasi magia Johnny» / «Orange Road»](https://www.antoniogenna.net/doppiaggio/anim/equasimagiajohnny.htm)
- [Anime News Network — *House of 1000 Manga*: Kimagure Orange Road](https://www.animenewsnetwork.com/house-of-1000-manga/2014-09-11/kimagure-orange-road/.78669)
- [TV Tropes — *Kimagure Orange Road*](https://tvtropes.org/pmwiki/pmwiki.php/Manga/KimagureOrangeRoad)

---

## 4-bis. Rilettura sistematica dell'opera (in corso)

Prima passata conclusa il 19/09/2026: vedi [`CANONE.md`](CANONE.md). Materiale archiviato in
`fonti/canone/` e `fonti/capitoli_manga.tsv` (indice completo dei 156 capitoli con numero di
*Jump*, titolo e posizione in classifica; fonte dichiarata: *Media Arts Database* dell'Agenzia
per gli Affari Culturali giapponese, tramite ジャジャン研).

Aggiunta il 19/09/2026 la scansione dei due siti di appassionati indicati dall'utente:
**467 pagine** da `madoka.ayukawa.free.fr` (chiusura raggiunta) e **550** da
`orangeroad.it`, in `fonti/siti/`. Dal secondo vengono tutti e 156 i titoli italiani dei
capitoli; dal primo la **FAQ storica di KOR**, versione francese 3.17 del marzo 2025
(`fonti/faq_fr.txt`), che è la fonte secondaria più densa trovata finora.

> **Nota tecnica sulla FAQ.** Il file è UTF-16LE su cui è passata una conversione
> Latin-1→UTF-8: ogni byte accentato è stato espanso in due, e una decodifica ingenua
> restituisce ideogrammi al posto delle vocali accentate. Si disfa al contrario:
> `bytes.decode('utf-8').encode('latin-1').decode('utf-16-le')`.

> **orangeroad.it è giù per colpa nostra.** Il primo raccoglitore ha fatto 550 richieste a
> mezzo secondo l'una e il sito — un'applicazione ASP amatoriale — ha cominciato a rispondere
> HTTP 500 su tutto, home compresa. Per i siti piccoli: **una richiesta ogni quattro
> secondi**, filtro sui binari al momento di *accodare* e non di scaricare, e raccoglitore
> mirato invece di strisciata quando la struttura degli URL è già nota.
> `fonti/siti/riprendi_capitoli.py` è il raccoglitore paziente che riprende da dove era
> rimasto.

Due fonti scartate, e perché:
- **kor.fandom.com**: nonostante il nome, è il wiki di un'ambientazione fantasy senza alcun
  rapporto con l'opera. Non usarlo.
- **orangeroad.fandom.com via `action=raw`**: risponde con la pagina di attesa di Cloudflare
  e codice 200. Va interrogato dall'**API** (`api.php?action=query&prop=revisions`).

## 4-ter. Su cosa poggia F6 — abitanti, club, eventi

Questa sezione esiste perché F6 ha messo nel gioco parecchia roba che il canone **non**
attesta, e distinguere è più importante che riempire.

### Attestato dall'opera (`canone`)

- **Yusaku Hino fa karate**, e ha cominciato da bambino perché Hikaru gli disse che se fosse
  diventato forte l'avrebbe sposato — FAQ 36. È l'unico club dell'elenco che l'opera dichiara.
- **Madoka non è iscritta a nessun club.** FAQ 40: è nota a molte bande e non ha mai aderito
  a nessuna, il che «rinforza la sua immagine di solitaria». Nel gioco resta senza club, e
  metterla in uno per simmetria sarebbe la cosa più sbagliata da fare a questo personaggio.
- **I poteri dei canonici** vengono dalla rilettura in `CANONE.md` §2 e §3: Kyosuke senza
  telepatia, Kazuya unico telepate e più forte di lui, Akane con il cambio d'identità a un
  bersaglio per volta, Kurumi con l'ipnosi che *si impara* (FAQ 42).
- **Date di nascita** di Kyosuke (15/11/1969), Madoka (25/05/1969) e Hikaru (15/11/1971).
- **Komatsu Seiji** e **Hatta Kazuya**: nomi completi ricavati dal corpus italiano
  (`confidence: documentato`, due occorrenze indipendenti).

### Nostra ricostruzione (`ricostruita`) — e va detto

- **Gli altri nove club.** Sono costruiti su com'era davvero un liceo giapponese nel 1987
  (il 部活 come struttura sociale, i giorni di ritrovo, le dimensioni), non su scene
  dell'opera. Ogni riga di `db/seed/club.php` porta il proprio campo `confidenza`.
- **I compleanni** di Manami, Kurumi, Kazuya, Akane, Yusaku, Komatsu, Hatta e del Master: il
  canone non li dà. Sono scelti in modo che la **coorte scolastica torni** — è l'unico
  vincolo duro, ed è verificato da una prova.
- **La taratura numerica di tutte le schede.** Sono scritte a mano e non tirate: Madoka è
  brava a sci, tennis, surf, atletica, canto, chitarra, tastiere, cucina, cucito, equitazione
  e inglese perché ognuna ha il suo capitolo (§4), ma il *numero* che ci mettiamo è nostro.
- **Dove dormono Hikaru, Yusaku, Komatsu e Hatta.** Le loro case non sono sulla nostra mappa;
  li abbiamo messi in via commerciale, che nel Giappone del 1987 è fatta di negozi con
  l'appartamento sopra.
- **Il calendario dei quattordici eventi.** Le ricorrenze sono reali e datate correttamente
  (Golden Week, 文化祭, 体育祭, 初詣, San Valentino e White Day con l'usanza giapponese, la
  vigilia di Natale come sera di coppia); *quali* di esse il quartiere celebri e con quanto
  richiamo è scelta nostra.
- **Le frasi delle voci.** Le quattro fasce di degrado e le scale di frasi in
  `src/Game/Voci.php` sono scritte a mano e non hanno una fonte: sono il modo in cui secondo
  noi si racconta un pettegolezzo in italiano.

---

## 5. Da verificare leggendo l'opera

Elenco aperto, da chiudere prima di scrivere i seed definitivi (fase F2).

1. Statistiche e poteri effettivi di ciascun personaggio canonico, contro quelli «a stima» del
   1990.
2. Elenco completo e attestato degli usi dei poteri nel manga, con volume e pagina, per
   calibrare i limiti (peso, distanza, durata, costo).
3. Quante volte la famiglia Kasuga si è trasferita e per quali cause precise — è il parametro
   che tara la soglia del **Trasloco**.
4. Elenco degli artefatti canonici (orologio del vol. 13, macchina fotografica del vol. 9,
   pappagallo del vol. 13) con i loro effetti esatti.
5. Geografia interna coerente del quartiere: quali luoghi confinano con quali, nelle tavole.
6. Il ruolo esatto di Sayuri Hirose e Hiromi (solo manga), che il regolamento tratta di sfuggita
   ma che servono come PNG antagonisti non violenti.
7. **I club veri del Kōryō**, se l'opera ne nomina altri oltre al karate di Yusaku: i riassunti
   dei capitoli che ci mancano (144 su 156) sono il posto dove cercarli.
8. **I compleanni canonici** di Manami, Kurumi, Kazuya e Akane, se esistono da qualche parte:
   i nostri sono scelti per far tornare la coorte, e andrebbero sostituiti da quelli veri.
9. **Dove abitano** Hikaru, Yusaku, Komatsu e Hatta, se le tavole lo mostrano.
