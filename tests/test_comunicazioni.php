<?php

declare(strict_types=1);

/**
 * Le comunicazioni fra la gestione e i giocatori.
 *
 * La cosa che conta non e' che i messaggi si scrivano — e' che si sappia
 * sempre CHI aspetta una risposta. Un canale in cui il conteggio dei non
 * letti sbaglia e' peggio di nessun canale: chi amministra smette di
 * guardarlo, e i giocatori scrivono nel vuoto.
 */

require __DIR__ . '/_prova.php';

use App\Core\Config;
use App\Core\Database;
use App\Core\GameConfig;
use App\Game\Comunicazioni;

Config::load(dirname(__DIR__));

const PREFISSO = 'zzcom';

function utente(string $suffisso): int
{
    $nome = PREFISSO . '-' . $suffisso;
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

function pulisci(): void
{
    Database::run(
        'DELETE FROM comunicazioni WHERE user_id IN (SELECT id FROM users WHERE username LIKE ?)',
        [PREFISSO . '-%']
    );
    Database::run('DELETE FROM mail_queue WHERE destinatario LIKE ?', [PREFISSO . '-%@example.invalid']);
}

pulisci();

// =============================================================================
titolo('Il filo');

prova('la gestione scrive e il giocatore lo trova non letto', function () {
    $u = utente('uno');
    vero(Comunicazioni::scrivi($u, null, true, 'Ciao, due parole sul tuo avatar.')['ok']);
    uguale(1, Comunicazioni::nonLetti($u));

    $filo = Comunicazioni::filo($u);
    uguale(1, count($filo));
    uguale(1, (int) $filo[0]['da_admin']);
    uguale(null, $filo[0]['letto_at']);
});

prova('aprendo la pagina si spengono solo i messaggi dell\'altra parte', function () {
    $u = utente('due');
    Comunicazioni::scrivi($u, null, true, 'Dalla gestione.');
    Comunicazioni::scrivi($u, $u, false, 'Dal giocatore.');

    // Il giocatore legge: si spegne il messaggio della gestione, NON il suo.
    Comunicazioni::segnaLetti($u, true);
    uguale(0, Comunicazioni::nonLetti($u));
    $filo = Comunicazioni::filo($u);
    $suo = array_values(array_filter($filo, static fn (array $m): bool => (int) $m['da_admin'] === 0));
    uguale(null, $suo[0]['letto_at'], 'il messaggio del giocatore lo segna la gestione, non lui');

    // Finche' la gestione non apre, quel filo risulta in attesa.
    vero(Comunicazioni::daLeggere() >= 1);
    Comunicazioni::segnaLetti($u, false);
    $filo = Comunicazioni::filo($u);
    $suo = array_values(array_filter($filo, static fn (array $m): bool => (int) $m['da_admin'] === 0));
    vero($suo[0]['letto_at'] !== null, 'adesso la gestione l\'ha letto');
});

prova('il filo arriva in ordine di tempo, dal piu\' vecchio', function () {
    $u = utente('tre');
    foreach (['primo', 'secondo', 'terzo'] as $t) {
        Comunicazioni::scrivi($u, null, true, $t);
    }
    uguale(['primo', 'secondo', 'terzo'],
        array_map(static fn (array $m): string => (string) $m['testo'], Comunicazioni::filo($u)));
});

prova('un messaggio vuoto non si manda', function () {
    $u = utente('quattro');
    $r = Comunicazioni::scrivi($u, null, true, "   \n  ");
    vero(!$r['ok'], 'uno spazio non è un messaggio');
    uguale(0, count(Comunicazioni::filo($u)));
});

prova('a un utente inesistente non si scrive', function () {
    vero(!Comunicazioni::scrivi(999999999, null, true, 'ciao')['ok']);
});

prova('un messaggio lunghissimo si taglia invece di far saltare la colonna', function () {
    $u = utente('cinque');
    Comunicazioni::scrivi($u, null, true, str_repeat('a', Comunicazioni::MAX + 500));
    uguale(Comunicazioni::MAX, mb_strlen((string) Comunicazioni::filo($u)[0]['testo']));
});

// =============================================================================
titolo('Chi aspetta una risposta');

prova('i fili in attesa vengono prima, non i piu\' recenti', function () {
    // Il filo vecchio ha una domanda senza risposta; quello nuovo no. In un
    // elenco cronologico il vecchio finirebbe in fondo proprio perche' e'
    // vecchio, che e' l'opposto di quello che serve.
    $vecchio = utente('vecchio');
    $nuovo   = utente('nuovo');
    Comunicazioni::scrivi($vecchio, $vecchio, false, 'Una domanda di tre giorni fa.');
    Comunicazioni::scrivi($nuovo, null, true, 'Un avviso di adesso.');

    $fili = array_values(array_filter(Comunicazioni::fili(),
        static fn (array $f): bool => str_starts_with((string) $f['username'], PREFISSO . '-')));
    uguale($vecchio, (int) $fili[0]['user_id'], 'in cima ci va chi aspetta');
    uguale(1, (int) $fili[0]['da_leggere']);
});

// =============================================================================
titolo('L\'avviso per posta');

prova('scrivendo al giocatore gli parte un\'e-mail, ma non col messaggio dentro', function () {
    $u = utente('posta');
    $prima = GameConfig::bool('posta.avvisa_comunicazioni', true);
    GameConfig::set('posta.avvisa_comunicazioni', '1');
    try {
        Comunicazioni::scrivi($u, null, true, 'Questo testo non deve finire per intero nella posta, '
            . 'perché letto fuori contesto prende un altro tono. ' . str_repeat('x', 400));
        $m = Database::first(
            'SELECT oggetto, corpo FROM mail_queue WHERE destinatario = ? ORDER BY id DESC LIMIT 1',
            [PREFISSO . '-posta@example.invalid']
        );
        vero($m !== null, 'l\'avviso doveva essere accodato');
        vero(str_contains((string) $m['corpo'], 'Comunicazioni'), 'deve dire dove leggerlo');
        vero(!str_contains((string) $m['corpo'], str_repeat('x', 400)),
            'il messaggio non va ricopiato per intero nella posta');
    } finally {
        GameConfig::set('posta.avvisa_comunicazioni', $prima ? '1' : '0');
    }
});

prova('col richiamo spento non parte niente', function () {
    $u = utente('muto');
    $prima = GameConfig::bool('posta.avvisa_comunicazioni', true);
    GameConfig::set('posta.avvisa_comunicazioni', '0');
    try {
        Comunicazioni::scrivi($u, null, true, 'in silenzio');
        $m = Database::first('SELECT id FROM mail_queue WHERE destinatario = ?',
            [PREFISSO . '-muto@example.invalid']);
        uguale(null, $m, 'la leva spenta deve valere');
    } finally {
        GameConfig::set('posta.avvisa_comunicazioni', $prima ? '1' : '0');
    }
});

prova('la risposta del giocatore non manda e-mail a nessuno', function () {
    // Sarebbe un'e-mail a se stesso: il giocatore sa gia' di aver scritto.
    $u = utente('rispondo');
    Comunicazioni::scrivi($u, $u, false, 'Rispondo io.');
    uguale(null, Database::first('SELECT id FROM mail_queue WHERE destinatario = ?',
        [PREFISSO . '-rispondo@example.invalid']));
});

// --- pulizia ------------------------------------------------------------------
pulisci();
Database::run('DELETE FROM users WHERE username LIKE ?', [PREFISSO . '-%']);

riepilogo();
