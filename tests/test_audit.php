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
        Database::run('DELETE FROM voci_versioni WHERE voce_id IN (SELECT id FROM voci WHERE soggetto_id = ?)', [$id]);
        Database::run('DELETE FROM voci WHERE soggetto_id = ?', [$id]);
        Database::run('DELETE FROM tracce WHERE personaggio_id = ?', [$id]);
        Database::run('DELETE FROM oggetti_passaggi WHERE da_id = ? OR a_id = ?', [$id, $id]);
    }
    Database::run('DELETE FROM personaggi WHERE cognome = ?', [ucfirst(PREFISSO)]);
    Database::run('DELETE FROM users WHERE username LIKE ?', [PREFISSO . '-%']);
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

// =============================================================================
titolo('Il trasloco, e il ritorno (audit del 23 settembre)');

/** Un giocatore vero, con utente: serve ai ricordi, che stanno sull'utente. */
function giocatore(string $nome, string $luogo = 'abcb', int $esper = 1): array
{
    Database::run('INSERT INTO users (username, email, password_hash, status) VALUES (?, ?, ?, ?)',
        [PREFISSO . '-' . mb_strtolower($nome), PREFISSO . '-' . mb_strtolower($nome) . '@example.invalid', 'x', 'active']);
    $uid = (int) Database::lastInsertId();
    Database::run(
        'INSERT INTO personaggi (user_id, nome, cognome, sesso, sezione, anno, scheda, stato, luogo,
            anno_nascita, nato_mese, nato_giorno, esper, rissa, testa, dai_suki, cuore,
            pf, pf_max, pp, pp_max, compostezza, compostezza_max)
         VALUES (?, ?, ?, ?, ?, 2, ?, ?, ?, 1970, 5, 5, ?, 8, 10, 9, 7, 11, 11, 20, 20, 6, 6)',
        [$uid, $nome, ucfirst(PREFISSO), 'f', 'superiori', 'completa', 'attivo', $luogo, $esper]
    );
    return Database::first('SELECT * FROM personaggi WHERE id = ?', [(int) Database::lastInsertId()]);
}
function giocatrice(string $nome): array
{
    $pg = attore($nome, 'gradini', 1);
    Database::run("UPDATE personaggi SET sesso = 'f' WHERE id = ?", [(int) $pg['id']]);
    return Database::first('SELECT * FROM personaggi WHERE id = ?', [(int) $pg['id']]);
}
function ricarica(array $pg): array
{
    return Database::first('SELECT * FROM personaggi WHERE id = ?', [(int) $pg['id']]);
}
$cappelloPrima = Database::first('SELECT * FROM oggetti_unici WHERE okey = ?', ['cappello']);
$rimettiCappello = static function () use ($cappelloPrima): void {
    Database::run('UPDATE oggetti_unici SET detentore_id = ?, luogo = ?, gts = ? WHERE okey = ?',
        [$cappelloPrima['detentore_id'], $cappelloPrima['luogo'], $cappelloPrima['gts'], 'cappello']);
};

prova('il cappello non parte col camioncino', function () use ($rimettiCappello) {
    // Prima partiva: trasloca() non toccava gli oggetti unici, e l'unico
    // cappello del server restava in mano a chi non era piu' nel quartiere.
    $a = giocatore('Cappello', 'parco');
    try {
        Database::run('UPDATE oggetti_unici SET detentore_id = ?, luogo = NULL WHERE okey = ?', [(int) $a['id'], 'cappello']);
        vero(Segreto::trasloca(ricarica($a))['ok']);
        $c = Database::first('SELECT detentore_id, luogo FROM oggetti_unici WHERE okey = ?', ['cappello']);
        uguale(null, $c['detentore_id'], 'nessuno deve tenerlo');
        uguale('parco', (string) $c['luogo'], 'resta dove lo si teneva in mano');
    } finally { $rimettiCappello(); }
});

prova('il cappello di un account cancellato torna nel mondo', function () use ($rimettiCappello) {
    // La chiave esterna mette detentore_id a NULL, ma luogo e' gia' NULL
    // finche' lo si tiene in mano: prima il cappello non era piu' da nessuna parte.
    $ultimo = giocatore('Ultima');
    try {
        Database::run("INSERT INTO oggetti_passaggi (okey, da_id, a_id, luogo, gts) VALUES ('cappello', NULL, ?, 'tempio', 1)", [(int) $ultimo['id']]);
        Database::run('UPDATE oggetti_unici SET detentore_id = NULL, luogo = NULL WHERE okey = ?', ['cappello']);
        vero(\App\Game\Legami::ritrovaOggetti() >= 1);
        uguale('tempio', (string) Database::first('SELECT luogo FROM oggetti_unici WHERE okey = ?', ['cappello'])['luogo'],
            'torna dove e\' stato visto passare l\'ultima volta');
    } finally {
        Database::run("DELETE FROM oggetti_passaggi WHERE okey = 'cappello' AND luogo = 'tempio' AND gts = 1");
        $rimettiCappello();
    }
});

prova('chi ha traslocato non conta fra quelli che hanno capito', function () {
    $esper = giocatore('Scoperta');
    $uno = giocatore('Testimone Uno', 'abcb', 0);
    $due = giocatore('Testimone Due', 'abcb', 0);
    foreach ([$uno, $due] as $t) {
        Database::run('INSERT INTO sanno (esper_id, chi_sa_id, come, gts) VALUES (?, ?, ?, 0)', [(int) $esper['id'], (int) $t['id'], 'scoperto']);
    }
    uguale(2, Segreto::quantiSanno((int) $esper['id']));
    Database::run("UPDATE personaggi SET stato = 'trasferito' WHERE id = ?", [(int) $due['id']]);
    uguale(1, Segreto::quantiSanno((int) $esper['id']), 'chi se n\'e\' andato non puo\' piu\' raccontarlo in giro');
    uguale('avviso', Segreto::pericolo(ricarica($esper))['stato'], 'quindi niente trasloco');
});

prova('il battito non fa traslocare gli abitanti canonici', function () {
    $finto = giocatore('Abitante');
    Database::run('UPDATE personaggi SET png = ?, user_id = NULL WHERE id = ?', ['zzfinto', (int) $finto['id']]);
    foreach ([giocatore('Sa Uno', 'abcb', 0), giocatore('Sa Due', 'abcb', 0)] as $t) {
        Database::run('INSERT INTO sanno (esper_id, chi_sa_id, come, gts) VALUES (?, ?, ?, 0)', [(int) $finto['id'], (int) $t['id'], 'scoperto']);
    }
    uguale('trasloco', Segreto::pericolo(ricarica($finto))['stato'], 'per le regole sarebbe da trasloco');
    Segreto::traslochiDovuti();
    uguale('attivo', (string) ricarica($finto)['stato'], 'ma un abitante canonico resta: il quartiere senza Kyosuke non e\' piu\' quello');
});

prova('si torna solo dopo l\'attesa', function () {
    $a = giocatore('Paziente');
    Segreto::trasloca(ricarica($a));
    $r = Segreto::rientra(ricarica($a));
    vero(!$r['ok'], 'appena partita non si torna');
    vero(Segreto::ritornoDa(ricarica($a)) > Orologio::lineare());
});

prova('chi torna tiene il carattere e perde il quartiere', function () {
    $a = giocatore('Ritorno', 'parco');
    $amica = giocatore('Amica', 'parco', 0);
    $segreto = giocatore('Segreto', 'parco');
    Database::run('INSERT INTO personaggio_poteri (personaggio_id, pkey, primario, controllo) VALUES (?, ?, 1, 55)', [(int) $a['id'], 'telecinesi']);
    Database::run('INSERT INTO legami (da_id, a_id, affetto, fraintendimento, conosciuti_gts, ultimo_gts) VALUES (?, ?, 40, 0, 0, 0), (?, ?, 30, 10, 0, 0)',
        [(int) $a['id'], (int) $amica['id'], (int) $amica['id'], (int) $a['id']]);
    Database::run('INSERT INTO sanno (esper_id, chi_sa_id, come, gts) VALUES (?, ?, ?, 0)', [(int) $a['id'], (int) $amica['id'], 'scoperto']);
    Database::run('INSERT INTO sanno (esper_id, chi_sa_id, come, gts) VALUES (?, ?, ?, 0)', [(int) $segreto['id'], (int) $a['id'], 'scoperto']);
    Database::run('INSERT INTO club_membri (ckey, personaggio_id, ruolo, dal_gts) VALUES (?, ?, ?, 0)', ['karate', (int) $a['id'], 'socio']);
    Database::run('INSERT INTO ricordi (personaggio_id, user_id, titolo, testo, gts, luogo) VALUES (?, ?, ?, ?, 0, ?)',
        [(int) $a['id'], (int) $a['user_id'], 'Un pomeriggio', 'Niente di speciale.', 'parco']);

    Segreto::trasloca(ricarica($a));
    // L'attesa si fa passare spostando indietro il giorno del trasloco.
    Database::run('UPDATE personaggi SET ultimo_trasloco_gts = ? WHERE id = ?', [Orologio::lineare() - 30 * 86400, (int) $a['id']]);
    $r = Segreto::rientra(ricarica($a));
    vero($r['ok'], $r['error'] ?? '');

    $dopo = ricarica($a);
    uguale('attivo', (string) $dopo['stato']);
    uguale('stazione', (string) $dopo['luogo'], 'si arriva in stazione, come chi si trasferisce');
    uguale(55, (int) Database::first('SELECT controllo FROM personaggio_poteri WHERE personaggio_id = ? AND pkey = ?', [(int) $a['id'], 'telecinesi'])['controllo'], 'il Controllo resta');
    vero(Database::first('SELECT 1 x FROM personaggio_tratti WHERE personaggio_id = ? AND tkey = ?', [(int) $a['id'], 'trasferito']) !== null, 'e c\'e\' il tratto nuovo');
    uguale(1, (int) Database::first('SELECT COUNT(*) n FROM ricordi WHERE user_id = ?', [(int) $a['user_id']])['n'], 'i ricordi restano');
    uguale(0, (int) Database::first('SELECT COUNT(*) n FROM legami WHERE da_id = ? OR a_id = ?', [(int) $a['id'], (int) $a['id']])['n'], 'i legami no, in tutte e due le direzioni');
    uguale(0, Segreto::quantiSanno((int) $a['id']), 'chi aveva capito ha solo una storia vecchia');
    vero(Segreto::sa((int) $segreto['id'], (int) $a['id']), 'ma quello che sapeva degli altri se lo ricorda');
    uguale(0, (int) Database::first('SELECT COUNT(*) n FROM club_membri WHERE personaggio_id = ?', [(int) $a['id']])['n'], 'e i club si lasciano');
    vero(!Segreto::rientra(ricarica($a))['ok'], 'e non si torna due volte');
});

prova('un giro di semi non tocca lo stato del gioco', function () {
    // Il seme gira a ogni deploy, e riscriveva ogni colonna tranne la chiave:
    // ogni pubblicazione rimetteva il cappello sui gradini e riportava gli
    // abitanti al punto e ai valori di partenza.
    $k = Database::first('SELECT id, luogo, compostezza FROM personaggi WHERE png = ?', ['kyosuke']);
    $c = Database::first('SELECT * FROM oggetti_unici WHERE okey = ?', ['cappello']);
    try {
        Database::run('UPDATE oggetti_unici SET detentore_id = ?, luogo = NULL WHERE okey = ?', [(int) $k['id'], 'cappello']);
        Database::run('UPDATE personaggi SET luogo = ?, compostezza = 1 WHERE id = ?', ['spiaggia', (int) $k['id']]);
        (new \App\Cli\Seeder(dirname(__DIR__)))->all();
        uguale((int) $k['id'], (int) Database::first('SELECT detentore_id FROM oggetti_unici WHERE okey = ?', ['cappello'])['detentore_id'],
            'il cappello resta a chi lo tiene');
        $dopo = Database::first('SELECT luogo, compostezza FROM personaggi WHERE id = ?', [(int) $k['id']]);
        uguale('spiaggia', (string) $dopo['luogo'], 'Kyosuke resta dov\'e\'');
        uguale(1, (int) $dopo['compostezza'], 'e come sta');
    } finally {
        Database::run('UPDATE oggetti_unici SET detentore_id = ?, luogo = ?, gts = ? WHERE okey = ?',
            [$c['detentore_id'], $c['luogo'], $c['gts'], 'cappello']);
        Database::run('UPDATE personaggi SET luogo = ?, compostezza = ? WHERE id = ?', [$k['luogo'], $k['compostezza'], (int) $k['id']]);
    }
});

// =============================================================================
titolo('Gli episodi fanno quello che i copioni dicono');

/** Il vocabolario degli effetti, letto dal motore e non scritto a mano qui. */
function effettiDelMotore(): array
{
    $src = (string) file_get_contents(dirname(__DIR__) . '/src/Game/Episodi.php');
    preg_match('/function applicaEffetti.*?\n    }\n/s', $src, $m);
    preg_match_all("/'([a-z_]+)'(?:\s*,\s*'([a-z_]+)')?\s*=>/", $m[0] ?? '', $mm);
    return array_values(array_filter(array_unique(array_merge($mm[1], $mm[2]))));
}

prova('ogni effetto scritto in un copione il motore lo sa applicare', function () {
    // Prima un effetto sconosciuto finiva in `default => null`: un errore di
    // battitura in un copione non faceva niente e nessuno se ne accorgeva.
    $noti = effettiDelMotore();
    vero(in_array('calore', $noti, true) && in_array('affetto_cast', $noti, true), 'lettura del vocabolario fallita');
    $ignoti = [];
    foreach (Database::all('SELECT ckey, scene FROM copioni') as $c) {
        foreach (json_decode((string) $c['scene'], true) as $sc) {
            foreach ($sc['opzioni'] as $o) {
                foreach (['effetti_ok', 'effetti_ko'] as $f) {
                    foreach (array_keys((array) ($o[$f] ?? [])) as $e) {
                        if (!in_array($e, $noti, true)) { $ignoti[] = "{$c['ckey']}/{$o['k']}: {$e}"; }
                    }
                }
            }
        }
    }
    uguale([], $ignoti);
});

prova('ogni prova e ogni peso stanno su un\'abilita\' vera', function () {
    $abilita = ['rissa','testa','dai_suki','cuore','inglese','sport','guida','kakko','nuoto','musica','cucina','candore'];
    $male = [];
    foreach (Database::all('SELECT ckey, scene FROM copioni') as $c) {
        foreach (json_decode((string) $c['scene'], true) as $sc) {
            foreach ($sc['opzioni'] as $o) {
                $pr = (string) ($o['prova'] ?? '');
                if ($pr !== 'nessuna' && !in_array($pr, $abilita, true)) { $male[] = "{$c['ckey']}/{$o['k']}: prova su «{$pr}»"; }
                foreach (array_keys((array) ($o['peso'] ?? [])) as $w) {
                    if (!in_array($w, $abilita, true)) { $male[] = "{$c['ckey']}/{$o['k']}: peso su «{$w}»"; }
                }
                if (($o['peso'] ?? []) === []) { $male[] = "{$c['ckey']}/{$o['k']}: senza peso, l'agente non la sceglie mai in carattere"; }
            }
        }
    }
    uguale([], $male);
});

prova('un\'opzione piu\' difficile riesce meno spesso, non di piu\'', function () {
    // Fino al 23 settembre la difficolta' si sommava alla probabilita': «stare
    // col bar, funziona sempre» (30) riusciva il 42% delle volte, «scendere di
    // corsa sul ghiaccio» (55) il 67%.
    $tira = new ReflectionMethod(\App\Game\Episodi::class, 'tira');
    $tira->setAccessible(true);
    $pg = ['id' => 1, 'sport' => 8];
    $facile = $difficile = 0;
    for ($ep = 1; $ep <= 600; $ep++) {
        $facile    += $tira->invoke(null, $pg, ['prova' => 'sport', 'difficolta' => 30], $ep, 0) ? 1 : 0;
        $difficile += $tira->invoke(null, $pg, ['prova' => 'sport', 'difficolta' => 55], $ep, 0) ? 1 : 0;
    }
    vero($facile > $difficile + 60, "facile {$facile}/600, difficile {$difficile}/600: la scala e' ancora rovesciata");
});

prova('una scelta che calma un posto ne abbassa davvero il calore', function () {
    $prima = Database::first('SELECT * FROM calore WHERE luogo = ?', ['tempio']);
    try {
        Segreto::muoviCalore('tempio', 40);
        $alto = Segreto::calore('tempio');
        Segreto::muoviCalore('tempio', -10);
        uguale($alto - 10, Segreto::calore('tempio'), 'il calore deve scendere di dieci');
        Segreto::muoviCalore('tempio', -500);
        uguale(0, Segreto::calore('tempio'), 'e non va sotto zero');
    } finally {
        if ($prima === null) {
            Database::run('DELETE FROM calore WHERE luogo = ?', ['tempio']);
        } else {
            Database::run('UPDATE calore SET valore = ?, gts = ? WHERE luogo = ?', [$prima['valore'], $prima['gts'], 'tempio']);
        }
    }
});

// =============================================================================
titolo('Le voci e le anomalie si scrivono in italiano');

/** Tutte le frasi che il motore sa fare, per ogni tipo, fascia, genere e coppia. */
function tutteLeFrasi(): array
{
    $m = new ReflectionMethod(\App\Game\Voci::class, 'racconta');
    $m->setAccessible(true);
    $lui  = ['s1_nome' => 'Kyosuke', 's1_cognome' => 'Kasuga', 's1_sesso' => 'm', 's1_sezione' => 'superiori', 's1_anno' => 1];
    $lei  = ['s1_nome' => 'Kurumi', 's1_cognome' => 'Kasuga', 's1_sesso' => 'f', 's1_sezione' => 'medie', 's1_anno' => 3];
    $lei2 = ['s2_nome' => 'Madoka', 's2_cognome' => 'Ayukawa', 's2_sesso' => 'f', 's2_sezione' => 'superiori', 's2_anno' => 1];
    $poteri = array_column(Database::all('SELECT pkey FROM poteri'), 'pkey');
    $gesti  = array_column(Database::all('SELECT gkey FROM gesti WHERE ambiguo = 0'), 'gkey');
    $out = [];
    $id = 1;
    foreach (\App\Game\Voci::TIPI as $tipo) {
        $det = match ($tipo) { 'potere' => $poteri, 'gesto' => $gesti, default => [''] };
        $aDue = in_array($tipo, ['gesto', 'confessione', 'litigio', 'insieme'], true);
        foreach ([95, 70, 45, 10] as $p) {
            foreach ($det as $d) {
                foreach ([$lui, $lei] as $chi) {
                    foreach (['gradini', 'abcb', 'casa_kasuga'] as $l) {
                        foreach ([-60, 0, 60] as $tono) {
                            $r = $chi + ($aDue ? $lei2 : []) + ['id' => $id++, 'seme' => 777, 'precisione' => $p,
                                'tono' => $tono, 'passaggi' => $p < 50 ? 4 : 1, 'tipo' => $tipo, 'dettaglio' => $d,
                                'luogo' => $l, 'soggetto2_id' => $aDue ? 2 : null];
                            $out[] = [$tipo, $chi['s1_sesso'], $m->invoke(null, $r)];
                        }
                    }
                }
            }
        }
    }
    return $out;
}

prova('nessuna voce parla di una ragazza al maschile', function () {
    $male = [];
    foreach (tutteLeFrasi() as [$tipo, $sesso, $f]) {
        if ($sesso === 'f' && preg_match("/\b(si è (preso|dichiarato|comportato|fatto|teletrasportato|scambiato|ipnotizzato)|se n'è andato|è sparito|è diventato|è più tornato)\b/u", $f)) {
            $male[] = $f;
        }
    }
    uguale([], array_slice($male, 0, 3));
});

prova('chi fa e a chi non diventano un soggetto plurale col verbo singolare', function () {
    // «Kyosuke Kasuga e Madoka Ayukawa si e' dichiarato»: una confessione ha un
    // autore e un destinatario, non due autori.
    $male = array_filter(tutteLeFrasi(), static fn (array $x): bool
        => preg_match('/^[A-Z][a-zè]+( [A-Z][a-z]+)? e [A-Z][a-z]+( [A-Z][a-z]+)? (ha|si è|è) /u', $x[2]) === 1);
    uguale([], array_slice(array_column($male, 2), 0, 3));
});

prova('una partenza non ha un luogo in coda che sembri la destinazione', function () {
    $male = array_filter(tutteLeFrasi(), static fn (array $x): bool
        => $x[0] === 'partenza' && preg_match("/ (all'|al |ai |sui |dai |alla )[A-Z]/u", $x[2]) === 1);
    uguale([], array_slice(array_column($male, 2), 0, 3));
});

prova('un potere si racconta come un\'azione, non incollandone il nome', function () {
    $male = array_filter(tutteLeFrasi(), static fn (array $x): bool
        => preg_match('/ha (usato|fatto) (telecinesi|teletrasporto|telepatia|supervelocità|ipnosi|bloccare|comunicare|invisibilità)/u', $x[2]) === 1);
    uguale([], array_slice(array_column($male, 2), 0, 3));
    // e ogni potere che esiste ha la sua azione
    $senza = array_diff(array_column(Database::all('SELECT pkey FROM poteri'), 'pkey'), array_keys(\App\Game\Voci::AZIONI_POTERE));
    uguale([], array_values($senza), 'poteri senza un modo di raccontarli');
});

prova('nessuna frase resta spezzata o con un segnaposto', function () {
    $male = array_filter(tutteLeFrasi(), static fn (array $x): bool
        => preg_match('/\{|\}|\s{2,}| ,|con \.|con,| a,|,\.$/u', $x[2]) === 1);
    uguale([], array_slice(array_column($male, 2), 0, 3));
});

prova('il taccuino annota una ragazza al femminile', function () {
    $m = new ReflectionMethod(Segreto::class, 'comeAppare');
    $m->setAccessible(true);
    foreach (array_column(Database::all('SELECT pkey FROM poteri'), 'pkey') as $k) {
        $lei = (string) $m->invoke(null, $k, true);
        vero(!preg_match('/\b(voltato|rimasto|comportato|sembrato|messo|uscito|diverso|lui)\b/u', $lei), "{$k}: «{$lei}»");
        vero(!str_contains($lei, '{'), "{$k}: segnaposto rimasto");
    }
});

prova('una giocatrice legge scheda, tratti e poteri al femminile', function () {
    // Un personaggio femminile con TUTTI i tratti e TUTTI i poteri: se da
    // qualche parte un «{o}» arrivasse a schermo senza accordo, qui si vede.
    $lei = giocatrice('Tuttotratti');
    foreach (Database::all('SELECT tkey FROM tratti') as $t) {
        Database::run('INSERT IGNORE INTO personaggio_tratti (personaggio_id, tkey) VALUES (?, ?)', [(int) $lei['id'], $t['tkey']]);
    }
    foreach (Database::all('SELECT pkey FROM poteri') as $pp) {
        Database::run('INSERT IGNORE INTO personaggio_poteri (personaggio_id, pkey, primario, controllo) VALUES (?, ?, 0, 10)', [(int) $lei['id'], $pp['pkey']]);
    }
    $pg = Database::first('SELECT * FROM personaggi WHERE id = ?', [(int) $lei['id']]);
    $dati = ['pg' => $pg, 'mondo' => \App\Game\Mondo::adesso(),
             'tratti' => \App\Game\Scheda::tratti((int) $pg['id']), 'poteri' => \App\Game\Scheda::poteri((int) $pg['id']), 'punti' => 0];
    \App\Core\View::setPath(dirname(__DIR__) . '/views');
    foreach (['gioco/scheda', 'gioco/abilita'] as $v) {
        $html = \App\Core\View::renderPartial($v, $dati);
        vero(!str_contains($html, '{o}'), "{$v}: un «{o}» e' arrivato a schermo");
        vero(str_contains($html, 'Sei arrivata da poco'), "{$v}: il tratto «Trasferito di recente» non si e' accordato");
        vero(!str_contains($html, 'Sei arrivato da poco'), "{$v}: e' rimasto al maschile");
    }
    $diario = \App\Game\Diario::componi($pg);
    vero(!str_contains($diario, '{o}'), 'il diario ha un «{o}» non accordato');
    // e gli esiti degli episodi: ogni testo dei copioni, accordato, e' pulito
    foreach (Database::all('SELECT scene FROM copioni') as $c) {
        foreach (json_decode((string) $c['scene'], true) as $sc) {
            foreach ($sc['opzioni'] as $o) {
                foreach (['ok', 'ko'] as $k) {
                    vero(!str_contains(accorda((string) ($o[$k] ?? ''), $pg), '{'), 'esito di episodio non accordato');
                }
            }
            vero(!str_contains((string) $sc['testo'], '{o}'), 'il testo di una scena non passa da accorda(): non ci vanno segnaposto');
            foreach ($sc['opzioni'] as $o) { vero(!str_contains((string) $o['testo'], '{o}'), 'idem per il testo di un\'opzione'); }
        }
    }
});

// =============================================================================
titolo('Un orologio solo');

prova('il database e PHP leggono la stessa ora', function () {
    // Le scadenze dei gettoni le scrive NOW() del database e le rilegge time()
    // di PHP. Col database in UTC e PHP a Roma, un collegamento da due ore per
    // rifare la password nasceva gia' scaduto.
    $db = strtotime((string) Database::first('SELECT NOW() n')['n']);
    vero(abs($db - time()) < 5, sprintf('scarto di %d minuti fra database e PHP', ($db - time()) / 60));
});

// --- pulizia ------------------------------------------------------------------
pulisci();
riepilogo();
