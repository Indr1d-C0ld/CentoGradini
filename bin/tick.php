<?php

declare(strict_types=1);

/**
 * Cento Gradini — battito del quartiere.
 *
 * Da cron, ogni minuto:
 *   * * * * * /usr/bin/php /var/www/orangeroad/bin/tick.php >/dev/null 2>&1
 *
 * Il battito non e' l'unico modo in cui il mondo procede: anche la richiesta
 * del giocatore fa avanzare cio' che lo riguarda (avanzamento pigro). Il
 * battito serve perche' il quartiere cammini anche per chi non e' collegato —
 * che e' tutto il punto di un mondo a tempo compresso.
 *
 * Ogni fase sta in piedi da sola: se una cade, cade da sola e il battito va
 * avanti. Un guasto nella posta non deve poter fermare il mondo.
 */

$projectRoot = require __DIR__ . '/_bootstrap.php';

use App\Core\Database;
use App\Core\Posta;
use App\Core\RateLimiter;
use App\Game\Abitanti;
use App\Game\Bacheca;
use App\Game\Personaggio;
use App\Game\Episodi;
use App\Game\Legami;
use App\Game\Segreto;
use App\Game\Voci;

$avvio = microtime(true);
$lock  = $projectRoot . '/storage/tick.lock';

// Un solo battito per volta: se il precedente e' ancora in corso si esce subito.
$fp = fopen($lock, 'c');
if ($fp === false || !flock($fp, LOCK_EX | LOCK_NB)) {
    exit(0);
}

$id = null;
try {
    // Prima delle migrazioni non c'e' niente da battere.
    if (Database::first("SHOW TABLES LIKE 'tick_runs'") === null) {
        exit(0);
    }

    Database::run('INSERT INTO tick_runs (started_at, ok) VALUES (NOW(3), 0)');
    $id = Database::lastInsertId();

    // Ogni fase sta in piedi da sola: se una cade, cade da sola, lascia detto
    // perche', e il battito va avanti. Un guasto nella posta non deve poter
    // fermare il mondo, e viceversa.
    $guasti = [];
    $fase = static function (string $nome, callable $f, mixed $difetto) use (&$guasti): mixed {
        try {
            return $f();
        } catch (\Throwable $e) {
            $guasti[] = $nome . ': ' . $e->getMessage();
            logger('battito: fase ' . $nome . ' — ' . $e->getMessage(), 'error');
            return $difetto;
        }
    };

    // Chi e' per strada arriva anche se non sta guardando. E' lo stesso
    // metodo che usa la richiesta web (avanzamento pigro): una strada sola,
    // quindi i due percorsi non possono divergere.
    $arrivati = $fase('arrivi', static fn (): int => Personaggio::avanzaTutti(), 0);

    // Il quartiere dimentica: le tracce spente si buttano via.
    $tracce = $fase('tracce', static fn (): int => Personaggio::potaTracce(), 0);

    // Chi si e' fatto scoprire da troppa gente trasloca, anche se non e'
    // collegato: il camioncino arriva di mattina presto e non chiede permesso.
    $partiti = $fase('traslochi', static fn (): int => Segreto::traslochiDovuti(), 0);

    // Gli oggetti unici che nessuno puo' piu' passare tornano nel mondo: chi li
    // teneva ha traslocato, o il suo account non c'e' piu'. Il trasloco lo fa
    // gia' da se'; questa e' la rete per i casi a cui nessuno ha pensato.
    $ritrovati = $fase('oggetti', static fn (): int => Legami::ritrovaOggetti(), 0);

    // Gli abitanti canonici seguono il loro giro. Vanno mossi PRIMA delle
    // chiacchiere: e' il loro spostarsi che mette in contatto persone che
    // altrimenti non si incontrerebbero mai, ed e' il motivo per cui una
    // voce attraversa la mappa invece di restare dov'e' nata.
    $abitanti = $fase('abitanti', static fn (): int => Abitanti::muovi(), 0);

    // Gli incidenti che nessuno ha chiuso valgono come «non ho fatto niente»:
    // il silenzio e' una risposta, e il testimone si tiene l'anomalia. Va
    // DOPO gli abitanti e PRIMA delle voci: l'anomalia appena nata deve poter
    // entrare nel giro delle chiacchiere dello stesso battito.
    $incidenti = $fase('incidenti', static fn (): int => Segreto::incidentiScaduti(), 0);

    // Il quartiere chiacchiera: chi e' nello stesso posto si racconta le
    // cose, e le voci si deformano passando di bocca in bocca.
    $ciarle = $fase('voci', static fn (): array => Voci::giro(),
        ['di_persona' => 0, 'per_club' => 0]);

    // E le voci vecchie si spengono, come le tracce.
    $vociPotate = $fase('voci_potate', static fn (): int => Voci::pota(), 0);
    $avvisi     = $fase('bacheca', static fn (): int => Bacheca::potaAvvisi(), 0);

    // Le scene la cui finestra e' scaduta si chiudono da sole: per chi non
    // ha scelto decide l'agente autonomo, e il mondo non aspetta nessuno.
    $scene = $fase('scene', static fn (): int => Episodi::scadute(), 0);

    // E si guarda se le condizioni del mondo aprono qualche storia nuova.
    $nuovi = $fase('episodi', static fn (): int => Episodi::verificaAperture(), 0);

    $posta = $fase('posta', static fn (): array => Posta::smista(), ['tentati' => 0, 'inviati' => 0]);
    $freni = $fase('freni', static fn (): int => RateLimiter::gc(), 0);

    $ms = (int) round((microtime(true) - $avvio) * 1000);
    Database::run(
        'UPDATE tick_runs SET finished_at = NOW(3), ok = ?, duration_ms = ?, tasks = ?, note = ? WHERE id = ?',
        [
            $guasti === [] ? 1 : 0,
            $ms,
            json_encode([
                'arrivati'     => $arrivati,
                'tracce_potate'=> $tracce,
                'traslocati'   => $partiti,
                'oggetti_ritrovati' => $ritrovati,
                'scene_chiuse' => $scene,
                'episodi_nuovi'=> $nuovi,
                'abitanti'     => $abitanti,
                'incidenti_scaduti' => $incidenti,
                'voci'         => $ciarle,
                'voci_potate'  => $vociPotate,
                'avvisi_potati'=> $avvisi,
                'posta'        => $posta,
                'freni_potati' => $freni,
            ], JSON_UNESCAPED_UNICODE),
            $guasti === [] ? null : mb_substr(implode(' | ', $guasti), 0, 255),
            $id,
        ]
    );
} catch (\Throwable $e) {
    logger('battito fallito: ' . $e->getMessage(), 'error');
    if ($id !== null) {
        try {
            Database::run(
                'UPDATE tick_runs SET finished_at = NOW(3), ok = 0, note = ? WHERE id = ?',
                [mb_substr($e->getMessage(), 0, 255), $id]
            );
        } catch (\Throwable) {
            // Se non si riesce neanche a scrivere il fallimento, resta il diario.
        }
    }
    exit(1);
} finally {
    flock($fp, LOCK_UN);
    fclose($fp);
}
