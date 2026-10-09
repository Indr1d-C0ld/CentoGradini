<?php

declare(strict_types=1);

namespace App\Game;

use App\Core\Database;
use App\Core\GameConfig;
use App\Sim\Luoghi;
use App\Sim\Meteo;
use App\Sim\Orologio;
use App\Sim\Rng;

/**
 * Le cose che durano un po' e poi passano.
 *
 * Il regolamento del 1990 ne aveva tre che stanno benissimo in questo
 * quartiere, e che il manga usa di continuo:
 *
 *  - **il raffreddore** («una persona sotto la pioggia senza ombrello se lo
 *    prende il 50% delle volte»), e con lui il fatto che **un esper malato
 *    non controlla i poteri** — «teletrasporti casuali e problemi con la
 *    telecinesi». È la febbre di Kyosuke che lo fa finire dentro l'auto di
 *    Umao e Ushiko (TV4);
 *  - **le bende** dopo una brutta giornata: una gag visiva, e un modo per
 *    far vedere agli altri che qualcosa è successo;
 *  - e l'**invisibilità**, che è un potere ma dura qualche minuto.
 *
 * Ognuno ha un inizio e una fine, e alla fine sparisce da solo: nessun
 * battito deve ricordarsi di toglierlo, perché chi chiede guarda l'orologio.
 */
final class Stati
{
    /** Come si vede addosso a qualcuno, per chi lo incrocia. */
    public const COME_SI_VEDE = [
        'raffreddore' => 'ha il raffreddore',
        'bende'       => 'è pieno di cerotti',
        'invisibile'  => '',   // appunto
    ];

    public static function metti(int $pgId, string $stato, int $minuti, ?int $gts = null): void
    {
        $gts ??= Orologio::lineare();
        Database::run(
            'INSERT INTO stati (personaggio_id, stato, dal_gts, fino_gts) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE dal_gts = VALUES(dal_gts), fino_gts = GREATEST(fino_gts, VALUES(fino_gts))',
            [$pgId, $stato, $gts, $gts + max(1, $minuti) * 60]
        );
    }

    public static function ha(int $pgId, string $stato, ?int $gts = null): bool
    {
        $gts ??= Orologio::lineare();
        return Database::first(
            'SELECT 1 x FROM stati WHERE personaggio_id = ? AND stato = ? AND fino_gts > ?',
            [$pgId, $stato, $gts]
        ) !== null;
    }

    /** @return list<string> gli stati in corso */
    public static function di(int $pgId, ?int $gts = null): array
    {
        $gts ??= Orologio::lineare();
        return array_map(
            static fn (array $r): string => (string) $r['stato'],
            Database::all('SELECT stato FROM stati WHERE personaggio_id = ? AND fino_gts > ? ORDER BY stato',
                [$pgId, $gts])
        );
    }

    /** Quello che si vede addosso, in parole: «ha il raffreddore, è pieno di cerotti». */
    public static function addosso(array $pg, ?int $gts = null): string
    {
        $parole = [];
        foreach (self::di((int) $pg['id'], $gts) as $s) {
            $p = self::COME_SI_VEDE[$s] ?? '';
            if ($p !== '') {
                $parole[] = $s === 'bende' && (string) ($pg['sesso'] ?? 'm') === 'f' ? 'è piena di cerotti' : $p;
            }
        }
        return implode(', ', $parole);
    }

    /** I poteri non rispondono come dovrebbero: raffreddore, febbre. */
    public static function poteriSballati(int $pgId, ?int $gts = null): bool
    {
        return self::ha($pgId, 'raffreddore', $gts);
    }

    /**
     * Arrivare all'aperto sotto la pioggia, senza che qualcuno abbia diviso
     * l'ombrello con te nelle ultime due ore: si rischia il raffreddore.
     *
     * Il 50% del regolamento era tarato su una partita da tavolo, dove sotto
     * la pioggia ci si finisce una volta a sessione; qui ci si sposta di
     * continuo, e la probabilità è una manopola (`stati.raffreddore_prob`).
     *
     * @return bool se se l'è preso
     */
    public static function forseRaffreddore(array $pg, string $lkey, int $gts): bool
    {
        $l = Luoghi::uno($lkey);
        if ($l === null || !in_array((string) $l['tipo'], ['strada', 'natura', 'fuori'], true)) {
            return false;   // al coperto non piove
        }
        if (Meteo::a($gts)['pioggia'] <= 0.0) {
            return false;
        }
        $id = (int) $pg['id'];
        if (self::ha($id, 'raffreddore', $gts)) {
            return false;
        }
        $riparato = Database::first(
            "SELECT 1 x FROM gesti_fatti WHERE gkey = 'ombrello' AND (attore_id = ? OR verso_id = ?) AND gts > ? LIMIT 1",
            [$id, $id, $gts - 7200]
        ) !== null;
        if ($riparato) {
            return false;
        }
        $rng = Rng::for(GameConfig::int('world.seed', 19870406), 'raffreddore', $id, intdiv($gts, 3600));
        if ($rng->int(1, 100) > GameConfig::int('stati.raffreddore_prob', 15)) {
            return false;
        }
        self::metti($id, 'raffreddore', GameConfig::int('stati.raffreddore_ore', 24) * 60, $gts);
        Personaggio::traccia($lkey, $id, 'stato', accorda(sprintf(
            '%s è arrivat{o} bagnat{o} fradici{o}, e ha starnutito tre volte di fila.',
            Personaggio::nomeCompleto($pg)), $pg), $gts, 40);
        return true;
    }

    /**
     * Le bende: dopo aver perso punti ferita, e con meno della metà, si tira
     * sul 40% + 2×(PF massimi − PF attuali) del regolamento del 1990.
     */
    public static function forseBende(int $pgId, int $gts): bool
    {
        $pg = Database::first('SELECT id, pf, pf_max FROM personaggi WHERE id = ?', [$pgId]);
        if ($pg === null || (int) $pg['pf'] * 2 >= (int) $pg['pf_max']) {
            return false;
        }
        $prob = 40 + 2 * ((int) $pg['pf_max'] - (int) $pg['pf']);
        $rng  = Rng::for(GameConfig::int('world.seed', 19870406), 'bende', $pgId, $gts);
        if ($rng->int(1, 100) > $prob) {
            return false;
        }
        self::metti($pgId, 'bende', GameConfig::int('stati.bende_ore', 48) * 60, $gts);
        return true;
    }
}
