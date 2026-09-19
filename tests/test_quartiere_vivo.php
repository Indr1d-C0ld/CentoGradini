<?php

declare(strict_types=1);

/**
 * F6 — il quartiere vivo: voci, abitanti, club, bacheca, biglietti, eventi.
 *
 * L'orologio si congela in ogni prova il cui esito dipende da ora, stagione o
 * meteo, e si ripristina in un finally. È la lezione 15 del README, imparata
 * su due prove del Segreto che cadevano a caso: qui ce ne sarebbero state
 * parecchie, perché metà di F6 guarda il calendario.
 */

require __DIR__ . '/_prova.php';

use App\Core\Config;
use App\Core\Database;
use App\Core\GameConfig;
use App\Game\Abitanti;
use App\Game\Bacheca;
use App\Game\Club;
use App\Game\Eventi;
use App\Game\Voci;
use App\Sim\Luoghi;
use App\Sim\Orologio;
use App\Sim\Scuola;

Config::load(dirname(__DIR__));

const PREFISSO = 'zzviv';
const PALCO    = 'albero';      // folla zero a ogni ora, e nessun PNG ci passa
const LIMBO    = 'montagna';    // lontano, e non è casa di nessun abitante

// Epoca fissa: 5400 secondi reali dopo l'epoca sono le 13:00 del 6 aprile
// 1987 a Tokyo, in pieno giorno e con pioggia zero.
const EPOCA = 1735689600;

$creati = [];

/**
 * Sgombra il palco. Da F6 in poi non basta più spostare i personaggi di
 * prova: nel quartiere ci sono anche gli abitanti canonici, che si muovono
 * per conto loro e che finirebbero a fare da testimoni.
 */
function sgombra(): void
{
    Database::run('UPDATE personaggi SET luogo = ? WHERE luogo = ?', [LIMBO, PALCO]);
}

function attore(string $nome, array $campi = []): array
{
    global $creati;
    $c = array_merge([
        'sesso' => 'm', 'sezione' => 'superiori', 'anno' => 2,
        'testa' => 10, 'dai_suki' => 10, 'cuore' => 8, 'rissa' => 6,
    ], $campi);
    Database::run(
        'INSERT INTO personaggi (user_id, nome, cognome, sesso, sezione, anno, scheda, stato,
             luogo, anno_nascita, nato_mese, nato_giorno, testa, dai_suki, cuore, rissa)
         VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, 1970, 5, 5, ?, ?, ?, ?)',
        [$nome, ucfirst(PREFISSO), $c['sesso'], $c['sezione'], $c['anno'], 'completa', 'attivo',
         LIMBO, $c['testa'], $c['dai_suki'], $c['cuore'], $c['rissa']]
    );
    $id = (int) Database::lastInsertId();
    $creati[] = $id;
    return Database::first('SELECT * FROM personaggi WHERE id = ?', [$id]);
}

function suPalco(array $pg): array
{
    Database::run('UPDATE personaggi SET luogo = ?, verso = NULL WHERE id = ?', [PALCO, (int) $pg['id']]);
    return Database::first('SELECT * FROM personaggi WHERE id = ?', [(int) $pg['id']]);
}

/** Congela l'istante su un mezzogiorno asciutto e lo ripristina dopo. */
function aMezzogiorno(callable $f): void
{
    Orologio::fingiEpoca(EPOCA);
    Orologio::fingiAdesso(EPOCA + 5400);
    try {
        $f();
    } finally {
        Orologio::fingiEpoca(null);
        Orologio::fingiAdesso(null);
    }
}

/** La versione che una persona ha di una voce, o null. */
function versione(int $voceId, int $pgId): ?array
{
    return Database::first(
        'SELECT * FROM voci_versioni WHERE voce_id = ? AND personaggio_id = ?', [$voceId, $pgId]
    );
}

// =============================================================================
titolo('Le voci nascono');

prova('chi c\'era la sa quasi per intero', function () {
    sgombra();
    $a = suPalco(attore('Attore'));
    $t = suPalco(attore('Testimone'));
    $v = Voci::nasce('potere', (int) $a['id'], null, PALCO, [(int) $t['id']], 'telecinesi');

    $mia = versione($v, (int) $t['id']);
    vero($mia !== null, 'il testimone deve avere la sua versione');
    vero((int) $mia['precisione'] >= 82, 'chi ha visto parte alto: ' . (int) $mia['precisione']);
    uguale(0, (int) $mia['passaggi'], 'chi ha visto non ha passaggi alle spalle');
    vero($mia['da_id'] === null, 'chi ha visto non se l\'è sentita dire');
});

prova('chi ha visto non parte mai da cento', function () {
    sgombra();
    $a = suPalco(attore('Attore2'));
    $visti = [];
    for ($i = 0; $i < 12; $i++) {
        $t = suPalco(attore('Occhio' . $i));
        $v = Voci::nasce('potere', (int) $a['id'], null, PALCO, [(int) $t['id']], 'telecinesi');
        $visti[] = (int) versione($v, (int) $t['id'])['precisione'];
    }
    // Anche vedere è interpretare: due testimoni dello stesso fatto non
    // raccontano la stessa cosa, e nessuno dei due è una telecamera.
    vero(min($visti) >= 82 && max($visti) <= 100, 'fuori dalla forbice 82-100');
    vero(count(array_unique($visti)) > 1, 'dodici testimoni con la stessa precisione: non varia');
});

prova('il testo cambia con la precisione', function () {
    sgombra();
    $a = suPalco(attore('Famoso'));
    $t = suPalco(attore('Ascolta'));
    $v = Voci::nasce('potere', (int) $a['id'], null, PALCO, [], 'telecinesi');

    $testi = [];
    foreach ([95, 65, 40, 10] as $p) {
        Database::run('DELETE FROM voci_versioni WHERE voce_id = ? AND personaggio_id = ?',
            [$v, (int) $t['id']]);
        Database::run('INSERT INTO voci_versioni (voce_id, personaggio_id, precisione, tono, passaggi, gts)
                       VALUES (?, ?, ?, 0, 0, ?)', [$v, (int) $t['id'], $p, Orologio::lineare()]);
        $testi[$p] = Voci::per((int) $t['id'], 1)[0]['testo'];
    }
    vero(str_contains($testi[95], 'Famoso'), 'a precisione alta il nome c\'è: ' . $testi[95]);
    vero(!str_contains($testi[40], 'Famoso'), 'a precisione media il nome se n\'è andato: ' . $testi[40]);
    vero(!str_contains($testi[10], 'Famoso'), 'nella nebbia il nome non c\'è: ' . $testi[10]);
    vero(count(array_unique($testi)) === 4, 'le quattro fasce devono dare quattro frasi diverse');
});

prova('il nome se ne va prima del fatto', function () {
    sgombra();
    $a = suPalco(attore('Riconoscibile'));
    $t = suPalco(attore('Orecchio'));
    $v = Voci::nasce('potere', (int) $a['id'], null, PALCO, [], 'telecinesi');
    Database::run('INSERT INTO voci_versioni (voce_id, personaggio_id, precisione, tono, passaggi, gts)
                   VALUES (?, ?, 40, 0, 2, ?)', [$v, (int) $t['id'], Orologio::lineare()]);
    $testo = Voci::per((int) $t['id'], 1)[0]['testo'];
    // A quarantacinque il nome è sparito ma la classe resta: è la sostituzione
    // che fa più danno di tutte, perché adesso può essere chiunque del secondo anno.
    vero(str_contains($testo, 'secondo anno'), 'deve restare la classe: ' . $testo);
});

prova('due protagonisti si nominano sempre in due', function () {
    sgombra();
    $a = suPalco(attore('Lui', ['sesso' => 'm']));
    $b = suPalco(attore('Lei', ['sesso' => 'f']));
    $t = suPalco(attore('Vede'));
    $v = Voci::nasce('insieme', (int) $a['id'], (int) $b['id'], PALCO, []);
    foreach ([90, 35] as $p) {
        Database::run('DELETE FROM voci_versioni WHERE voce_id = ?', [$v]);
        Database::run('INSERT INTO voci_versioni (voce_id, personaggio_id, precisione, tono, passaggi, gts)
                       VALUES (?, ?, ?, 0, 1, ?)', [$v, (int) $t['id'], $p, Orologio::lineare()]);
        $testo = Voci::per((int) $t['id'], 1)[0]['testo'];
        // Il pettegolezzo perde i nomi, non il fatto che fossero in due.
        vero(str_contains($testo, ' e '), "a precisione {$p} devono restare in due: {$testo}");
    }
});

// =============================================================================
titolo('Le voci si deformano');

prova('un passaggio costa precisione', function () {
    sgombra();
    $a  = suPalco(attore('Soggetto'));
    $t1 = suPalco(attore('Primo', ['dai_suki' => 15]));
    $t2 = suPalco(attore('Secondo'));
    $v  = Voci::nasce('potere', (int) $a['id'], null, PALCO, [(int) $t1['id']], 'telecinesi');
    $prima = (int) versione($v, (int) $t1['id'])['precisione'];

    // Si fanno passare le ore finché la voce passa: il tiro è
    // probabilistico, la perdita no. Ripetere lo stesso istante non
    // servirebbe a niente — l'esito di quell'ora è già deciso.
    $g = Orologio::lineare();
    for ($i = 0; $i < 60 && versione($v, (int) $t2['id']) === null; $i++) {
        Voci::giro($g + $i * 3600);
    }
    $dopo = versione($v, (int) $t2['id']);
    vero($dopo !== null, 'in sessanta giri la voce deve essere passata almeno una volta');
    vero((int) $dopo['precisione'] < $prima,
        "la precisione deve calare: {$prima} -> " . (int) $dopo['precisione']);
    uguale(1, (int) $dopo['passaggi'], 'e il contatore dei passaggi sale');
    uguale((int) $t1['id'], (int) $dopo['da_id'], 'e si ricorda da chi l\'ha sentita');
});

prova('il tono si polarizza, non si smorza', function () {
    sgombra();
    $a  = suPalco(attore('Chiacchierato'));
    $t1 = suPalco(attore('Bocca', ['dai_suki' => 15]));
    $t2 = suPalco(attore('Orecchia'));
    $v  = Voci::nasce('potere', (int) $a['id'], null, PALCO, [(int) $t1['id']], 'telecinesi');
    // Si parte da una versione decisamente malevola.
    Database::run('UPDATE voci_versioni SET tono = -40, precisione = 90 WHERE voce_id = ? AND personaggio_id = ?',
        [$v, (int) $t1['id']]);

    $g = Orologio::lineare();
    for ($i = 0; $i < 60 && versione($v, (int) $t2['id']) === null; $i++) {
        Voci::giro($g + $i * 3600);
    }
    $dopo = versione($v, (int) $t2['id']);
    vero($dopo !== null, 'la voce deve essere passata');
    // Il pettegolezzo non torna mai verso lo zero: una storia brutta
    // raccontata di nuovo diventa più brutta, non più equilibrata.
    vero((int) $dopo['tono'] < -40,
        'il tono deve peggiorare, non smorzarsi: ' . (int) $dopo['tono']);
});

prova('di sé stessi non si sente parlare', function () {
    sgombra();
    $a  = suPalco(attore('Protagonista'));
    $t1 = suPalco(attore('Testimone2', ['dai_suki' => 15]));
    $v  = Voci::nasce('potere', (int) $a['id'], null, PALCO, [(int) $t1['id']], 'telecinesi');

    $g = Orologio::lineare();
    for ($i = 0; $i < 30; $i++) {
        Voci::giro($g + $i * 3600);
    }
    // Il soggetto della voce non la riceve mai: raccontare a qualcuno una
    // cosa che ha fatto lui non è propagare un pettegolezzo.
    vero(versione($v, (int) $a['id']) === null, 'il soggetto non deve ricevere la propria voce');
});

prova('una voce troppo sbiadita non si racconta più', function () {
    sgombra();
    $a  = suPalco(attore('Vecchio'));
    $t1 = suPalco(attore('Smemorato', ['dai_suki' => 15]));
    $t2 = suPalco(attore('Nuovo'));
    $v  = Voci::nasce('potere', (int) $a['id'], null, PALCO, [(int) $t1['id']], 'telecinesi');
    $soglia = GameConfig::int('voci.soglia_racconto', 12);
    Database::run('UPDATE voci_versioni SET precisione = ? WHERE voce_id = ?', [max(0, $soglia - 1), $v]);

    $g = Orologio::lineare();
    for ($i = 0; $i < 40; $i++) {
        Voci::giro($g + $i * 3600);
    }
    vero(versione($v, (int) $t2['id']) === null,
        'sotto la soglia non vale più la pena raccontarla');
});

// =============================================================================
titolo('Il club porta le voci dove la mappa non arriva');

prova('due soci lontani si parlano lo stesso', function () {
    sgombra();
    $a  = suPalco(attore('Fatto'));
    $t1 = suPalco(attore('Socio1', ['dai_suki' => 15]));
    $t2 = attore('Socio2');    // resta nel limbo: non si incontrano mai
    $v  = Voci::nasce('potere', (int) $a['id'], null, PALCO, [(int) $t1['id']], 'telecinesi');
    Database::run('UPDATE voci_versioni SET precisione = 100 WHERE voce_id = ?', [$v]);

    $g = Orologio::lineare();
    foreach ([(int) $t1['id'], (int) $t2['id']] as $m) {
        Database::run('INSERT INTO club_membri (ckey, personaggio_id, ruolo, dal_gts) VALUES (?,?,?,?)
                       ON DUPLICATE KEY UPDATE ruolo = ruolo', ['letteratura', $m, 'socio', $g]);
    }
    try {
        $gg = Orologio::lineare();
        for ($i = 0; $i < 80 && versione($v, (int) $t2['id']) === null; $i++) {
            Voci::giro($gg + $i * 3600);
        }
        vero(versione($v, (int) $t2['id']) !== null,
            'il club deve far arrivare la voce a chi non si è mai incontrato');
    } finally {
        Database::run('DELETE FROM club_membri WHERE ckey = ? AND personaggio_id IN (?, ?)',
            ['letteratura', (int) $t1['id'], (int) $t2['id']]);
    }
});

// =============================================================================
titolo('Gli abitanti');

prova('i canonici stanno nel quartiere', function () {
    $n = count(Abitanti::tutti());
    vero($n >= 10, "ci si aspettano almeno dieci abitanti, ce ne sono {$n}");
});

prova('Kyosuke non ha la telepatia, Kazuya sì', function () {
    Abitanti::assicuraPoteri();
    $ha = static function (string $png, string $pkey): bool {
        return Database::first(
            'SELECT 1 x FROM personaggio_poteri pp JOIN personaggi p ON p.id = pp.personaggio_id
             WHERE p.png = ? AND pp.pkey = ?', [$png, $pkey]) !== null;
    };
    // È la correzione più pesante della rilettura del canone, ed è il motore
    // della storia: se Kyosuke leggesse nel pensiero, il fumetto finirebbe al
    // terzo capitolo.
    vero(!$ha('kyosuke', 'telepatia'), 'Kyosuke NON deve avere la telepatia');
    vero($ha('kyosuke', 'teletrasporto'), 'ma il teletrasporto sì');
    vero($ha('kazuya', 'telepatia'), 'Kazuya è il solo telepate della famiglia');
});

prova('nessun potere secondario si perde per strada', function () {
    Abitanti::assicuraPoteri();
    // Il primo secondario spariva: l'unione fra array lavora sulle chiavi, e
    // con «+» la chiave 0 del primario si mangiava la chiave 0 dei secondari.
    $n = (int) Database::first(
        'SELECT COUNT(*) n FROM personaggio_poteri pp JOIN personaggi p ON p.id = pp.personaggio_id
         WHERE p.png = ?', ['kyosuke'])['n'];
    uguale(5, $n, 'Kyosuke ha un primario e quattro secondari');
});

prova('le coorti dei canonici tornano', function () {
    foreach (Abitanti::tutti() as $a) {
        if ((string) $a['sezione'] === 'adulti') {
            continue;   // gli adulti non stanno in una coorte scolastica
        }
        $atteso = Scuola::annoDiNascita((string) $a['sezione'], (int) $a['anno'],
            (int) $a['nato_mese'], (int) $a['nato_giorno']);
        uguale($atteso, (int) $a['anno_nascita'],
            "{$a['png']} è in " . Scuola::nomeClasse((string) $a['sezione'], (int) $a['anno'])
            . ' ma è nato nel ' . (int) $a['anno_nascita']);
    }
});

prova('il giro è una funzione dell\'istante, non uno stato', function () {
    $k = Database::first('SELECT * FROM personaggi WHERE png = ?', ['komatsu']);
    $g = Orologio::lineare();
    // Chiesto due volte lo stesso istante, deve dare due volte lo stesso posto.
    uguale(Abitanti::dove($k, $g), Abitanti::dove($k, $g), 'stesso istante, stesso posto');
    $posti = [];
    for ($h = 0; $h < 48; $h++) {
        $posti[] = Abitanti::dove($k, $g + $h * 3600);
    }
    vero(count(array_unique($posti)) >= 3, 'in due giorni deve girare per almeno tre posti');
});

prova('di notte si sta a casa', function () {
    $k    = Database::first('SELECT * FROM personaggi WHERE png = ?', ['komatsu']);
    $casa = Abitanti::giro($k)[0];
    $g    = Orologio::lineare();
    // Si cercano le ore piccole scorrendo un giorno intero, invece di
    // fidarsi di un'aritmetica sull'epoca: l'ora che conta e' quella di
    // Tokyo, e calcolarla a mano qui sarebbe un modo elegante di sbagliare.
    $trovate = 0;
    for ($h = 0; $h < 26; $h++) {
        $t   = $g + $h * 3600;
        $ora = (int) Orologio::data($t)->format('G');
        if ($ora >= 0 && $ora < 5) {
            uguale($casa, Abitanti::dove($k, $t), "alle {$ora} ognuno e' a casa propria");
            $trovate++;
        }
    }
    vero($trovate > 0, 'in ventisei ore deve capitarci almeno un ora piccola');
});

prova('nessuno sta in un posto chiuso', function () {
    // Il giro e' un'abitudine, ma gli orari sono un dato del luogo: senza
    // questo controllo il giro metteva gente dentro il luna park alle sette
    // di mattina.
    $g = Orologio::lineare();
    foreach (Abitanti::tutti() as $a) {
        for ($h = 0; $h < 48; $h += 3) {
            $t    = $g + $h * 3600;
            $dove = Abitanti::dove($a, $t);
            [$aperto] = Luoghi::accessibile($dove, $t);
            // Casa propria vale sempre: e' il rifugio, e non ha orari.
            if ($dove === Abitanti::giro($a)[0]) {
                continue;
            }
            vero($aperto, "{$a['png']} sta {$dove} e a quell'ora e' chiuso");
        }
    }
});

prova('un bambino delle elementari non va al liceo', function () {
    // Il Koryo e' l'unica scuola della mappa e ospita medie e superiori.
    // Kazuya ha otto anni: la sua scuola non c'e', quindi durante le lezioni
    // semplicemente non e' in giro.
    $k = Database::first('SELECT * FROM personaggi WHERE png = ?', ['kazuya']);
    uguale('elementari', (string) $k['sezione']);
    $g = Orologio::lineare();
    for ($h = 0; $h < 72; $h++) {
        uguale(true, Abitanti::dove($k, $g + $h * 3600) !== 'liceo',
            'un bambino di terza elementare in corridoio al liceo si nota');
    }
});

prova('il Master non esce mai dall\'ABCB', function () {
    $m = Database::first('SELECT * FROM personaggi WHERE png = ?', ['master']);
    $g = Orologio::lineare();
    for ($h = 0; $h < 30; $h++) {
        uguale('abcb', Abitanti::dove($m, $g + $h * 3600), 'il suo giro è di un posto solo');
    }
});

// =============================================================================
titolo('I club');

prova('ci si iscrive e si esce', function () {
    $pg = attore('Socio');
    $r  = Club::iscrivi($pg, 'letteratura');
    vero($r['ok'], $r['error'] ?? '');
    uguale(1, count(Club::di((int) $pg['id'])));
    vero(Club::esci($pg, 'letteratura')['ok']);
    uguale(0, count(Club::di((int) $pg['id'])));
});

prova('non ci si iscrive due volte allo stesso club', function () {
    $pg = attore('Doppio');
    Club::iscrivi($pg, 'letteratura');
    $r = Club::iscrivi($pg, 'letteratura');
    vero(!$r['ok'], 'la seconda iscrizione deve essere rifiutata');
    Club::esci($pg, 'letteratura');
});

prova('oltre il tetto non si va', function () {
    $pg  = attore('Iperattivo');
    $max = GameConfig::int('club.max_per_pg', 2);
    $chiavi = array_column(Club::tutti(), 'ckey');
    $fatte = 0;
    foreach ($chiavi as $c) {
        if (Club::iscrivi($pg, $c)['ok']) {
            $fatte++;
        }
    }
    uguale($max, $fatte, 'con i compiti non si sta in più di ' . $max . ' club');
    foreach ($chiavi as $c) {
        Club::esci($pg, $c);
    }
});

prova('Yusaku fa karate, e Madoka non fa niente', function () {
    Abitanti::assicuraClub();
    $ha = static fn (string $png, string $ckey): bool => Database::first(
        'SELECT 1 x FROM club_membri m JOIN personaggi p ON p.id = m.personaggio_id
         WHERE p.png = ? AND m.ckey = ?', [$png, $ckey]) !== null;
    vero($ha('yusaku', 'karate'), 'è l\'unico club attestato dall\'opera');
    // Il canone la dà esplicitamente per solitaria: nota a tutte le bande,
    // mai entrata in nessuna. Metterla in un club per simmetria sarebbe la
    // cosa più sbagliata che si possa fare a questo personaggio.
    $m = Database::first('SELECT id FROM personaggi WHERE png = ?', ['madoka']);
    uguale(0, count(Club::di((int) $m['id'])), 'Madoka non è iscritta a niente');
});

// =============================================================================
titolo('La bacheca');

prova('si appende e si legge', function () {
    $pg = suPalco(attore('Affissore'));
    $r  = Bacheca::affiggi($pg, PALCO, 'avviso', 'Cerco una bicicletta', 'Anche vecchia, purché freni.');
    vero($r['ok'], $r['error'] ?? '');
    $avvisi = Bacheca::avvisi(PALCO);
    vero($avvisi !== [], 'l\'avviso deve comparire');
    uguale('Cerco una bicicletta', $avvisi[0]['titolo']);
    Bacheca::stacca($pg, (int) $r['id']);
});

prova('la firma vince sul nome vero', function () {
    $pg = suPalco(attore('Nascosto'));
    $r  = Bacheca::affiggi($pg, PALCO, 'avviso', 'Un titolo', 'Un testo qualunque.', 'un amico');
    vero($r['ok'], $r['error'] ?? '');
    $a = Bacheca::avvisi(PALCO)[0];
    uguale('un amico', $a['mostra_firma'], 'nel manga si firma con quello che si vuole');
    Bacheca::stacca($pg, (int) $r['id']);
});

prova('oltre il tetto di avvisi non si appende', function () {
    $pg  = suPalco(attore('Prolifico'));
    $max = GameConfig::int('bacheca.max_per_pg', 3);
    $ids = [];
    for ($i = 0; $i < $max; $i++) {
        $r = Bacheca::affiggi($pg, PALCO, 'avviso', 'Titolo ' . $i, 'Testo qualunque.');
        vero($r['ok'], $r['error'] ?? '');
        $ids[] = (int) $r['id'];
    }
    $r = Bacheca::affiggi($pg, PALCO, 'avviso', 'Uno di troppo', 'Testo qualunque.');
    vero(!$r['ok'], 'il tetto deve mordere');
    foreach ($ids as $id) {
        Bacheca::stacca($pg, $id);
    }
});

prova('l\'avviso di un altro non si stacca', function () {
    $a = suPalco(attore('Mio'));
    $b = suPalco(attore('Tuo'));
    $r = Bacheca::affiggi($a, PALCO, 'avviso', 'Il mio foglio', 'Non toccarlo.');
    vero(!Bacheca::stacca($b, (int) $r['id'])['ok'], 'solo chi lo ha appeso lo stacca');
    Bacheca::stacca($a, (int) $r['id']);
});

prova('un avviso firmato fa nascere una voce, uno anonimo no', function () {
    sgombra();
    $a = suPalco(attore('Firmato'));
    $t = suPalco(attore('Presente'));
    $prima = (int) Database::first('SELECT COUNT(*) n FROM voci WHERE tipo = ?', ['affisso'])['n'];

    $r1 = Bacheca::affiggi($a, PALCO, 'avviso', 'Con la firma', 'Testo qualunque.');
    $dopo = (int) Database::first('SELECT COUNT(*) n FROM voci WHERE tipo = ?', ['affisso'])['n'];
    uguale($prima + 1, $dopo, 'un foglio firmato lo si vede appendere');

    $r2 = Bacheca::affiggi($a, PALCO, 'anonimo', 'Senza firma', 'Testo qualunque.');
    $dopo2 = (int) Database::first('SELECT COUNT(*) n FROM voci WHERE tipo = ?', ['affisso'])['n'];
    uguale($dopo, $dopo2, 'nessuno ha visto chi ha appeso quello anonimo');

    Bacheca::stacca($a, (int) $r1['id']);
    Bacheca::stacca($a, (int) $r2['id']);
});

// =============================================================================
titolo('I biglietti');

prova('un biglietto si trova dove è stato lasciato, non altrove', function () {
    sgombra();
    $a = suPalco(attore('Scrive'));
    $b = suPalco(attore('Riceve'));
    vero(Bacheca::lascia($a, (int) $b['id'], 'Ci vediamo dopo.')['ok']);

    uguale(1, count(Bacheca::quiPer((int) $b['id'], PALCO)), 'qui c\'è');
    uguale(0, count(Bacheca::quiPer((int) $b['id'], 'stazione')), 'altrove no');
});

prova('leggere un biglietto richiede di essere nel posto giusto', function () {
    sgombra();
    $a = suPalco(attore('Scrive2'));
    $b = suPalco(attore('Riceve2'));
    Bacheca::lascia($a, (int) $b['id'], 'Un messaggio.');
    $big = Bacheca::quiPer((int) $b['id'], PALCO)[0];

    // Lo stesso biglietto, con il destinatario spostato: non si legge.
    Database::run('UPDATE personaggi SET luogo = ? WHERE id = ?', ['stazione', (int) $b['id']]);
    $lontano = Database::first('SELECT * FROM personaggi WHERE id = ?', [(int) $b['id']]);
    vero(!Bacheca::leggi($lontano, (int) $big['id'])['ok'], 'da lontano non si legge');

    $vicino = suPalco($b);
    $r = Bacheca::leggi($vicino, (int) $big['id']);
    vero($r['ok'], $r['error'] ?? '');
    uguale('Un messaggio.', $r['testo']);
    uguale(0, count(Bacheca::quiPer((int) $b['id'], PALCO)), 'letto una volta, non aspetta più');
});

prova('i biglietti di un altro non si leggono', function () {
    sgombra();
    $a = suPalco(attore('Scrive3'));
    $b = suPalco(attore('Riceve3'));
    $c = suPalco(attore('Ficcanaso'));
    Bacheca::lascia($a, (int) $b['id'], 'Roba privata.');
    $big = Bacheca::quiPer((int) $b['id'], PALCO)[0];
    vero(!Bacheca::leggi($c, (int) $big['id'])['ok'], 'non è per lui');
});

// =============================================================================
titolo('Gli eventi stagionali');

prova('un evento non comincia il giorno prima', function () {
    // Il difetto vero che questa prova blocca: DateTime::diff() con '%r%a'
    // rende «-0» una differenza di mezza giornata, e castata a intero è 0.
    // «Al mare» del 5 agosto risultava in corso il 4.
    Orologio::fingiEpoca(EPOCA);
    try {
        $trovato = ['prima' => null, 'primo' => null];
        for ($g = 0; $g < 366; $g++) {
            Orologio::fingiAdesso(EPOCA + $g * 21600);
            $md = Orologio::data(Orologio::gts())->format('m-d');
            $in = array_column(Eventi::inCorso(), 'ekey');
            if ($md === '08-04') { $trovato['prima'] = $in; }
            if ($md === '08-05') { $trovato['primo'] = $in; }
        }
        vero($trovato['prima'] !== null && $trovato['primo'] !== null, 'i due giorni devono esistere');
        vero(!in_array('mare', $trovato['prima'], true), 'il 4 agosto «Al mare» non è cominciato');
        vero(in_array('mare', $trovato['primo'], true), 'il 5 agosto sì');
    } finally {
        Orologio::fingiEpoca(null);
        Orologio::fingiAdesso(null);
    }
});

prova('un evento dura quanto dichiarato, e non un giorno di più', function () {
    Orologio::fingiEpoca(EPOCA);
    try {
        $durata = (int) Database::first('SELECT durata FROM eventi WHERE ekey = ?', ['mare'])['durata'];
        $giorniInCorso = 0;
        for ($g = 0; $g < 366; $g++) {
            Orologio::fingiAdesso(EPOCA + $g * 21600);
            if (in_array('mare', array_column(Eventi::inCorso(), 'ekey'), true)) {
                $giorniInCorso++;
            }
        }
        uguale($durata, $giorniInCorso, 'i giorni in corso devono essere esattamente la durata');
    } finally {
        Orologio::fingiEpoca(null);
        Orologio::fingiAdesso(null);
    }
});

prova('gli eventi alzano la chiacchiera, e fuori resta a uno', function () {
    Orologio::fingiEpoca(EPOCA);
    try {
        // 14 febbraio: il giorno con più chiacchiere del calendario.
        $valori = [];
        for ($g = 0; $g < 366; $g++) {
            Orologio::fingiAdesso(EPOCA + $g * 21600);
            $md = Orologio::data(Orologio::gts())->format('m-d');
            $valori[$md] = Eventi::chiacchiera();
        }
        vero(($valori['02-14'] ?? 1.0) > 1.9, 'San Valentino deve quasi raddoppiare: '
            . ($valori['02-14'] ?? 0));
        vero(($valori['09-03'] ?? 0.0) === 1.0, 'un giorno qualunque resta a uno');
    } finally {
        Orologio::fingiEpoca(null);
        Orologio::fingiAdesso(null);
    }
});

prova('il richiamo vale nel luogo dell\'evento', function () {
    Orologio::fingiEpoca(EPOCA);
    try {
        for ($g = 0; $g < 366; $g++) {
            Orologio::fingiAdesso(EPOCA + $g * 21600);
            if (Orologio::data(Orologio::gts())->format('m-d') === '07-25') {
                break;
            }
        }
        // Il festival d'estate è al tempio: lì la gente c'è, altrove no.
        vero(Eventi::richiamo('tempio') > 50, 'al tempio il festival tira: ' . Eventi::richiamo('tempio'));
        uguale(0, Eventi::richiamo('argine'), 'sull\'argine no');
    } finally {
        Orologio::fingiEpoca(null);
        Orologio::fingiAdesso(null);
    }
});

// --- pulizia ------------------------------------------------------------------
Database::run('DELETE FROM personaggi WHERE cognome = ?', [ucfirst(PREFISSO)]);

riepilogo();
