<?php

declare(strict_types=1);

/**
 * Le relazioni: i due assi, i gesti ambigui, la gelosia, il chiarimento,
 * la confessione, il cappello.
 *
 *   php tests/test_legami.php
 */

require __DIR__ . '/_prova.php';

use App\Core\Config;
use App\Core\Database;
use App\Core\GameConfig;
use App\Game\Legami;
use App\Game\Personaggio;
use App\Game\Scheda;
use App\Sim\Orologio;
use App\Sim\Scuola;

Config::load(dirname(__DIR__));

const PREFISSO = 'zzleg';
/** Palco senza folla e limbo, come nelle prove del Segreto. */
const PALCO = 'albero';
const LIMBO = 'casa_ayukawa';

$creati = [];

function attore(string $nome, array $ab = []): array
{
    global $creati;
    $u = PREFISSO . ' ' . $nome;
    Database::run('INSERT INTO users (username, email, password_hash, status) VALUES (?, ?, ?, ?)',
        [$u, md5($u) . '@zzleg.invalid', 'x', 'active']);
    $uid = Database::lastInsertId();
    $creati[] = $uid;
    $r = Personaggio::crea($uid, $nome, ucfirst(PREFISSO), 'f', Scuola::SUPERIORI, 2, 6, 15, false);
    if (!$r['ok']) {
        throw new RuntimeException($r['error'] ?? 'creazione fallita');
    }
    $id = (int) $r['id'];
    Scheda::assicuraTiri(Database::first('SELECT * FROM personaggi WHERE id = ?', [$id]));
    $ab += ['cuore' => 8, 'testa' => 8];
    Database::run(
        'UPDATE personaggi SET cuore=?, testa=?, compostezza=8, compostezza_max=8,
                scheda=?, luogo=?, verso=NULL WHERE id=?',
        [$ab['cuore'], $ab['testa'], 'completa', LIMBO, $id]
    );
    return Database::first('SELECT * FROM personaggi WHERE id = ?', [$id]);
}

function sgombra(): void
{
    Database::run('UPDATE personaggi SET luogo = ? WHERE luogo = ? AND cognome = ?',
        [LIMBO, PALCO, ucfirst(PREFISSO)]);
}

function suPalco(array $pg): array
{
    Database::run('UPDATE personaggi SET luogo = ?, verso = NULL WHERE id = ?', [PALCO, (int) $pg['id']]);
    return Database::first('SELECT * FROM personaggi WHERE id = ?', [(int) $pg['id']]);
}

function ric(array $pg): array
{
    return Database::first('SELECT * FROM personaggi WHERE id = ?', [(int) $pg['id']]);
}

function pulisci(): void
{
    global $creati;
    foreach ($creati as $uid) {
        try { Database::run('DELETE FROM users WHERE id = ?', [$uid]); } catch (Throwable) {}
    }
    try { Database::run('DELETE FROM users WHERE username LIKE ?', [PREFISSO . '%']); } catch (Throwable) {}
    try { Database::run('DELETE FROM tracce WHERE testo LIKE ?', ['%' . ucfirst(PREFISSO) . '%']); } catch (Throwable) {}
    try { Database::run('UPDATE oggetti_unici SET detentore_id = NULL, luogo = ? WHERE okey = ?',
        ['gradini', 'cappello']); } catch (Throwable) {}
    try { Database::run('DELETE FROM oggetti_passaggi WHERE okey = ?', ['cappello']); } catch (Throwable) {}
    $creati = [];
}
register_shutdown_function('pulisci');

// --- I due assi -----------------------------------------------------------

prova('due sconosciuti non hanno una riga a database', function () {
    sgombra();
    $a = attore('Uno'); $b = attore('Due');
    $l = Legami::fra((int) $a['id'], (int) $b['id']);
    uguale(0, $l['affetto']);
    uguale(0, $l['fraintendimento']);
    vero(!$l['esiste'], 'non si scrive niente per chi non si conosce');
});

prova('i legami sono orientati', function () {
    sgombra();
    $a = attore('Ama'); $b = attore('Amato');
    Legami::muovi((int) $a['id'], (int) $b['id'], 60, 0);
    uguale(60, Legami::fra((int) $a['id'], (int) $b['id'])['affetto']);
    uguale(0,  Legami::fra((int) $b['id'], (int) $a['id'])['affetto'],
        'quello che prova A non è quello che prova B');
});

prova('i valori non sfondano i limiti', function () {
    sgombra();
    $a = attore('Estremo'); $b = attore('Bersaglio');
    for ($i = 0; $i < 12; $i++) {
        Legami::muovi((int) $a['id'], (int) $b['id'], 30, 30);
    }
    $l = Legami::fra((int) $a['id'], (int) $b['id']);
    uguale(GameConfig::int('legami.affetto_max', 100), $l['affetto']);
    uguale(100, $l['fraintendimento']);
    for ($i = 0; $i < 20; $i++) {
        Legami::muovi((int) $a['id'], (int) $b['id'], -30, -30);
    }
    $l = Legami::fra((int) $a['id'], (int) $b['id']);
    uguale(-GameConfig::int('legami.affetto_max', 100), $l['affetto']);
    uguale(0, $l['fraintendimento'], 'il fraintendimento non va sotto zero');
});

// --- I gesti ---------------------------------------------------------------

prova('un gesto scalda chi lo riceve più di chi lo fa', function () {
    sgombra();
    $a = suPalco(attore('Gentile')); $b = suPalco(attore('Ricevente'));
    $r = Legami::compi($a, (int) $b['id'], 'ascoltare');
    vero($r['ok'], $r['error'] ?? '');

    $versoChiFa = Legami::fra((int) $b['id'], (int) $a['id'])['affetto'];
    $versoChiRiceve = Legami::fra((int) $a['id'], (int) $b['id'])['affetto'];
    vero($versoChiFa > 0, 'chi riceve si affeziona');
    vero($versoChiRiceve > 0, 'e anche chi fa, un po\'');
    vero($versoChiFa > $versoChiRiceve, 'ma di meno');
});

prova('ripetere lo stesso gesto rende sempre meno', function () {
    sgombra();
    $a = suPalco(attore('Insistente')); $b = suPalco(attore('Paziente'));
    $rese = [];
    $prec = 0;
    for ($i = 0; $i < 5; $i++) {
        Legami::compi(ric($a), (int) $b['id'], 'ascoltare');
        $ora = Legami::fra((int) $b['id'], (int) $a['id'])['affetto'];
        $rese[] = $ora - $prec;
        $prec = $ora;
    }
    vero($rese[0] > $rese[1], "primo {$rese[0]}, secondo {$rese[1]}: deve calare");
    vero(end($rese) === 0 || end($rese) < $rese[0],
        'a forza di ripetere non deve più valere niente');
});

prova('l\'ombrello si divide solo se piove', function () {
    sgombra();
    $a = suPalco(attore('Bagnato')); $b = suPalco(attore('Asciutto'));
    $met = \App\Sim\Meteo::a();
    $r = Legami::compi($a, (int) $b['id'], 'ombrello');
    if ($met['pioggia'] > 0) {
        vero($r['ok'], 'piove: si può');
    } else {
        vero(!$r['ok'], 'non piove: non si può');
        vero(str_contains((string) $r['error'], 'piovendo'), $r['error'] ?? '');
    }
});

prova('i gesti che costano Cuore consumano compostezza', function () {
    sgombra();
    $a = suPalco(attore('Generoso')); $b = suPalco(attore('Destinatario'));
    $prima = (int) ric($a)['compostezza'];
    $r = Legami::compi($a, (int) $b['id'], 'regalo');
    vero($r['ok'], $r['error'] ?? '');
    uguale($prima - 1, (int) ric($a)['compostezza']);
});

prova('non si fanno gesti a chi non è presente', function () {
    sgombra();
    $a = suPalco(attore('Qui')); $b = attore('Altrove');
    $r = Legami::compi($a, (int) $b['id'], 'parlare');
    vero(!$r['ok']);
});

// --- Il malinteso e la gelosia -------------------------------------------------

prova('un gesto chiaro non genera letture', function () {
    sgombra();
    $a = suPalco(attore('Chiaro')); $b = suPalco(attore('Interlocutore'));
    $c = suPalco(attore('Spettatore'));
    $r = Legami::compi($a, (int) $b['id'], 'parlare');   // «parlare» non è ambiguo
    uguale(0, $r['visto_da'] ?? -1, 'nessuno ha niente da interpretare');
    uguale(0, Legami::fra((int) $c['id'], (int) $a['id'])['fraintendimento']);
});

prova('UN GESTO AMBIGUO DAVANTI A UN TERZO genera una lettura', function () {
    sgombra();
    $a = suPalco(attore('Affettuoso')); $b = suPalco(attore('Oggetto'));
    $c = suPalco(attore('Testimone', ['testa' => 3]));   // distratto: capirà male
    $r = Legami::compi($a, (int) $b['id'], 'accompagnare');
    vero($r['ok'], $r['error'] ?? '');
    uguale(1, $r['visto_da'] ?? 0, 'una persona ha guardato');

    $n = (int) Database::first(
        'SELECT COUNT(*) n FROM gesti_letture WHERE osservatore_id = ?', [(int) $c['id']])['n'];
    uguale(1, $n, 'e si è fatta la sua idea');
});

prova('chi non capisce si porta a casa un malinteso', function () {
    sgombra();
    // Testa 1: Intuizione al minimo, sbaglierà quasi sempre.
    $fraintesi = 0;
    for ($i = 0; $i < 12; $i++) {
        sgombra();
        $a = suPalco(attore('Amb' . $i)); $b = suPalco(attore('Verso' . $i));
        $c = suPalco(attore('Tonto' . $i, ['testa' => 1]));
        Legami::compi($a, (int) $b['id'], 'accompagnare');
        if (Legami::fra((int) $c['id'], (int) $a['id'])['fraintendimento'] > 0) {
            $fraintesi++;
        }
    }
    vero($fraintesi >= 4, "su dodici distratti solo {$fraintesi} hanno frainteso");
});

prova('LA GELOSIA non è un malinteso: chi ci tiene ha visto benissimo', function () {
    sgombra();
    $a = suPalco(attore('Conteso'));
    $b = suPalco(attore('Rivale'));
    // C è innamorato di A, e ha Testa 15: capirà perfettamente cos'è successo.
    $c = suPalco(attore('Innamorato', ['testa' => 15]));
    Legami::muovi((int) $c['id'], (int) $a['id'], 80, 0);

    $affettoPrima = Legami::fra((int) $c['id'], (int) $a['id'])['affetto'];
    Legami::compi($a, (int) $b['id'], 'ombrello');   // se non piove ripiego
    if (Legami::fra((int) $c['id'], (int) $a['id'])['fraintendimento'] === 0) {
        Legami::compi(ric($a), (int) $b['id'], 'difendere');
    }
    $dopo = Legami::fra((int) $c['id'], (int) $a['id']);
    vero($dopo['fraintendimento'] > 0, 'chi è innamorato ci si rode comunque');
    vero($dopo['affetto'] < $affettoPrima, 'e gli costa anche un po\' di affetto');
});

prova('IL FRAINTENDIMENTO NON DECADE COL TEMPO', function () {
    // È la regola che non ammette eccezioni, e da cui dipende tutto il resto:
    // se il tempo aggiustasse le cose, il triangolo si scioglierebbe da solo.
    sgombra();
    $a = attore('Frainteso'); $b = attore('Convinto');
    Legami::muovi((int) $b['id'], (int) $a['id'], 0, 60);
    uguale(60, Legami::fra((int) $b['id'], (int) $a['id'])['fraintendimento']);

    // Si finge che passino trenta giorni di gioco: nessun processo del mondo
    // deve averlo toccato.
    Database::run('UPDATE legami SET ultimo_gts = ultimo_gts - ? WHERE da_id = ? AND a_id = ?',
        [30 * 86400, (int) $b['id'], (int) $a['id']]);
    uguale(60, Legami::fra((int) $b['id'], (int) $a['id'])['fraintendimento'],
        'il tempo non chiarisce niente');
});

// --- Chiarire ------------------------------------------------------------------------

prova('non si chiarisce quello che non c\'è', function () {
    sgombra();
    $a = suPalco(attore('Sereno')); $b = suPalco(attore('Tranquillo'));
    $r = Legami::chiarisci($a, (int) $b['id']);
    vero(!$r['ok']);
    vero(str_contains((string) $r['error'], 'niente da chiarire'), $r['error'] ?? '');
});

prova('un chiarimento riuscito abbassa il malinteso, uno fallito lo alza', function () {
    sgombra();
    $riusciti = $falliti = 0;
    for ($i = 0; $i < 14; $i++) {
        sgombra();
        $a = suPalco(attore('Spiega' . $i, ['cuore' => 12]));
        $b = suPalco(attore('Ostinato' . $i));
        Legami::muovi((int) $b['id'], (int) $a['id'], 0, 50);
        $prima = Legami::fra((int) $b['id'], (int) $a['id'])['fraintendimento'];
        $r = Legami::chiarisci(ric($a), (int) $b['id']);
        vero($r['ok'], $r['error'] ?? '');
        $dopo = Legami::fra((int) $b['id'], (int) $a['id'])['fraintendimento'];
        if ($r['riuscito']) { $riusciti++; vero($dopo < $prima, 'riuscito ma non è sceso'); }
        else                { $falliti++;  vero($dopo > $prima, 'fallito ma non è salito'); }
    }
    vero($riusciti > 0 && $falliti > 0,
        "su quattordici tentativi: {$riusciti} riusciti, {$falliti} falliti — deve poter andare male");
});

prova('chiarire costa compostezza', function () {
    sgombra();
    $a = suPalco(attore('Parla')); $b = suPalco(attore('Ascolta'));
    Legami::muovi((int) $b['id'], (int) $a['id'], 0, 40);
    $prima = (int) ric($a)['compostezza'];
    Legami::chiarisci(ric($a), (int) $b['id']);
    uguale($prima - 1, (int) ric($a)['compostezza']);
});

// --- La confessione -----------------------------------------------------------------------

prova('non ci si dichiara senza esserci arrivati', function () {
    sgombra();
    $a = suPalco(attore('Frettoloso')); $b = suPalco(attore('Ignaro'));
    $r = Legami::confessa($a, (int) $b['id']);
    vero(!$r['ok']);
    vero(str_contains(mb_strtolower((string) $r['error']), 'non sarebbe vero'), $r['error'] ?? '');
});

prova('con Cuore basso spesso non esce niente: è la cosa più fedele', function () {
    sgombra();
    $muti = 0;
    for ($i = 0; $i < 14; $i++) {
        sgombra();
        $a = suPalco(attore('Timido' . $i, ['cuore' => 3]));
        $b = suPalco(attore('Attesa' . $i));
        Legami::muovi((int) $a['id'], (int) $b['id'], 70, 0);
        $r = Legami::confessa(ric($a), (int) $b['id']);
        vero($r['ok'], $r['error'] ?? '');
        if (($r['esito'] ?? '') === 'muto') { $muti++; }
    }
    vero($muti >= 5, "con Cuore 3 solo {$muti} volte su quattordici non è uscito niente");
});

prova('la risposta dipende da quanto tiene l\'altro', function () {
    // Tre casi, con Cuore alto perché la frase esca di sicuro.
    $casi = [[70, 'accolta'], [25, 'sospesa'], [0, 'respinta']];
    foreach ($casi as $k => [$affettoLoro, $atteso]) {
        $trovato = null;
        for ($i = 0; $i < 10 && $trovato === null; $i++) {
            sgombra();
            $a = suPalco(attore("Dice{$k}_{$i}", ['cuore' => 15]));
            $b = suPalco(attore("Risponde{$k}_{$i}"));
            Legami::muovi((int) $a['id'], (int) $b['id'], 70, 0);
            if ($affettoLoro > 0) {
                Legami::muovi((int) $b['id'], (int) $a['id'], $affettoLoro, 0);
            }
            $r = Legami::confessa(ric($a), (int) $b['id']);
            if (($r['esito'] ?? '') !== 'muto') { $trovato = $r['esito']; }
        }
        uguale($atteso, $trovato, "con affetto altrui {$affettoLoro}");
    }
});

// --- Il cappello ------------------------------------------------------------------------------

prova('il cappello si raccoglie dove sta, e si dà a chi è presente', function () {
    sgombra();
    Database::run('UPDATE oggetti_unici SET detentore_id = NULL, luogo = ? WHERE okey = ?',
        [PALCO, 'cappello']);
    $a = suPalco(attore('Trova')); $b = suPalco(attore('Riceve'));

    vero(Legami::oggettiQui(PALCO) !== [], 'il cappello è lì');
    $r = Legami::raccogli($a, 'cappello');
    vero($r['ok'], $r['error'] ?? '');
    uguale((int) $a['id'], (int) Legami::oggettoDi((int) $a['id'])['detentore_id']);
    uguale([], Legami::oggettiQui(PALCO), 'e non è più per terra');

    $prima = Legami::fra((int) $b['id'], (int) $a['id'])['affetto'];
    $r = Legami::passaOggetto(ric($a), (int) $b['id'], 'cappello');
    vero($r['ok'], $r['error'] ?? '');
    uguale(null, Legami::oggettoDi((int) $a['id']));
    uguale((int) $b['id'], (int) Legami::oggettoDi((int) $b['id'])['detentore_id']);
    vero(Legami::fra((int) $b['id'], (int) $a['id'])['affetto'] > $prima + 10,
        'darlo vuol dire molto');
});

prova('non si dà quello che non si ha', function () {
    sgombra();
    Database::run('UPDATE oggetti_unici SET detentore_id = NULL, luogo = ? WHERE okey = ?',
        ['gradini', 'cappello']);
    $a = suPalco(attore('Senza')); $b = suPalco(attore('Nulla'));
    $r = Legami::passaOggetto($a, (int) $b['id'], 'cappello');
    vero(!$r['ok']);
    $r = Legami::raccogli($a, 'cappello');
    vero(!$r['ok'], 'e non si raccoglie da un altro luogo');
});

prova('il passaggio del cappello resta negli annali', function () {
    sgombra();
    // Il registro dei passaggi e' cumulativo per costruzione: va azzerato
    // qui, se no questa prova conta anche i passaggi di quella prima.
    Database::run('DELETE FROM oggetti_passaggi WHERE okey = ?', ['cappello']);
    Database::run('UPDATE oggetti_unici SET detentore_id = NULL, luogo = ? WHERE okey = ?',
        [PALCO, 'cappello']);
    $a = suPalco(attore('Primo')); $b = suPalco(attore('Secondo'));
    Legami::raccogli($a, 'cappello');
    Legami::passaOggetto(ric($a), (int) $b['id'], 'cappello');
    $n = (int) Database::first('SELECT COUNT(*) n FROM oggetti_passaggi WHERE okey = ?', ['cappello'])['n'];
    uguale(2, $n, 'la raccolta e il passaggio');
});

riepilogo();
