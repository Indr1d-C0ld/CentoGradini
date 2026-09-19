<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\GameConfig;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Game\Eventi;
use App\Game\Mondo;
use App\Sim\Calendario;
use App\Sim\Meteo;
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
