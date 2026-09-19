<?php

declare(strict_types=1);

/**
 * Il tempo che fa.
 *
 * Due famiglie di prove. Le prime verificano che il modello sia **usabile**:
 * deterministico, continuo, senza valori assurdi. Le seconde che sia
 * **giusto**: un anno simulato deve somigliare a un anno di Tokyo, non a un
 * anno qualunque. Le bande di confronto vengono dalle medie climatiche
 * riportate in docs/FONTI.md.
 *
 *   php tests/test_meteo.php
 */

require __DIR__ . '/_prova.php';

use App\Core\Config;
use App\Sim\Meteo;
use App\Sim\Orologio;

Config::load(dirname(__DIR__));

$TZ = new DateTimeZone('Asia/Tokyo');
$ts = static fn (string $s): int => (new DateTimeImmutable($s, $TZ))->getTimestamp();

/** Passata su un anno intero, un campione ogni tre ore. */
function annoSimulato(callable $ts): array
{
    $out = [];
    $g = new DateTimeImmutable('1987-04-06 00:00:00', new DateTimeZone('Asia/Tokyo'));
    $fine = new DateTimeImmutable('1988-04-06 00:00:00', new DateTimeZone('Asia/Tokyo'));
    while ($g < $fine) {
        $out[$g->format('Y-m-d H')] = Meteo::a($g->getTimestamp()) + ['mese' => (int) $g->format('n')];
        $g = $g->modify('+3 hours');
    }
    return $out;
}

prova('è deterministico', function () use ($ts) {
    $x = $ts('1987-07-14 15:00:00');
    $a = Meteo::a($x);
    $b = Meteo::a($x);
    uguale($a, $b, 'stesso istante, stesso tempo');
});

prova('è continuo: non salta da un minuto all\'altro', function () use ($ts) {
    // Se qui salta, il giocatore che ricarica la pagina vede il sole
    // diventare temporale in un secondo.
    $x = $ts('1987-06-20 12:00:00');
    $prec = Meteo::a($x);
    for ($i = 1; $i <= 60; $i++) {
        $ora = Meteo::a($x + $i * 60);
        vero(abs($ora['temperatura'] - $prec['temperatura']) < 0.6,
            'salto di temperatura al minuto ' . $i);
        vero(abs($ora['nuvolosita'] - $prec['nuvolosita']) < 0.05,
            'salto di nuvolosità al minuto ' . $i);
        $prec = $ora;
    }
});

prova('nessun valore assurdo in tutto l\'anno', function () use ($ts) {
    foreach (annoSimulato($ts) as $quando => $m) {
        vero($m['temperatura'] > -12 && $m['temperatura'] < 44, "{$quando}: temperatura {$m['temperatura']}");
        vero($m['nuvolosita'] >= 0 && $m['nuvolosita'] <= 1, "{$quando}: nuvolosità");
        vero($m['pioggia'] >= 0 && $m['pioggia'] < 60, "{$quando}: pioggia {$m['pioggia']}");
        vero($m['vento'] >= 0 && $m['vento'] < 45, "{$quando}: vento {$m['vento']}");
        vero($m['umidita'] > 0 && $m['umidita'] <= 1, "{$quando}: umidità");
        vero($m['descrizione'] !== '', "{$quando}: descrizione vuota");
        vero($m['icona'] !== '', "{$quando}: icona vuota");
    }
});

prova('le medie mensili stanno nelle bande di Tokyo', function () use ($ts) {
    // Tolleranza di 3 gradi sulla media climatica: il rumore deve variare
    // l'anno, non riscrivere il clima.
    $attese = [1 => 5.2, 2 => 5.7, 3 => 8.7, 4 => 14.0, 5 => 18.4, 6 => 21.4,
               7 => 25.0, 8 => 26.4, 9 => 22.8, 10 => 17.5, 11 => 12.1, 12 => 7.6];
    $somme = []; $conti = [];
    foreach (annoSimulato($ts) as $m) {
        $somme[$m['mese']] = ($somme[$m['mese']] ?? 0) + $m['temperatura'];
        $conti[$m['mese']] = ($conti[$m['mese']] ?? 0) + 1;
    }
    foreach ($attese as $mese => $att) {
        $media = $somme[$mese] / $conti[$mese];
        vicino($att, $media, 3.0, 'media di ' . Orologio::nomeMese($mese));
    }
});

prova('agosto è il mese più caldo, gennaio il più freddo', function () use ($ts) {
    $somme = []; $conti = [];
    foreach (annoSimulato($ts) as $m) {
        $somme[$m['mese']] = ($somme[$m['mese']] ?? 0) + $m['temperatura'];
        $conti[$m['mese']] = ($conti[$m['mese']] ?? 0) + 1;
    }
    $medie = [];
    foreach ($somme as $mese => $s) { $medie[$mese] = $s / $conti[$mese]; }
    uguale(8, (int) array_search(max($medie), $medie, true));
    uguale(1, (int) array_search(min($medie), $medie, true));
});

prova('la stagione delle piogge piove davvero', function () use ($ts) {
    // Nel tsuyu deve piovere molto più che in inverno: se non fosse così,
    // sarebbe un modello di un posto qualunque, non del Giappone.
    $bagnate = static function (string $da, string $a) use ($ts): float {
        $tot = 0; $con = 0;
        for ($g = new DateTimeImmutable($da, new DateTimeZone('Asia/Tokyo')),
             $f = new DateTimeImmutable($a, new DateTimeZone('Asia/Tokyo')); $g < $f; $g = $g->modify('+3 hours')) {
            $tot++;
            if (Meteo::a($g->getTimestamp())['pioggia'] > 0) { $con++; }
        }
        return $con / max(1, $tot);
    };
    $tsuyu   = $bagnate('1987-06-10 00:00:00', '1987-07-18 00:00:00');
    $inverno = $bagnate('1987-12-10 00:00:00', '1988-02-10 00:00:00');
    vero($tsuyu > 0.30, 'nel tsuyu deve piovere spesso, ottenuto ' . round($tsuyu * 100) . '%');
    vero($inverno < 0.18, 'l\'inverno di Tokyo è secco, ottenuto ' . round($inverno * 100) . '%');
    vero($tsuyu > $inverno * 2.5, 'il tsuyu deve staccare nettamente l\'inverno');
});

prova('la neve cade solo d\'inverno, e poco', function () use ($ts) {
    $giorniNeve = [];
    foreach (annoSimulato($ts) as $quando => $m) {
        if ($m['neve']) { $giorniNeve[substr($quando, 0, 10)] = $m['mese']; }
    }
    foreach ($giorniNeve as $giorno => $mese) {
        vero(in_array($mese, [12, 1, 2, 3], true), "neve nel mese {$mese} ({$giorno})");
    }
    vero(count($giorniNeve) <= 12, 'a Tokyo non nevica per due settimane: ' . count($giorniNeve) . ' giorni');
});

prova('di notte fa più freddo che di giorno', function () use ($ts) {
    foreach (['1987-05-14', '1987-08-14', '1987-11-14', '1988-01-14'] as $g) {
        $notte  = Meteo::a($ts("{$g} 05:00:00"))['temperatura'];
        $giorno = Meteo::a($ts("{$g} 14:00:00"))['temperatura'];
        vero($giorno > $notte, "{$g}: le 14 devono battere le 5 del mattino");
    }
});

prova('la notte è notte quando deve', function () use ($ts) {
    vero(Meteo::notte($ts('1987-12-21 18:00:00')), 'd\'inverno alle 18 è già buio');
    vero(!Meteo::notte($ts('1987-06-21 18:00:00')), 'd\'estate alle 18 c\'è ancora luce');
    vero(Meteo::notte($ts('1987-06-21 03:00:00')));
    vero(!Meteo::notte($ts('1987-06-21 12:00:00')));
});

prova('i tifoni esistono, ma solo in settembre', function () use ($ts) {
    $mesi = [];
    foreach (annoSimulato($ts) as $m) {
        if ($m['tifone']) { $mesi[$m['mese']] = true; }
    }
    foreach (array_keys($mesi) as $mese) {
        vero(in_array($mese, [9, 10], true), "tifone nel mese {$mese}");
    }
});

prova('il calendario si ripete, il tempo no', function () use ($ts) {
    // È la differenza fra un anno che torna e una registrazione che si
    // riavvolge: la data del secondo giro è la stessa, il cielo no.
    $x = $ts('1987-05-20 14:00:00');
    $unGiro = Orologio::cicloDurata();
    $primo  = Meteo::a($x);
    $secondo = Meteo::a($x + $unGiro);
    uguale('1987-05-20', Orologio::data($x + $unGiro)->format('Y-m-d'), 'stessa data');
    vero($primo['temperatura'] !== $secondo['temperatura']
        || $primo['nuvolosita'] !== $secondo['nuvolosita'], 'ma non lo stesso tempo');
});

prova('l\'ombrello serve quando piove', function () use ($ts) {
    foreach (annoSimulato($ts) as $quando => $m) {
        if ($m['pioggia'] >= 1.0) {
            vero($m['ombrello'], "{$quando}: piove {$m['pioggia']} mm ma niente ombrello");
        }
        if ($m['pioggia'] === 0.0) {
            vero(!$m['ombrello'], "{$quando}: ombrello senza pioggia");
        }
    }
});

riepilogo();
