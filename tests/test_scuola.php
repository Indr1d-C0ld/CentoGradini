<?php

declare(strict_types=1);

/**
 * Il modello scolastico, verificato contro i tre protagonisti.
 *
 *   php tests/test_scuola.php
 */

require __DIR__ . '/_prova.php';

use App\Core\Config;
use App\Sim\Orologio;
use App\Sim\Scuola;

Config::load(dirname(__DIR__));

$TZ = new DateTimeZone('Asia/Tokyo');
$ts = static fn (string $s): int => (new DateTimeImmutable($s, $TZ))->getTimestamp();

prova('IL CANONE: i tre protagonisti finiscono nella classe giusta', function () {
    // È la prova che dice se il modello delle coorti è quello vero. Le date
    // di nascita vengono dal canone (docs/CANONE.md); le classi nell'anno
    // scolastico 1987-88 si ricavano dalla cronologia di Madoka.
    $casi = [
        // nome,     giorno, mese, anno,  sezione,             anno di corso
        ['Kyosuke',  15, 11, 1969, Scuola::SUPERIORI, 3],
        ['Madoka',   25,  5, 1969, Scuola::SUPERIORI, 3],
        ['Hikaru',   15, 11, 1971, Scuola::SUPERIORI, 1],
    ];
    foreach ($casi as [$chi, $g, $m, $annoNato, $sez, $anno]) {
        uguale($annoNato, Scuola::annoDiNascita($sez, $anno, $m, $g),
            "{$chi}: la classe " . Scuola::nomeClasse($sez, $anno) . ' deve dare il ' . $annoNato);
    }
});

prova('Kyosuke e Hikaru sono nati lo stesso giorno, a due anni di distanza', function () {
    uguale(1969, Scuola::annoDiNascita(Scuola::SUPERIORI, 3, 11, 15));
    uguale(1971, Scuola::annoDiNascita(Scuola::SUPERIORI, 1, 11, 15));
    uguale(2, Scuola::distanza(Scuola::SUPERIORI, 3, Scuola::SUPERIORI, 1),
        'Hikaru è due anni di scuola sotto');
});

prova('il taglio della coorte è il 1º aprile', function () {
    // Il «nato presto» (早生まれ) finisce in classe con chi ha quasi un anno
    // in meno: è la regola che rende il modello giapponese diverso dal nostro.
    vero(Scuola::primaDelTaglio(1, 1),  'il 1º gennaio è nato presto');
    vero(Scuola::primaDelTaglio(4, 1),  'il 1º aprile è ancora nato presto');
    vero(!Scuola::primaDelTaglio(4, 2), 'il 2 aprile apre la coorte nuova');
    vero(!Scuola::primaDelTaglio(12, 31));

    // Due compagni di classe, nati a un giorno di distanza, hanno anni
    // di nascita diversi.
    $a = Scuola::annoDiNascita(Scuola::SUPERIORI, 2, 4, 1);
    $b = Scuola::annoDiNascita(Scuola::SUPERIORI, 2, 4, 2);
    uguale(1, $a - $b, 'il nato il 1º aprile è dell\'anno dopo rispetto al nato il 2');
});

prova('le sei classi coprono sei coorti diverse e consecutive', function () {
    $anni = [];
    foreach ([[Scuola::MEDIE,1],[Scuola::MEDIE,2],[Scuola::MEDIE,3],
              [Scuola::SUPERIORI,1],[Scuola::SUPERIORI,2],[Scuola::SUPERIORI,3]] as [$s,$a]) {
        $anni[] = Scuola::annoDiNascita($s, $a, 6, 15);
    }
    uguale(6, count(array_unique($anni)), 'sei classi, sei coorti');
    for ($i = 1; $i < count($anni); $i++) {
        uguale(-1, $anni[$i] - $anni[$i-1], 'le coorti sono consecutive e decrescenti');
    }
});

prova('la 3ª media è la classe in cui comincia la storia', function () {
    // Kyosuke si trasferisce in 中等部3年 nell'aprile 1984: chi è in 3ª media
    // nel nostro anno congelato è nato tre anni dopo di lui.
    uguale(1972, Scuola::annoDiNascita(Scuola::MEDIE, 3, 11, 15));
    vero(Scuola::giocabile(Scuola::MEDIE, 3));
    vero(!Scuola::giocabile(Scuola::MEDIE, 1), 'le prime due medie non si giocano');
    vero(!Scuola::giocabile(Scuola::MEDIE, 2));
    uguale(4, count(Scuola::GIOCABILI));
});

prova('l\'età cambia al compleanno, non a capodanno', function () use ($ts) {
    // Madoka compie 18 anni il 25 maggio 1987.
    $prima = Scuola::eta(1969, 5, 25, $ts('1987-05-24 12:00:00'));
    $dopo  = Scuola::eta(1969, 5, 25, $ts('1987-05-25 12:00:00'));
    uguale(17, $prima);
    uguale(18, $dopo);
    // E resta 18 fino al maggio successivo, capodanno compreso.
    uguale(18, Scuola::eta(1969, 5, 25, $ts('1988-01-02 12:00:00')));
    uguale(18, Scuola::eta(1969, 5, 25, $ts('1988-03-30 12:00:00')));
});

prova('il compleanno si riconosce e si aspetta', function () use ($ts) {
    vero(Scuola::compleanno(11, 15, $ts('1987-11-15 08:00:00')));
    vero(!Scuola::compleanno(11, 15, $ts('1987-11-14 23:59:00')));
    uguale(0, Scuola::giorniAlCompleanno(11, 15, $ts('1987-11-15 08:00:00')));
    uguale(1, Scuola::giorniAlCompleanno(11, 15, $ts('1987-11-14 08:00:00')));
    // Scavallando la fine del ciclo: da marzo al novembre successivo.
    $q = Scuola::giorniAlCompleanno(11, 15, $ts('1988-03-01 08:00:00'));
    vero($q > 200 && $q < 300, "atteso un conto lungo, ottenuto {$q}");
});

prova('ogni compleanno possibile trova la sua classe', function () {
    // Passata completa: nessuna data deve produrre una coorte assurda.
    foreach (Scuola::GIOCABILI as [$sez, $anno]) {
        for ($m = 1; $m <= 12; $m++) {
            $giorniMese = (int) (new DateTimeImmutable("1987-{$m}-01"))->format('t');
            for ($g = 1; $g <= $giorniMese; $g++) {
                $n = Scuola::annoDiNascita($sez, $anno, $m, $g);
                vero($n >= 1969 && $n <= 1973, "{$sez}{$anno} {$g}/{$m} -> {$n}");
                $eta = Scuola::eta($n, $m, $g, (new DateTimeImmutable('1987-06-15', new DateTimeZone('Asia/Tokyo')))->getTimestamp());
                vero($eta >= 13 && $eta <= 18, "{$sez}{$anno} {$g}/{$m} -> {$eta} anni");
            }
        }
    }
});

prova('le classi giocabili si presentano con la fascia d\'età', function () {
    $s = Scuola::scelte();
    uguale(4, count($s));
    uguale('3ª media', $s[0]['nome']);
    uguale('中3', $s[0]['sigla']);
    uguale('3ª superiore', $s[3]['nome']);
    uguale('高3', $s[3]['sigla']);
    foreach ($s as $x) {
        vero(preg_match('/^\d{2}-\d{2} anni$/', $x['eta']) === 1, $x['nome'] . ': ' . $x['eta']);
        [$da, $a] = array_map('intval', explode('-', str_replace(' anni', '', $x['eta'])));
        uguale(1, $a - $da, $x['nome'] . ': in una classe convivono due età');
    }
});

prova('senpai e kōhai si contano bene', function () {
    uguale(0, Scuola::distanza(Scuola::SUPERIORI, 2, Scuola::SUPERIORI, 2), 'compagni di classe');
    uguale(1, Scuola::distanza(Scuola::SUPERIORI, 2, Scuola::SUPERIORI, 1));
    uguale(-1, Scuola::distanza(Scuola::SUPERIORI, 1, Scuola::SUPERIORI, 2));
    // Il passaggio dalle medie alle superiori non salta un gradino.
    uguale(1, Scuola::distanza(Scuola::SUPERIORI, 1, Scuola::MEDIE, 3));
});

riepilogo();
