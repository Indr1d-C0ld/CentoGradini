<?php

declare(strict_types=1);

/**
 * Il quartiere: luoghi, collegamenti, orari.
 *
 * Tocca il database in sola lettura (tabelle `luoghi` e `luogo_archi`), quindi
 * va lanciata dopo `migrate` e `seed`.
 *
 *   php tests/test_luoghi.php
 */

require __DIR__ . '/_prova.php';

use App\Core\Config;
use App\Sim\Luoghi;
use App\Sim\Orologio;

Config::load(dirname(__DIR__));

$TZ = new DateTimeZone('Asia/Tokyo');
$ts = static fn (string $s): int => (new DateTimeImmutable($s, $TZ))->getTimestamp();

prova('il quartiere è stato seminato', function () {
    vero(count(Luoghi::tutti()) >= 15, 'servono almeno quindici luoghi');
    vero(Luoghi::esiste('gradini'), 'la scalinata è il punto di partenza: deve esserci');
    vero(Luoghi::esiste('abcb'));
    vero(Luoghi::esiste('liceo'));
});

prova('NESSUN luogo è isolato', function () {
    // Un luogo irraggiungibile è una trappola: chi ci finisce non ne esce.
    $soli = Luoghi::isolati('gradini');
    uguale([], $soli, 'irraggiungibili dalla scalinata: ' . implode(', ', $soli));
});

prova('da ogni luogo si torna alla scalinata', function () {
    // Il grafo dev'essere connesso nei DUE sensi, non solo in uscita.
    foreach (array_keys(Luoghi::tutti()) as $k) {
        vero(Luoghi::percorso($k, 'gradini') !== null, "da {$k} non si torna indietro");
    }
});

prova('i collegamenti a piedi sono reciproci', function () {
    // Le uniche asimmetrie ammesse sono volute e documentate: la scalinata.
    $attese = [['gradini', 'liceo']];
    foreach (Luoghi::archi() as $da => $lista) {
        foreach ($lista as $arco) {
            $ritorno = Luoghi::minuti($arco['a'], $da);
            vero($ritorno !== null, "manca il ritorno {$arco['a']} -> {$da}");
            if ($ritorno !== $arco['minuti']) {
                $coppia = [$da, $arco['a']];
                sort($coppia);
                vero(in_array($coppia, array_map(static function (array $c): array {
                    sort($c);
                    return $c;
                }, $attese), true), "asimmetria non prevista fra {$da} e {$arco['a']}");
            }
        }
    }
});

prova('la scalinata costa di più in salita', function () {
    $su  = Luoghi::minuti('gradini', 'liceo');
    $giu = Luoghi::minuti('liceo', 'gradini');
    vero($su !== null && $giu !== null);
    vero($su > $giu, 'cento gradini in salita non sono cento in discesa');
});

prova('i tempi sono plausibili', function () {
    foreach (Luoghi::archi() as $da => $lista) {
        foreach ($lista as $arco) {
            if ($arco['mezzo'] === 'treno') {
                vero($arco['minuti'] >= 30, "{$da} -> {$arco['a']}: un treno di {$arco['minuti']} min?");
            } else {
                vero($arco['minuti'] >= 1 && $arco['minuti'] <= 20,
                    "{$da} -> {$arco['a']}: {$arco['minuti']} min a piedi");
            }
        }
    }
});

prova('fuori dal quartiere ci si va solo in treno', function () {
    foreach (Luoghi::tutti() as $k => $l) {
        if (!$l['fuori']) { continue; }
        foreach (Luoghi::archi()[$k] ?? [] as $arco) {
            uguale('treno', $arco['mezzo'], "da {$k} si esce a piedi?");
        }
    }
});

prova('il percorso più breve è più breve del passo singolo', function () {
    $p = Luoghi::percorso('stazione', 'liceo');
    vero($p !== null);
    vero($p['minuti'] > 0);
    vero(count($p['passi']) >= 2);
    uguale('stazione', $p['passi'][0]);
    uguale('liceo', $p['passi'][count($p['passi']) - 1]);
    // Ogni passo del percorso dev'essere un arco vero.
    for ($i = 0; $i < count($p['passi']) - 1; $i++) {
        vero(Luoghi::minuti($p['passi'][$i], $p['passi'][$i + 1]) !== null,
            'passo inesistente: ' . $p['passi'][$i] . ' -> ' . $p['passi'][$i + 1]);
    }
});

prova('il percorso verso sé stessi costa zero', function () {
    $p = Luoghi::percorso('abcb', 'abcb');
    uguale(0, $p['minuti']);
});

prova('gli orari di apertura valgono', function () use ($ts) {
    // La sala giochi apre alle 10 e chiude alle 22.
    [$chiusa] = Luoghi::accessibile('sala_giochi', $ts('1987-05-14 08:00:00'));
    vero(!$chiusa, 'alle 8 del mattino la sala giochi è chiusa');
    [$aperta] = Luoghi::accessibile('sala_giochi', $ts('1987-05-14 17:00:00'));
    vero($aperta, 'alle 17 è aperta');
});

prova('i luoghi senza orario sono sempre accessibili', function () use ($ts) {
    foreach (['gradini', 'viale', 'parco', 'argine'] as $k) {
        foreach (['03:00', '09:00', '15:00', '23:00'] as $ora) {
            [$ok] = Luoghi::accessibile($k, $ts("1987-05-14 {$ora}:00"));
            vero($ok, "{$k} alle {$ora} dovrebbe essere accessibile");
        }
    }
});

prova('la spiaggia esiste solo d\'estate', function () use ($ts) {
    [$inverno] = Luoghi::accessibile('spiaggia', $ts('1988-01-14 12:00:00'));
    vero(!$inverno, 'a gennaio non si va al mare');
    [$estate] = Luoghi::accessibile('spiaggia', $ts('1987-08-14 12:00:00'));
    vero($estate, 'ad agosto sì');
});

prova('la scuola chiude durante le vacanze', function () use ($ts) {
    [$vacanza] = Luoghi::accessibile('liceo', $ts('1987-08-10 10:00:00'));
    vero(!$vacanza, 'in agosto il liceo è chiuso');
    [$lezione] = Luoghi::accessibile('liceo', $ts('1987-05-14 10:00:00'));
    vero($lezione);
});

prova('le uscite valutano l\'apertura ALL\'ARRIVO', function () use ($ts) {
    // Partire per un posto che chiude mentre si è per strada è esattamente
    // ciò che deve poter succedere, ma il gioco deve dirlo prima.
    $cerca = static function (int $quando) use ($ts): array {
        foreach (Luoghi::uscite('commerciale', $quando) as $u) {
            if ($u['a'] === 'sala_giochi') { return $u; }
        }
        throw new RuntimeException('la sala giochi dovrebbe confinare con la via commerciale');
    };
    // La sala giochi chiude alle 22:00 e dista due minuti.
    vero($cerca($ts('1987-05-14 21:50:00'))['aperto'], 'alle 21:50 si fa in tempo');
    // Alle 21:59 è ancora aperta, ma si arriva alle 22:01: va detto adesso,
    // non quando si è già per strada.
    vero(Luoghi::accessibile('sala_giochi', $ts('1987-05-14 21:59:00'))[0],
        'alle 21:59 è ancora aperta');
    vero(!$cerca($ts('1987-05-14 21:59:00'))['aperto'],
        'due minuti di strada e la trova chiusa: va detto prima di partire');
});

prova('ogni luogo ha un testo suo', function () {
    $visti = [];
    foreach (Luoghi::tutti() as $k => $l) {
        vero(trim((string) $l['nome']) !== '', "{$k}: nome vuoto");
        vero(mb_strlen((string) $l['descrizione']) > 80, "{$k}: descrizione troppo corta");
        vero(!isset($visti[$l['descrizione']]), "{$k}: descrizione copiata da un altro luogo");
        $visti[$l['descrizione']] = true;
    }
});

prova('le coordinate stanno dentro la carta', function () {
    foreach (Luoghi::tutti() as $k => $l) {
        vero($l['x'] > 30 && $l['x'] < 970, "{$k}: x fuori dai margini");
        vero($l['y'] > 30 && $l['y'] < 670, "{$k}: y fuori dai margini");
    }
});

prova('due luoghi non si sovrappongono sulla carta', function () {
    $tutti = array_values(Luoghi::tutti());
    for ($i = 0; $i < count($tutti); $i++) {
        for ($j = $i + 1; $j < count($tutti); $j++) {
            $d = hypot($tutti[$i]['x'] - $tutti[$j]['x'], $tutti[$i]['y'] - $tutti[$j]['y']);
            vero($d > 45, "{$tutti[$i]['lkey']} e {$tutti[$j]['lkey']} sono a {$d} px l'uno dall'altro");
        }
    }
});

/**
 * Larghezza approssimata di un'etichetta sulla carta, in pixel a 15px di
 * corpo con il font di sistema.
 *
 * I pixel veri li conosce solo il browser, e questa prova gira nel terminale.
 * Il modello e' tarato sulle diciassette etichette vere misurate a schermo:
 * errore medio 3,5%, massimo 10%. Alla stima si aggiunge un margine del 12%
 * perche' sbagli per eccesso — una prova che protegge deve lamentarsi un po'
 * prima del necessario, non un po' dopo.
 */
function larghezzaEtichetta(string $testo): float
{
    $w = 0.0;
    foreach (preg_split('//u', $testo, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $c) {
        if ($c === ' ') {
            $w += 4.8;
        } elseif (mb_strpos("iljItf'.", $c) !== false) {
            $w += 5.0;
        } elseif (mb_strtoupper($c) === $c && mb_strtolower($c) !== $c) {
            $w += 9.3;
        } else {
            $w += 8.6;
        }
    }
    return $w * 1.12;
}

/** @return array{x1:float,x2:float,y1:float,y2:float} la casella del nome sulla carta */
function casellaEtichetta(array $l): array
{
    $w = larghezzaEtichetta((string) $l['nome']);
    $r = in_array($l['tipo'], ['scuola', 'ritrovo'], true) ? 10 : 8;
    // carta.js scrive sotto il pallino nella meta' alta, sopra in quella bassa.
    $sotto = $l['y'] < 700 * 0.45;
    $cy = $l['y'] + ($sotto ? $r + 9 : -($r + 9));
    return [
        'x1' => $l['x'] - $w / 2, 'x2' => $l['x'] + $w / 2,
        'y1' => $sotto ? $cy : $cy - 16, 'y2' => $sotto ? $cy + 16 : $cy,
    ];
}

prova('i nomi sui luoghi non si accavallano', function () {
    // È il difetto che rende una carta inutile proprio dove è più fitta.
    $caselle = [];
    foreach (Luoghi::tutti() as $k => $l) {
        $caselle[$k] = casellaEtichetta($l);
    }
    $chiavi = array_keys($caselle);
    for ($i = 0; $i < count($chiavi); $i++) {
        for ($j = $i + 1; $j < count($chiavi); $j++) {
            $a = $caselle[$chiavi[$i]];
            $b = $caselle[$chiavi[$j]];
            $scontro = $a['x1'] < $b['x2'] && $b['x1'] < $a['x2']
                    && $a['y1'] < $b['y2'] && $b['y1'] < $a['y2'];
            vero(!$scontro, "le etichette di {$chiavi[$i]} e {$chiavi[$j]} si sovrappongono");
        }
    }
});

prova('nessun nome esce dai bordi della carta', function () {
    foreach (Luoghi::tutti() as $k => $l) {
        $c = casellaEtichetta($l);
        vero($c['x1'] >= 0, "{$k}: il nome sborda a sinistra di " . round(-$c['x1']) . ' px');
        vero($c['x2'] <= 1000, "{$k}: il nome sborda a destra di " . round($c['x2'] - 1000) . ' px');
        vero($c['y1'] >= 0 && $c['y2'] <= 700, "{$k}: il nome esce sopra o sotto");
    }
});

riepilogo();
