<?php

declare(strict_types=1);

/**
 * Gli eventi stagionali del server.
 *
 * Sono di server e non di giocatore: capitano a tutti insieme, perché il
 * calendario è lo stesso per tutti. È la cosa che manca a un mondo persistente
 * asincrono — senza, ognuno gioca in una bolla e non c'è mai un «ti ricordi
 * quella volta al festival». Con, il quartiere si raduna in un posto solo, e
 * quello è esattamente il momento in cui le voci corrono di più.
 *
 * Due numeri li governano:
 *
 *   RICHIAMO     quanta gente tira in quel posto. Alza la Folla, che alza il
 *                calore e il numero di testimoni: usare un potere al festival
 *                d'estate è la cosa più imprudente dell'anno.
 *   CHIACCHIERA  quanto accelera la propagazione delle voci. Un funerale e
 *                una festa raccolgono la stessa gente e la fanno parlare in
 *                modo molto diverso.
 *
 * Le date seguono il calendario giapponese del 1987-88 già in `Calendario`:
 * l'anno scolastico apre il 6 aprile e chiude a marzo.
 */

$e = static fn (
    string $ekey, string $nome, int $mese, int $giorno, int $durata,
    string $luogo, int $richiamo, int $chiacchiera, int $ordine, string $desc
): array => [
    'ekey' => $ekey, 'nome' => $nome, 'mese' => $mese, 'giorno' => $giorno,
    'durata' => $durata, 'luogo' => $luogo, 'richiamo' => $richiamo,
    'chiacchiera' => $chiacchiera, 'ordine' => $ordine, 'descrizione' => $desc,
];

return [
    'tabella'     => 'eventi',
    'chiave'      => 'ekey',
    'descrizione' => 'eventi stagionali del quartiere',
    'righe' => [

        $e('ingresso', 'Cerimonia d\'ingresso', 4, 6, 1, 'liceo', 85, 70, 10,
           'Il primo giorno. I nuovi arrivati in fila nel cortile con la divisa ancora rigida, e '
           . 'i grandi che li guardano dalle finestre. Tutti i nomi che sentirai per un anno li '
           . 'senti oggi per la prima volta.'),

        $e('ciliegi', 'I ciliegi in fiore', 4, 12, 6, 'parco', 80, 65, 20,
           'Una settimana e poi cadono. Si stende un telo sotto gli alberi, si mangia per terra e '
           . 'si resta fino a buio: è l\'unico momento dell\'anno in cui stare seduti in un parco '
           . 'a non fare niente è una cosa che si fa apposta.'),

        $e('reclutamento', 'Reclutamento dei club', 4, 20, 8, 'liceo', 70, 75, 30,
           'Banchetti nel corridoio, volantini, capitani che ti bloccano all\'uscita. Chi non si '
           . 'iscrive adesso non si iscrive più fino all\'anno prossimo.'),

        $e('golden_week', 'Golden Week', 4, 29, 8, '', 60, 55, 40,
           'Quattro feste in nove giorni. La scuola chiude, il quartiere si svuota di chi parte e '
           . 'si riempie di chi resta, e chi resta finisce sempre negli stessi tre posti.'),

        $e('stagione_piogge', 'La stagione delle piogge', 6, 10, 30, '', 30, 40, 50,
           'Un mese d\'acqua. Ci si ripara sotto le tettoie con gente che non si conosce, e si '
           . 'aspetta che spiova. Metà delle storie di questo quartiere cominciano così.'),

        $e('festival_estate', 'Festival d\'estate', 7, 25, 2, 'tempio', 95, 90, 60,
           'Yukata, lanterne di carta, bancarelle lungo la salita del tempio, e i fuochi alla fine. '
           . 'È la sera dell\'anno in cui il quartiere è tutto nello stesso posto — e quella in cui '
           . 'conviene meno di tutte fare qualcosa di strano davanti alla gente.'),

        $e('mare', 'Al mare', 8, 5, 14, 'spiaggia', 75, 70, 70,
           'Le vacanze vere. Chi può ci va, e chi ci va torna con qualcosa da raccontare che nel '
           . 'quartiere nessuno ha visto: sono le voci che reggono meglio, perché non c\'è modo di '
           . 'verificarle.'),

        $e('culturale', 'Festival culturale', 11, 3, 2, 'liceo', 90, 85, 80,
           'Il 文化祭. Ogni classe monta qualcosa — un bar, una casa stregata, uno spettacolo — e '
           . 'per due giorni la scuola è aperta a chiunque. È il giorno in cui si dichiarano in '
           . 'tanti, e in cui si viene visti da tutti.'),

        $e('sportivo', 'Giornata sportiva', 10, 10, 1, 'liceo', 80, 70, 90,
           'Il 体育祭, e la giornata dell\'anno in cui essere negati per lo sport è un fatto '
           . 'pubblico. Staffette, tiro alla fune, e le classi divise in squadre di colore.'),

        $e('natale', 'La vigilia', 12, 24, 2, 'commerciale', 85, 80, 100,
           'In Giappone il Natale non è una festa di famiglia: è la sera in cui si esce in due. '
           . 'Le vetrine restano accese fino a tardi, e chi resta solo lo nota parecchio.'),

        $e('capodanno', 'Capodanno al tempio', 1, 1, 3, 'tempio', 90, 60, 110,
           'Il 初詣: la prima visita dell\'anno, di notte, con la fila che scende per tutta la '
           . 'salita. Si tira la corda, si batte le mani due volte, e si chiede una cosa sola.'),

        $e('san_valentino', 'San Valentino', 2, 14, 1, 'liceo', 85, 95, 120,
           'In Giappone lo regalano le ragazze, e il cioccolato fatto in casa dice una cosa diversa '
           . 'da quello comprato. È il giorno con più chiacchiere dell\'intero calendario: la '
           . 'scuola non parla d\'altro per una settimana.'),

        $e('white_day', 'White Day', 3, 14, 1, 'commerciale', 70, 85, 130,
           'Un mese dopo, tocca ai ragazzi rispondere. Chi non risponde risponde lo stesso, e tutti '
           . 'lo sanno.'),

        $e('diploma', 'Cerimonia di diploma', 3, 20, 1, 'liceo', 90, 75, 140,
           'Il terzo anno se ne va. Bottoni della divisa staccati e regalati, lacrime nel cortile, '
           . 'e la certezza che ad aprile la scuola sarà la stessa con altre facce.'),
    ],
];
