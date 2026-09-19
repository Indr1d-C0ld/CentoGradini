<?php

declare(strict_types=1);

namespace App\Game;

use App\Core\Database;
use App\Core\GameConfig;
use App\Sim\Luoghi;
use App\Sim\Orologio;

/**
 * La bacheca e i biglietti: i due modi di scrivere agli altri, e sono
 * l'opposto l'uno dell'altro.
 *
 * La **bacheca** è pubblica, legata a un luogo e a scadenza. Quello che ci si
 * appende diventa una voce ad alta precisione: la carta non dimentica come le
 * persone, e per questo un avviso firmato è il modo più veloce che c'è per
 * far sapere una cosa a tutto il quartiere — e il più difficile da ritirare.
 *
 * Il **biglietto** è privato, e soprattutto **non è posta istantanea**: si
 * lascia in un luogo, e arriva quando il destinatario ci passa. È il mezzo di
 * comunicazione più presente nell'opera — il foglietto nell'armadietto delle
 * scarpe, quello passato sotto il banco, quello infilato nella borsa — e la
 * sua caratteristica è proprio l'attesa. Un biglietto lasciato al liceo a chi
 * non ci mette piede da tre giorni resta lì tre giorni.
 *
 * Entrambi si possono firmare con un nome che non è il proprio. Nel manga
 * succede in continuazione, e non è un trucco da giocatori furbi: è materiale
 * narrativo.
 */
final class Bacheca
{
    // --- La bacheca ------------------------------------------------------------

    /** @return list<array<string,mixed>> gli avvisi ancora appesi in un luogo */
    public static function avvisi(string $lkey, int $quanti = 20): array
    {
        $adesso = Orologio::lineare();
        $righe  = Database::all(
            'SELECT b.*, p.nome, p.cognome, p.png
             FROM bacheca b LEFT JOIN personaggi p ON p.id = b.autore_id
             WHERE b.luogo = ? AND b.scade_gts > ?
             ORDER BY b.gts DESC LIMIT ?',
            [$lkey, $adesso, $quanti]
        );
        foreach ($righe as &$r) {
            $r['fa_ore']  = (int) floor(($adesso - (int) $r['gts']) / 3600);
            $r['resta_ore'] = (int) floor(((int) $r['scade_gts'] - $adesso) / 3600);
            // La firma vince sul nome vero: se uno si è firmato «un amico»,
            // in bacheca c'è scritto «un amico».
            $r['mostra_firma'] = (string) $r['firma'] !== ''
                ? (string) $r['firma']
                : trim((string) ($r['nome'] ?? '') . ' ' . (string) ($r['cognome'] ?? ''));
            if ($r['tipo'] === 'anonimo') {
                $r['mostra_firma'] = 'nessuna firma';
            }
        }
        return $righe;
    }

    /** Quanti avvisi ci sono appesi, per l'insegna del luogo. */
    public static function quantiAvvisi(string $lkey): int
    {
        return (int) (Database::first(
            'SELECT COUNT(*) n FROM bacheca WHERE luogo = ? AND scade_gts > ?',
            [$lkey, Orologio::lineare()]
        )['n'] ?? 0);
    }

    /** @return array{ok:bool, error?:string, id?:int} */
    public static function affiggi(array $pg, string $lkey, string $tipo, string $titolo, string $testo, string $firma = ''): array
    {
        if (!Luoghi::esiste($lkey)) {
            return ['ok' => false, 'error' => 'Quel posto non esiste.'];
        }
        $titolo = trim($titolo);
        $testo  = trim($testo);
        if (mb_strlen($titolo) < 3) {
            return ['ok' => false, 'error' => 'Serve un titolo, anche corto.'];
        }
        if (mb_strlen($testo) < 5) {
            return ['ok' => false, 'error' => 'E serve scriverci qualcosa.'];
        }
        if (!in_array($tipo, ['avviso', 'cerca', 'offre', 'club', 'anonimo'], true)) {
            $tipo = 'avviso';
        }

        $id  = (int) $pg['id'];
        $max = GameConfig::int('bacheca.max_per_pg', 3);
        $miei = (int) (Database::first(
            'SELECT COUNT(*) n FROM bacheca WHERE autore_id = ? AND scade_gts > ?',
            [$id, Orologio::lineare()]
        )['n'] ?? 0);
        if ($miei >= $max) {
            return ['ok' => false, 'error' => "Hai già {$max} avvisi appesi. Aspetta che scadano, o togline uno."];
        }

        $gts    = Orologio::lineare();
        $durata = GameConfig::int('bacheca.durata_giorni', 10) * 86400;
        Database::run(
            'INSERT INTO bacheca (luogo, autore_id, firma, tipo, titolo, testo, gts, scade_gts)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$lkey, $id, mb_substr(trim($firma), 0, 48), $tipo,
             mb_substr($titolo, 0, 80), mb_substr($testo, 0, 600), $gts, $gts + $durata]
        );
        $nuovo = (int) Database::lastInsertId();

        // Chi è lì vede appendere il foglio, e la cosa diventa una voce. Un
        // avviso anonimo fa nascere la voce lo stesso: quello che si sa è che
        // «qualcuno ha attaccato un foglio», ed è precisamente il tipo di
        // informazione che mette in moto una scuola.
        if ($tipo !== 'anonimo') {
            $presenti = self::presentiAltri($lkey, $id);
            if ($presenti !== []) {
                Voci::nasce('affisso', $id, null, $lkey, $presenti, '', $gts);
            }
        }
        return ['ok' => true, 'id' => $nuovo];
    }

    /** @return array{ok:bool, error?:string} */
    public static function stacca(array $pg, int $avvisoId): array
    {
        $n = Database::run(
            'DELETE FROM bacheca WHERE id = ? AND autore_id = ?', [$avvisoId, (int) $pg['id']]
        )->rowCount();
        return $n > 0 ? ['ok' => true] : ['ok' => false, 'error' => 'Quell\'avviso non è tuo.'];
    }

    /** Toglie gli avvisi scaduti. La chiama il battito. */
    public static function potaAvvisi(): int
    {
        return Database::run('DELETE FROM bacheca WHERE scade_gts < ?', [Orologio::lineare()])->rowCount();
    }

    // --- I biglietti -----------------------------------------------------------

    /** @return array{ok:bool, error?:string} */
    public static function lascia(array $pg, int $aId, string $testo, bool $anonimo = false): array
    {
        $testo = trim($testo);
        if (mb_strlen($testo) < 2) {
            return ['ok' => false, 'error' => 'Un biglietto vuoto non si lascia.'];
        }
        $id = (int) $pg['id'];
        if ($aId === $id) {
            return ['ok' => false, 'error' => 'Scriversi da soli non serve a niente.'];
        }
        $dest = Database::first('SELECT id, stato FROM personaggi WHERE id = ?', [$aId]);
        if ($dest === null || (string) $dest['stato'] !== 'attivo') {
            return ['ok' => false, 'error' => 'Quella persona non c\'è più.'];
        }

        $max = GameConfig::int('biglietti.max_al_giorno', 8);
        $da24 = (int) (Database::first(
            'SELECT COUNT(*) n FROM biglietti WHERE da_id = ? AND gts >= ?',
            [$id, Orologio::lineare() - 86400]
        )['n'] ?? 0);
        if ($da24 >= $max) {
            return ['ok' => false, 'error' => "Hai già scritto {$max} biglietti oggi. Anche la carta finisce."];
        }

        Database::run(
            'INSERT INTO biglietti (da_id, a_id, luogo, anonimo, testo, gts) VALUES (?, ?, ?, ?, ?, ?)',
            [$id, $aId, (string) $pg['luogo'], $anonimo ? 1 : 0,
             mb_substr($testo, 0, 600), Orologio::lineare()]
        );
        return ['ok' => true];
    }

    /**
     * I biglietti che aspettano questa persona **in questo luogo**.
     *
     * È il punto della faccenda: un biglietto non arriva, si trova. Chi non
     * passa dove gliel'hanno lasciato non lo legge.
     *
     * @return list<array<string,mixed>>
     */
    public static function quiPer(int $pgId, string $lkey): array
    {
        return Database::all(
            'SELECT b.*, p.nome, p.cognome FROM biglietti b
             LEFT JOIN personaggi p ON p.id = b.da_id
             WHERE b.a_id = ? AND b.luogo = ? AND b.letto_gts IS NULL
             ORDER BY b.gts',
            [$pgId, $lkey]
        );
    }

    /** Tutti i biglietti già letti, per rileggerseli. @return list<array<string,mixed>> */
    public static function letti(int $pgId, int $quanti = 40): array
    {
        return Database::all(
            'SELECT b.*, p.nome, p.cognome FROM biglietti b
             LEFT JOIN personaggi p ON p.id = b.da_id
             WHERE b.a_id = ? AND b.letto_gts IS NOT NULL
             ORDER BY b.letto_gts DESC LIMIT ?',
            [$pgId, $quanti]
        );
    }

    /** Quelli che ho lasciato io e non sono ancora stati raccolti. */
    public static function miei(int $pgId, int $quanti = 40): array
    {
        return Database::all(
            'SELECT b.*, p.nome, p.cognome FROM biglietti b
             JOIN personaggi p ON p.id = b.a_id
             WHERE b.da_id = ? ORDER BY b.gts DESC LIMIT ?',
            [$pgId, $quanti]
        );
    }

    /** @return array{ok:bool, error?:string, testo?:string} */
    public static function leggi(array $pg, int $bigliettoId): array
    {
        $b = Database::first(
            'SELECT * FROM biglietti WHERE id = ? AND a_id = ?', [$bigliettoId, (int) $pg['id']]
        );
        if ($b === null) {
            return ['ok' => false, 'error' => 'Quel biglietto non è per te.'];
        }
        if ((string) $b['luogo'] !== (string) $pg['luogo']) {
            return ['ok' => false, 'error' => 'Quel biglietto è altrove: bisogna andarci.'];
        }
        if ($b['letto_gts'] === null) {
            Database::run('UPDATE biglietti SET letto_gts = ? WHERE id = ?',
                [Orologio::lineare(), $bigliettoId]);
        }
        return ['ok' => true, 'testo' => (string) $b['testo']];
    }

    // --- Utilità ---------------------------------------------------------------

    /** @return list<int> chi è qui, a parte me */
    private static function presentiAltri(string $lkey, int $io): array
    {
        return array_map(
            static fn (array $r): int => (int) $r['id'],
            Database::all(
                'SELECT id FROM personaggi WHERE luogo = ? AND id <> ? AND stato = ? AND verso IS NULL',
                [$lkey, $io, 'attivo']
            )
        );
    }
}
