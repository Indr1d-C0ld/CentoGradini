<?php

declare(strict_types=1);

/**
 * I personaggi non giocanti canonici.
 *
 * Sono righe di `personaggi` con `user_id` NULL e `png` valorizzato: la scelta
 * è spiegata nella migrazione 0011, e in breve è che così un PNG può fare da
 * testimone, ricevere un gesto, entrare nel cast di un episodio e portare in
 * giro le voci senza una riga di codice dedicata.
 *
 * **Le loro schede sono scritte a mano, non tirate.** È deliberato. Madoka
 * Ayukawa non è «un personaggio con buone statistiche»: è brava a sci, tennis,
 * surf, atletica, canto, chitarra, tastiere, cucina, cucito, equitazione e
 * inglese, ognuna con il suo capitolo, e ha un solo punto debole dichiarato —
 * le storie di fantasmi. Un tiro di dadi non ci arriverebbe mai, e se ci
 * arrivasse sarebbe per caso. Le attestazioni sono in `docs/CANONE.md` §4.
 *
 * `png_giro` è il giro abituale: i luoghi in cui quella persona capita, in
 * ordine. Chi ha un giro corto lo si incontra sempre nello stesso posto — il
 * Master non esce mai dall'ABCB — e chi ce l'ha lungo gira tutto il quartiere,
 * che è il modo in cui le voci attraversano la mappa.
 *
 * **La prima tappa è dove si dorme**, e la notte ci si torna: la usa
 * `Abitanti::dove()`. I Kasuga stanno alla palazzina in cima alla collina,
 * Akane e Kazuya dai loro genitori a pochi minuti a piedi (`casa_zii`, dal
 * 9/10/2026: prima dormivano dai Kasuga, e la famiglia di Kyosuke è di
 * quattro persone), Madoka a casa sua in fondo ai gradini, e **Hikaru pure**: la
 * ricostruzione del grande escalier (CANONE §7-ter) dice che le case di
 * Madoka e di Hikaru, l'ABCB e la scuola stanno tutte nel quartiere sotto la
 * scalinata, e dal 19/09/2026 `casa_hiyama` è sulla mappa.
 *
 * Per **Yusaku, Komatsu e Hatta** la casa resta ignota: li abbiamo messi in
 * via commerciale, che nel Giappone del 1987 è fatta di negozi con
 * l'appartamento sopra. Quella è una scelta nostra, non canone, e si vede
 * che lo è. Il Master dorme sopra il bar, e infatti il suo giro è di un
 * posto solo.
 *
 * Confidenza: `canone` per date e fatti attestati, `ricostruita` per i
 * compleanni che il canone non dà e per la taratura numerica, che è nostra.
 * Il registro è in `docs/FONTI.md`.
 */

// La fotografia di ciascun abitante, se ce n'e' una: vedi db/ritratti_abitanti.php.
// Solo quando il file esiste davvero, perche' una riga che punta a un'immagine
// assente mostrerebbe un riquadro rotto invece dell'iniziale.
$fotografie = is_file(__DIR__ . '/../ritratti_abitanti.php') ? (array) require __DIR__ . '/../ritratti_abitanti.php' : [];
$ritratto = static function (string $png) use ($fotografie): ?string {
    $file = isset($fotografie[$png]) ? basename((string) $fotografie[$png]) : '';
    return $file !== '' && is_file(__DIR__ . '/../../assets/img/ritratti/' . $file) ? $file : null;
};

// I numeri delle quattro abilita' vengono dal canone (CANONE §7, verificata
// il 13 ottobre 2026 su tutti i 156 riassunti, la FAQ e le voci giapponesi):
// prima erano messi a occhio, e dicevano il contrario dell'opera — Kyosuke
// con CUORE 10, Madoka con DAI-SUKI 8, Komatsu e Hatta fra i piu' affascinanti
// del quartiere. La scala e' quella dei giocatori: 3-10 il tiro, 13-15
// l'eccezione.
//
// **La chiacchiera non e' il fascino.** Le voci usavano DAI-SUKI come
// loquacita', e con i numeri del canone Madoka — che sta per conto suo e i
// segreti li tiene (FAQ 40; capp. 9, 155) — sarebbe diventata la piu'
// pettegola del quartiere, e Komatsu, da cui le voci partono davvero (capp.
// 28, 31, 40, 48), quasi muto. Per gli abitanti la loquacita' si scrive qui,
// con la sua fonte; per i giocatori resta DAI-SUKI.
$chiacchiera = [
    'komatsu' => 13,  // «grazie a Komatsu si sparge la voce» (cap. 28; e 31, 40, 48)
    'kurumi'  => 11,  // il megafono di casa: la voce del cap. 7, riferisce tutto a Hikaru (67, 82)
    'sayuri'  => 11,  // fa girare le voci tramite le amiche (capp. 112, 155)
    'hiromi'  => 10,  // le rivelazioni alla classe (capp. 83, 85)
    'hikaru'  => 9,   // dice tutto, a tutti, subito; «ムードメーカー»
    'kazuya'  => 9,   // ripete ad alta voce i pensieri altrui (cap. 35; TV 24)
    'hatta'   => 7,   // va dietro a Komatsu: da solo non fa partire niente (cap. 48)
    'akane'   => 6,
    'kyosuke' => 5,
    'takashi' => 5,
    'manami'  => 4,   // le voci le smonta, non le fa girare (cap. 7)
    'master'  => 4,   // sa tutto e tace; qualcosa lo lascia scappare per scherzo (47, 89, 97)
    'madoka'  => 3,   // «孤高», «solitaire» (FAQ 40): i segreti li tiene
    'yusaku'  => 3,   // quello che scopre se lo tiene
    'umao'    => 2,   // non vedono nessun altro che l'altra persona (cap. 38)
    'ushiko'  => 2,
];

$p = static fn (
    string $png, string $nome, string $cognome, string $sesso,
    string $sezione, int $anno, int $mese, int $giorno, int $annoNascita,
    bool $esper, string $luogo, string $giro,
    array $ab, array $sec, int $pf, int $pp,
    string $nota, string $aspetto
): array => [
    'png'          => $png,
    'user_id'      => null,
    'nome'         => $nome,
    'cognome'      => $cognome,
    'sesso'        => $sesso,
    'sezione'      => $sezione,
    'anno'         => $anno,
    'nato_mese'    => $mese,
    'nato_giorno'  => $giorno,
    'anno_nascita' => $annoNascita,
    'esper'        => $esper ? 1 : 0,
    'scheda'       => 'completa',
    'stato'        => 'attivo',
    'luogo'        => $luogo,
    'png_giro'     => $giro,
    'png_nota'     => $nota,
    'aspetto'      => $aspetto,
    'rissa'        => $ab[0], 'testa' => $ab[1], 'dai_suki' => $ab[2], 'cuore' => $ab[3],
    'chiacchiera'  => $chiacchiera[$png] ?? null,
    'inglese'      => $sec[0], 'sport' => $sec[1], 'guida'  => $sec[2], 'kakko'   => $sec[3],
    'nuoto'        => $sec[4], 'musica' => $sec[5], 'cucina' => $sec[6], 'candore' => $sec[7],
    'pf_max'       => $pf, 'pf' => $pf,
    'pp_max'       => $pp, 'pp' => $pp,
    // La compostezza è derivata come per i giocatori: 12 - candore, fra 2 e 10.
    'compostezza_max' => max(2, min(10, 12 - $sec[7])),
    'compostezza'     => max(2, min(10, 12 - $sec[7])),
    'ritratto_file'   => $ritratto($png),
    'ritratto_hash'   => $ritratto($png) === null ? null : basename((string) $ritratto($png), '.webp'),
    'ritratto_at'     => $ritratto($png) === null ? null : date('Y-m-d H:i:s'),
];

return [
    'tabella'     => 'personaggi',
    'chiave'      => 'png',
    'descrizione' => 'personaggi canonici che abitano il quartiere',
    // Dove sono e come stanno lo decide il gioco: gli abitanti si spostano col
    // loro giro, e gli episodi muovono Compostezza, punti ferita e punti
    // potere anche a loro. Il seme scrive queste colonne solo quando la riga
    // nasce; tutto il resto — nomi, giro, abilita' — lo riallinea a ogni giro.
    // Lo stesso vale per la fotografia: il seme la da' a chi nasce, e da li'
    // in poi la cambia soltanto l'amministratore dal pannello.
    'solo_alla_nascita' => ['stato', 'luogo', 'pf', 'pp', 'compostezza',
                            'ritratto_file', 'ritratto_hash', 'ritratto_at'],
    'righe' => [

        // --- Il triangolo -----------------------------------------------------
        // Kyosuke: esper completo, ma negato per lo studio E per lo sport
        // (correzione al regolamento del 1990, CANONE §2.3), e soprattutto
        // senza telepatia (§2.1). Candore alto: arrossisce di continuo, e la
        // compostezza che ne deriva è 3. È il motore della storia.
        $p('kyosuke', 'Kyosuke', 'Kasuga', 'm', 'superiori', 3, 11, 15, 1969, true,
           'gradini', 'casa_kasuga,gradini,liceo,abcb,parco,viale',
           // Nuoto 1: non sa nuotare (cap. 66; al 143 sguazza ancora
           // nella vasca dei bambini). Prima era 6.
           [6, 6, 8, 3], [4, 2, 3, 3, 1, 5, 2, 9], 9, 10,
           'Un ragazzo del terzo anno con l\'aria di chi sta per dire qualcosa e poi non la dice.',
           'Capelli scuri spettinati, giacca della scuola sempre aperta, mani in tasca.'),

        // Madoka: nessun potere e non le serve. Kakko 10, musica 10: il canone
        // le attesta canto, chitarra e tastiere a livello professionale.
        // Candore 3, quindi compostezza 9: è la sola che non perde la faccia.
        $p('madoka', 'Madoka', 'Ayukawa', 'f', 'superiori', 3, 5, 25, 1969, false,
           'abcb', 'casa_ayukawa,abcb,liceo,dischi,argine,gradini,moebius',
           [13, 14, 15, 4], [9, 9, 7, 10, 8, 10, 9, 3], 11, 0,
           'La ragazza che al bar sta dietro il bancone e non ti guarda finché non parli tu.',
           'Capelli lunghi castani, sguardo dritto, sigaretta che non accende più.'),

        // Hikaru: tutta cuore e nessun filtro. CUORE 13, il piu' alto del
        // triangolo: «所構わず抱きつく», abbraccia ovunque. Rissa 7 perche' da
        // bambina era lei a difendere Yusaku, e risponde per le rime (capp.
        // 22, 57); Testa 4, i recuperi con Kurumi; e stonata (musica 2).
        // Dai-suki 11: all'ABCB i clienti la preferiscono per la sua
        // allegria (cap. 28).
        $p('hikaru', 'Hikaru', 'Hiyama', 'f', 'superiori', 1, 11, 15, 1971, false,
           'liceo', 'casa_hiyama,liceo,abcb,sala_giochi,parco,gradini',
           [7, 4, 11, 13], [5, 8, 3, 7, 6, 2, 5, 8], 8, 0,
           'Una del primo anno che ti chiama «darling» in mezzo al corridoio.',
           'Capelli corti castani da maschiaccio, un neo sotto l\'occhio destro, non sta ferma un attimo.'),

        // --- I Kasuga ---------------------------------------------------------
        // Manami è la maggiore delle gemelle, quella con gli occhiali e la
        // testa a posto (CANONE §2.4: il regolamento del 1990 le aveva
        // scambiate). È lei che tiene in piedi la casa.
        //
        // **Le gemelle sono gemelle.** Prima avevano compleanni diversi (8/9/1972
        // e 3/7/1973) e stavano in anni diversi delle medie. Il canone le fa
        // compagne di classe di Hikaru, due anni sotto Kyosuke (voce giapponese:
        // «恭介、まどかとは2学年下でまなみとくるみの同級生»; FAQ 16), nate nel
        // gennaio 1972 (FAQ 1, stima): nel 1987 sono al primo anno delle
        // superiori. Il giorno, il 20, è nostro — il canone non lo dice.
        $p('manami', 'Manami', 'Kasuga', 'f', 'superiori', 1, 1, 20, 1972, true,
           'casa_kasuga', 'casa_kasuga,commerciale,liceo,abcb',
           [5, 11, 9, 8], [6, 5, 3, 6, 5, 5, 9, 4], 7, 9,
           'Una del primo anno con gli occhiali, che ti saluta per prima e si ricorda il tuo nome.',
           'Coda di cavallo, occhiali tondi, borsa della spesa.'),

        // Kurumi: la minore, poteri d'istinto e l'ipnosi — che nel canone si
        // impara (FAQ 42), non si nasce sapendola.
        // Cucina 1: in cucina è un disastro (voce giapponese, «壊滅的»: mette
        // le verdure nello stufato senza tagliarle). Prima era 4.
        $p('kurumi', 'Kurumi', 'Kasuga', 'f', 'superiori', 1, 1, 20, 1972, true,
           'casa_kasuga', 'casa_kasuga,sala_giochi,parco,commerciale',
           [7, 4, 8, 10], [4, 9, 3, 6, 7, 5, 1, 7], 8, 9,
           'Una del primo anno che sta sempre correndo dietro a qualcosa.',
           'Capelli semilunghi, ginocchia sbucciate, sorriso di chi le ha appena combinate.'),

        // Kazuya: otto anni, telepate — l'unico della famiglia (CANONE §2 e
        // FAQ). Ed è più forte di Kyosuke. Sta alle elementari, che è il
        // motivo per cui la sezione «elementari» esiste.
        // Lui e Akane non abitano dai Kasuga — la famiglia di Kyosuke è di
        // quattro persone (voce giapponese) — ma con i loro genitori, a pochi
        // minuti a piedi (FAQ 50): `casa_zii`.
        $p('kazuya', 'Kazuya', 'Kasuga', 'm', 'elementari', 3, 1, 25, 1979, true,
           'parco', 'casa_zii,parco,luna_park,sala_giochi',
           [4, 12, 11, 9], [2, 6, 1, 4, 5, 3, 2, 4], 5, 11,
           'Un bambino che risponde a domande che non hai fatto ad alta voce.',
           'Otto anni, berretto calato sugli occhi, e l\'aria di sapere già cosa stai per dire.'),

        // Akane: la cugina, e il suo potere è apparire come un'altra persona —
        // un solo bersaglio per volta nel manga (CANONE). Ha l'età di Kyosuke
        // (cap. 57; FAQ 51, 1969): prima stava al secondo anno.
        $p('akane', 'Akane', 'Kasuga', 'f', 'superiori', 3, 8, 12, 1969, true,
           'stazione', 'casa_zii,stazione,commerciale,abcb,liceo,dischi,moebius,cinema',
           [11, 10, 12, 7], [7, 7, 6, 9, 6, 7, 6, 5], 9, 10,
           'Una del terzo anno che sembra sempre sul punto di ridere di te.',
           'Capelli corti, quasi sempre in pantaloni, andatura sicura, cambia espressione troppo in fretta.'),

        // --- La scuola --------------------------------------------------------
        // Yusaku fa karate perché da bambino Hikaru gli disse che se fosse
        // diventato forte l'avrebbe sposato (FAQ 36). Rissa 13 (secondo dan),
        // CUORE 4, candore 9: sa picchiare e non sa parlare — «davanti a
        // Hikaru diventa un vigliacco» (voce giapponese).
        $p('yusaku', 'Yusaku', 'Hino', 'm', 'superiori', 1, 10, 7, 1971, false,
           'liceo', 'commerciale,liceo,parco,gradini',
           [13, 6, 6, 4], [4, 9, 3, 5, 6, 3, 4, 9], 10, 0,
           'Uno del primo anno in tuta da karate, che arrossisce se lo guardi.',
           'Fascia in testa, spalle larghe, sguardo fisso a terra quando c\'è Hikaru.'),

        // Komatsu e Hatta: i due amici. Poco fascino — brufoli e capelli
        // ricci l'uno, occhiali e pancetta l'altro, e le gemelle non li
        // degnano (voce giapponese) — e nessuna vergogna: candore basso.
        // Komatsu e' il megafono del quartiere (la chiacchiera e' sopra, a
        // parte dal fascino), si dichiara a Sayuri davanti alla classe
        // (cap. 112), e da bambino era il primo della classe (cap. 132).
        $p('komatsu', 'Seiji', 'Komatsu', 'm', 'superiori', 3, 6, 19, 1969, false,
           'liceo', 'commerciale,liceo,sala_giochi,abcb,dischi,parco,stazione,cinema',
           // Nuoto 1: non sa nuotare nemmeno lui (cap. 30). Prima era 6.
           [5, 7, 5, 9], [4, 3, 4, 4, 1, 5, 3, 3], 8, 0,
           'Uno del terzo anno che sa già tutto di tutti, e te lo racconta.',
           'Capelli ricci di natura, qualche brufolo, ride prima di finire la frase.'),

        $p('hatta', 'Kazuya', 'Hatta', 'm', 'superiori', 3, 2, 28, 1970, false,
           'liceo', 'commerciale,liceo,sala_giochi,abcb,dischi,cinema',
           [5, 6, 4, 6], [5, 5, 4, 4, 5, 6, 3, 5], 8, 0,
           'Uno del terzo anno che sta sempre a un passo dietro Komatsu.',
           'Occhiali, paffuto, sempre mezzo passo dietro Komatsu.'),

        // --- Le complicazioni -------------------------------------------------
        // Trovate rileggendo i 156 riassunti (CANONE §7-bis): erano fra le
        // questioni aperte di FONTI.md, e adesso hanno un capitolo ciascuna.
        // Sono antagoniste non violente — non fanno del male a nessuno,
        // complicano la vita a tutti — che e' esattamente quello che serviva
        // a un gioco in cui non si combatte.

        // Sayuri Hirose (capp. 88-89): da' la caccia ai bei ragazzi e ha un
        // metodo, studiato. Quando Kyosuke non ci casca, ne conclude che il
        // problema sia la concorrenza. Kakko altissimo, candore bassissimo:
        // sa benissimo quello che fa.
        $p('sayuri', 'Sayuri', 'Hirose', 'f', 'superiori', 2, 6, 3, 1970, false,
           'commerciale', 'commerciale,liceo,abcb,dischi,stazione,sala_giochi,cinema,moebius',
           [6, 12, 13, 4], [7, 6, 5, 10, 6, 6, 6, 2], 8, 0,
           'Una del secondo anno che ti sorride come se vi conosceste da sempre. Non è vero.',
           'Codini, curatissima, e ti guarda un mezzo secondo più del necessario.'),

        // Hiromi Sugi (capp. 83, 85): studentessa nuova, ex compagna di
        // classe di Kyosuke nella scuola di prima. Arriva con le fotografie
        // di com'era, e il fascino di lui crolla. E' la memoria che nessuno
        // ha chiesto — e in un gioco costruito sul Segreto, una persona che
        // ti ha conosciuto *prima* e' una minaccia con le gambe.
        $p('hiromi', 'Hiromi', 'Sugi', 'f', 'superiori', 3, 1, 22, 1970, false,
           'liceo', 'liceo,commerciale,abcb,parco,gradini',
           [6, 9, 9, 8], [8, 5, 4, 6, 5, 5, 7, 4], 8, 0,
           'Una del terzo anno che ti conosce da prima, e si ricorda di com\'eri.',
           'Capelli corti, sveglia, e un album di fotografie che non doveva portare.'),

        // --- Il quartiere -----------------------------------------------------
        // Il Master non esce mai dall'ABCB. Giro di un luogo solo: è il punto
        // fermo della mappa, e chi vuole sapere qualcosa passa da lui.
        // **Questa è una scelta di gioco, non canone**: nel manga è sposato,
        // vive con la moglie in una casa a due passi dal bar, ha una
        // Volkswagen e porta i ragazzi al mare (voce giapponese, capp. 50,
        // 87, 155). Qui la sua casa non è sulla mappa, e lui sta al bancone.
        // Si chiama «Master» e basta, com'è nel manga: niente cognome. Prima
        // era cognome «Master», nome «Il», e le liste — che mettono il cognome
        // davanti — scrivevano «Master Il».
        $p('master', 'Master', '', 'm', 'adulti', 0, 1, 30, 1952, false,
           'abcb', 'abcb',
           [7, 13, 9, 12], [8, 4, 8, 8, 4, 8, 10, 2], 12, 0,
           'L\'uomo dietro il bancone dell\'ABCB. Non chiede niente e sente tutto.',
           'Capelli ricci, baffi e barba, grembiule, un bicchiere che lucida da mezz\'ora.'),

        // --- Aggiunti dal controllo del 9 ottobre 2026 --------------------------
        // Takashi Kasuga, il padre (capp. 14, 51-52, 62, 103, 127; TV8, TV15):
        // fotografo di paesaggi, vedovo, senza poteri — vengono dal ramo della
        // madre (FAQ 55) — ma al corrente di tutto. Sulla quarantina (FAQ 57).
        // La camera oscura e' gia' nella descrizione della palazzina. Il
        // compleanno e' nostro: il canone non lo da'.
        $p('takashi', 'Takashi', 'Kasuga', 'm', 'adulti', 0, 10, 2, 1944, false,
           'casa_kasuga', 'casa_kasuga,gradini,argine,parco,tempio,stazione',
           [7, 10, 9, 12], [6, 4, 7, 5, 5, 5, 4, 6], 10, 0,
           'Un uomo con la macchina fotografica al collo, che aspetta la luce giusta come si aspetta l\'autobus.',
           'Giacca stropicciata, borsa da fotografo a tracolla, l\'aria gentile di chi si distrae.'),

        // Umao e Ushiko (capp. 38, 62; FAQ 58-59): la coppietta che compare
        // dappertutto, sempre abbracciata, e che si dice le battute di Romeo
        // e Giulietta. I nomi sono un cavallo e una mucca. Nel gioco servono
        // a una cosa precisa: sono testimoni, e ogni volta che qualcuno fa
        // qualcosa di strano al parco, loro erano li' e non hanno visto
        // niente — o forse si'. Dove abitino non si sa: li mettiamo sopra un
        // negozio della via, come Yusaku, ed e' una scelta nostra.
        $p('umao', 'Umao', '', 'm', 'adulti', 0, 6, 14, 1966, false,
           'parco', 'commerciale,parco,albero,argine,luna_park,giardini,cinema',
           [6, 5, 6, 14], [3, 5, 8, 4, 5, 5, 5, 2], 9, 0,
           'Un ragazzo che chiama la fidanzata «Ushiko!» come se recitasse a teatro.',
           'Pettinatura alla moda di qualche anno fa, e occhi solo per lei.'),

        $p('ushiko', 'Ushiko', '', 'f', 'adulti', 0, 9, 3, 1966, false,
           'parco', 'commerciale,parco,albero,argine,luna_park,giardini,cinema',
           [6, 5, 6, 14], [3, 4, 5, 4, 5, 6, 6, 2], 8, 0,
           'Una ragazza che risponde «Umao!» con la stessa enfasi, e non si accorge di nessun altro.',
           'Vestito a fiori, e occhi solo per lui.'),
    ],
];
