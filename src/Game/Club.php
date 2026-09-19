<?php

declare(strict_types=1);

namespace App\Game;

use App\Core\Database;
use App\Core\GameConfig;
use App\Sim\Calendario;
use App\Sim\Orologio;

/**
 * I club scolastici.
 *
 * Nel liceo giapponese il 部活 è la struttura sociale che viene subito dopo
 * la classe: è dove si passano i pomeriggi, dove si diventa senpai o kōhai di
 * qualcuno, e dove ci si incontra senza doverlo dichiarare.
 *
 * Nel gioco fa due cose che nessun altro sistema fa:
 *
 *  1. **Porta le voci dove la geografia non arriva.** Chi gioca poco non
 *     incrocia nessuno; se però è iscritto a un club, quello che succede nel
 *     quartiere lo raggiunge lo stesso. È l'unica propagazione che non
 *     dipende dal trovarsi nello stesso posto.
 *  2. **Dà un posto dove essere il pomeriggio.** Nella fase «club» del
 *     calendario, chi è iscritto sta dove si ritrova il suo club invece di
 *     seguire l'abitudine.
 *
 * Madoka non è iscritta a niente, e non è una dimenticanza: il canone la dà
 * esplicitamente per solitaria.
 */
final class Club
{
    /** @return list<array<string,mixed>> */
    public static function tutti(): array
    {
        return Database::all('SELECT * FROM club ORDER BY ordine, ckey');
    }

    public static function uno(string $ckey): ?array
    {
        return Database::first('SELECT * FROM club WHERE ckey = ?', [$ckey]);
    }

    /** @return list<array<string,mixed>> i club di una persona */
    public static function di(int $pgId): array
    {
        return Database::all(
            'SELECT c.*, m.ruolo, m.dal_gts FROM club_membri m
             JOIN club c ON c.ckey = m.ckey
             WHERE m.personaggio_id = ? ORDER BY c.ordine',
            [$pgId]
        );
    }

    /** @return list<array<string,mixed>> gli iscritti a un club */
    public static function membri(string $ckey): array
    {
        return Database::all(
            'SELECT p.id, p.nome, p.cognome, p.sezione, p.anno, p.png, m.ruolo
             FROM club_membri m JOIN personaggi p ON p.id = m.personaggio_id
             WHERE m.ckey = ? AND p.stato = ?
             ORDER BY m.ruolo DESC, p.anno DESC, p.id',
            [$ckey, 'attivo']
        );
    }

    public static function quanti(string $ckey): int
    {
        return (int) (Database::first(
            'SELECT COUNT(*) n FROM club_membri m JOIN personaggi p ON p.id = m.personaggio_id
             WHERE m.ckey = ? AND p.stato = ?', [$ckey, 'attivo']
        )['n'] ?? 0);
    }

    // --- Iscrizione ------------------------------------------------------------

    /** @return array{ok:bool, error?:string} */
    public static function iscrivi(array $pg, string $ckey): array
    {
        $club = self::uno($ckey);
        if ($club === null) {
            return ['ok' => false, 'error' => 'Quel club non esiste.'];
        }
        if ((string) $pg['sezione'] === 'adulti') {
            return ['ok' => false, 'error' => 'I club sono roba di scuola.'];
        }
        $id = (int) $pg['id'];
        if (Database::first('SELECT 1 x FROM club_membri WHERE ckey = ? AND personaggio_id = ?',
            [$ckey, $id]) !== null) {
            return ['ok' => false, 'error' => 'Ci sei già dentro.'];
        }
        $max = GameConfig::int('club.max_per_pg', 2);
        if (count(self::di($id)) >= $max) {
            return ['ok' => false, 'error' => "Non si sta in più di {$max} club: con i compiti non ci si sta dentro."];
        }
        if (self::quanti($ckey) >= (int) $club['posti']) {
            return ['ok' => false, 'error' => 'Il club è al completo. Riprova il trimestre prossimo.'];
        }

        Database::run(
            'INSERT INTO club_membri (ckey, personaggio_id, ruolo, dal_gts) VALUES (?, ?, ?, ?)',
            [$ckey, $id, 'socio', Orologio::lineare()]
        );
        return ['ok' => true];
    }

    /** @return array{ok:bool, error?:string} */
    public static function esci(array $pg, string $ckey): array
    {
        $n = Database::run(
            'DELETE FROM club_membri WHERE ckey = ? AND personaggio_id = ?',
            [$ckey, (int) $pg['id']]
        )->rowCount();
        return $n > 0 ? ['ok' => true] : ['ok' => false, 'error' => 'Non eri iscritto.'];
    }

    // --- Dove si sta il pomeriggio ---------------------------------------------

    /**
     * Il luogo del club che si riunisce adesso per questa persona, o null.
     *
     * I giorni sono quelli della settimana (1 = lunedì); fuori dalla fase
     * «club» del calendario non si riunisce nessuno.
     */
    public static function ritrovoAdesso(int $pgId, ?int $gts = null): ?string
    {
        $c = Calendario::stato($gts);
        if (($c['fase'] ?? '') !== 'club') {
            return null;
        }
        $dow = (int) $c['data']->format('N');
        foreach (self::di($pgId) as $club) {
            $giorni = array_filter(array_map('intval', explode(',', (string) $club['giorni'])));
            if ($giorni === [] || in_array($dow, $giorni, true)) {
                return (string) $club['luogo'];
            }
        }
        return null;
    }
}
