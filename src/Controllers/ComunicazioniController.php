<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Game\Comunicazioni;
use App\Game\Mondo;
use App\Support\Audit;

/**
 * Le comunicazioni: il giocatore da una parte, la gestione dall'altra.
 *
 * Due azioni per ogni lato, e il lato lo decide chi sta guardando — non una
 * rotta diversa. Il controllo del diritto sta in `filo()`, in un posto solo,
 * per lo stesso motivo per cui sta in un posto solo in ProfiloController.
 */
final class ComunicazioniController
{
    // --- Il giocatore ---------------------------------------------------------

    public function mie(Request $request): Response
    {
        $uid = Auth::id();
        if ($uid === null) {
            return redirect('/accesso');
        }
        $filo = Comunicazioni::filo($uid);
        // Si segna come letto DOPO aver letto il filo, se no i messaggi nuovi
        // arriverebbero alla pagina gia' spenti e non si distinguerebbero.
        Comunicazioni::segnaLetti($uid, true);

        return Response::html(view('gioco/comunicazioni', [
            'title' => 'Comunicazioni',
            'mondo' => Mondo::adesso(),
            'filo'  => $filo,
        ]));
    }

    public function rispondi(Request $request): Response
    {
        $uid = Auth::id();
        if ($uid === null) {
            return redirect('/accesso');
        }
        $res = Comunicazioni::scrivi($uid, $uid, false, $request->str('testo'));
        Session::flash($res['ok'] ? 'success' : 'error',
            $res['ok'] ? 'Mandato. Ti rispondiamo appena possiamo.' : ($res['error'] ?? 'Non riesco a mandarlo.'));
        return redirect('/comunicazioni');
    }

    // --- La gestione ----------------------------------------------------------

    public function elenco(Request $request): Response
    {
        return Response::html(view('admin/comunicazioni', [
            'title' => 'Le comunicazioni',
            'mondo' => Mondo::adesso(),
            'fili'  => Comunicazioni::fili(),
        ]));
    }

    public function filo(Request $request, string $id): Response
    {
        $uid = (int) $id;
        $u = Database::first('SELECT id, username, status, email FROM users WHERE id = ?', [$uid]);
        if ($u === null) {
            Session::flash('error', 'Nessun utente con quel numero.');
            return redirect('/admin/comunicazioni');
        }
        $filo = Comunicazioni::filo($uid);
        Comunicazioni::segnaLetti($uid, false);

        return Response::html(view('admin/comunicazione', [
            'title' => 'Comunicazioni con ' . $u['username'],
            'mondo' => Mondo::adesso(),
            'u'     => $u,
            'filo'  => $filo,
        ]));
    }

    public function scrivi(Request $request): Response
    {
        $uid = $request->int('utente');
        $res = Comunicazioni::scrivi($uid, Auth::id(), true, $request->str('testo'));
        if ($res['ok']) {
            // Nel registro va il fatto, non il testo: quello sta nel filo, e
            // ricopiarlo qui vorrebbe dire conservarlo in due posti che prima
            // o poi si contraddicono.
            Audit::log('admin.comunicazione', Auth::id(), 'user', $uid, [], $request->ip());
            Session::flash('success', 'Messaggio mandato.');
        } else {
            Session::flash('error', $res['error'] ?? 'Non riesco a mandarlo.');
        }
        return redirect('/admin/comunicazioni/' . $uid);
    }
}
