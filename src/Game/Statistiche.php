<?php

declare(strict_types=1);

namespace App\Game;

use App\Core\Database;
use App\Sim\Calendario;
use App\Sim\Folla;
use App\Sim\Luoghi;
use App\Sim\Meteo;
use App\Sim\Orologio;

/**
 * Le statistiche del quartiere.
 *
 * Non sono un cruscotto di vanità: servono a rispondere alle domande che
 * decidono se il gioco sta funzionando. Il Segreto morde davvero o si gioca
 * impunemente? I fraintendimenti si accumulano senza mai sciogliersi? Le voci
 * arrivano a qualcuno o muoiono dove nascono? Ci sono luoghi dove non va
 * mai nessuno?
 *
 * Ogni metodo torna dati grezzi e basta: la vista decide come mostrarli, e il
 * comando `bilancio` può usarli senza passare dal web.
 */
final class Statistiche
{
    /** Il mondo adesso: orologio, stagione, tempo, eventi, salute del battito. */
    public static function mondo(): array
    {
        $gts = Orologio::lineare();
        $cal = Calendario::stato();
        $ultimo = Database::first('SELECT started_at, ok, duration_ms FROM tick_runs ORDER BY id DESC LIMIT 1');

        return [
            'quando'     => Orologio::esteso(),
            'fase'       => (string) $cal['fase_nome'],
            'stagione'   => (string) $cal['stagione'],
            'scuola'     => (bool) $cal['scuola'],
            'festa'      => $cal['festa'],
            'vacanza'    => $cal['vacanza'],
            'meteo'      => Meteo::a(),
            'eventi'     => Eventi::inCorso(),
            'prossimo'   => Eventi::prossimo(),
            'gts'        => $gts,
            'battito_da' => $ultimo === null ? null : time() - strtotime((string) $ultimo['started_at']),
            'battito_ok' => $ultimo !== null && (int) $ultimo['ok'] === 1,
            'battito_ms' => $ultimo === null ? null : (int) $ultimo['duration_ms'],
        ];
    }

    /** Quanta gente c'è, e com'è fatta. */
    public static function popolazione(): array
    {
        $n = static fn (string $sql, array $p = []): int
            => (int) (Database::first($sql, $p)['n'] ?? 0);

        return [
            'utenti'        => $n('SELECT COUNT(*) n FROM users'),
            'attivi'        => $n('SELECT COUNT(*) n FROM users WHERE status = ?', ['active']),
            'da_confermare' => $n('SELECT COUNT(*) n FROM users WHERE status = ?', ['pending']),
            'fermati'       => $n('SELECT COUNT(*) n FROM users WHERE status IN (?, ?)', ['suspended', 'banned']),
            'collegati'     => $n('SELECT COUNT(*) n FROM users WHERE last_seen_at >= NOW() - INTERVAL 10 MINUTE'),
            'oggi'          => $n('SELECT COUNT(*) n FROM users WHERE last_seen_at >= NOW() - INTERVAL 1 DAY'),
            'settimana'     => $n('SELECT COUNT(*) n FROM users WHERE last_seen_at >= NOW() - INTERVAL 7 DAY'),
            'personaggi'    => $n('SELECT COUNT(*) n FROM personaggi WHERE png IS NULL AND stato = ?', ['attivo']),
            'traslocati'    => $n('SELECT COUNT(*) n FROM personaggi WHERE png IS NULL AND stato = ?', ['trasferito']),
            'abbozzi'       => $n('SELECT COUNT(*) n FROM personaggi WHERE png IS NULL AND scheda <> ?', ['completa']),
            'abitanti'      => $n('SELECT COUNT(*) n FROM personaggi WHERE png IS NOT NULL'),
            'per_classe'    => Database::all(
                "SELECT sezione, anno, COUNT(*) n FROM personaggi
                 WHERE png IS NULL AND stato = 'attivo' GROUP BY sezione, anno ORDER BY sezione, anno"),
            'esper'         => $n("SELECT COUNT(*) n FROM personaggi WHERE png IS NULL AND stato = 'attivo' AND esper = 1"),
            'non_esper'     => $n("SELECT COUNT(*) n FROM personaggi WHERE png IS NULL AND stato = 'attivo' AND esper = 0"),
        ];
    }

    /**
     * Il Segreto: morde o no?
     *
     * La riga che conta e' la percentuale di usi notati. Se e' bassissima il
     * gioco non ha tensione; se e' altissima nessuno usa piu' i poteri, e
     * meta' del gioco smette di esistere.
     */
    public static function segreto(): array
    {
        $n = static fn (string $sql, array $p = []): int
            => (int) (Database::first($sql, $p)['n'] ?? 0);

        $usi    = $n('SELECT COUNT(*) n FROM incidenti');
        $notati = $n('SELECT COUNT(*) n FROM incidenti WHERE notato_da > 0');

        return [
            'usi'          => $usi,
            'notati'       => $notati,
            'quota_notati' => $usi > 0 ? round(100 * $notati / $usi, 1) : 0.0,
            'per_stato'    => Database::all('SELECT stato, COUNT(*) n FROM incidenti GROUP BY stato ORDER BY n DESC'),
            'per_potere'   => Database::all(
                'SELECT i.pkey, p.nome, COUNT(*) n, SUM(i.notato_da > 0) notati
                 FROM incidenti i LEFT JOIN poteri p ON p.pkey = i.pkey
                 GROUP BY i.pkey ORDER BY n DESC LIMIT 12'),
            'anomalie'     => $n('SELECT COUNT(*) n FROM anomalie'),
            'collegate'    => $n('SELECT COUNT(*) n FROM anomalie WHERE collegata = 1'),
            'scoperti'     => $n("SELECT COUNT(*) n FROM sanno WHERE come = 'scoperto'"),
            'confidati'    => $n("SELECT COUNT(*) n FROM sanno WHERE come = 'confidato'"),
            'traslochi'    => $n('SELECT COALESCE(SUM(traslochi), 0) n FROM personaggi'),
            'calore'       => Database::all(
                'SELECT luogo, valore FROM calore WHERE valore > 0 ORDER BY valore DESC LIMIT 8'),
            'a_rischio'    => Database::all(
                "SELECT p.nome, p.cognome, COUNT(*) sanno FROM sanno s
                 JOIN personaggi p ON p.id = s.esper_id
                 WHERE s.come = 'scoperto' AND p.stato = 'attivo' AND p.png IS NULL
                 GROUP BY p.id ORDER BY sanno DESC LIMIT 8"),
        ];
    }

    /**
     * I legami: l'affetto cresce, e il fraintendimento si scioglie mai?
     *
     * La seconda domanda e' quella importante. Il fraintendimento non decade
     * col tempo — e' la regola che non ammette eccezioni — quindi se nessuno
     * chiarisce mai, il quartiere si ingolfa e la media sale senza fermarsi.
     */
    public static function legami(): array
    {
        $r = Database::first(
            'SELECT COUNT(*) n, AVG(affetto) ma, AVG(fraintendimento) mf,
                    SUM(affetto > 0) positivi, SUM(affetto < 0) negativi,
                    SUM(fraintendimento >= 50) grossi, MAX(fraintendimento) peggiore
             FROM legami'
        ) ?? [];

        return [
            'quanti'    => (int) ($r['n'] ?? 0),
            'affetto'   => round((float) ($r['ma'] ?? 0), 1),
            'malinteso' => round((float) ($r['mf'] ?? 0), 1),
            'positivi'  => (int) ($r['positivi'] ?? 0),
            'negativi'  => (int) ($r['negativi'] ?? 0),
            'grossi'    => (int) ($r['grossi'] ?? 0),
            'peggiore'  => (int) ($r['peggiore'] ?? 0),
            'gesti'     => Database::all(
                'SELECT g.gkey, ge.nome, COUNT(*) n, SUM(g.riuscito) riusciti, SUM(g.visto_da) visti
                 FROM gesti_fatti g LEFT JOIN gesti ge ON ge.gkey = g.gkey
                 GROUP BY g.gkey ORDER BY n DESC LIMIT 12'),
        ];
    }

    /**
     * Le voci: arrivano a qualcuno, o muoiono dove sono nate?
     *
     * Se i passaggi medi restano a zero la propagazione non funziona, e le
     * voci sono solo un registro di fatti. Il bello comincia dal secondo
     * passaggio in poi, quando il testo non somiglia piu' all'originale.
     */
    public static function voci(): array
    {
        $r = Database::first(
            'SELECT COUNT(*) n, AVG(precisione) mp, AVG(passaggi) mpa,
                    MAX(passaggi) max_pa, SUM(passaggi >= 3) pettegolezzi
             FROM voci_versioni'
        ) ?? [];

        return [
            'fatti'        => (int) (Database::first('SELECT COUNT(*) n FROM voci')['n'] ?? 0),
            'versioni'     => (int) ($r['n'] ?? 0),
            'precisione'   => round((float) ($r['mp'] ?? 0), 1),
            'passaggi'     => round((float) ($r['mpa'] ?? 0), 2),
            'max_passaggi' => (int) ($r['max_pa'] ?? 0),
            'pettegolezzi' => (int) ($r['pettegolezzi'] ?? 0),
            'per_tipo'     => Database::all(
                'SELECT v.tipo, COUNT(DISTINCT v.id) fatti, COUNT(vv.personaggio_id) versioni
                 FROM voci v LEFT JOIN voci_versioni vv ON vv.voce_id = v.id
                 GROUP BY v.tipo ORDER BY versioni DESC'),
            'piu_diffuse'  => Database::all(
                'SELECT v.tipo, v.luogo, v.gts, COUNT(vv.personaggio_id) quanti, MAX(vv.passaggi) passaggi
                 FROM voci v JOIN voci_versioni vv ON vv.voce_id = v.id
                 GROUP BY v.id ORDER BY quanti DESC LIMIT 8'),
        ];
    }

    /** Gli episodi: quanti se ne giocano, e quanti li gioca il motore. */
    public static function episodi(): array
    {
        $scelte = Database::first('SELECT COUNT(*) n, SUM(da_solo) soli, SUM(riuscita) riuscite FROM episodio_scelte') ?? [];
        $tot    = (int) ($scelte['n'] ?? 0);

        return [
            'aperti'     => (int) (Database::first("SELECT COUNT(*) n FROM episodi WHERE stato = 'aperto'")['n'] ?? 0),
            'conclusi'   => (int) (Database::first("SELECT COUNT(*) n FROM episodi WHERE stato = 'concluso'")['n'] ?? 0),
            'scelte'     => $tot,
            'da_agente'  => (int) ($scelte['soli'] ?? 0),
            'quota_agente' => $tot > 0 ? round(100 * (int) ($scelte['soli'] ?? 0) / $tot, 1) : 0.0,
            'quota_riuscite' => $tot > 0 ? round(100 * (int) ($scelte['riuscite'] ?? 0) / $tot, 1) : 0.0,
            'per_copione' => Database::all(
                'SELECT e.ckey, c.titolo, COUNT(*) n FROM episodi e
                 LEFT JOIN copioni c ON c.ckey = e.ckey GROUP BY e.ckey ORDER BY n DESC'),
            'ricordi'    => (int) (Database::first('SELECT COUNT(*) n FROM ricordi')['n'] ?? 0),
        ];
    }

    /**
     * Il quartiere: dove va la gente, e dove non va nessuno.
     *
     * Un luogo che nessuno ha mai visitato non e' un problema di per se' —
     * la montagna e' lontana apposta — ma se sono tanti vuol dire che la
     * mappa e' piu' grande di quanto il gioco riesca a riempire.
     */
    public static function quartiere(): array
    {
        $visite = [];
        foreach (Database::all(
            'SELECT luogo, COUNT(*) n, COUNT(DISTINCT personaggio_id) chi FROM presenze GROUP BY luogo'
        ) as $r) {
            $visite[(string) $r['luogo']] = ['visite' => (int) $r['n'], 'persone' => (int) $r['chi']];
        }

        $gts   = Orologio::lineare();
        $righe = [];
        foreach (Luoghi::tutti() as $lkey => $l) {
            $righe[] = [
                'lkey'    => $lkey,
                'nome'    => (string) $l['nome'],
                'visite'  => $visite[$lkey]['visite'] ?? 0,
                'persone' => $visite[$lkey]['persone'] ?? 0,
                'ora'     => (int) (Database::first(
                    "SELECT COUNT(*) n FROM personaggi WHERE luogo = ? AND verso IS NULL AND stato = 'attivo'",
                    [$lkey])['n'] ?? 0),
                'folla'   => Folla::a($lkey, $gts),
            ];
        }
        usort($righe, static fn (array $a, array $b): int => $b['visite'] <=> $a['visite']);

        return [
            'luoghi'    => $righe,
            'mai_visti' => count(array_filter($righe, static fn (array $r): bool => $r['visite'] === 0)),
        ];
    }

    /** La macchina: battiti, durate, posta. */
    public static function macchina(): array
    {
        return [
            'battiti_24h' => (int) (Database::first(
                'SELECT COUNT(*) n FROM tick_runs WHERE started_at >= NOW() - INTERVAL 1 DAY')['n'] ?? 0),
            'guasti_24h'  => (int) (Database::first(
                'SELECT COUNT(*) n FROM tick_runs WHERE ok = 0 AND started_at >= NOW() - INTERVAL 1 DAY')['n'] ?? 0),
            'durata'      => Database::first(
                'SELECT AVG(duration_ms) media, MAX(duration_ms) massimo FROM tick_runs
                 WHERE started_at >= NOW() - INTERVAL 1 DAY'),
            'posta'       => \App\Core\Posta::stato(),
            'ultimi'      => Database::all(
                'SELECT started_at, ok, duration_ms, tasks FROM tick_runs ORDER BY id DESC LIMIT 8'),
        ];
    }
}
