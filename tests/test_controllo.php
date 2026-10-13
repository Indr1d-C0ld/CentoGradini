<?php

declare(strict_types=1);

/**
 * Le prove nate dal controllo globale del 9 ottobre 2026.
 *
 * Il controllo aveva trovato un mondo che regge ma che, a una settimana
 * dall'apertura, non lasciava più fare niente: le forze non tornavano, le
 * storie si ripetevano, il triangolo si poteva sciogliere, il Segreto non
 * aveva denti. Ogni prova qui sotto copre uno di quei difetti, e quasi tutte
 * sono state viste fallire sul codice di prima.
 */

require __DIR__ . '/_prova.php';

use App\Core\Config;
use App\Core\Database;
use App\Game\Legami;
use App\Game\Personaggio;
use App\Game\Riposo;
use App\Game\Segreto;
use App\Sim\Orologio;

Config::load(dirname(__DIR__));

const PREFISSO = 'zzctl';

function pulisci(): void
{
    $ids = array_column(Database::all('SELECT id FROM personaggi WHERE cognome = ?', [ucfirst(PREFISSO)]), 'id');
    foreach ($ids as $id) {
        Database::run('DELETE FROM anomalie WHERE osservatore_id = ? OR soggetto_id = ?', [$id, $id]);
        Database::run('DELETE FROM sanno WHERE esper_id = ? OR chi_sa_id = ?', [$id, $id]);
        Database::run('DELETE FROM incidente_testimoni WHERE personaggio_id = ?', [$id]);
        Database::run('DELETE FROM incidente_testimoni WHERE incidente_id IN (SELECT id FROM incidenti WHERE attore_id = ?)', [$id]);
        Database::run('DELETE FROM incidenti WHERE attore_id = ?', [$id]);
        Database::run('DELETE FROM voci_versioni WHERE voce_id IN (SELECT id FROM voci WHERE soggetto_id = ? OR soggetto2_id = ?)', [$id, $id]);
        Database::run('DELETE FROM voci WHERE soggetto_id = ? OR soggetto2_id = ?', [$id, $id]);
        Database::run('DELETE FROM tracce WHERE personaggio_id = ?', [$id]);
        Database::run('DELETE FROM legami WHERE da_id = ? OR a_id = ?', [$id, $id]);
        Database::run('DELETE FROM oggetti_passaggi WHERE da_id = ? OR a_id = ?', [$id, $id]);
        Database::run('DELETE FROM episodio_scelte WHERE personaggio_id = ?', [$id]);
        Database::run('DELETE FROM episodio_cast WHERE personaggio_id = ?', [$id]);
    }
    Database::run('DELETE FROM personaggi WHERE cognome = ?', [ucfirst(PREFISSO)]);
}
pulisci();

function persona(string $nome, string $luogo, int $esper = 0, string $sesso = 'm'): array
{
    Database::run(
        'INSERT INTO personaggi (user_id, nome, cognome, sesso, sezione, anno, scheda, stato, luogo,
            anno_nascita, nato_mese, nato_giorno, esper, rissa, testa, dai_suki, cuore,
            pf, pf_max, pp, pp_max, compostezza, compostezza_max)
         VALUES (NULL, ?, ?, ?, ?, 2, ?, ?, ?, 1970, 5, 5, ?, 8, 10, 9, 15, 11, 11, 20, 20, 6, 6)',
        [$nome, ucfirst(PREFISSO), $sesso, 'superiori', 'completa', 'attivo', $luogo, $esper]
    );
    return Database::first('SELECT * FROM personaggi WHERE id = ?', [(int) Database::lastInsertId()]);
}
function di(array $pg): array
{
    return Database::first('SELECT * FROM personaggi WHERE id = ?', [(int) $pg['id']]);
}
function abitante(string $png): array
{
    return Database::first('SELECT * FROM personaggi WHERE png = ?', [$png]);
}

$tz  = new DateTimeZone('Asia/Tokyo');
$ore = static fn (string $s): int => (new DateTimeImmutable($s, $tz))->getTimestamp();

// =============================================================================
titolo('Il riposo: le forze tornano');

prova('un punto potere all\'ora, uno ferita ogni due, uno di compostezza ogni tre', function () use ($ore) {
    $q = Riposo::quanto($ore('1987-05-14 10:00:00'), $ore('1987-05-14 16:00:00'));
    uguale(false, $q['notte']);
    uguale(6, $q['pp']);
    uguale(3, $q['pf']);
    uguale(2, $q['compostezza']);
});

prova('nell\'ora in cui si usa un potere i punti potere non tornano', function () use ($ore) {
    uguale(0, Riposo::quanto($ore('1987-05-14 10:30:00'), $ore('1987-05-14 11:10:00'), true)['pp']);
    uguale(1, Riposo::quanto($ore('1987-05-14 10:30:00'), $ore('1987-05-14 11:10:00'), false)['pp']);
});

prova('una notte di sonno rimette tutto a posto, e conta le albe e non le mezzanotti', function () use ($ore) {
    uguale(1, Riposo::albe($ore('1987-05-14 23:00:00'), $ore('1987-05-15 07:00:00')));
    uguale(0, Riposo::albe($ore('1987-05-14 23:00:00'), $ore('1987-05-15 05:00:00')), 'a mezzanotte non si è ancora riposati');
    uguale(2, Riposo::albe($ore('1987-05-14 05:00:00'), $ore('1987-05-15 06:00:00')));
});

prova('chi era a compostezza zero la mattina dopo è di nuovo in piedi', function () use ($ore) {
    $pg = persona('Stanco', 'parco', 1);
    Database::run('UPDATE personaggi SET compostezza = 0, pp = 0, pf = 2, ristoro_gts = ? WHERE id = ?',
        [$ore('1987-05-14 22:00:00'), (int) $pg['id']]);
    // Tre ore dopo, a mezzanotte passata: un punto di compostezza, tre di potere.
    Riposo::ristora(di($pg), $ore('1987-05-15 01:00:00'));
    $dopo = di($pg);
    uguale(1, (int) $dopo['compostezza']);
    uguale(3, (int) $dopo['pp']);
    // E all'alba, tutto.
    Riposo::ristora(di($pg), $ore('1987-05-15 06:30:00'));
    $mattina = di($pg);
    uguale(6, (int) $mattina['compostezza']);
    uguale(20, (int) $mattina['pp']);
    uguale(11, (int) $mattina['pf']);
});

prova('il primo conto non regala il tempo che non si è visto passare', function () use ($ore) {
    $pg = persona('Nuovo', 'parco');
    Database::run('UPDATE personaggi SET compostezza = 0, ristoro_gts = NULL WHERE id = ?', [(int) $pg['id']]);
    vero(!Riposo::ristora(di($pg), $ore('1987-05-15 06:30:00')));
    uguale(0, (int) di($pg)['compostezza']);
    uguale($ore('1987-05-15 06:30:00'), (int) di($pg)['ristoro_gts']);
});

// =============================================================================
titolo('Le storie non si ripetono');

prova('lo stesso copione non torna allo stesso giocatore prima di dieci giorni', function () {
    $pg = persona('Ripetente', 'parco');
    $gts = Orologio::lineare();
    Database::run(
        "INSERT INTO episodi (ckey, titolo, luogo, scena, stato, aperto_gts, scena_gts, chiuso_gts, scade_reale)
         VALUES ('chiacchiere', 'prova', 'parco', 0, 'concluso', ?, ?, ?, 0)",
        [$gts - 3 * 86400, $gts - 3 * 86400, $gts - 3 * 86400]
    );
    $ep = (int) Database::lastInsertId();
    Database::run('INSERT INTO episodio_cast (episodio_id, personaggio_id, entrato_gts) VALUES (?, ?, ?)',
        [$ep, (int) $pg['id'], $gts - 3 * 86400]);
    $cast = new ReflectionMethod(\App\Game\Episodi::class, 'castDisponibile');
    $ids = static fn (array $r): array => array_map(static fn (array $p): int => (int) $p['id'], $r);
    try {
        vero(!in_array((int) $pg['id'], $ids($cast->invoke(null, 'parco', 10, $gts, 'chiacchiere')), true),
            'tre giorni fa ha vissuto «chiacchiere»: non deve rientrarci');
        vero(in_array((int) $pg['id'], $ids($cast->invoke(null, 'parco', 10, $gts, 'temporale')), true),
            'a un\'altra storia invece sì');
    } finally {
        Database::run('DELETE FROM episodio_cast WHERE episodio_id = ?', [$ep]);
        Database::run('DELETE FROM episodi WHERE id = ?', [$ep]);
    }
});

// =============================================================================
titolo('Il triangolo non si scioglie');

prova('a Madoka ci si può dichiarare, ma la risposta è quella dell\'opera', function () {
    $madoka = abitante('madoka');
    $prima  = (string) $madoka['luogo'];
    $pg = persona('Innamorato', $prima);
    Legami::muovi((int) $pg['id'], (int) $madoka['id'], 90, 0);
    Legami::muovi((int) $madoka['id'], (int) $pg['id'], 95, 0);
    $esiti = [];
    try {
        for ($i = 0; $i < 6; $i++) {
            Database::run('UPDATE personaggi SET compostezza = 6 WHERE id = ?', [(int) $pg['id']]);
            Orologio::fingiAdesso(time() + $i * 900);
            $r = Legami::confessa(di($pg), (int) $madoka['id']);
            vero($r['ok'], $r['error'] ?? '');
            $esiti[] = $r['esito'];
        }
    } finally {
        Orologio::fingiAdesso(null);
        Database::run('UPDATE personaggi SET luogo = ? WHERE id = ?', [$prima, (int) $madoka['id']]);
    }
    vero(!in_array('accolta', $esiti, true), 'Madoka non accoglie nessuna dichiarazione: ' . implode(', ', $esiti));
    vero(in_array('altrove', $esiti, true), 'ma la si può fare, e lei risponde: ' . implode(', ', $esiti));
});

// =============================================================================
titolo('Il Segreto ha i denti');

prova('un abitante con tre annotazioni ci ragiona sopra da solo', function () {
    $master = abitante('master');
    $esper  = persona('Imprudente', (string) $master['luogo'], 1);
    $gts    = Orologio::lineare();
    for ($i = 0; $i < 3; $i++) {
        Database::run('INSERT INTO anomalie (osservatore_id, soggetto_id, incidente_id, gts, luogo, testo)
                       VALUES (?, ?, NULL, ?, ?, ?)',
            [(int) $master['id'], (int) $esper['id'], $gts - $i * 3600, 'abcb', 'prova']);
    }
    for ($i = 0; $i < 3; $i++) {
        Segreto::abitantiCollegano($gts);
    }
    $usate = (int) Database::first('SELECT SUM(collegata) n FROM anomalie WHERE osservatore_id = ? AND soggetto_id = ?',
        [(int) $master['id'], (int) $esper['id']])['n'];
    // Un tentativo per annotazione, finché non capisce: o ha capito, o le ha
    // usate tutte e tre. Con TESTA 12 capisce il 54% delle volte.
    $sa = Segreto::sa((int) $esper['id'], (int) $master['id']);
    vero($usate >= 1, 'non ci ha nemmeno provato');
    vero($sa || $usate === 3, "si e' fermato dopo {$usate} tentativi senza aver capito");
    if ($sa) {
        uguale(1, Segreto::quantiSanno((int) $esper['id']), 'chi ha capito conta per il trasloco');
    }
    Database::run('DELETE FROM anomalie WHERE osservatore_id = ?', [(int) $master['id']]);
    Database::run('DELETE FROM sanno WHERE chi_sa_id = ?', [(int) $master['id']]);
});

prova('i Kasuga non ci ragionano sopra: il segreto di un esper è anche il loro', function () {
    $kurumi = abitante('kurumi');
    $esper  = persona('Cugino', (string) $kurumi['luogo'], 1);
    $gts    = Orologio::lineare();
    for ($i = 0; $i < 3; $i++) {
        Database::run('INSERT INTO anomalie (osservatore_id, soggetto_id, incidente_id, gts, luogo, testo)
                       VALUES (?, ?, NULL, ?, ?, ?)',
            [(int) $kurumi['id'], (int) $esper['id'], $gts - $i * 3600, 'parco', 'prova']);
    }
    Segreto::abitantiCollegano($gts);
    $usate = (int) Database::first('SELECT SUM(collegata) n FROM anomalie WHERE osservatore_id = ?', [(int) $kurumi['id']])['n'];
    Database::run('DELETE FROM anomalie WHERE osservatore_id = ?', [(int) $kurumi['id']]);
    uguale(0, $usate);
});

prova('i sogni premonitori non hanno più un pulsante', function () {
    $pg = persona('Sognatore', 'parco', 1);
    Database::run("INSERT INTO personaggio_poteri (personaggio_id, pkey, primario, controllo) VALUES (?, 'sogni', 0, 10)",
        [(int) $pg['id']]);
    $r = Segreto::usa(di($pg), 'sogni');
    Database::run('DELETE FROM personaggio_poteri WHERE personaggio_id = ?', [(int) $pg['id']]);
    vero(!$r['ok'] && str_contains((string) $r['error'], 'non si comanda'));
});

// =============================================================================
titolo('Le case private');

prova('a casa di Madoka si entra solo se c\'è lei e ti fa entrare', function () {
    $madoka = abitante('madoka');
    $prima  = (string) $madoka['luogo'];
    $pg = persona('Visitatore', 'casa_hiyama');
    try {
        Database::run('UPDATE personaggi SET luogo = ? WHERE id = ?', ['liceo', (int) $madoka['id']]);
        $r = Personaggio::parti(di($pg), 'casa_ayukawa');
        vero(!$r['ok'] && str_contains((string) $r['error'], 'privata'), 'lei non c\'è: non si entra');

        Database::run('UPDATE personaggi SET luogo = ? WHERE id = ?', ['casa_ayukawa', (int) $madoka['id']]);
        vero(!Personaggio::parti(di($pg), 'casa_ayukawa')['ok'], 'c\'è, ma non ti conosce abbastanza');

        Legami::muovi((int) $madoka['id'], (int) $pg['id'], 40, 0);
        vero(Personaggio::parti(di($pg), 'casa_ayukawa')['ok'], 'c\'è, e ti fa entrare');
    } finally {
        Database::run('UPDATE personaggi SET luogo = ? WHERE id = ?', [$prima, (int) $madoka['id']]);
    }
});

prova('la carta dice perché una porta è chiusa, prima di partire', function () {
    $pg = persona('Curioso', 'casa_hiyama');
    $u = array_values(array_filter(Personaggio::usciteDi(di($pg), 'casa_hiyama'),
        static fn (array $u): bool => $u['a'] === 'casa_ayukawa'));
    vero($u !== [] && $u[0]['aperto'] === false && str_contains((string) $u[0]['motivo'], 'privata'));
});

// =============================================================================
titolo('Il cappello, gli adulti, il calendario');

prova('un abitante non si tiene il cappello per sempre: lo riporta sui gradini', function () {
    $prima  = Database::first('SELECT * FROM oggetti_unici WHERE okey = ?', ['cappello']);
    $master = abitante('master');
    try {
        Database::run('UPDATE oggetti_unici SET detentore_id = ?, luogo = NULL, gts = ? WHERE okey = ?',
            [(int) $master['id'], Orologio::lineare() - 5 * 86400, 'cappello']);
        Legami::ritrovaOggetti();
        $dopo = Database::first('SELECT * FROM oggetti_unici WHERE okey = ?', ['cappello']);
        uguale(null, $dopo['detentore_id']);
        uguale('gradini', $dopo['luogo']);
    } finally {
        Database::run('UPDATE oggetti_unici SET detentore_id = ?, luogo = ?, gts = ? WHERE okey = ?',
            [$prima['detentore_id'], $prima['luogo'], $prima['gts'], 'cappello']);
        Database::run("DELETE FROM tracce WHERE personaggio_id = ? AND tipo = 'oggetto'", [(int) $master['id']]);
    }
});

prova('una voce sul Master, sbiadita, parla di un signore e non di uno del terzo anno', function () {
    $m = new ReflectionMethod(\App\Game\Voci::class, 'comeSiChiama');
    $rng = \App\Sim\Rng::for(1, 'prova');
    $r = ['s1_nome' => 'Master', 's1_cognome' => '', 's1_sesso' => 'm', 's1_sezione' => 'adulti', 's1_anno' => 0];
    uguale('un signore del quartiere', $m->invoke(null, $r, '1', 40, $rng));
    $b = ['s1_nome' => 'Kazuya', 's1_cognome' => 'Kasuga', 's1_sesso' => 'm', 's1_sezione' => 'elementari', 's1_anno' => 3];
    uguale('un bambino delle elementari', $m->invoke(null, $b, '1', 40, $rng));
});

prova('la testata e le meccaniche hanno lo stesso calendario', function () {
    $testata = (new ReflectionClassConstant(\App\Sim\Calendario::class, 'EVENTI'))->getValue();
    $stesse = [
        'ingresso' => 'Cerimonia d\'ingresso', 'reclutamento' => 'Reclutamento dei club',
        'festival_estate' => 'Festival d\'estate', 'sportivo' => 'Festival sportivo',
        'culturale' => 'Festival culturale', 'natale' => 'Vigilia di Natale',
        'capodanno' => 'Capodanno', 'san_valentino' => 'San Valentino',
        'white_day' => 'White Day', 'diploma' => 'Cerimonia di diploma',
        'tanabata' => 'Tanabata', 'obon' => 'Obon', 'hinamatsuri' => 'Festa delle bambole',
        'pesce_aprile' => 'Pesce d\'aprile', 'esami_maggio' => 'Esami di metà trimestre',
        'esami_luglio' => 'Esami di luglio', 'esami_dicembre' => 'Esami di fine trimestre',
    ];
    $date = [];
    foreach ($testata as $md => $ev) {
        $date[$ev['titolo']] = $md;
    }
    foreach (\App\Game\Eventi::calendario() as $e) {
        if (!isset($stesse[$e['ekey']])) {
            continue;
        }
        uguale(sprintf('%02d-%02d', $e['mese'], $e['giorno']), $date[$stesse[$e['ekey']]] ?? '?', $e['ekey']);
    }
});

// =============================================================================
titolo('Il canone');

prova('le gemelle sono gemelle, e compagne di classe di Hikaru', function () {
    $ma = abitante('manami');
    $ku = abitante('kurumi');
    $hi = abitante('hikaru');
    foreach (['nato_mese', 'nato_giorno', 'anno_nascita', 'sezione', 'anno'] as $c) {
        uguale((string) $ma[$c], (string) $ku[$c], "gemelle diverse in {$c}");
    }
    uguale((string) $hi['sezione'] . $hi['anno'], (string) $ma['sezione'] . $ma['anno'], 'stessa classe di Hikaru');
    uguale('sorella gemella', \App\Game\Parentele::come($ma, $ku));
});

prova('Akane e Kazuya dormono a casa loro, non dai Kasuga', function () {
    foreach (['akane', 'kazuya'] as $png) {
        uguale('casa_zii', \App\Game\Abitanti::giro(abitante($png))[0]);
    }
    uguale('3', (string) abitante('akane')['anno'], 'Akane ha l\'età di Kyosuke');
});

prova('chi non sa nuotare non ha nuoto 6, e Kurumi non sa cucinare', function () {
    vero((int) abitante('kyosuke')['nuoto'] <= 2);
    vero((int) abitante('komatsu')['nuoto'] <= 2);
    vero((int) abitante('kurumi')['cucina'] <= 2);
});

// =============================================================================
// Seconda parte: il quartiere risponde.

function conPotere(array $pg, string $pkey, int $primario = 1, int $controllo = 10): void
{
    Database::run('INSERT INTO personaggio_poteri (personaggio_id, pkey, primario, controllo) VALUES (?, ?, ?, ?)
                   ON DUPLICATE KEY UPDATE primario = VALUES(primario), controllo = VALUES(controllo)',
        [(int) $pg['id'], $pkey, $primario, $controllo]);
}
function statoMondo(): void
{
    // Un posto vuoto e fisso, dove nessun abitante passa: le prove non devono
    // dipendere da chi c'è. Il tempio di notte non serve: basta togliere tutti.
}
function conManopola(string $k, string $v, callable $f): void
{
    $prima = \App\Core\GameConfig::all()[$k]['value'] ?? null;
    \App\Core\GameConfig::set($k, $v);
    try { $f(); } finally {
        if ($prima !== null) { \App\Core\GameConfig::set($k, (string) $prima); }
    }
}

titolo('I poteri fanno qualcosa');

prova('il teletrasporto porta dove si è già stati, e da nessun\'altra parte', function () {
    $pg = persona('Saltatore', 'argine', 1);
    conPotere($pg, 'teletrasporto', 1);
    Database::run('INSERT INTO presenze (personaggio_id, luogo, dal_gts, al_gts) VALUES (?, ?, ?, ?)',
        [(int) $pg['id'], 'parco', Orologio::lineare() - 9000, Orologio::lineare() - 8000]);
    $r = Segreto::usa(di($pg), 'teletrasporto', 'tempio');
    vero(!$r['ok'], 'al tempio non c\'è mai stato');
    uguale(20, (int) di($pg)['pp'], 'e un teletrasporto che non parte non costa');
    $r = Segreto::usa(di($pg), 'teletrasporto', 'parco');
    vero($r['ok'], $r['error'] ?? '');
    uguale('parco', (string) di($pg)['luogo']);
    vero(str_contains((string) $r['racconto'], 'al parco'), $r['racconto']);
});

prova('da secondario, portare sé stessi costa cinque punti', function () {
    $c = \App\Game\Scheda::costo(['pkey' => 'teletrasporto', 'primario' => 0, 'costo_primario' => 2,
        'costo_secondario' => 2, 'controllo' => 90]);
    uguale(5, $c);
});

prova('la telepatia dice cosa pensano di te; da secondario, solo un\'idea', function () {
    $pg = persona('Telepate', 'argine', 1);
    $altro = persona('Pensieroso', 'argine', 0, 'f');
    Legami::muovi((int) $altro['id'], (int) $pg['id'], 75, 50);
    conPotere($pg, 'telepatia', 1);
    $r = Segreto::usa(di($pg), 'telepatia', (string) $altro['id']);
    vero($r['ok'], $r['error'] ?? '');
    vero(str_contains($r['racconto'], 'pensa a te') && str_contains($r['racconto'], 'convinta'), $r['racconto']);
    conPotere($pg, 'telepatia', 0);
    $r = Segreto::usa(di($pg), 'telepatia', (string) $altro['id']);
    vero(str_contains($r['racconto'], 'sfocata') && !str_contains($r['racconto'], 'convinta'), $r['racconto']);
});

prova('l\'ipnosi fa dimenticare una cosa vista, fa arrabbiare, e vale una persona al giorno', function () {
    $pg = persona('Ipnotista', 'argine', 1);
    $t  = persona('Testimone', 'argine');
    conPotere($pg, 'ipnosi', 1);
    Database::run('INSERT INTO anomalie (osservatore_id, soggetto_id, incidente_id, gts, luogo, testo) VALUES (?, ?, NULL, ?, ?, ?)',
        [(int) $t['id'], (int) $pg['id'], Orologio::lineare() - 100, 'argine', 'prova']);
    $r = Segreto::usa(di($pg), 'ipnosi', (string) $t['id']);
    vero($r['ok'], $r['error'] ?? '');
    uguale(0, (int) Database::first('SELECT COUNT(*) n FROM anomalie WHERE osservatore_id = ? AND soggetto_id = ? AND testo = ?',
        [(int) $t['id'], (int) $pg['id'], 'prova'])['n'], 'l\'annotazione è sparita');
    vero(Legami::fra((int) $t['id'], (int) $pg['id'])['fraintendimento'] > 0, 'e si ricorda abbastanza da essere furioso');
    $ancora = Segreto::usa(di($pg), 'ipnosi', (string) $t['id']);
    vero(!$ancora['ok'] && str_contains((string) $ancora['error'], 'al giorno'));
});

prova('chi è invisibile non compare fra i presenti, e si fa notare un quinto', function () {
    $pg = persona('Invisibile', 'argine', 1);
    $altro = persona('Guarda', 'argine');
    \App\Game\Stati::metti((int) $pg['id'], 'invisibile', 20);
    $ids = array_map(static fn (array $p): int => (int) $p['id'], Personaggio::presenti('argine', (int) $altro['id']));
    vero(!in_array((int) $pg['id'], $ids, true));
    vero(Segreto::probabilitaNota(9, 15, 0, null, 0, 0.2) < Segreto::probabilitaNota(9, 15, 0, null, 0, 1.0));
});

prova('il calore di un luogo alza la probabilità di essere notati', function () {
    vero(Segreto::probabilitaNota(5, 10, 0, null, 100) > Segreto::probabilitaNota(5, 10, 0, null, 0));
});

// =============================================================================
titolo('Gli stati del 1990');

prova('un esper col raffreddore non comanda i poteri', function () {
    $pg = persona('Febbricitante', 'argine', 1);
    conPotere($pg, 'sesto_senso', 1);
    conPotere($pg, 'supersensi', 0);
    \App\Game\Stati::metti((int) $pg['id'], 'raffreddore', 600);
    conManopola('stati.potere_sballa_pct', '100', function () use ($pg) {
        $r = Segreto::usa(di($pg), 'sesto_senso');
        vero($r['ok'], $r['error'] ?? '');
        uguale('supersensi', $r['potere'], 'voleva il sesto senso, è partito altro');
        vero(str_contains($r['racconto'], 'febbre'));
    });
});

prova('il raffreddore si vede addosso, e le bende pure', function () {
    $pg = persona('Malconcio', 'argine', 0, 'f');
    \App\Game\Stati::metti((int) $pg['id'], 'raffreddore', 600);
    \App\Game\Stati::metti((int) $pg['id'], 'bende', 600);
    uguale('è piena di cerotti, ha il raffreddore', \App\Game\Stati::addosso(di($pg)));
});

prova('dopo una botta, con pochi punti ferita, spesso ci si ritrova bendati', function () {
    $bendati = 0;
    for ($i = 0; $i < 10; $i++) {
        $pg = persona('Pesto' . $i, 'argine');
        Database::run('UPDATE personaggi SET pf = 1 WHERE id = ?', [(int) $pg['id']]);
        if (\App\Game\Stati::forseBende((int) $pg['id'], Orologio::lineare() + $i)) {
            $bendati++;
        }
    }
    // 40% + 2×(11−1) = 60%: su dieci, quasi sempre fra tre e nove.
    vero($bendati >= 2 && $bendati <= 10, "bendati {$bendati} su dieci");
});

prova('al coperto non ci si bagna', function () {
    $pg = persona('Asciutto', 'abcb');
    vero(!\App\Game\Stati::forseRaffreddore(di($pg), 'abcb', Orologio::lineare()));
});

// =============================================================================
titolo('Chi ha capito sceglie cosa farne');

function scoperto(array $esper, array $chi): void
{
    Database::run("INSERT INTO sanno (esper_id, chi_sa_id, come, gts) VALUES (?, ?, 'scoperto', ?)",
        [(int) $esper['id'], (int) $chi['id'], Orologio::lineare()]);
}

prova('affrontato, l\'esper può confidarsi: chi l\'aveva scoperto diventa un complice', function () {
    $e = persona('Scoperto', 'argine', 1);
    $c = persona('Sveglio', 'argine');
    scoperto($e, $c);
    uguale(1, Segreto::quantiSanno((int) $e['id']));
    $r = Segreto::scegli(di($c), (int) $e['id'], 'affronta');
    vero($r['ok'], $r['error'] ?? '');
    uguale(1, count(Segreto::affrontatoDa((int) $e['id'])));
    $r = Segreto::confida(di($e), (int) $c['id']);
    vero($r['ok'], $r['error'] ?? '');
    uguale(0, Segreto::quantiSanno((int) $e['id']), 'confidarsi disinnesca chi aveva capito da solo');
    uguale([], Segreto::affrontatoDa((int) $e['id']));
});

prova('affrontato, l\'esper può negare: o torna il dubbio, o adesso è anche offeso', function () {
    $esiti = [];
    for ($i = 0; $i < 6; $i++) {
        $e = persona('Negatore' . $i, 'argine', 1);
        $c = persona('Accusatore' . $i, 'argine');
        Database::run('UPDATE personaggi SET kakko = 8 WHERE id = ?', [(int) $e['id']]);
        scoperto($e, $c);
        Segreto::scegli(di($c), (int) $e['id'], 'affronta');
        $r = Segreto::nega(di($e), (int) $c['id']);
        vero($r['ok'], $r['error'] ?? '');
        $resta = Segreto::sa((int) $e['id'], (int) $c['id']);
        uguale(!$r['riuscito'], $resta, 'riuscito vuol dire che non ne è più sicuro');
        $esiti[] = $r['riuscito'] ? 'ok' : 'ko';
    }
    vero(in_array('ok', $esiti, true) && in_array('ko', $esiti, true), implode(',', $esiti));
});

prova('dirlo in giro fa nascere una voce chiara e scalda il posto, una volta al giorno', function () {
    $e = persona('Chiacchierato', 'argine', 1);
    $c = persona('Pettegolo', 'argine');
    $ascolta = persona('Orecchio', 'argine');
    scoperto($e, $c);
    $prima = Segreto::calore('argine');
    vero(Segreto::scegli(di($c), (int) $e['id'], 'dice')['ok']);
    vero(Segreto::calore('argine') > $prima);
    $voce = Database::first("SELECT id FROM voci WHERE tipo = 'potere' AND dettaglio = 'segreto' AND soggetto_id = ?", [(int) $e['id']]);
    vero($voce !== null);
    vero(Database::first('SELECT 1 x FROM voci_versioni WHERE voce_id = ? AND personaggio_id = ?',
        [(int) $voce['id'], (int) $ascolta['id']]) !== null, 'chi era lì l\'ha sentita');
    vero(!Segreto::scegli(di($c), (int) $e['id'], 'dice')['ok'], 'due volte lo stesso giorno no');
    Database::run('DELETE FROM calore WHERE luogo = ?', ['argine']);
});

prova('chi osserva trasforma ogni potere usato davanti a lui in una voce precisa', function () {
    $e = persona('Osservato', 'argine', 1);
    $c = persona('Paziente', 'argine');
    conPotere($e, 'telecinesi', 1);
    scoperto($e, $c);
    Segreto::scegli(di($c), (int) $e['id'], 'osserva');
    Segreto::usa(di($e), 'telecinesi');
    vero(Database::first(
        "SELECT 1 x FROM voci v JOIN voci_versioni vv ON vv.voce_id = v.id
         WHERE v.soggetto_id = ? AND v.tipo = 'potere' AND vv.personaggio_id = ? AND vv.precisione >= 80",
        [(int) $e['id'], (int) $c['id']]) !== null);
});

prova('quando lo sanno in troppi, è di pubblico dominio: si trasloca (FAQ 26)', function () {
    $e = persona('Famoso', 'argine', 1);
    $sentito = [];
    for ($i = 0; $i < 8; $i++) {
        $sentito[] = (int) persona('Folla' . $i, 'argine')['id'];
    }
    Segreto::dominio((int) $e['id']);
    \App\Game\Voci::nasce('potere', (int) $e['id'], null, 'argine', $sentito, 'segreto');
    uguale(8, Segreto::dominio((int) $e['id']));
    uguale('trasloco', Segreto::pericolo(di($e))['stato']);
});

prova('un segreto confidato a chi poi si guasta con te torna, moltiplicato', function () {
    $e = persona('Fiducioso', 'argine', 1);
    $c = persona('Voltagabbana', 'argine');
    vero(Segreto::confida(di($e), (int) $c['id'])['ok']);
    uguale(0, Segreto::quantiSanno((int) $e['id']));
    Legami::muovi((int) $c['id'], (int) $e['id'], -40, 0);
    uguale('tradito', (string) Database::first('SELECT come FROM sanno WHERE esper_id = ? AND chi_sa_id = ?',
        [(int) $e['id'], (int) $c['id']])['come']);
    uguale(2, Segreto::quantiSanno((int) $e['id']), 'conta doppio');
});

prova('Komatsu, quando capisce, lo racconta subito', function () {
    $komatsu = abitante('komatsu');
    $e = persona('Sfortunato', (string) $komatsu['luogo'], 1);
    $gts = Orologio::lineare();
    try {
        for ($tentativo = 0; $tentativo < 12 && !Segreto::sa((int) $e['id'], (int) $komatsu['id']); $tentativo++) {
            Database::run('INSERT INTO anomalie (osservatore_id, soggetto_id, incidente_id, gts, luogo, testo) VALUES (?, ?, NULL, ?, ?, ?)',
                [(int) $komatsu['id'], (int) $e['id'], $gts - $tentativo * 60, (string) $komatsu['luogo'], 'prova']);
            Segreto::abitantiCollegano($gts);
        }
        vero(Segreto::sa((int) $e['id'], (int) $komatsu['id']), 'in dodici tentativi Komatsu doveva capire');
        uguale('dice', (string) Database::first('SELECT atteggiamento FROM sanno WHERE esper_id = ? AND chi_sa_id = ?',
            [(int) $e['id'], (int) $komatsu['id']])['atteggiamento']);
        vero(Database::first("SELECT 1 x FROM voci WHERE soggetto_id = ? AND dettaglio = 'segreto'", [(int) $e['id']]) !== null);
    } finally {
        Database::run('DELETE FROM anomalie WHERE osservatore_id = ?', [(int) $komatsu['id']]);
        Database::run('DELETE FROM sanno WHERE chi_sa_id = ?', [(int) $komatsu['id']]);
        Database::run('DELETE FROM calore WHERE luogo = ?', [(string) $komatsu['luogo']]);
    }
});

// =============================================================================
titolo('Gli abitanti agiscono');

prova('i legami del canone ci sono, e quelli del triangolo tornano a posto', function () {
    $ky = abitante('kyosuke');
    $ma = abitante('madoka');
    \App\Game\Abitanti::assicuraLegami();
    uguale(85, Legami::fra((int) $ky['id'], (int) $ma['id'])['affetto']);
    Legami::muovi((int) $ky['id'], (int) $ma['id'], -30, 20);
    \App\Game\Abitanti::assicuraLegami();
    uguale(['affetto' => 85, 'fraintendimento' => 10, 'esiste' => true], Legami::fra((int) $ky['id'], (int) $ma['id']));
});

prova('un abitante fa gesti a chi ha davanti; il triangolo, a chi gioca, solo gentili', function () {
    $hikaru = abitante('hikaru');
    $prima = (string) $hikaru['luogo'];
    $pg = persona('Davanti', 'tempio');
    try {
        Database::run('UPDATE personaggi SET luogo = ?, verso = NULL WHERE id = ?', ['tempio', (int) $hikaru['id']]);
        Legami::muovi((int) $hikaru['id'], (int) $pg['id'], 80, 0);
        Database::run('DELETE FROM gesti_fatti WHERE attore_id = ?', [(int) $hikaru['id']]);
        $fatti = [];
        conManopola('abitanti.gesto_pct', '100', function () use ($hikaru, $pg, &$fatti) {
            conManopola('abitanti.gesto_pausa_ore', '0', function () use ($hikaru, $pg, &$fatti) {
                for ($i = 0; $i < 12; $i++) {
                    Database::run('UPDATE personaggi SET compostezza = compostezza_max WHERE id = ?', [(int) $hikaru['id']]);
                    \App\Game\Abitanti::gesti(Orologio::lineare() + $i * 240);
                }
                $fatti = array_column(Database::all('SELECT gkey FROM gesti_fatti WHERE attore_id = ? AND verso_id = ?',
                    [(int) $hikaru['id'], (int) $pg['id']]), 'gkey');
            });
        });
        vero($fatti !== [], 'Hikaru non ha fatto niente');
        uguale([], array_values(array_diff($fatti, ['parlare', 'ascoltare', 'ombrello', 'difendere'])),
            'il triangolo verso chi gioca resta gentile');
    } finally {
        Database::run('UPDATE personaggi SET luogo = ? WHERE id = ?', [$prima, (int) $hikaru['id']]);
        Database::run('DELETE FROM gesti_fatti WHERE attore_id = ? AND verso_id = ?', [(int) $hikaru['id'], (int) $pg['id']]);
    }
});

prova('il padre di Kyosuke è suo padre, e lo zio di Akane', function () {
    $ta = abitante('takashi');
    uguale('padre', \App\Game\Parentele::come(abitante('kyosuke'), $ta));
    uguale('figlia', \App\Game\Parentele::come($ta, abitante('kurumi')));
    uguale('zio', \App\Game\Parentele::come(abitante('akane'), $ta));
    uguale('nipote', \App\Game\Parentele::come($ta, abitante('kazuya')));
    vero(!(bool) $ta['esper'], 'i poteri vengono dal ramo della madre (FAQ 55)');
});

prova('i sogni premonitori dicono una cosa vera, storta', function () {
    $pg = persona('Sognante', 'argine', 1, 'f');
    $nemico = persona('Rancoroso', 'argine');
    Legami::muovi((int) $nemico['id'], (int) $pg['id'], -40, 50);
    $visti = 0;
    for ($i = 0; $i < 10; $i++) {
        $s = \App\Game\Poteri::sogno(di($pg), Orologio::lineare(), \App\Sim\Rng::for(7, 'prova-sogno', $i));
        if (str_contains($s, 'Rancoroso')) { $visti++; }
    }
    vero($visti >= 3, "chi ce l'ha con te compare nei sogni: {$visti} su dieci");
});

prova('il bentō lo si cucina: chi non sa cucinare lo brucia, e rende meno', function () {
    // Il bentō si fa a pranzo: si cerca l'ora giusta spostando l'orologio.
    $adesso = time();
    for ($s = 0; $s < 3000; $s++) {
        Orologio::fingiAdesso($adesso + $s * 60);
        $c = \App\Sim\Calendario::stato(Orologio::lineare());
        if ($c['fase'] === 'pranzo') {
            break;
        }
    }
    try {
        vero(\App\Sim\Calendario::stato(Orologio::lineare())['fase'] === 'pranzo', 'nessuna pausa pranzo in due giorni');
        $resa = [];
        $racconti = [];
        foreach ([1, 10] as $cucina) {
            $a = persona('Cuoco' . $cucina, 'liceo');
            $b = persona('Affamato' . $cucina, 'liceo');
            Database::run('UPDATE personaggi SET cucina = ?, compostezza = 6 WHERE id = ?', [$cucina, (int) $a['id']]);
            $r = Legami::compi(di($a), (int) $b['id'], 'bento');
            vero($r['ok'], $r['error'] ?? '');
            $resa[$cucina] = Legami::fra((int) $b['id'], (int) $a['id'])['affetto'];
            $racconti[$cucina] = $r['racconto'];
        }
        vero($resa[1] < $resa[10], json_encode($resa));
        vero(str_contains($racconti[1], 'scotto') && !str_contains($racconti[10], 'scotto'));
    } finally {
        Orologio::fingiAdesso(null);
    }
});

// =============================================================================
titolo('Le storie dell\'opera');

prova('i copioni dell\'opera ci sono, e ognuno può aprirsi in qualche momento', function () {
    $n = (int) Database::first('SELECT COUNT(*) n FROM copioni')['n'];
    vero($n >= 26, "copioni: {$n}");
    $nuovi = ['doppio', 'bancone', 'vigilia', 'coraggio', 'asso', 'lettera', 'terzo_natale', 'senza_di_te',
              'isola', 'funivia', 'nonni', 'tanabata', 'bambole', 'pesce_aprile', 'lenti', 'due_biglietti'];
    $presenti = array_column(Database::all('SELECT ckey FROM copioni'), 'ckey');
    uguale([], array_values(array_diff($nuovi, $presenti)));
});

prova('le condizioni composte: tutte insieme, o niente', function () use ($ore) {
    $m = new ReflectionMethod(\App\Game\Episodi::class, 'condizioneSoddisfatta');
    $notteEstate = $ore('1987-08-03 23:30:00');
    $giornoEstate = $ore('1987-08-03 14:00:00');
    vero($m->invoke(null, 'estate+notte', $notteEstate));
    vero(!$m->invoke(null, 'estate+notte', $giornoEstate), 'di giorno no');
    vero(!$m->invoke(null, 'inverno+notte', $notteEstate), 'd\'estate no');
});

prova('la vigilia di un esame è il giorno prima, non il giorno stesso', function () use ($ore) {
    $m = new ReflectionMethod(\App\Game\Episodi::class, 'condizioneSoddisfatta');
    vero($m->invoke(null, 'vigilia:esami', $ore('1987-05-17 21:00:00')), 'il 17 maggio è la vigilia');
    vero(!$m->invoke(null, 'vigilia:esami', $ore('1987-05-18 21:00:00')), 'il 18 è l\'esame');
    vero(!$m->invoke(null, 'vigilia:esami', $ore('1987-05-10 21:00:00')), 'il 10 non c\'entra');
    vero($m->invoke(null, 'evento:esami', $ore('1987-05-19 10:00:00')), '«esami» copre gli esami di maggio');
});

prova('il pesce d\'aprile cade nell\'ultima settimana del ciclo, e si accende', function () use ($ore) {
    $m = new ReflectionMethod(\App\Game\Episodi::class, 'condizioneSoddisfatta');
    vero($m->invoke(null, 'evento:pesce_aprile', $ore('1988-04-01 12:00:00')), 'il primo aprile 1988');
    vero(!$m->invoke(null, 'evento:pesce_aprile', $ore('1988-04-03 12:00:00')), 'il 3 no');
    vero($m->invoke(null, 'evento:tanabata', $ore('1987-07-07 19:00:00')), 'e Tanabata il 7 luglio');
});

prova('una giornata rara è rara: circa una su dieci, e dura tutto il giorno', function () use ($ore) {
    $m = new ReflectionMethod(\App\Game\Episodi::class, 'condizioneSoddisfatta');
    $rari = 0;
    for ($g = 0; $g < 200; $g++) {
        $mattina = $ore('1987-06-01 09:00:00') + $g * 86400;
        $sera    = $mattina + 10 * 3600;
        $r = $m->invoke(null, 'raro', $mattina);
        uguale($r, $m->invoke(null, 'raro', $sera), 'deve valere per tutta la giornata');
        $rari += $r ? 1 : 0;
    }
    vero($rari >= 8 && $rari <= 35, "rari {$rari} su 200");
});

prova('un\'opzione che usa un potere la vede solo chi ce l\'ha, e chi non c\'è non la sceglie', function () {
    $opz = [['k' => 'a', 'testo' => 'a', 'prova' => 'nessuna', 'peso' => ['cuore' => 1]],
            ['k' => 'b', 'testo' => 'b', 'prova' => 'nessuna', 'peso' => ['cuore' => 9], 'potere' => 'telecinesi']];
    $senza = persona('Normale', 'argine');
    $con   = persona('Telecineta', 'argine', 1);
    conPotere($con, 'telecinesi', 1);
    uguale(['a'], array_column(\App\Game\Episodi::opzioniPer(di($senza), $opz), 'k'));
    uguale(['a', 'b'], array_column(\App\Game\Episodi::opzioniPer(di($con), $opz), 'k'));
    // Anche se il peso lo spingerebbe lì, l'agente autonomo non usa poteri.
    Database::run('UPDATE personaggi SET cuore = 15 WHERE id = ?', [(int) $con['id']]);
    uguale('a', \App\Game\Episodi::agenteAutonomo(di($con), $opz));
});

prova('scegliere il potere in una scena lo usa davvero, con i suoi rischi', function () {
    $e = persona('Tentato', 'argine', 1);
    $t = persona('Vede', 'argine');
    Database::run('UPDATE personaggi SET testa = 15 WHERE id = ?', [(int) $t['id']]);
    conPotere($e, 'telecinesi', 1);
    $scene = [['testo' => 'Una scena.', 'opzioni' => [
        ['k' => 'normale', 'testo' => 'x', 'prova' => 'nessuna', 'difficolta' => 0, 'ok' => 'ok', 'ko' => '',
         'effetti_ok' => [], 'effetti_ko' => [], 'peso' => ['cuore' => 1]],
        ['k' => 'potere', 'testo' => 'y', 'prova' => 'nessuna', 'difficolta' => 0, 'ok' => 'fatto', 'ko' => 'niente',
         'effetti_ok' => [], 'effetti_ko' => [], 'peso' => ['cuore' => 1], 'potere' => 'telecinesi'],
    ]]];
    Database::run("INSERT INTO copioni (ckey, titolo, occhiello, premessa, luogo, condizione, min_cast, max_cast, finestra, scene, attivo)
                   VALUES ('zzctl', 'Prova', 'prova', 'prova', 'argine', 'sempre', 2, 4, 1800, ?, 0)
                   ON DUPLICATE KEY UPDATE scene = VALUES(scene)", [json_encode($scene, JSON_UNESCAPED_UNICODE)]);
    $gts = Orologio::lineare();
    Database::run("INSERT INTO episodi (ckey, titolo, luogo, scena, stato, aperto_gts, scena_gts, scade_reale)
                   VALUES ('zzctl', 'Prova', 'argine', 0, 'aperto', ?, ?, ?)", [$gts, $gts, Orologio::adessoReale() + 600]);
    $ep = (int) Database::lastInsertId();
    foreach ([$e, $t] as $p) {
        Database::run('INSERT INTO episodio_cast (episodio_id, personaggio_id, entrato_gts) VALUES (?, ?, ?)', [$ep, (int) $p['id'], $gts]);
    }
    try {
        $prima = (int) di($e)['pp'];
        vero(\App\Game\Episodi::scegli(di($e), $ep, 'potere')['ok']);
        vero(!\App\Game\Episodi::scegli(di($t), $ep, 'potere')['ok'], 'chi non ha il potere non può sceglierlo');
        \App\Game\Episodi::scegli(di($t), $ep, 'normale');
        vero((int) di($e)['pp'] < $prima, 'il potere costa');
        vero(Database::first("SELECT 1 x FROM incidenti WHERE attore_id = ? AND pkey = 'telecinesi'", [(int) $e['id']]) !== null,
            'ed è un uso vero, con il suo incidente');
    } finally {
        Database::run('DELETE FROM episodio_scelte WHERE episodio_id = ?', [$ep]);
        Database::run('DELETE FROM episodio_cast WHERE episodio_id = ?', [$ep]);
        Database::run('DELETE FROM ricordi WHERE episodio_id = ?', [$ep]);
        Database::run('DELETE FROM episodi WHERE id = ?', [$ep]);
        Database::run("DELETE FROM copioni WHERE ckey = 'zzctl'");
    }
});

prova('il sake dolce fa venire i poteri sballati', function () {
    $e = persona('Brillo', 'argine', 1);
    $m = new ReflectionMethod(\App\Game\Episodi::class, 'applicaEffetti');
    $m->invoke(null, di($e), [di($e)], ['brillo' => 120], 'argine', Orologio::lineare());
    vero(\App\Game\Stati::ha((int) $e['id'], 'brillo'));
    vero(\App\Game\Stati::poteriSballati((int) $e['id']));
    vero(str_contains(\App\Game\Stati::addosso(di($e)), 'brillo'));
});

// =============================================================================
titolo('Luoghi, gesti e oggetti nuovi');

prova('alla Moebius si entra solo in coppia', function () {
    $pg = persona('Single', 'commerciale');
    vero(Personaggio::porta(di($pg), 'moebius') !== null, 'da soli no');
    $amico = persona('Cavaliere', 'commerciale');
    Legami::muovi((int) $amico['id'], (int) $pg['id'], 40, 0);
    uguale(null, Personaggio::porta(di($pg), 'moebius'), 'con qualcuno che ci tiene sì');
});

prova('a chi ha il raffreddore si porta da mangiare, e guarisce prima', function () {
    $a = persona('Infermiere', 'argine');
    $b = persona('Febbricitante2', 'argine');
    $r = Legami::compi(di($a), (int) $b['id'], 'curare');
    vero(!$r['ok'] && str_contains((string) $r['error'], 'benissimo'), 'a chi sta bene no');
    \App\Game\Stati::metti((int) $b['id'], 'raffreddore', 600);
    $r = Legami::compi(di($a), (int) $b['id'], 'curare');
    vero($r['ok'], $r['error'] ?? '');
    vero(!\App\Game\Stati::ha((int) $b['id'], 'raffreddore'), 'il raffreddore è passato');
});

function prendi(string $okey, array $pg): array
{
    $prima = Database::first('SELECT * FROM oggetti_unici WHERE okey = ?', [$okey]);
    Database::run('UPDATE oggetti_unici SET detentore_id = ?, luogo = NULL, gts = ?, pronto_gts = NULL WHERE okey = ?',
        [(int) $pg['id'], Orologio::lineare(), $okey]);
    return $prima;
}
function rimetti(array $prima): void
{
    Database::run('UPDATE oggetti_unici SET detentore_id = ?, luogo = ?, gts = ?, pronto_gts = ? WHERE okey = ?',
        [$prima['detentore_id'], $prima['luogo'], $prima['gts'], $prima['pronto_gts'], $prima['okey']]);
}

prova('l\'orologio del nonno ferma il tempo per un minuto, e poi va ricaricato', function () {
    $e = persona('Orologiaio', 'argine', 1);
    $t = persona('Distratto', 'argine');
    Database::run('UPDATE personaggi SET testa = 15 WHERE id = ?', [(int) $t['id']]);
    conPotere($e, 'teletrasporto', 1);
    $prima = prendi('orologio', $e);
    try {
        vero(Legami::usaOggetto(di($e), 'orologio')['ok']);
        $r = Segreto::usa(di($e), 'teletrasporto');
        vero($r['ok'] && !$r['notato'], 'col tempo fermo nessuno vede niente');
        vero(!Legami::usaOggetto(di($e), 'orologio')['ok'], 'subito dopo è scarico');
    } finally {
        rimetti($prima);
    }
});

prova('col pappagallo accanto nessun gesto si fraintende', function () {
    $a = persona('Pappagallaio', 'argine');
    $b = persona('Accompagnato', 'argine');
    $c = persona('Distrattissimo', 'argine');
    Database::run('UPDATE personaggi SET testa = 1 WHERE id = ?', [(int) $c['id']]);
    $prima = prendi('pappagallo', $a);
    try {
        $r = Legami::compi(di($a), (int) $b['id'], 'accompagnare');
        vero($r['ok'], $r['error'] ?? '');
        uguale(0, (int) $r['fraintesi'], 'il pappagallo ha gridato a tutti cosa voleva dire');
    } finally {
        rimetti($prima);
    }
});

prova('l\'orologio si può dare a un parente, il ciondolo no', function () {
    $ky = abitante('kyosuke');
    $ku = abitante('kurumi');
    $prima = [prendi('orologio', $ky), prendi('ciondolo', $ky)];
    $luogo = (string) $ky['luogo'];
    try {
        Database::run('UPDATE personaggi SET luogo = ?, verso = NULL WHERE id = ?', [$luogo, (int) $ku['id']]);
        vero(Legami::passaOggetto(abitante('kyosuke'), (int) $ku['id'], 'orologio')['ok']);
        vero(!Legami::passaOggetto(abitante('kyosuke'), (int) $ku['id'], 'ciondolo')['ok']);
    } finally {
        rimetti($prima[0]);
        rimetti($prima[1]);
        Database::run('DELETE FROM oggetti_passaggi WHERE okey IN (?, ?) AND da_id = ?', ['orologio', 'ciondolo', (int) $ky['id']]);
    }
});

prova('dato a un abitante, un oggetto torna a casa sua, non sempre ai gradini', function () {
    $master = abitante('master');
    $prima = prendi('orologio', $master);
    try {
        Database::run('UPDATE oggetti_unici SET gts = ? WHERE okey = ?', [Orologio::lineare() - 5 * 86400, 'orologio']);
        Legami::ritrovaOggetti();
        uguale('tempio', (string) Database::first('SELECT luogo FROM oggetti_unici WHERE okey = ?', ['orologio'])['luogo']);
    } finally {
        rimetti($prima);
        Database::run("DELETE FROM tracce WHERE personaggio_id = ? AND tipo = 'oggetto'", [(int) $master['id']]);
    }
});

prova('il primo sogno dell\'anno lo fanno tutti, poteri o no', function () use ($ore) {
    $m = new ReflectionMethod(\App\Game\Poteri::class, 'primoSogno');
    vero($m->invoke(null, $ore('1988-01-02 07:00:00')) >= 0);
    uguale(0, $m->invoke(null, $ore('1988-01-05 07:00:00')), 'solo il 2 gennaio');
});

pulisci();
riepilogo();
