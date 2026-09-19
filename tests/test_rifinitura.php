<?php

declare(strict_types=1);

/**
 * F7 — la rifinitura: diario, illustrazioni, PWA, bilanciamento.
 *
 * Sono pezzi che si vedono a occhio, e proprio per questo vanno provati: una
 * pagina brutta la nota chiunque, un SVG malformato no — il browser lo
 * scarta in silenzio e resta un buco bianco.
 */

require __DIR__ . '/_prova.php';

use App\Cli\Bilancio;
use App\Core\Config;
use App\Core\Database;
use App\Game\Diario;
use App\Game\Illustrazione;
use App\Sim\Luoghi;
use App\Sim\Orologio;

Config::load(dirname(__DIR__));

const PREFISSO = 'zzrif';
const EPOCA    = 1735689600;

/** Un utente usa e getta, per i vincoli che ne pretendono uno vero. */
function utenteDiProva(): int
{
    $nome = PREFISSO . '-utente';
    $u = Database::first('SELECT id FROM users WHERE username = ?', [$nome]);
    if ($u !== null) {
        return (int) $u['id'];
    }
    Database::run(
        'INSERT INTO users (username, email, password_hash, status) VALUES (?, ?, ?, ?)',
        [$nome, $nome . '@example.invalid', 'x', 'active']
    );
    return (int) Database::lastInsertId();
}

function attore(string $nome, array $campi = []): array
{
    $c = array_merge(['esper' => 1, 'sesso' => 'm', 'luogo' => 'gradini'], $campi);
    Database::run(
        'INSERT INTO personaggi (user_id, nome, cognome, sesso, sezione, anno, scheda, stato,
             luogo, anno_nascita, nato_mese, nato_giorno, esper,
             rissa, testa, dai_suki, cuore, pf, pf_max, pp, pp_max, compostezza, compostezza_max, aspetto)
         VALUES (NULL, ?, ?, ?, ?, 2, ?, ?, ?, 1970, 5, 5, ?, 8, 10, 9, 7, 11, 11, 9, 9, 6, 6, ?)',
        [$nome, ucfirst(PREFISSO), $c['sesso'], 'superiori', 'completa', 'attivo',
         $c['luogo'], $c['esper'], 'Uno che si nota poco.']
    );
    return Database::first('SELECT * FROM personaggi WHERE id = ?', [(int) Database::lastInsertId()]);
}

// =============================================================================
titolo('Il diario');

prova('il diario di un personaggio vuoto sta comunque in piedi', function () {
    $pg = attore('Nuovo');
    $d  = Diario::componi($pg);
    vero(str_contains($d, 'Nuovo'), 'deve nominare il personaggio');
    vero(str_contains($d, '## La scheda'), 'deve avere la scheda');
    vero(str_contains($d, 'Izumi Matsumoto'), 'deve chiudere con l\'attribuzione');
    vero(mb_strlen($d) > 400, 'un diario di quattro righe non serve a niente');
});

prova('il nome del file e\' usabile su qualunque sistema', function () {
    $pg = attore('Nome Con Spazi');
    $n  = Diario::nomeFile($pg);
    uguale(1, preg_match('/^[a-z0-9.\-]+$/', $n) ? 1 : 0, "nome poco portabile: {$n}");
    vero(str_ends_with($n, '.md'));
});

prova('un non-esper non finge di avere poteri', function () {
    $pg = attore('Umano', ['esper' => 0]);
    $d  = Diario::componi($pg);
    vero(str_contains($d, 'Nessuno, e non è una mancanza'),
        'il diario di chi non ha poteri deve dirlo, non lasciare un buco');
});

prova('il diario di chi ha traslocato lo dice', function () {
    $pg = attore('Partito');
    Database::run('UPDATE personaggi SET stato = ? WHERE id = ?', ['trasferito', (int) $pg['id']]);
    $pg = Database::first('SELECT * FROM personaggi WHERE id = ?', [(int) $pg['id']]);
    vero(str_contains(Diario::componi($pg), 'Se n\'è andato'), 'il trasloco va detto');
});

prova('i ricordi finiscono nel diario', function () {
    // `ricordi.user_id` ha un vincolo verso `users`: i ricordi sopravvivono
    // al personaggio e restano al giocatore, quindi serve un utente vero.
    $pg = attore('Ricordante');
    $uid = utenteDiProva();
    Database::run(
        'INSERT INTO ricordi (personaggio_id, user_id, titolo, testo, gts, luogo)
         VALUES (?, ?, ?, ?, ?, ?)',
        [(int) $pg['id'], $uid, 'Il pomeriggio dei funghi', 'Nessuno sapeva più mentire.',
         Orologio::lineare(), 'abcb']
    );
    $d = Diario::componi($pg);
    vero(str_contains($d, 'Il pomeriggio dei funghi'), 'manca il titolo del ricordo');
    vero(str_contains($d, 'Nessuno sapeva più mentire'), 'manca il testo del ricordo');
});

// =============================================================================
titolo('Le illustrazioni');

prova('ogni luogo, in ogni stagione e a ogni ora, produce un SVG valido', function () {
    // Un SVG malformato non da' errore: il browser lo scarta e resta un buco
    // bianco. E' il tipo di guasto che si scopre mesi dopo.
    Orologio::fingiEpoca(EPOCA);
    try {
        Orologio::fingiAdesso(EPOCA + 5400);
        $g = Orologio::lineare();
        libxml_use_internal_errors(true);
        $n = $ko = 0;
        foreach (array_keys(Luoghi::tutti()) as $lkey) {
            foreach ([0, 90, 180, 270] as $giorno) {
                foreach ([3, 10, 17, 23] as $ora) {
                    $n++;
                    $svg = Illustrazione::per([
                        'id' => $n, 'luogo' => $lkey, 'gts' => $g + $giorno * 86400 + $ora * 3600,
                    ]);
                    $doc = new DOMDocument();
                    if (!$doc->loadXML($svg)) {
                        $ko++;
                    }
                    libxml_clear_errors();
                }
            }
        }
        vero($n >= 250, "campione troppo piccolo: {$n}");
        uguale(0, $ko, "{$ko} illustrazioni su {$n} non sono XML valido");
    } finally {
        libxml_use_internal_errors(false);
        Orologio::fingiEpoca(null);
        Orologio::fingiAdesso(null);
    }
});

prova('la stessa scena da\' sempre la stessa figura', function () {
    $r = ['id' => 42, 'luogo' => 'tempio', 'gts' => Orologio::lineare()];
    uguale(Illustrazione::per($r), Illustrazione::per($r),
        'due ricordi identici devono dare due immagini identiche');
});

prova('scene diverse danno figure diverse', function () {
    $g = Orologio::lineare();
    $a = Illustrazione::per(['id' => 1, 'luogo' => 'tempio',   'gts' => $g]);
    $b = Illustrazione::per(['id' => 1, 'luogo' => 'spiaggia', 'gts' => $g]);
    $c = Illustrazione::per(['id' => 1, 'luogo' => 'tempio',   'gts' => $g + 180 * 86400]);
    vero($a !== $b, 'due luoghi diversi devono vedersi diversi');
    vero($a !== $c, 'la stessa piazza a sei mesi di distanza deve vedersi diversa');
});

prova('l\'illustrazione ha un testo per chi non la vede', function () {
    $svg = Illustrazione::per(['id' => 7, 'luogo' => 'gradini', 'gts' => Orologio::lineare()]);
    vero(str_contains($svg, 'role="img"'), 'serve il ruolo');
    vero(str_contains($svg, 'aria-label="'), 'serve l\'etichetta');
    vero(str_contains($svg, 'Cento Gradini'), 'l\'etichetta deve nominare il luogo');
});

prova('un luogo sconosciuto non manda in pezzi il disegno', function () {
    $svg = Illustrazione::per(['id' => 9, 'luogo' => 'non_esiste', 'gts' => Orologio::lineare()]);
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $ok = $doc->loadXML($svg);
    libxml_clear_errors();
    libxml_use_internal_errors(false);
    vero($ok, 'anche il ripiego deve essere XML valido');
});

// =============================================================================
titolo('La PWA');

prova('il manifesto e\' JSON valido e si regge sui propri percorsi', function () {
    $f = dirname(__DIR__) . '/assets/manifest.webmanifest';
    vero(is_file($f), 'manca il manifesto');
    $m = json_decode((string) file_get_contents($f), true);
    vero(is_array($m), 'il manifesto non e\' JSON valido');
    foreach (['name', 'short_name', 'start_url', 'scope', 'display', 'icons'] as $k) {
        vero(isset($m[$k]), "manca la voce «{$k}»");
    }
    // I percorsi sono relativi al punto in cui il manifesto viene servito,
    // cioe' la radice dell'applicazione: nessuno deve cominciare con «/».
    foreach ($m['icons'] as $i) {
        vero(!str_starts_with((string) $i['src'], '/'),
            'un percorso assoluto rompe l\'installazione sotto un sottopercorso: ' . $i['src']);
        vero(is_file(dirname(__DIR__) . '/' . $i['src']), 'icona mancante: ' . $i['src']);
    }
});

prova('l\'icona e\' un SVG valido', function () {
    $f = dirname(__DIR__) . '/assets/img/icona.svg';
    vero(is_file($f), 'manca l\'icona');
    $d = new DOMDocument();
    libxml_use_internal_errors(true);
    $ok = $d->loadXML((string) file_get_contents($f));
    libxml_clear_errors();
    libxml_use_internal_errors(false);
    vero($ok, 'l\'icona non e\' XML valido');
});

prova('il service worker non mette in cache le pagine di gioco', function () {
    // E' la regola che conta: il quartiere cambia ogni minuto, e una pagina
    // salvata direbbe bugie su dove si trovano gli altri.
    $sw = (string) file_get_contents(dirname(__DIR__) . '/assets/js/sw.js');
    vero(str_contains($sw, 'statico'), 'deve distinguere gli statici dal resto');
    vero(str_contains($sw, 'fetch(req).catch'), 'il resto deve andare in rete per primo');
});

prova('lo script di registrazione non e\' inline', function () {
    // La Content-Security-Policy dice script-src 'self': uno script dentro la
    // pagina non verrebbe mai eseguito, e il browser lo direbbe solo in
    // console. Ci si e' gia' inciampati una volta.
    $layout = (string) file_get_contents(dirname(__DIR__) . '/views/layout.php');
    vero(!str_contains($layout, 'serviceWorker.register'),
        'la registrazione deve stare in un file, non nel layout');
    vero(is_file(dirname(__DIR__) . '/assets/js/pwa.js'), 'manca assets/js/pwa.js');
});

// =============================================================================
titolo('Il bilanciamento');

prova('il rapporto gira e non lascia niente dietro', function () {
    $prima = (int) Database::first('SELECT COUNT(*) n FROM personaggi')['n'];
    $righe = (new Bilancio(40))->esegui();
    $dopo  = (int) Database::first('SELECT COUNT(*) n FROM personaggi')['n'];

    uguale($prima, $dopo, 'il rapporto deve cancellare tutto quello che crea');
    vero(count($righe) > 30, 'un rapporto di tre righe non dice niente');

    $testo = implode("\n", $righe);
    foreach (['LE SCHEDE', 'I POTERI', 'FARSI NOTARE', 'QUANTA GENTE', 'IL TRASLOCO'] as $sezione) {
        vero(str_contains($testo, $sezione), "manca la sezione «{$sezione}»");
    }
});

prova('il rapporto non si accontenta di schede a zero', function () {
    // La prima versione distribuiva i punti abilita' sforando il tetto,
    // finalizza() rifiutava, e il rapporto dichiarava punti ferita mediani a
    // zero dando la colpa al gioco. Adesso o la scheda si chiude o lo dice.
    $testo = implode("\n", (new Bilancio(40))->esegui());
    vero(!str_contains($testo, 'non si sono chiuse'),
        'qualche scheda non si e\' chiusa: il campione sarebbe falsato');
    vero(preg_match('/Punti ferita\s+min\s+([1-9]\d*)/u', $testo) === 1,
        'i punti ferita minimi devono essere maggiori di zero');
});

// --- pulizia ------------------------------------------------------------------
Database::run('DELETE FROM personaggi WHERE cognome = ?', [ucfirst(PREFISSO)]);
Database::run('DELETE FROM users WHERE username = ?', [PREFISSO . '-utente']);

riepilogo();
