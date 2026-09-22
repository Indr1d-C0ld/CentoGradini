<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\Database;
use App\Core\GameConfig;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Game\Eventi;
use App\Game\Bacheca;
use App\Game\Mondo;
use App\Game\Segreto;
use App\Game\Statistiche;
use App\Sim\Folla;
use App\Sim\Luoghi;
use App\Sim\Calendario;
use App\Sim\Meteo;
use App\Support\Audit;
use App\Sim\Orologio;

/**
 * Il pannello di amministrazione.
 *
 * Una pagina sola, e apposta. Un cruscotto che si sfoglia non lo guarda
 * nessuno: qui sopra ci deve stare tutto quello che serve per capire in
 * trenta secondi se il mondo sta girando — il battito, le code, i numeri che
 * crescono — e le tre o quattro leve che si toccano davvero.
 *
 * Non duplica la console: `bin/console.php` resta il posto dove si fanno le
 * cose serie (migrazioni, semina, bilanciamento, gestione degli account).
 * Qui c'è quello che ha senso guardare da un telefono mentre si è fuori.
 */
final class AdminController
{
    public function index(Request $request): Response
    {
        return Response::html(view('admin/index', [
            'title'      => 'Amministrazione',
            'mondo'      => Mondo::adesso(),
            'calendario' => Calendario::stato(),
            'meteo'      => Meteo::a(),
            'eventi'     => Eventi::inCorso(),
            'prossimo'   => Eventi::prossimo(),
            'numeri'     => $this->numeri(),
            'battiti'    => $this->battiti(),
            'posta'      => $this->posta(),
            'config'     => GameConfig::all(),
        ]));
    }

    /**
     * Il battito a richiesta. Il cron lo fa ogni minuto, ma quando si sta
     * guardando una cosa non si aspetta il minuto.
     */
    public function battito(Request $request): Response
    {
        $script = dirname(__DIR__, 2) . '/bin/tick.php';
        if (!is_file($script)) {
            Session::flash('error', 'Non trovo bin/tick.php.');
            return redirect('/admin');
        }
        // Si lancia in un processo a parte apposta: il battito tocca mezzo
        // mondo, e un suo errore non deve portarsi dietro la richiesta web.
        $cmd = escapeshellcmd(PHP_BINARY) . ' ' . escapeshellarg($script) . ' 2>&1';
        $out = [];
        $rc  = 0;
        exec($cmd, $out, $rc);
        Session::flash($rc === 0 ? 'info' : 'error',
            $rc === 0 ? 'Battito eseguito.' : 'Il battito è tornato con errore: ' . implode(' ', array_slice($out, -3)));
        return redirect('/admin');
    }

    /** Cambia una manopola del mondo. */
    public function config(Request $request): Response
    {
        $chiave = trim($request->str('chiave'));
        $valore = trim($request->str('valore'));
        if ($chiave === '') {
            return redirect('/admin');
        }
        $riga = Database::first('SELECT ctype FROM game_config WHERE ckey = ?', [$chiave]);
        if ($riga === null) {
            Session::flash('error', "La chiave «{$chiave}» non esiste. Le manopole si creano con una migrazione, non da qui.");
            return redirect('/admin');
        }
        GameConfig::set($chiave, $valore, (string) $riga['ctype']);
        Session::flash('info', "«{$chiave}» adesso vale «{$valore}».");
        return redirect('/admin');
    }

    // --- Gli utenti ------------------------------------------------------------

    /**
     * L'elenco degli utenti, con accanto il loro personaggio.
     *
     * Un pannello utenti che mostra solo l'account e' inutile in un gioco:
     * la domanda vera non e' «chi si e' iscritto» ma «chi sta giocando, dove
     * si trova, e in che guaio e'». Per questo l'account e il personaggio
     * stanno sulla stessa riga.
     */
    public function utenti(Request $request): Response
    {
        $cerca = trim($request->str('cerca'));
        $dove  = '';
        $par   = [];
        if ($cerca !== '') {
            $dove = 'WHERE u.username LIKE ? OR u.email LIKE ? OR p.nome LIKE ? OR p.cognome LIKE ?';
            $par  = array_fill(0, 4, '%' . $cerca . '%');
        }

        $righe = Database::all(
            "SELECT u.id, u.username, u.email, u.status, u.role, u.created_at,
                    u.last_login_at, u.last_seen_at, u.last_login_ip, u.nota_admin,
                    p.id AS pg_id, p.nome, p.cognome, p.luogo, p.stato AS pg_stato,
                    p.esper, p.scheda, p.sezione, p.anno
             FROM users u
             LEFT JOIN personaggi p ON p.user_id = u.id
             {$dove}
             ORDER BY u.last_seen_at IS NULL, u.last_seen_at DESC, u.id",
            $par
        );

        return Response::html(view('admin/utenti', [
            'title'  => 'Gli utenti',
            'mondo'  => Mondo::adesso(),
            'utenti' => $righe,
            'cerca'  => $cerca,
            'ora'    => time(),
        ]));
    }

    /** La scheda completa di un utente: account, personaggio, e cosa ha combinato. */
    public function utente(Request $request, string $id): Response
    {
        $uid = (int) $id;
        $u = Database::first('SELECT * FROM users WHERE id = ?', [$uid]);
        if ($u === null) {
            Session::flash('error', 'Nessun utente con quel numero.');
            return redirect('/admin/utenti');
        }
        $pg = Database::first('SELECT * FROM personaggi WHERE user_id = ?', [$uid]);

        return Response::html(view('admin/utente', [
            'title'    => (string) $u['username'],
            'mondo'    => Mondo::adesso(),
            'u'        => $u,
            'pg'       => $pg,
            'dettagli' => $pg === null ? [] : $this->dettagliPersonaggio((int) $pg['id']),
            'registro' => Database::all(
                'SELECT * FROM audit_log WHERE (target_type = ? AND target_id = ?) OR actor_user_id = ?
                 ORDER BY id DESC LIMIT 20',
                ['user', $uid, $uid]
            ),
            'ora'      => time(),
        ]));
    }

    /**
     * Tutte le fotografie caricate, in una schermata sola.
     *
     * Moderare una fotografia alla volta aprendo la scheda di ogni utente non
     * e' moderare: e' sperare di inciampare in quella sbagliata. Qui si
     * guardano tutte insieme — che e' l'unico modo in cui l'occhio nota quella
     * che non c'entra niente — e si toglie da qui.
     */
    public function fotografie(Request $request): Response
    {
        return Response::html(view('admin/fotografie', [
            'title' => 'Le fotografie',
            'mondo' => Mondo::adesso(),
            'righe' => Database::all(
                "SELECT p.id, p.nome, p.cognome, p.user_id, p.ritratto_file, p.ritratto_at,
                        p.stato, p.png, u.username, u.status,
                        (SELECT COUNT(*) FROM personaggi q WHERE q.ritratto_file = p.ritratto_file)
                          AS condivisa
                 FROM personaggi p LEFT JOIN users u ON u.id = p.user_id
                 WHERE p.ritratto_file IS NOT NULL
                 ORDER BY p.ritratto_at DESC"
            ),
        ]));
    }

    /**
     * Le azioni di moderazione.
     *
     * Ognuna vuole un motivo scritto, e non per burocrazia: uno stato senza
     * motivo, fra tre mesi, non si sa piu' come interpretarlo, e nel dubbio
     * non si riattiva nessuno. Tutto finisce anche in `audit_log`.
     */
    public function moderazione(Request $request): Response
    {
        $uid    = $request->int('utente');
        $azione = $request->str('azione');
        $motivo = trim($request->str('motivo'));

        $u = Database::first('SELECT * FROM users WHERE id = ?', [$uid]);
        if ($u === null) {
            Session::flash('error', 'Nessun utente con quel numero.');
            return redirect('/admin/utenti');
        }
        // Un amministratore non si sospende e non si retrocede da solo: il
        // modo piu' rapido di restare fuori dal proprio pannello.
        if ($uid === Auth::id() && in_array($azione, ['sospendi', 'bandisci', 'retrocedi'], true)) {
            Session::flash('error', 'Non puoi farlo a te stesso: resteresti fuori.');
            return redirect('/admin/utente/' . $uid);
        }

        $stati = ['attiva' => 'active', 'sospendi' => 'suspended', 'bandisci' => 'banned'];
        if (isset($stati[$azione])) {
            if ($azione !== 'attiva' && $motivo === '') {
                Session::flash('error', 'Serve un motivo: fra sei mesi nessuno se lo ricorda.');
                return redirect('/admin/utente/' . $uid);
            }
            Database::run(
                'UPDATE users SET status = ?, nota_admin = ?, nota_admin_at = NOW() WHERE id = ?',
                [$stati[$azione], mb_substr($motivo, 0, 255), $uid]
            );
        } elseif ($azione === 'promuovi' || $azione === 'retrocedi') {
            Database::run('UPDATE users SET role = ? WHERE id = ?',
                [$azione === 'promuovi' ? 'admin' : 'player', $uid]);
        } elseif ($azione === 'nota') {
            Database::run('UPDATE users SET nota_admin = ?, nota_admin_at = NOW() WHERE id = ?',
                [mb_substr($motivo, 0, 255), $uid]);
        } else {
            Session::flash('error', 'Azione sconosciuta.');
            return redirect('/admin/utente/' . $uid);
        }

        Audit::log('moderazione:' . $azione, Auth::id(), 'user', $uid,
            ['motivo' => $motivo], $request->ip());
        Session::flash('info', 'Fatto.');
        return redirect('/admin/utente/' . $uid);
    }

    // --- La situazione, adesso ---------------------------------------------------

    /**
     * La mappa della situazione: ogni luogo, chi c'e', e quanto scotta.
     *
     * E' la schermata che serve davvero a chi amministra un mondo
     * persistente: non «quanti utenti ho» ma «cosa sta succedendo in questo
     * momento, e dove». Mostra tutti e ventidue i luoghi anche quando sono
     * vuoti, perche' un quartiere vuoto e' esso stesso un'informazione.
     */
    /**
     * I dati della carta per l'amministrazione.
     *
     * E' la stessa carta del giocatore, con una differenza sola: i giocatori
     * si distinguono dagli abitanti mossi dal motore. E' un dato che sulla
     * carta del giocatore non deve comparire mai.
     */
    public function carta(Request $request): Response
    {
        return Response::json(Mondo::carta(null, true));
    }

    public function mappa(Request $request): Response
    {
        $gts   = Orologio::lineare();
        $luoghi = [];

        foreach (Luoghi::tutti() as $lkey => $l) {
            $presenti = Database::all(
                'SELECT id, user_id, nome, cognome, png, esper, stato, ritratto_file
                 FROM personaggi
                 WHERE luogo = ? AND verso IS NULL AND stato = ? ORDER BY png IS NOT NULL, id',
                [$lkey, 'attivo']
            );
            $luoghi[] = [
                'lkey'      => $lkey,
                'nome'      => (string) $l['nome'],
                'aperto'    => Luoghi::accessibile($lkey, $gts)[0],
                'folla'     => Folla::a($lkey, $gts),
                'calore'    => Segreto::calore($lkey, $gts),
                'giocatori' => array_values(array_filter($presenti,
                    static fn (array $p): bool => $p['png'] === null)),
                'abitanti'  => array_values(array_filter($presenti,
                    static fn (array $p): bool => $p['png'] !== null)),
                'avvisi'    => Bacheca::quantiAvvisi($lkey),
            ];
        }

        return Response::html(view('admin/mappa', [
            'title'    => 'La situazione',
            'mondo'    => Mondo::adesso(),
            'luoghi'   => $luoghi,
            'viaggio'  => Database::all(
                'SELECT p.nome, p.cognome, p.luogo, p.verso, p.arrivo_gts, p.png
                 FROM personaggi p WHERE p.verso IS NOT NULL AND p.stato = ? ORDER BY p.arrivo_gts',
                ['attivo']
            ),
            'episodi'  => Database::all(
                "SELECT e.id, e.ckey, e.titolo, e.luogo, e.scena, e.scade_reale,
                        (SELECT COUNT(*) FROM episodio_cast c WHERE c.episodio_id = e.id) AS quanti
                 FROM episodi e WHERE e.stato = 'aperto' ORDER BY e.id DESC"
            ),
            'voci'     => Database::all(
                'SELECT v.tipo, v.luogo, v.gts, COUNT(vv.personaggio_id) AS quanti,
                        MAX(vv.passaggi) AS passaggi
                 FROM voci v LEFT JOIN voci_versioni vv ON vv.voce_id = v.id
                 GROUP BY v.id ORDER BY v.gts DESC LIMIT 12'
            ),
            'pericolo' => Database::all(
                "SELECT p.id, p.nome, p.cognome, COUNT(s.chi_sa_id) AS sanno
                 FROM personaggi p JOIN sanno s ON s.esper_id = p.id
                 WHERE p.stato = 'attivo' AND s.come = 'scoperto' AND p.png IS NULL
                 GROUP BY p.id ORDER BY sanno DESC"
            ),
            'gts'      => $gts,
        ]));
    }

    /** @return array<string,mixed> */
    private function dettagliPersonaggio(int $pgId): array
    {
        $uno = static fn (string $sql, array $p = []): int
            => (int) (Database::first($sql, $p)['n'] ?? 0);

        return [
            'poteri'    => Database::all(
                'SELECT p.nome, pp.primario, pp.controllo, pp.usi
                 FROM personaggio_poteri pp JOIN poteri p ON p.pkey = pp.pkey
                 WHERE pp.personaggio_id = ? ORDER BY pp.primario DESC, p.nome', [$pgId]),
            'tratti'    => Database::all(
                'SELECT t.nome FROM personaggio_tratti pt JOIN tratti t ON t.tkey = pt.tkey
                 WHERE pt.personaggio_id = ?', [$pgId]),
            'incidenti' => $uno('SELECT COUNT(*) n FROM incidenti WHERE attore_id = ?', [$pgId]),
            'sanno'     => $uno("SELECT COUNT(*) n FROM sanno WHERE esper_id = ? AND come = 'scoperto'", [$pgId]),
            'confidato' => $uno("SELECT COUNT(*) n FROM sanno WHERE esper_id = ? AND come = 'confidato'", [$pgId]),
            'legami'    => $uno('SELECT COUNT(*) n FROM legami WHERE da_id = ?', [$pgId]),
            'ricordi'   => $uno('SELECT COUNT(*) n FROM ricordi WHERE personaggio_id = ?', [$pgId]),
            'voci'      => $uno('SELECT COUNT(*) n FROM voci_versioni WHERE personaggio_id = ?', [$pgId]),
            'club'      => Database::all(
                'SELECT c.nome FROM club_membri m JOIN club c ON c.ckey = m.ckey
                 WHERE m.personaggio_id = ?', [$pgId]),
        ];
    }

    // --- Le statistiche ----------------------------------------------------------

    public function statistiche(Request $request): Response
    {
        return Response::html(view('admin/statistiche', [
            'title'       => 'Statistiche',
            'mondo'       => Mondo::adesso(),
            's_mondo'     => Statistiche::mondo(),
            'popolazione' => Statistiche::popolazione(),
            'segreto'     => Statistiche::segreto(),
            'legami'      => Statistiche::legami(),
            'voci'        => Statistiche::voci(),
            'episodi'     => Statistiche::episodi(),
            'quartiere'   => Statistiche::quartiere(),
            'macchina'    => Statistiche::macchina(),
        ]));
    }

    // --- Gli accessi --------------------------------------------------------------

    /**
     * Chi entra, da dove, e chi ci prova senza riuscirci.
     *
     * Tutto viene da `audit_log`, che registra gia' accessi riusciti,
     * falliti, iscrizioni, conferme e uscite. Non serviva una tabella nuova:
     * serviva guardare quella che c'era.
     *
     * L'aggregazione per **indirizzo** e' la vista che conta davvero. Un
     * indirizzo con venti tentativi falliti e zero riusciti su tre account
     * diversi non e' un utente distratto.
     */
    public function accessi(Request $request): Response
    {
        $perIndirizzo = Database::all(
            "SELECT a.ip,
                    COUNT(*) tentativi,
                    SUM(a.action = 'auth.login') riusciti,
                    SUM(a.action = 'auth.login_failed') falliti,
                    COUNT(DISTINCT a.actor_user_id) account,
                    MIN(a.created_at) primo,
                    MAX(a.created_at) ultimo
             FROM audit_log a
             WHERE a.ip IS NOT NULL AND a.action IN ('auth.login', 'auth.login_failed', 'auth.register')
             GROUP BY a.ip
             ORDER BY falliti DESC, tentativi DESC
             LIMIT 60"
        );
        foreach ($perIndirizzo as &$r) {
            $r['indirizzo'] = self::leggibile($r['ip']);
        }
        unset($r);

        $recenti = Database::all(
            "SELECT a.action, a.ip, a.created_at, a.meta, u.username
             FROM audit_log a LEFT JOIN users u ON u.id = a.actor_user_id
             WHERE a.action LIKE 'auth.%'
             ORDER BY a.id DESC LIMIT 50"
        );
        foreach ($recenti as &$r) {
            $r['indirizzo'] = self::leggibile($r['ip']);
        }
        unset($r);

        return Response::html(view('admin/accessi', [
            'title'      => 'Accessi e origini',
            'mondo'      => Mondo::adesso(),
            'indirizzi'  => $perIndirizzo,
            'recenti'    => $recenti,
            'collegati'  => Database::all(
                'SELECT username, last_seen_at, last_login_at, last_login_ip, status, role
                 FROM users WHERE last_seen_at >= NOW() - INTERVAL 30 MINUTE
                 ORDER BY last_seen_at DESC'),
            'freni'      => Database::all(
                'SELECT rkey, hits, reset_at FROM rate_limits
                 WHERE reset_at > NOW() ORDER BY hits DESC LIMIT 20'),
        ]));
    }

    /** Un indirizzo binario torna leggibile, o «—» se non c'e'. */
    private static function leggibile(mixed $ip): string
    {
        if ($ip === null || $ip === '') {
            return '—';
        }
        $s = @inet_ntop(is_string($ip) ? $ip : (string) $ip);
        return $s === false ? '?' : $s;
    }

    // --- Le leve del mondo ---------------------------------------------------------

    /**
     * Tutte le manopole, raggruppate per famiglia e con il loro tipo.
     *
     * Il cruscotto ne aveva una versione sbrigativa: un menu a tendina con
     * quarantanove voci e una casella di testo. Funzionava e si leggeva male,
     * e soprattutto non diceva **che cosa fa** ciascuna manopola — la nota
     * c'era a database e non la mostrava nessuno. Qui ogni riga ha il suo
     * campo del tipo giusto, la sua spiegazione, e quando e' stata toccata
     * l'ultima volta.
     */
    public function impostazioni(Request $request): Response
    {
        $cerca = trim($request->str('cerca'));
        $righe = Database::all('SELECT * FROM game_config ORDER BY ckey');

        $famiglie = [];
        foreach ($righe as $r) {
            $k = (string) $r['ckey'];
            if ($cerca !== '' && stripos($k . ' ' . (string) $r['note'], $cerca) === false) {
                continue;
            }
            $famiglie[explode('.', $k)[0]][] = $r;
        }
        ksort($famiglie);

        return Response::html(view('admin/impostazioni', [
            'title'    => 'Le leve del mondo',
            'mondo'    => Mondo::adesso(),
            'famiglie' => $famiglie,
            'cerca'    => $cerca,
            'quante'   => count($righe),
            'titoli'   => self::TITOLI_FAMIGLIA,
        ]));
    }

    /**
     * Come si chiama, a parole, ciascuna famiglia di manopole.
     *
     * @var array<string, string>
     */
    private const TITOLI_FAMIGLIA = [
        'auth'      => 'Accessi e iscrizioni',
        'bacheca'   => 'La bacheca',
        'biglietti' => 'I biglietti',
        'blocco'    => 'Spegnere i poteri',
        'clock'     => 'L\'orologio del mondo',
        'club'      => 'I club',
        'episodi'   => 'Gli episodi',
        'legami'    => 'Affetto e fraintendimento',
        'limits'    => 'I freni',
        'mail'      => 'La posta',
        'mondo'     => 'Il quartiere',
        'pg'        => 'La creazione del personaggio',
        'segreto'   => 'Il Segreto',
        'voci'      => 'Le voci',
        'world'     => 'Il seme del mondo',
    ];

    // --- Raccolta ---------------------------------------------------------------

    /** @return array<string, int> */
    private function numeri(): array
    {
        $uno = static fn (string $sql, array $p = []): int
            => (int) (Database::first($sql, $p)['n'] ?? 0);

        return [
            'utenti'        => $uno('SELECT COUNT(*) n FROM users'),
            'utenti attivi' => $uno('SELECT COUNT(*) n FROM users WHERE status = ?', ['active']),
            'personaggi'    => $uno('SELECT COUNT(*) n FROM personaggi WHERE png IS NULL AND stato = ?', ['attivo']),
            'traslocati'    => $uno('SELECT COUNT(*) n FROM personaggi WHERE png IS NULL AND stato = ?', ['trasferito']),
            'abitanti'      => $uno('SELECT COUNT(*) n FROM personaggi WHERE png IS NOT NULL'),
            'incidenti'     => $uno('SELECT COUNT(*) n FROM incidenti'),
            'anomalie'      => $uno('SELECT COUNT(*) n FROM anomalie'),
            'legami'        => $uno('SELECT COUNT(*) n FROM legami'),
            'episodi vivi'  => $uno('SELECT COUNT(*) n FROM episodi WHERE stato = ?', ['aperto']),
            'ricordi'       => $uno('SELECT COUNT(*) n FROM ricordi'),
            'voci'          => $uno('SELECT COUNT(*) n FROM voci'),
            'versioni'      => $uno('SELECT COUNT(*) n FROM voci_versioni'),
            'avvisi'        => $uno('SELECT COUNT(*) n FROM bacheca WHERE scade_gts > ?', [Orologio::lineare()]),
            'biglietti'     => $uno('SELECT COUNT(*) n FROM biglietti WHERE letto_gts IS NULL'),
            'iscritti club' => $uno('SELECT COUNT(*) n FROM club_membri'),
        ];
    }

    /** @return list<array<string,mixed>> */
    private function battiti(): array
    {
        return Database::all(
            'SELECT started_at, finished_at, ok, duration_ms, tasks, note
             FROM tick_runs ORDER BY id DESC LIMIT 12'
        );
    }

    /**
     * Lo stato della posta lo sa gia' Posta::stato(), che e' quello che usa
     * anche la console: non si riscrive qui, o le due schermate comincerebbero
     * a dire numeri diversi.
     *
     * @return array<string,mixed>
     */
    private function posta(): array
    {
        return \App\Core\Posta::stato();
    }
}
