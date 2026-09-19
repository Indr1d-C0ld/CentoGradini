<?php

declare(strict_types=1);

/**
 * L'orologio dell'«eterno 1987».
 *
 * È il pezzo su cui si regge tutto il resto — calendario, meteo, spostamenti —
 * quindi le prove sono più fitte che altrove. Tocca il database solo per
 * leggere due chiavi di configurazione.
 *
 *   php tests/test_orologio.php
 */

require __DIR__ . '/_prova.php';

use App\Core\Config;
use App\Sim\Orologio;

Config::load(dirname(__DIR__));

$TZ  = new DateTimeZone('Asia/Tokyo');
$ts  = static fn (string $s): int => (new DateTimeImmutable($s, $TZ))->getTimestamp();

// Tutte le prove partono da un'epoca nota, così i numeri sono controllabili
// a mano invece che «quelli che escono».
$EPOCA_REALE = $ts('2026-09-19 00:00:00');
Orologio::fingiEpoca($EPOCA_REALE);   // il mondo nasce qui, in tutte le prove

prova('il ciclo dura 366 giorni', function () {
    // Il 1988 è bisestile e il 29 febbraio cade dentro l'anno scolastico:
    // serve davvero, ed è il giorno in cui è andato in onda l'episodio 47.
    uguale(366 * 86400, Orologio::cicloDurata());
});

prova('la compressione è quella configurata', function () {
    vero(Orologio::compressione() >= 1);
});

prova('un giorno reale vale quattro giorni di gioco', function () use ($EPOCA_REALE) {
    Orologio::fingiAdesso($EPOCA_REALE);
    $a = Orologio::lineare();
    Orologio::fingiAdesso($EPOCA_REALE + 86400);
    $b = Orologio::lineare();
    uguale(86400 * Orologio::compressione(), $b - $a);
    Orologio::fingiAdesso(null);
});

prova('l\'avvolgimento è idempotente', function () use ($ts) {
    $x = $ts('1987-08-14 13:00:00');
    uguale($x, Orologio::avvolgi($x));
    uguale($x, Orologio::avvolgi(Orologio::avvolgi($x)));
});

prova('un istante oltre il ciclo torna all\'inizio', function () use ($ts) {
    $inizio = $ts(Orologio::CICLO_INIZIO);
    // Un ciclo esatto dopo l'inizio si è di nuovo all'inizio.
    uguale($inizio, Orologio::avvolgi($inizio + Orologio::cicloDurata()));
    // E mezza giornata dopo, mezza giornata dentro il ciclo.
    uguale($inizio + 43200, Orologio::avvolgi($inizio + Orologio::cicloDurata() + 43200));
});

prova('un istante PRIMA del ciclo non finisce nel 1986', function () use ($ts) {
    // Il modulo di PHP tiene il segno del dividendo: senza il modulo «a
    // pavimento» qui uscirebbe una data dell'anno prima.
    $inizio = $ts(Orologio::CICLO_INIZIO);
    $prima  = Orologio::avvolgi($inizio - 3600);
    vero($prima >= $inizio, 'deve restare dentro il ciclo');
    uguale('1988', Orologio::data($prima)->format('Y'), 'un\'ora prima dell\'inizio è la fine del ciclo');
});

prova('l\'avvolgimento copre tutto il ciclo e niente di più', function () use ($ts) {
    $inizio = $ts(Orologio::CICLO_INIZIO);
    for ($g = 0; $g < 366; $g += 7) {
        $x = Orologio::avvolgi($inizio + $g * 86400 + 3 * Orologio::cicloDurata());
        vero($x >= $inizio && $x < $inizio + Orologio::cicloDurata(), "giorno {$g} fuori dal ciclo");
    }
});

prova('il giro si conta a partire da zero', function () use ($EPOCA_REALE) {
    Orologio::fingiAdesso($EPOCA_REALE);
    uguale(0, Orologio::giro(), 'il primo anno è il giro 0');
    // Un ciclo di gioco dura cicloDurata/compressione secondi reali.
    $unGiro = (int) (Orologio::cicloDurata() / Orologio::compressione());
    Orologio::fingiAdesso($EPOCA_REALE + $unGiro + 60);
    uguale(1, Orologio::giro());
    Orologio::fingiAdesso($EPOCA_REALE + 3 * $unGiro + 60);
    uguale(3, Orologio::giro());
    Orologio::fingiAdesso(null);
});

prova('il calendario torna al punto di partenza dopo un giro', function () use ($EPOCA_REALE) {
    Orologio::fingiAdesso($EPOCA_REALE);
    $primo = Orologio::gts();
    $unGiro = (int) (Orologio::cicloDurata() / Orologio::compressione());
    Orologio::fingiAdesso($EPOCA_REALE + $unGiro);
    uguale($primo, Orologio::gts(), 'stesso istante di gioco, un anno dopo');
    Orologio::fingiAdesso(null);
});

prova('l\'ora di gioco è ora di Tokyo, senza ora legale', function () use ($ts) {
    // Il Giappone non ha ora legale: lo scarto da UTC è +9 tutto l'anno.
    foreach (['1987-07-15 12:00:00', '1988-01-15 12:00:00'] as $q) {
        uguale('+09:00', Orologio::data($ts($q))->format('P'), $q);
    }
});

prova('il primo giorno di scuola è un lunedì', function () use ($ts) {
    uguale('lunedì', Orologio::giornoSettimana($ts('1987-04-06 08:00:00')));
});

prova('formattazione estesa e breve', function () use ($ts) {
    $x = $ts('1987-04-06 07:05:00');
    uguale('lunedì 6 aprile 1987, 7:05', Orologio::esteso($x));
    uguale('6 aprile, 7:05', Orologio::breve($x));
});

prova('quanto manca in tempo reale', function () use ($EPOCA_REALE, $ts) {
    Orologio::fingiAdesso($EPOCA_REALE);
    $ora  = Orologio::gts();
    $fra  = Orologio::avvolgi($ora + 3600);          // un'ora di gioco
    $atteso = (int) ceil(3600 / Orologio::compressione());
    uguale($atteso, Orologio::realiPerArrivare($fra));
    Orologio::fingiAdesso(null);
});

prova('un traguardo già passato si intende nel prossimo giro', function () use ($EPOCA_REALE) {
    Orologio::fingiAdesso($EPOCA_REALE);
    $indietro = Orologio::avvolgi(Orologio::gts() - 3600);
    vero(Orologio::realiPerArrivare($indietro) > 0, 'mai un\'attesa negativa');
    Orologio::fingiAdesso(null);
});

prova('l\'epoca non dipende dal fuso del processo', function () {
    // È il difetto che faceva vedere due mondi diversi allo stesso database:
    // la console imposta app.timezone, un php -r qualunque no, e due ore di
    // scarto diventano otto ore di gioco. L'epoca si legge sempre allo stesso
    // modo, qualunque cosa abbia in testa il processo.
    Orologio::fingiEpoca(null);
    $prima = date_default_timezone_get();
    $letture = [];
    foreach (['UTC', 'Europe/Rome', 'Asia/Tokyo', 'America/Los_Angeles'] as $tz) {
        date_default_timezone_set($tz);
        $letture[$tz] = Orologio::epocaReale();
    }
    date_default_timezone_set($prima);
    uguale(1, count(array_unique($letture)),
        'letture diverse per fuso: ' . json_encode($letture));
});

prova('l\'epoca si accetta sia come numero sia come data', function () {
    $tz = new DateTimeZone((string) Config::get('app.timezone', 'Europe/Rome'));
    $atteso = (new DateTimeImmutable('2026-09-19 05:27:17', $tz))->getTimestamp();
    // La forma vecchia (data in chiaro) va letta nel fuso dell'applicazione,
    // non in quello del processo.
    // La prova scrive su game_config, che è stato condiviso: si rimette
    // com'era comunque vada, altrimenti sposta il mondo di chi gioca.
    $originale = (string) \App\Core\GameConfig::get('clock.epoch_real', '');
    $primaTz = date_default_timezone_get();
    try {
        date_default_timezone_set('Asia/Tokyo');
        \App\Core\GameConfig::set('clock.epoch_real', '2026-09-19 05:27:17', 'string');
        Orologio::fingiEpoca(null);
        uguale($atteso, Orologio::epocaReale(), 'forma testuale');
        \App\Core\GameConfig::set('clock.epoch_real', (string) $atteso, 'int');
        uguale($atteso, Orologio::epocaReale(), 'forma numerica');
    } finally {
        date_default_timezone_set($primaTz);
        \App\Core\GameConfig::set('clock.epoch_real', $originale,
            ctype_digit($originale) ? 'int' : 'string');
    }
});

prova('l\'orologio è monotono', function () use ($EPOCA_REALE) {
    $prec = null;
    for ($i = 0; $i < 400; $i++) {
        Orologio::fingiAdesso($EPOCA_REALE + $i * 7919);   // passo primo, per non cadere sempre in fase
        $x = Orologio::lineare();
        if ($prec !== null) {
            vero($x > $prec, 'l\'istante lineare non torna mai indietro');
        }
        $prec = $x;
    }
    Orologio::fingiAdesso(null);
});

riepilogo();
