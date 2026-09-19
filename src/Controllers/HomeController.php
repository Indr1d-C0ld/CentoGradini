<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\Config;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

final class HomeController
{
    /** La soglia: cos'e' Cento Gradini, e le due porte (accesso / iscrizione). */
    public function index(Request $request): Response
    {
        if (Auth::check() && Auth::status() === 'active') {
            return Response::redirect(url('/quartiere'));
        }

        // La pagina d'ingresso di un mondo persistente deve dire che il mondo
        // c'e' ed e' acceso. Per ora si puo' dire solo quanta gente ci abita:
        // il resto arriva con F1, quando il quartiere comincera' a girare.
        $abitanti = 0;
        try {
            $abitanti = (int) (Database::first(
                "SELECT COUNT(*) AS n FROM users WHERE status = 'active'"
            )['n'] ?? 0);
        } catch (\Throwable) {
            // Prima delle migrazioni la pagina deve comunque aprirsi.
        }

        return Response::html(view('home', [
            'title'    => 'Cento Gradini',
            'abitanti' => $abitanti,
        ]));
    }

    /** Le fonti e il rispetto per l'opera. Una pagina, non una nota a pie' di pagina. */
    public function opera(Request $request): Response
    {
        return Response::html(view('opera', ['title' => 'L\'opera originale']));
    }

    /**
     * Il santuario: la sala dedicata a Izumi Matsumoto.
     *
     * Le fotografie si trovano da sole in assets/img/maestro/: cosi' se ne
     * possono aggiungere o togliere senza toccare il codice, e finche' non ce
     * n'e' nessuna la pagina si apre lo stesso — con il suo nome, le sue date
     * e la sua storia. Una pagina onesta senza ritratto vale piu' di una con
     * un ritratto preso da qualche parte.
     */
    public function santuario(Request $request): Response
    {
        $dir = (string) ($GLOBALS['__project_root'] ?? '') . '/assets/img/maestro';
        $didascalie = [
            'tavolo'  => 'Al tavolo da disegno, con Madoka sullo schermo.',
            'ritratto'=> 'Ritratto.',
            'bosco'   => 'Fuori città.',
        ];
        $ritratti = [];
        foreach (glob($dir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [] as $f) {
            $nome = basename($f);
            $chiave = pathinfo($nome, PATHINFO_FILENAME);
            $ritratti[] = ['file' => $nome, 'didascalia' => $didascalie[$chiave] ?? ''];
        }
        // Ordine stabile, qualunque cosa restituisca il filesystem.
        usort($ritratti, static fn (array $a, array $b): int => strcmp($a['file'], $b['file']));

        return Response::html(view('santuario', [
            'title'    => 'Izumi Matsumoto',
            'ritratti' => $ritratti,
        ]));
    }

    /** Il quartiere. Segnaposto di F0: il mondo vero arriva con F1. */
    public function quartiere(Request $request): Response
    {
        return Response::html(view('gioco/quartiere', [
            'title'  => 'Il quartiere',
            'utente' => Auth::user(),
        ]));
    }

    /** Sonda di servizio: il monitoraggio e il battito la usano per sapere se l'app e' viva. */
    /**
     * Il service worker, servito dalla radice dell'applicazione.
     *
     * Non si puo' lasciare in `assets/js/`: un service worker comanda solo
     * sotto la cartella da cui e' servito, e da li' governerebbe soltanto
     * gli script. Servito da `/sw.js` il suo raggio d'azione e' tutta
     * l'applicazione, che e' quello che serve.
     */
    public function serviceWorker(Request $request): Response
    {
        $f = dirname(__DIR__, 2) . '/assets/js/sw.js';
        if (!is_file($f)) {
            return Response::text('// assente', 404);
        }
        return Response::text((string) file_get_contents($f))
            ->withHeader('Content-Type', 'text/javascript; charset=utf-8')
            ->withHeader('Cache-Control', 'no-cache');
    }

    /** Il manifesto, accanto al service worker per la stessa ragione. */
    public function manifesto(Request $request): Response
    {
        $f = dirname(__DIR__, 2) . '/assets/manifest.webmanifest';
        if (!is_file($f)) {
            return Response::text('{}', 404);
        }
        return Response::text((string) file_get_contents($f))
            ->withHeader('Content-Type', 'application/manifest+json; charset=utf-8');
    }

    public function salute(Request $request): Response
    {
        $db = false;
        try {
            $db = Database::isReachable();
        } catch (\Throwable) {
        }
        return Response::json([
            'app'  => 'orangeroad',
            'nome' => (string) Config::get('app.name', 'Cento Gradini'),
            'ok'   => $db,
            'db'   => $db,
            'time' => date('c'),
        ], $db ? 200 : 503);
    }
}
