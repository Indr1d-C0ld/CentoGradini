<?php

declare(strict_types=1);

namespace App\Game;

use App\Core\Database;
use App\Core\GameConfig;
use App\Core\Posta;

/**
 * Il filo diretto fra la gestione e un giocatore.
 *
 * **Non e' il quartiere che parla.** Un avvertimento di moderazione travestito
 * da biglietto lasciato sui gradini sarebbe una bugia raccontata proprio nel
 * momento in cui serve essere creduti, e la prima volta che un giocatore se ne
 * accorge smette di credere anche al resto. Percio' questa roba non passa per
 * `Biglietti` ne' per `Voci`: ha una tabella sua e, a schermo, una faccia sua.
 *
 * Va in tutte e due le direzioni. Un canale a senso unico obbliga chi ha un
 * problema a scrivere un'e-mail e aspettare, e quasi nessuno lo fa: il
 * risultato pratico sarebbe che i problemi non arrivano.
 *
 * Il filo e' sempre intestato al GIOCATORE, anche per i messaggi scritti da un
 * amministratore: cosi' la conversazione si legge con una interrogazione sola
 * da tutte e due le parti, e non esiste il caso di due meta' che non si
 * trovano.
 */
final class Comunicazioni
{
    /** Quanto puo' essere lungo un messaggio, in caratteri. */
    public const MAX = 2000;

    /**
     * Scrive nel filo di un giocatore.
     *
     * @return array{ok:bool,error?:string}
     */
    public static function scrivi(int $userId, ?int $autoreId, bool $daAdmin, string $testo): array
    {
        $testo = trim($testo);
        if ($testo === '') {
            return ['ok' => false, 'error' => 'Il messaggio è vuoto.'];
        }
        $testo = mb_substr($testo, 0, self::MAX);

        $u = Database::first('SELECT id, username, email FROM users WHERE id = ?', [$userId]);
        if ($u === null) {
            return ['ok' => false, 'error' => 'Quel giocatore non esiste.'];
        }

        Database::run(
            'INSERT INTO comunicazioni (user_id, autore_id, da_admin, testo) VALUES (?, ?, ?, ?)',
            [$userId, $autoreId, $daAdmin ? 1 : 0, $testo]
        );

        if ($daAdmin) {
            self::avvisaPerPosta($u, $testo);
        }
        return ['ok' => true];
    }

    /**
     * L'e-mail che avvisa il giocatore che la gestione gli ha scritto.
     *
     * Non ripete il messaggio per intero: un avvertimento di moderazione
     * letto nella posta, fuori dal suo contesto e senza poter rispondere, e'
     * il modo piu' rapido di farlo prendere peggio di com'era inteso. Dice che
     * c'e' e dove leggerlo.
     *
     * Se la posta e' rotta non si perde niente: il messaggio e' gia' a
     * database e il giocatore lo trova comunque entrando.
     *
     * @param array<string,mixed> $u
     */
    private static function avvisaPerPosta(array $u, string $testo): void
    {
        if (!GameConfig::bool('posta.avvisa_comunicazioni', true)) {
            return;
        }
        $gioco = (string) (\App\Core\Config::get('app.name') ?? 'Cento Gradini');
        $corpo = "Ciao {$u['username']},\n\n"
            . "la gestione di {$gioco} ti ha scritto. Il messaggio è nel gioco, alla voce\n"
            . "«Comunicazioni»: da lì puoi anche rispondere.\n\n"
            . "Prime righe:\n"
            . '« ' . mb_strimwidth(preg_replace('/\s+/u', ' ', $testo) ?? '', 0, 180, '…') . " »\n\n"
            . "Se non ti aspettavi questo messaggio, entra e leggilo: quasi sempre è una cosa\n"
            . "di poco conto.\n";
        try {
            Posta::accoda((string) $u['email'], "{$gioco} — un messaggio dalla gestione",
                $corpo, 'comunicazione', 4);
        } catch (\Throwable) {
            // La posta non deve poter far fallire la scrittura del messaggio.
        }
    }

    /**
     * Il filo di un giocatore, dal piu' vecchio al piu' recente.
     *
     * @return list<array<string,mixed>>
     */
    public static function filo(int $userId, int $quanti = 100): array
    {
        $righe = Database::all(
            'SELECT c.*, u.username AS autore FROM comunicazioni c
             LEFT JOIN users u ON u.id = c.autore_id
             WHERE c.user_id = ? ORDER BY c.id DESC LIMIT ' . max(1, min(500, $quanti)),
            [$userId]
        );
        return array_reverse($righe);
    }

    /**
     * Segna come letto quello che ha scritto l'altra parte.
     *
     * `$daAdmin` dice di chi sono i messaggi da segnare: il giocatore che apre
     * la pagina segna quelli della gestione, la gestione che apre il filo
     * segna quelli del giocatore. Nessuno segna come letti i propri.
     */
    public static function segnaLetti(int $userId, bool $daAdmin): void
    {
        Database::run(
            'UPDATE comunicazioni SET letto_at = NOW()
             WHERE user_id = ? AND da_admin = ? AND letto_at IS NULL',
            [$userId, $daAdmin ? 1 : 0]
        );
    }

    /** Quanti messaggi della gestione questo giocatore non ha ancora letto. */
    public static function nonLetti(int $userId): int
    {
        $r = Database::first(
            'SELECT COUNT(*) n FROM comunicazioni
             WHERE user_id = ? AND da_admin = 1 AND letto_at IS NULL',
            [$userId]
        );
        return (int) ($r['n'] ?? 0);
    }

    /**
     * I fili aperti, per il pannello: chi ha scritto, quando, e quanto aspetta.
     *
     * Ordinati per chi ha piu' bisogno di una risposta — prima chi ha scritto
     * e non ha ancora avuto risposta, poi il resto per data. Un elenco in
     * ordine puramente cronologico fa sembrare tutto uguale, e la domanda di
     * tre giorni fa finisce in fondo proprio perche' e' vecchia.
     *
     * @return list<array<string,mixed>>
     */
    public static function fili(): array
    {
        return Database::all(
            "SELECT c.user_id, u.username, u.status,
                    MAX(c.id) AS ultimo_id,
                    MAX(c.created_at) AS ultimo_at,
                    SUM(c.da_admin = 0 AND c.letto_at IS NULL) AS da_leggere,
                    COUNT(*) AS quanti
             FROM comunicazioni c JOIN users u ON u.id = c.user_id
             GROUP BY c.user_id, u.username, u.status
             ORDER BY da_leggere DESC, ultimo_id DESC"
        );
    }

    /** Quanti giocatori aspettano una risposta dalla gestione. */
    public static function daLeggere(): int
    {
        $r = Database::first(
            'SELECT COUNT(DISTINCT user_id) n FROM comunicazioni
             WHERE da_admin = 0 AND letto_at IS NULL'
        );
        return (int) ($r['n'] ?? 0);
    }
}
