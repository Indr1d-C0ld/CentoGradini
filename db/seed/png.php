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
 * `Abitanti::dove()`. I Kasuga e Akane stanno alla palazzina in cima alla
 * collina, Madoka a casa sua in fondo ai gradini, e **Hikaru pure**: la
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
    'inglese'      => $sec[0], 'sport' => $sec[1], 'guida'  => $sec[2], 'kakko'   => $sec[3],
    'nuoto'        => $sec[4], 'musica' => $sec[5], 'cucina' => $sec[6], 'candore' => $sec[7],
    'pf_max'       => $pf, 'pf' => $pf,
    'pp_max'       => $pp, 'pp' => $pp,
    // La compostezza è derivata come per i giocatori: 12 - candore, fra 2 e 10.
    'compostezza_max' => max(2, min(10, 12 - $sec[7])),
    'compostezza'     => max(2, min(10, 12 - $sec[7])),
];

return [
    'tabella'     => 'personaggi',
    'chiave'      => 'png',
    'descrizione' => 'personaggi canonici che abitano il quartiere',
    // Dove sono e come stanno lo decide il gioco: gli abitanti si spostano col
    // loro giro, e gli episodi muovono Compostezza, punti ferita e punti
    // potere anche a loro. Il seme scrive queste colonne solo quando la riga
    // nasce; tutto il resto — nomi, giro, abilita' — lo riallinea a ogni giro.
    'solo_alla_nascita' => ['stato', 'luogo', 'pf', 'pp', 'compostezza'],
    'righe' => [

        // --- Il triangolo -----------------------------------------------------
        // Kyosuke: esper completo, ma negato per lo studio E per lo sport
        // (correzione al regolamento del 1990, CANONE §2.3), e soprattutto
        // senza telepatia (§2.1). Candore alto: arrossisce di continuo, e la
        // compostezza che ne deriva è 3. È il motore della storia.
        $p('kyosuke', 'Kyosuke', 'Kasuga', 'm', 'superiori', 3, 11, 15, 1969, true,
           'gradini', 'casa_kasuga,gradini,liceo,abcb,parco,viale',
           [6, 7, 7, 10], [4, 4, 3, 5, 6, 5, 4, 9], 9, 10,
           'Un ragazzo del terzo anno con l\'aria di chi sta per dire qualcosa e poi non la dice.',
           'Capelli scuri spettinati, giacca della scuola sempre aperta, mani in tasca.'),

        // Madoka: nessun potere e non le serve. Kakko 10, musica 10: il canone
        // le attesta canto, chitarra e tastiere a livello professionale.
        // Candore 3, quindi compostezza 9: è la sola che non perde la faccia.
        $p('madoka', 'Madoka', 'Ayukawa', 'f', 'superiori', 3, 5, 25, 1969, false,
           'abcb', 'casa_ayukawa,abcb,liceo,dischi,argine,gradini',
           [13, 12, 8, 9], [9, 9, 7, 10, 8, 10, 9, 3], 11, 0,
           'La ragazza che al bar sta dietro il bancone e non ti guarda finché non parli tu.',
           'Capelli lunghi castani, sguardo dritto, sigaretta che non accende più.'),

        // Hikaru: tutta cuore e nessun filtro. Dai suki 13 è il valore più
        // alto del quartiere: si attacca alle persone, ed è il suo modo di
        // stare al mondo, non un difetto.
        $p('hikaru', 'Hikaru', 'Hiyama', 'f', 'superiori', 1, 11, 15, 1971, false,
           'liceo', 'casa_hiyama,liceo,abcb,sala_giochi,parco,gradini',
           [5, 7, 13, 12], [5, 8, 3, 7, 6, 6, 5, 8], 8, 0,
           'Una del primo anno che ti chiama «darling» in mezzo al corridoio.',
           'Capelli corti scuri, fermaglio rosso, non sta ferma un attimo.'),

        // --- I Kasuga ---------------------------------------------------------
        // Manami è la maggiore delle gemelle, quella con gli occhiali e la
        // testa a posto (CANONE §2.4: il regolamento del 1990 le aveva
        // scambiate). È lei che tiene in piedi la casa.
        $p('manami', 'Manami', 'Kasuga', 'f', 'medie', 3, 9, 8, 1972, true,
           'casa_kasuga', 'casa_kasuga,commerciale,liceo,abcb',
           [5, 11, 10, 9], [6, 5, 3, 6, 5, 5, 9, 4], 7, 9,
           'Una delle medie con gli occhiali, che ti saluta per prima e si ricorda il tuo nome.',
           'Coda di cavallo, occhiali tondi, borsa della spesa.'),

        // Kurumi: la minore, poteri d'istinto e l'ipnosi — che nel canone si
        // impara (FAQ 42), non si nasce sapendola.
        $p('kurumi', 'Kurumi', 'Kasuga', 'f', 'medie', 2, 7, 3, 1973, true,
           'casa_kasuga', 'casa_kasuga,sala_giochi,parco,commerciale',
           [7, 7, 11, 8], [4, 9, 3, 6, 7, 5, 4, 7], 8, 9,
           'Una delle medie che sta sempre correndo dietro a qualcosa.',
           'Capelli semilunghi, ginocchia sbucciate, sorriso di chi le ha appena combinate.'),

        // Kazuya: otto anni, telepate — l'unico della famiglia (CANONE §2 e
        // FAQ). Ed è più forte di Kyosuke. Sta alle elementari, che è il
        // motivo per cui la sezione «elementari» esiste.
        $p('kazuya', 'Kazuya', 'Kasuga', 'm', 'elementari', 3, 4, 20, 1978, true,
           'parco', 'casa_kasuga,parco,luna_park,sala_giochi',
           [3, 9, 9, 7], [2, 6, 1, 4, 5, 3, 2, 8], 5, 11,
           'Un bambino che risponde a domande che non hai fatto ad alta voce.',
           'Otto anni, berretto, un gatto grasso sempre fra i piedi.'),

        // Akane: la cugina, e il suo potere è apparire come un'altra persona —
        // un solo bersaglio per volta nel manga (CANONE).
        $p('akane', 'Akane', 'Kasuga', 'f', 'superiori', 2, 8, 12, 1970, true,
           'stazione', 'casa_kasuga,stazione,commerciale,abcb,liceo,dischi',
           [9, 10, 12, 7], [7, 7, 6, 9, 6, 7, 6, 5], 9, 10,
           'Una del secondo anno che sembra sempre sul punto di ridere di te.',
           'Capelli raccolti, andatura sicura, cambia espressione troppo in fretta.'),

        // --- La scuola --------------------------------------------------------
        // Yusaku fa karate perché da bambino Hikaru gli disse che se fosse
        // diventato forte l'avrebbe sposato (FAQ 36). Rissa 11, candore 9:
        // sa picchiare e non sa parlare.
        $p('yusaku', 'Yusaku', 'Hino', 'm', 'superiori', 1, 10, 7, 1971, false,
           'liceo', 'commerciale,liceo,parco,gradini',
           [11, 7, 5, 9], [4, 9, 3, 5, 6, 3, 4, 9], 10, 0,
           'Uno del primo anno in tuta da karate, che arrossisce se lo guardi.',
           'Fascia in testa, spalle larghe, sguardo fisso a terra quando c\'è Hikaru.'),

        // Komatsu e Hatta: i due amici. Dai suki alto, discrezione zero — sono
        // i megafoni del quartiere, e in F6 è un ruolo meccanico oltre che
        // narrativo: sono quelli che fanno correre le voci.
        $p('komatsu', 'Seiji', 'Komatsu', 'm', 'superiori', 3, 6, 19, 1969, false,
           'liceo', 'commerciale,liceo,sala_giochi,abcb,dischi,parco,stazione',
           [6, 6, 12, 6], [4, 6, 4, 4, 6, 5, 3, 8], 8, 0,
           'Uno del terzo anno che sa già tutto di tutti, e te lo racconta.',
           'Basso, occhi piccoli, ride prima di finire la frase.'),

        $p('hatta', 'Kazuya', 'Hatta', 'm', 'superiori', 3, 2, 28, 1970, false,
           'liceo', 'commerciale,liceo,sala_giochi,abcb,dischi',
           [6, 7, 11, 6], [5, 5, 4, 4, 5, 6, 3, 8], 8, 0,
           'Uno del terzo anno che sta sempre a un passo dietro Komatsu.',
           'Alto e magro, capelli lisci, macchina fotografica al collo.'),

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
           'commerciale', 'commerciale,liceo,abcb,dischi,stazione,sala_giochi',
           [7, 12, 14, 6], [7, 6, 5, 10, 6, 6, 6, 2], 8, 0,
           'Una del secondo anno che ti sorride come se vi conosceste da sempre. Non e\' vero.',
           'Curatissima, sempre a fuoco, e ti guarda un mezzo secondo piu\' del necessario.'),

        // Hiromi Sugi (capp. 83, 85): studentessa nuova, ex compagna di
        // classe di Kyosuke nella scuola di prima. Arriva con le fotografie
        // di com'era, e il fascino di lui crolla. E' la memoria che nessuno
        // ha chiesto — e in un gioco costruito sul Segreto, una persona che
        // ti ha conosciuto *prima* e' una minaccia con le gambe.
        $p('hiromi', 'Hiromi', 'Sugi', 'f', 'superiori', 3, 1, 22, 1970, false,
           'liceo', 'liceo,commerciale,abcb,parco,gradini',
           [5, 13, 10, 8], [8, 5, 4, 6, 5, 5, 7, 6], 8, 0,
           'Una del terzo anno appena trasferita, che pero\' si ricorda di te.',
           'Occhiali, quaderno sempre in mano, e un album di fotografie che non doveva portare.'),

        // --- Il quartiere -----------------------------------------------------
        // Il Master non esce mai dall'ABCB. Giro di un luogo solo: è il punto
        // fermo della mappa, e chi vuole sapere qualcosa passa da lui.
        $p('master', 'Il', 'Master', 'm', 'adulti', 0, 1, 30, 1952, false,
           'abcb', 'abcb',
           [10, 12, 6, 11], [8, 4, 8, 8, 4, 8, 10, 2], 12, 0,
           'L\'uomo dietro il bancone dell\'ABCB. Non chiede niente e sente tutto.',
           'Baffi, grembiule, un bicchiere che lucida da mezz\'ora.'),
    ],
];
