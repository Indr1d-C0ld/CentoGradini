<?php

declare(strict_types=1);

/**
 * Le prove nate dall'audit del 22 settembre 2026.
 *
 * Ognuna copre un difetto che era vivo e che nessuna prova vedeva. Stanno
 * insieme apposta: sono tutte dello stesso genere — cose che rispondono 200,
 * non sollevano eccezioni e intanto fanno la cosa sbagliata.
 */

require __DIR__ . '/_prova.php';

use App\Core\Config;
use App\Core\Database;
use App\Core\GameConfig;
use App\Game\Segreto;
use App\Sim\Orologio;

Config::load(dirname(__DIR__));

const PREFISSO = 'zzaud';

function pulisci(): void
{
    $ids = array_column(Database::all('SELECT id FROM personaggi WHERE cognome = ?', [ucfirst(PREFISSO)]), 'id');
    foreach ($ids as $id) {
        Database::run('DELETE FROM anomalie WHERE osservatore_id = ? OR soggetto_id = ?', [$id, $id]);
        Database::run('DELETE FROM incidente_testimoni WHERE personaggio_id = ?', [$id]);
        Database::run('DELETE FROM incidenti WHERE attore_id = ?', [$id]);
    }
    Database::run('DELETE FROM personaggi WHERE cognome = ?', [ucfirst(PREFISSO)]);
}
pulisci();

function attore(string $nome, string $luogo = 'gradini', int $esper = 0): array
{
    Database::run(
        'INSERT INTO personaggi (user_id, nome, cognome, sesso, sezione, anno, scheda, stato, luogo,
            anno_nascita, nato_mese, nato_giorno, esper, rissa, testa, dai_suki, cuore,
            pf, pf_max, pp, pp_max, compostezza, compostezza_max)
         VALUES (NULL, ?, ?, ?, ?, 2, ?, ?, ?, 1970, 5, 5, ?, 8, 10, 9, 7, 11, 11, 20, 20, 6, 6)',
        [$nome, ucfirst(PREFISSO), 'm', 'superiori', 'completa', 'attivo', $luogo, $esper]
    );
    return Database::first('SELECT * FROM personaggi WHERE id = ?', [(int) Database::lastInsertId()]);
}

// =============================================================================
titolo('Le manopole dicono la verità');

prova('cambiare una manopola non ne declassa il tipo', function () {
    // Il difetto: `set()` aveva come difetto 'string' e riscriveva sempre
    // `ctype`. `bin/console.php config:set chiave valore` — terzo argomento
    // omesso, come si fa sempre — declassava la manopola a stringa, e da quel
    // momento la pagina delle leve offriva una casella di testo dove serviva
    // una spunta. Il valore continuava a funzionare: il guasto non si vedeva.
    $prima = Database::first('SELECT cvalue, ctype FROM game_config WHERE ckey = ?', ['world.seed']);
    vero($prima !== null, 'la manopola di prova deve esistere');
    uguale('int', (string) $prima['ctype']);
    try {
        GameConfig::set('world.seed', '12345');
        $dopo = Database::first('SELECT cvalue, ctype FROM game_config WHERE ckey = ?', ['world.seed']);
        uguale('12345', (string) $dopo['cvalue'], 'il valore deve cambiare');
        uguale('int', (string) $dopo['ctype'], 'il tipo NO');
        // E quando lo si dice esplicitamente, il tipo cambia.
        GameConfig::set('world.seed', '12345', 'string');
        uguale('string', (string) Database::first('SELECT ctype FROM game_config WHERE ckey = ?', ['world.seed'])['ctype']);
    } finally {
        GameConfig::set('world.seed', (string) $prima['cvalue'], (string) $prima['ctype']);
    }
});

prova('ogni manopola letta dal codice esiste in tabella', function () {
    $sorgente = '';
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__) . '/src')) as $f) {
        if ($f->isFile() && $f->getExtension() === 'php') { $sorgente .= file_get_contents($f->getPathname()); }
    }
    preg_match_all('/GameConfig::(?:int|bool|float|str|string|get)\(\s*\'([^\']+)\'/', $sorgente, $m);
    $inDb = array_column(Database::all('SELECT ckey FROM game_config'), 'ckey');
    $mancanti = array_values(array_diff(array_unique($m[1]), $inDb));
    uguale([], $mancanti, 'manopole lette dal codice ma assenti dal pannello: ' . implode(', ', $mancanti));
});

prova('ogni manopola in tabella e\' letta da qualcuno', function () {
    // Una leva collegata a niente e' peggio di una leva assente: chi la gira e
    // non vede effetto conclude che sia rotto il gioco.
    $sorgente = '';
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__) . '/src')) as $f) {
        if ($f->isFile() && $f->getExtension() === 'php') { $sorgente .= file_get_contents($f->getPathname()); }
    }
    $morte = [];
    foreach (array_column(Database::all('SELECT ckey FROM game_config'), 'ckey') as $k) {
        if (!str_contains($sorgente, "'" . $k . "'")) { $morte[] = $k; }
    }
    uguale([], $morte, 'manopole che nessuno legge: ' . implode(', ', $morte));
});

// =============================================================================
titolo('Un incidente abbandonato non è gratis');

prova('passato il tempo, chi non ha coperto lascia l\'anomalia', function () {
    $epoca = Orologio::lineare();
    $esper = attore('Esper', 'gradini', 1);
    $teste = attore('Testimone', 'gradini');

    // Un incidente aperto, con un testimone che ha notato, gia' vecchio.
    $minuti = GameConfig::int('segreto.incidente_scade_minuti', 60);
    Database::run(
        'INSERT INTO incidenti (attore_id, pkey, luogo, gts, vistosita, notato_da, stato)
         VALUES (?, ?, ?, ?, 6, 1, ?)',
        [(int) $esper['id'], 'teletrasporto', 'gradini', $epoca - ($minuti + 5) * 60, 'aperto']
    );
    $inc = (int) Database::lastInsertId();
    Database::run('INSERT INTO incidente_testimoni (incidente_id, personaggio_id, notato) VALUES (?, ?, 1)',
        [$inc, (int) $teste['id']]);

    $prima = (int) Database::first('SELECT COUNT(*) n FROM anomalie WHERE osservatore_id = ?', [(int) $teste['id']])['n'];
    uguale(0, $prima, 'si parte senza anomalie');

    uguale(1, Segreto::incidentiScaduti($epoca), 'l\'incidente vecchio deve chiudersi');
    uguale('sfuggito', (string) Database::first('SELECT stato FROM incidenti WHERE id = ?', [$inc])['stato']);
    uguale(1, (int) Database::first('SELECT COUNT(*) n FROM anomalie WHERE osservatore_id = ?', [(int) $teste['id']])['n'],
        'il testimone deve essersi tenuto l\'anomalia');
});

prova('un incidente appena aperto non si tocca', function () {
    $epoca = Orologio::lineare();
    $esper = attore('Fresco', 'gradini', 1);
    Database::run(
        'INSERT INTO incidenti (attore_id, pkey, luogo, gts, vistosita, notato_da, stato) VALUES (?, ?, ?, ?, 6, 1, ?)',
        [(int) $esper['id'], 'teletrasporto', 'gradini', $epoca - 60, 'aperto']
    );
    $inc = (int) Database::lastInsertId();
    Segreto::incidentiScaduti($epoca);
    uguale('aperto', (string) Database::first('SELECT stato FROM incidenti WHERE id = ?', [$inc])['stato'],
        'chi ha appena usato il potere sta ancora decidendo');
});

prova('un testimone che non aveva notato non guadagna un\'anomalia', function () {
    $epoca = Orologio::lineare();
    $esper = attore('Fortunato', 'gradini', 1);
    $cieco = attore('Distratto', 'gradini');
    $minuti = GameConfig::int('segreto.incidente_scade_minuti', 60);
    Database::run(
        'INSERT INTO incidenti (attore_id, pkey, luogo, gts, vistosita, notato_da, stato) VALUES (?, ?, ?, ?, 6, 0, ?)',
        [(int) $esper['id'], 'teletrasporto', 'gradini', $epoca - ($minuti + 5) * 60, 'aperto']
    );
    $inc = (int) Database::lastInsertId();
    Database::run('INSERT INTO incidente_testimoni (incidente_id, personaggio_id, notato) VALUES (?, ?, 0)',
        [$inc, (int) $cieco['id']]);
    Segreto::incidentiScaduti($epoca);
    uguale(0, (int) Database::first('SELECT COUNT(*) n FROM anomalie WHERE osservatore_id = ?', [(int) $cieco['id']])['n'],
        'chi non ha visto niente non ha niente da annotare');
});

prova('un incidente gia\' coperto resta coperto', function () {
    $epoca = Orologio::lineare();
    $esper = attore('Coperto', 'gradini', 1);
    $minuti = GameConfig::int('segreto.incidente_scade_minuti', 60);
    Database::run(
        'INSERT INTO incidenti (attore_id, pkey, luogo, gts, vistosita, notato_da, stato) VALUES (?, ?, ?, ?, 6, 1, ?)',
        [(int) $esper['id'], 'teletrasporto', 'gradini', $epoca - ($minuti + 99) * 60, 'coperto']
    );
    $inc = (int) Database::lastInsertId();
    uguale(0, Segreto::incidentiScaduti($epoca), 'non c\'e' . "'" . ' niente da chiudere');
    uguale('coperto', (string) Database::first('SELECT stato FROM incidenti WHERE id = ?', [$inc])['stato']);
});

// --- pulizia ------------------------------------------------------------------
pulisci();
riepilogo();
