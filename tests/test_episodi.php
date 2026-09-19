<?php

declare(strict_types=1);

/**
 * Gli episodi: apertura dalle condizioni del mondo, turni, agente autonomo,
 * effetti, chiusura e ricordi.
 *
 *   php tests/test_episodi.php
 */

require __DIR__ . '/_prova.php';

use App\Core\Config;
use App\Core\Database;
use App\Core\GameConfig;
use App\Game\Episodi;
use App\Game\Legami;
use App\Game\Personaggio;
use App\Game\Scheda;
use App\Sim\Orologio;
use App\Sim\Scuola;

Config::load(dirname(__DIR__));

const PREFISSO = 'zzep';
const PALCO = 'albero';
const LIMBO = 'casa_ayukawa';
$creati = [];

function attore(string $nome, array $ab = []): array
{
    global $creati;
    $u = PREFISSO . ' ' . $nome;
    Database::run('INSERT INTO users (username, email, password_hash, status) VALUES (?, ?, ?, ?)',
        [$u, md5($u) . '@zzep.invalid', 'x', 'active']);
    $uid = Database::lastInsertId();
    $creati[] = $uid;
    $r = Personaggio::crea($uid, $nome, ucfirst(PREFISSO), 'f', Scuola::SUPERIORI, 2, 6, 15, false);
    if (!$r['ok']) { throw new RuntimeException($r['error'] ?? '?'); }
    $id = (int) $r['id'];
    Scheda::assicuraTiri(Database::first('SELECT * FROM personaggi WHERE id = ?', [$id]));
    $ab += ['cuore' => 8, 'testa' => 8, 'dai_suki' => 8, 'rissa' => 8];
    Database::run(
        'UPDATE personaggi SET cuore=?, testa=?, dai_suki=?, rissa=?, kakko=5,
                pf_max=12, pf=12, compostezza_max=8, compostezza=8,
                scheda=?, luogo=?, verso=NULL WHERE id=?',
        [$ab['cuore'], $ab['testa'], $ab['dai_suki'], $ab['rissa'], 'completa', LIMBO, $id]
    );
    return Database::first('SELECT * FROM personaggi WHERE id = ?', [$id]);
}

function scena(array ...$chi): array
{
    Database::run('UPDATE personaggi SET luogo = ? WHERE luogo = ? AND cognome = ?',
        [LIMBO, PALCO, ucfirst(PREFISSO)]);
    $out = [];
    foreach ($chi as $pg) {
        Database::run('UPDATE personaggi SET luogo = ?, verso = NULL WHERE id = ?', [PALCO, (int) $pg['id']]);
        $out[] = Database::first('SELECT * FROM personaggi WHERE id = ?', [(int) $pg['id']]);
    }
    return $out;
}

/** Un copione di prova, così le prove non dipendono dal contenuto vero. */
function copioneDiProva(string $ckey, array $scene, array $opt = []): void
{
    Database::run(
        'INSERT INTO copioni (ckey, titolo, occhiello, premessa, luogo, condizione,
                              min_cast, max_cast, finestra, scene, attivo)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
         ON DUPLICATE KEY UPDATE scene = VALUES(scene), condizione = VALUES(condizione),
                                 min_cast = VALUES(min_cast), luogo = VALUES(luogo),
                                 finestra = VALUES(finestra), attivo = 1',
        [$ckey, 'Prova ' . $ckey, 'prova', 'Una scena di prova.', PALCO,
         $opt['condizione'] ?? 'sempre', $opt['min_cast'] ?? 2, $opt['max_cast'] ?? 5,
         $opt['finestra'] ?? 1800, json_encode($scene, JSON_UNESCAPED_UNICODE)]
    );
}

function pulisci(): void
{
    global $creati;
    try { Database::run('DELETE FROM episodi WHERE ckey LIKE ?', ['zz%']); } catch (Throwable) {}
    try { Database::run('DELETE FROM copioni WHERE ckey LIKE ?', ['zz%']); } catch (Throwable) {}
    foreach ($creati as $uid) {
        try { Database::run('DELETE FROM users WHERE id = ?', [$uid]); } catch (Throwable) {}
    }
    try { Database::run('DELETE FROM users WHERE username LIKE ?', [PREFISSO . '%']); } catch (Throwable) {}
    try { Database::run('DELETE FROM tracce WHERE testo LIKE ?', ['%' . ucfirst(PREFISSO) . '%']); } catch (Throwable) {}
    $creati = [];
}
register_shutdown_function('pulisci');

/** Scene minime riusabili. */
$unaScena = [[
  'testo' => 'Succede una cosa.',
  'opzioni' => [
    ['k' => 'cuore', 'testo' => 'La scelta di chi ha coraggio', 'prova' => 'nessuna', 'difficolta' => 0,
     'ok' => 'Lo dici.', 'ko' => '', 'effetti_ok' => ['affetto_cast' => 6], 'effetti_ko' => [],
     'peso' => ['cuore' => 5]],
    ['k' => 'testa', 'testo' => 'La scelta di chi ragiona', 'prova' => 'nessuna', 'difficolta' => 0,
     'ok' => 'Trovi una scappatoia.', 'ko' => '', 'effetti_ok' => ['compostezza' => -1], 'effetti_ko' => [],
     'peso' => ['testa' => 5]],
  ],
]];

// --- Apertura ---------------------------------------------------------------

prova('un episodio si apre quando le condizioni ci sono', function () use ($unaScena) {
    copioneDiProva('zzapre', $unaScena, ['condizione' => 'sempre', 'min_cast' => 2]);
    Database::run('UPDATE copioni SET attivo = 0 WHERE ckey NOT LIKE ?', ['zz%']);
    scena(attore('A1'), attore('A2'));
    Database::run('DELETE FROM episodi');

    $n = Episodi::verificaAperture();
    vero($n >= 1, 'doveva aprirsi qualcosa');
    $ep = Database::first('SELECT * FROM episodi WHERE ckey = ?', ['zzapre']);
    vero($ep !== null, 'e doveva essere il nostro copione');
    uguale(PALCO, $ep['luogo']);
    uguale(2, count(Episodi::cast((int) $ep['id'])));
});

prova('senza abbastanza gente non si apre niente', function () use ($unaScena) {
    copioneDiProva('zzsoli', $unaScena, ['condizione' => 'sempre', 'min_cast' => 3]);
    Database::run('UPDATE copioni SET attivo = 0 WHERE ckey <> ?', ['zzsoli']);
    Database::run('DELETE FROM episodi');
    scena(attore('S1'));
    Episodi::verificaAperture();
    uguale(null, Database::first('SELECT id FROM episodi WHERE ckey = ?', ['zzsoli']));
});

prova('una condizione non soddisfatta tiene chiuso il copione', function () use ($unaScena) {
    copioneDiProva('zzmai', $unaScena, ['condizione' => 'neve']);
    Database::run('UPDATE copioni SET attivo = 0 WHERE ckey <> ?', ['zzmai']);
    Database::run('DELETE FROM episodi');
    scena(attore('N1'), attore('N2'));
    Episodi::verificaAperture();
    $c = \App\Sim\Meteo::a();
    if (!$c['neve']) {
        uguale(null, Database::first('SELECT id FROM episodi WHERE ckey = ?', ['zzmai']),
            'non nevica: non si apre');
    } else {
        vero(true, '(sta nevicando: prova saltata)');
    }
});

prova('chi è già in un episodio non entra in un altro', function () use ($unaScena) {
    copioneDiProva('zzuno', $unaScena);
    copioneDiProva('zzdue', $unaScena);
    Database::run('UPDATE copioni SET attivo = 0 WHERE ckey NOT IN (?, ?)', ['zzuno', 'zzdue']);
    Database::run('DELETE FROM episodi');
    scena(attore('D1'), attore('D2'));
    Episodi::verificaAperture();
    $tot = (int) Database::first('SELECT COUNT(*) n FROM episodio_cast ec
        JOIN episodi e ON e.id = ec.episodio_id WHERE e.ckey LIKE ?', ['zz%'])['n'];
    uguale(2, $tot, 'le due persone stanno in un episodio solo, non in due');
});

// --- Giocare -----------------------------------------------------------------

prova('si sceglie una volta sola per scena', function () use ($unaScena) {
    copioneDiProva('zzscelta', $unaScena);
    Database::run('UPDATE copioni SET attivo = 0 WHERE ckey <> ?', ['zzscelta']);
    Database::run('DELETE FROM episodi');
    [$a, $b] = scena(attore('C1'), attore('C2'));
    Episodi::verificaAperture();
    $ep = Database::first('SELECT * FROM episodi WHERE ckey = ?', ['zzscelta']);

    $r = Episodi::scegli($a, (int) $ep['id'], 'cuore');
    vero($r['ok'], $r['error'] ?? '');
    $r = Episodi::scegli($a, (int) $ep['id'], 'testa');
    vero(!$r['ok'], 'la seconda scelta va respinta');
});

prova('un\'opzione che non esiste viene respinta', function () use ($unaScena) {
    copioneDiProva('zzfinta', $unaScena);
    Database::run('UPDATE copioni SET attivo = 0 WHERE ckey <> ?', ['zzfinta']);
    Database::run('DELETE FROM episodi');
    [$a] = scena(attore('F1'), attore('F2'));
    Episodi::verificaAperture();
    $ep = Database::first('SELECT * FROM episodi WHERE ckey = ?', ['zzfinta']);
    vero(!Episodi::scegli($a, (int) $ep['id'], 'inventata')['ok']);
});

prova('chi non è del cast non può scegliere', function () use ($unaScena) {
    copioneDiProva('zzestraneo', $unaScena);
    Database::run('UPDATE copioni SET attivo = 0 WHERE ckey <> ?', ['zzestraneo']);
    Database::run('DELETE FROM episodi');
    scena(attore('E1'), attore('E2'));
    Episodi::verificaAperture();
    $ep = Database::first('SELECT * FROM episodi WHERE ckey = ?', ['zzestraneo']);
    $fuori = attore('Fuori');
    vero(!Episodi::scegli($fuori, (int) $ep['id'], 'cuore')['ok']);
});

prova('quando hanno scelto tutti, la scena si chiude subito', function () use ($unaScena) {
    copioneDiProva('zzchiude', $unaScena);
    Database::run('UPDATE copioni SET attivo = 0 WHERE ckey <> ?', ['zzchiude']);
    Database::run('DELETE FROM episodi');
    [$a, $b] = scena(attore('T1'), attore('T2'));
    Episodi::verificaAperture();
    $ep = Database::first('SELECT * FROM episodi WHERE ckey = ?', ['zzchiude']);

    Episodi::scegli($a, (int) $ep['id'], 'cuore');
    uguale('aperto', Database::first('SELECT stato FROM episodi WHERE id = ?', [(int) $ep['id']])['stato'],
        'con una scelta sola si aspetta ancora');
    Episodi::scegli($b, (int) $ep['id'], 'cuore');
    // Una scena sola: chiudendola si chiude l'episodio.
    uguale('concluso', Database::first('SELECT stato FROM episodi WHERE id = ?', [(int) $ep['id']])['stato']);
});

// --- L'agente autonomo --------------------------------------------------------------

prova('L\'AGENTE AUTONOMO sceglie in carattere, non a caso', function () use ($unaScena) {
    // Chi ha Cuore alto e Testa bassa deve tendere alla scelta «di cuore», e
    // viceversa. Non sempre — c'è un pizzico di imprevedibilità — ma spesso.
    $opzioni = $unaScena[0]['opzioni'];
    $dicuore = $ditesta = 0;
    for ($i = 0; $i < 30; $i++) {
        $coraggioso = ['id' => 9000 + $i, 'cuore' => 15, 'testa' => 2];
        $prudente   = ['id' => 9500 + $i, 'cuore' => 2,  'testa' => 15];
        if (Episodi::agenteAutonomo($coraggioso, $opzioni, 1, $i) === 'cuore') { $dicuore++; }
        if (Episodi::agenteAutonomo($prudente, $opzioni, 1, $i) === 'testa')   { $ditesta++; }
    }
    vero($dicuore >= 25, "chi ha Cuore 15 ha scelto di cuore solo {$dicuore} volte su 30");
    vero($ditesta >= 25, "chi ha Testa 15 ha scelto di testa solo {$ditesta} volte su 30");
});

prova('la scena scaduta va avanti lo stesso, e lo annota', function () use ($unaScena) {
    copioneDiProva('zzscade', $unaScena, ['finestra' => 1800]);
    Database::run('UPDATE copioni SET attivo = 0 WHERE ckey <> ?', ['zzscade']);
    Database::run('DELETE FROM episodi');
    scena(attore('Z1'), attore('Z2'));
    Episodi::verificaAperture();
    $ep = Database::first('SELECT * FROM episodi WHERE ckey = ?', ['zzscade']);

    // Nessuno sceglie, e il tempo scade.
    Database::run('UPDATE episodi SET scade_reale = ? WHERE id = ?',
        [Orologio::adessoReale() - 10, (int) $ep['id']]);
    uguale(1, Episodi::scadute());

    $scelte = Database::all('SELECT * FROM episodio_scelte WHERE episodio_id = ?', [(int) $ep['id']]);
    uguale(2, count($scelte), 'ha scelto per entrambi');
    foreach ($scelte as $s) {
        uguale(1, (int) $s['da_solo'], 'ed è segnato che non c\'erano');
    }
});

// --- Gli effetti e la fine ---------------------------------------------------------------

prova('gli effetti di una scena arrivano sui legami', function () use ($unaScena) {
    copioneDiProva('zzeffetti', $unaScena);
    Database::run('UPDATE copioni SET attivo = 0 WHERE ckey <> ?', ['zzeffetti']);
    Database::run('DELETE FROM episodi');
    [$a, $b] = scena(attore('G1'), attore('G2'));
    Episodi::verificaAperture();
    $ep = Database::first('SELECT * FROM episodi WHERE ckey = ?', ['zzeffetti']);

    uguale(0, Legami::fra((int) $b['id'], (int) $a['id'])['affetto']);
    Episodi::scegli($a, (int) $ep['id'], 'cuore');
    Episodi::scegli($b, (int) $ep['id'], 'testa');
    vero(Legami::fra((int) $b['id'], (int) $a['id'])['affetto'] > 0,
        'chi ha scelto «di cuore» ha scaldato il cast');
});

prova('alla fine ognuno si porta via il suo ricordo', function () use ($unaScena) {
    copioneDiProva('zzricordo', $unaScena);
    Database::run('UPDATE copioni SET attivo = 0 WHERE ckey <> ?', ['zzricordo']);
    Database::run('DELETE FROM episodi');
    [$a, $b] = scena(attore('R1'), attore('R2'));
    Episodi::verificaAperture();
    $ep = Database::first('SELECT * FROM episodi WHERE ckey = ?', ['zzricordo']);
    Episodi::scegli($a, (int) $ep['id'], 'cuore');
    Episodi::scegli($b, (int) $ep['id'], 'testa');

    foreach ([$a, $b] as $pg) {
        $r = Database::first('SELECT * FROM ricordi WHERE personaggio_id = ?', [(int) $pg['id']]);
        vero($r !== null, 'manca il ricordo di ' . $pg['nome']);
        vero(str_contains((string) $r['testo'], 'Con '), 'il ricordo dice con chi eri');
    }
    // Ma sono due ricordi DIVERSI: ciascuno ha la propria versione.
    $ra = Database::first('SELECT testo FROM ricordi WHERE personaggio_id = ?', [(int) $a['id']]);
    $rb = Database::first('SELECT testo FROM ricordi WHERE personaggio_id = ?', [(int) $b['id']]);
    vero($ra['testo'] !== $rb['testo'], 'ognuno ricorda la propria serata');
});

prova('IL RICORDO SOPRAVVIVE AL TRASLOCO', function () use ($unaScena) {
    // È il senso dell'album: le persone si perdono, i posti si perdono,
    // quello che è successo no. Il ricordo è legato all'ACCOUNT, non al
    // personaggio, che può sparire dal quartiere.
    copioneDiProva('zztrasloco', $unaScena);
    Database::run('UPDATE copioni SET attivo = 0 WHERE ckey <> ?', ['zztrasloco']);
    Database::run('DELETE FROM episodi');
    [$a, $b] = scena(attore('P1'), attore('P2'));
    Episodi::verificaAperture();
    $ep = Database::first('SELECT * FROM episodi WHERE ckey = ?', ['zztrasloco']);
    Episodi::scegli($a, (int) $ep['id'], 'cuore');
    Episodi::scegli($b, (int) $ep['id'], 'cuore');

    $uid = (int) Database::first('SELECT user_id FROM personaggi WHERE id = ?', [(int) $a['id']])['user_id'];
    uguale(1, count(Episodi::ricordi($uid)));

    \App\Game\Segreto::trasloca(Database::first('SELECT * FROM personaggi WHERE id = ?', [(int) $a['id']]));
    uguale(1, count(Episodi::ricordi($uid)), 'il ricordo resta al giocatore');
});

// Si rimettono in piedi i copioni veri, che le prove avevano spento.
Database::run('UPDATE copioni SET attivo = 1 WHERE ckey NOT LIKE ?', ['zz%']);

riepilogo();
