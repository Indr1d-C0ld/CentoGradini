<?php

declare(strict_types=1);

/**
 * Il mondo da capo (bin/console.php mondo:ricomincia).
 *
 * ATTENZIONE: questa prova azzera davvero lo stato del gioco del database su
 * cui gira — che per le prove e' quello di sviluppo. Rimette a posto l'epoca
 * del calendario che c'era prima, ma legami, voci, ricordi e il resto del
 * quartiere di sviluppo li cancella: e' il suo mestiere.
 */

require __DIR__ . '/_prova.php';

use App\Cli\Ricomincia;
use App\Core\Config;
use App\Core\Database;
use App\Core\GameConfig;
use App\Game\Legami;
use App\Sim\Orologio;

Config::load(dirname(__DIR__));

const PREFISSO = 'zzric';

function pulisci(): void
{
    Database::run('DELETE FROM personaggi WHERE cognome = ?', [ucfirst(PREFISSO)]);
}
pulisci();

$epocaPrima = (string) GameConfig::get('clock.epoch_real', '');

prova('si azzera quello che e\' successo, e si tengono personaggi e poteri', function () {
    Database::run(
        "INSERT INTO personaggi (user_id, nome, cognome, sesso, sezione, anno, scheda, stato, luogo,
            anno_nascita, nato_mese, nato_giorno, esper, pf, pf_max, pp, pp_max, compostezza, compostezza_max, traslochi)
         VALUES (NULL, 'Reduce', ?, 'f', 'superiori', 2, 'completa', 'trasferito', 'parco', 1970, 5, 5, 1, 2, 11, 0, 9, 0, 6, 1)",
        [ucfirst(PREFISSO)]);
    $id = (int) Database::lastInsertId();
    Database::run("INSERT INTO personaggio_poteri (personaggio_id, pkey, primario, controllo, usi) VALUES (?, 'telecinesi', 1, 70, 12)", [$id]);
    $madoka = Database::first("SELECT id FROM personaggi WHERE png = 'madoka'");
    Legami::muovi($id, (int) $madoka['id'], 50, 10);
    Database::run("INSERT INTO tracce (luogo, gts, personaggio_id, tipo, testo) VALUES ('parco', 1, ?, 'prova', 'prova')", [$id]);
    Database::run("UPDATE oggetti_unici SET detentore_id = ?, luogo = NULL WHERE okey = 'cappello'", [$id]);

    $adesso = time();
    $conti = Ricomincia::esegui($adesso);

    foreach (Ricomincia::TABELLE as $t) {
        if (in_array($t, ['presenze', 'club_membri', 'legami'], true)) {
            continue;   // queste si riempiono subito di nuovo: presenze dei giocatori, club e legami del canone
        }
        uguale(0, (int) Database::first("SELECT COUNT(*) n FROM {$t}")['n'], "{$t} non e' vuota");
    }
    uguale(0, (int) Database::first('SELECT COUNT(*) n FROM legami WHERE da_id = ? OR a_id = ?', [$id, $id])['n'],
        'i legami di chi gioca ripartono da zero');
    vero(Legami::fra(
        (int) Database::first("SELECT id FROM personaggi WHERE png = 'kyosuke'")['id'], (int) $madoka['id'])['affetto'] > 50,
        'quelli del canone ci sono di nuovo');

    $pg = Database::first('SELECT * FROM personaggi WHERE id = ?', [$id]);
    uguale('attivo', $pg['stato'], 'chi era via torna');
    uguale('gradini', $pg['luogo']);
    uguale(9, (int) $pg['pp']);
    uguale(11, (int) $pg['pf']);
    uguale(6, (int) $pg['compostezza']);
    uguale(0, (int) $pg['traslochi']);
    $potere = Database::first('SELECT * FROM personaggio_poteri WHERE personaggio_id = ?', [$id]);
    uguale(['telecinesi', 10, 0], [$potere['pkey'], (int) $potere['controllo'], (int) $potere['usi']], 'il potere resta, il Controllo riparte');

    $cappello = Database::first("SELECT * FROM oggetti_unici WHERE okey = 'cappello'");
    uguale([null, 'gradini'], [$cappello['detentore_id'], $cappello['luogo']]);

    uguale('1987-04-06 07:00', Orologio::data(Orologio::lineare($adesso))->format('Y-m-d H:i'), 'il calendario riparte');
    uguale((int) $pg['arrivato_gts'], Orologio::lineare($adesso), 'e gli istanti dei personaggi sono quelli nuovi');
    vero(($conti['giocatori'] ?? 0) >= 1);
});

GameConfig::set('clock.epoch_real', $epocaPrima);
pulisci();
riepilogo();
