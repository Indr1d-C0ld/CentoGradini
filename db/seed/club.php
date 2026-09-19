<?php

declare(strict_types=1);

/**
 * I club scolastici.
 *
 * A che servono, meccanicamente: sono il **secondo canale di propagazione
 * delle voci**, e l'unico che non passa dalla geografia. Due persone dello
 * stesso club si parlano anche se non si incrociano mai nel quartiere, ed è
 * il modo in cui chi gioca poco viene comunque raggiunto da quello che
 * succede. Quello che gira in uno spogliatoio arriva già di seconda mano: la
 * perdita di precisione è più alta che di persona, ed è voluta.
 *
 * A che servono, narrativamente: nel liceo giapponese il club è la struttura
 * sociale principale dopo la classe. Il 部活 non è un passatempo — è dove si
 * passano i pomeriggi, dove nascono i senpai e i kōhai, e dove ci si incontra
 * senza doverlo dichiarare.
 *
 * **Attenzione alla confidenza.** Il canone attesta due cose sole e le
 * rispettiamo: Yusaku fa karate, e ha cominciato da bambino perché Hikaru gli
 * aveva detto che se fosse diventato forte l'avrebbe sposato (FAQ 36); e
 * Madoka **non entra in nessun club** — è nota a molte bande, non ha mai
 * aderito a nessuna, e la sua immagine di solitaria è esplicita nella FAQ 40.
 * Tutto il resto dell'elenco è nostra ricostruzione su com'era davvero un
 * liceo giapponese nel 1987, ed è marcato `ricostruita`. Il registro sta in
 * `docs/FONTI.md`.
 */

$c = static fn (
    string $ckey, string $nome, string $jp, string $luogo, string $giorni,
    string $abilita, int $posti, string $conf, int $ordine, string $desc
): array => [
    'ckey' => $ckey, 'nome' => $nome, 'nome_jp' => $jp, 'luogo' => $luogo,
    'giorni' => $giorni, 'abilita' => $abilita, 'posti' => $posti,
    'confidenza' => $conf, 'ordine' => $ordine, 'descrizione' => $desc,
];

return [
    'tabella'     => 'club',
    'chiave'      => 'ckey',
    'descrizione' => 'club scolastici',
    'righe' => [

        $c('karate', 'Club di karate', '空手部', 'liceo', '1,3,5', 'sport', 24,
           'canone', 10,
           'Palestra, tatami consumato e un capitano che urla poco. Yusaku Hino ci passa tutti '
           . 'i pomeriggi liberi, e ci è arrivato per un motivo che non ha niente a che fare con '
           . 'il karate: da bambino Hikaru gli disse che se fosse diventato forte l\'avrebbe '
           . 'sposato, e lui ci ha creduto. Non ha mai smesso.'),

        $c('tennis', 'Club di tennis', 'テニス部', 'liceo', '2,4,6', 'sport', 30,
           'ricostruita', 20,
           'Due campi in terra rossa dietro la palestra, e una rete che qualcuno rattoppa ogni '
           . 'primavera. È il club più affollato della scuola e quello dove si guarda più gente '
           . 'di quanta ne giochi.'),

        $c('atletica', 'Club di atletica', '陸上部', 'liceo', '1,2,4,6', 'sport', 28,
           'ricostruita', 30,
           'Giri di pista al mattino presto, quando la scuola è ancora vuota. Chi ci sta dentro '
           . 'lo si riconosce perché arriva a lezione con i capelli bagnati.'),

        $c('musica', 'Club di musica leggera', '軽音楽部', 'liceo', '3,5', 'musica', 16,
           'ricostruita', 40,
           'Una stanza in fondo al corridoio con l\'insonorizzazione fatta di cartoni delle uova. '
           . 'Si prova per il festival della scuola tutto l\'anno, e il festival dura un giorno.'),

        $c('fotografia', 'Club di fotografia', '写真部', 'liceo', '2,5', 'kakko', 14,
           'ricostruita', 50,
           'Camera oscura ricavata da uno sgabuzzino, e il divieto tassativo di accendere la luce. '
           . 'È il club che sa più cose di tutti, perché è quello che guarda.'),

        $c('giornalino', 'Club del giornalino', '新聞部', 'liceo', '3,6', 'testa', 12,
           'ricostruita', 60,
           'Un ciclostile, tre macchine da scrivere e l\'ambizione di pubblicare qualcosa che la '
           . 'presidenza non censuri. Le voci ci arrivano prima che altrove, e ne escono scritte.'),

        $c('radio', 'Club radiofonico', '放送部', 'liceo', '1,4', 'musica', 10,
           'documentato', 70,
           'La cabina accanto alla presidenza, da cui parte la musica dell\'intervallo e la voce '
           . 'che chiama la gente in presidenza. Piccolo, e per questo ambito.'),

        $c('letteratura', 'Club di letteratura', '文芸部', 'liceo', '2,4', 'inglese', 12,
           'ricostruita', 80,
           'Quattro sedie, una teiera e una discussione su un libro che due dei presenti non hanno '
           . 'letto. Ci si iscrive anche per avere un posto tranquillo dove stare dopo le lezioni.'),

        $c('cucina', 'Club di cucina', '料理部', 'liceo', '5', 'cucina', 18,
           'ricostruita', 90,
           'Aula di economia domestica, sei fornelli e il problema ricorrente di chi deve mangiare '
           . 'quello che esce. Verso San Valentino diventa il posto più affollato della scuola.'),

        $c('nuoto', 'Club di nuoto', '水泳部', 'liceo', '1,3,6', 'nuoto', 22,
           'ricostruita', 100,
           'La piscina si apre a maggio e si chiude a settembre; negli altri mesi si fa palestra '
           . 'e si aspetta. Chi ci sta dentro conta i giorni.'),
    ],
];
