<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Game\Abitanti;
use App\Game\Bacheca;
use App\Game\Club;
use App\Game\Eventi;
use App\Game\Mondo;
use App\Game\Personaggio;
use App\Game\Voci;

/**
 * Le schermate di F6: quello che si sa, quello che si scrive, e chi c'è.
 *
 * Stanno tutte insieme perché sono la stessa cosa vista da tre lati — il
 * quartiere come rete di persone invece che come mappa di posti — e perché
 * condividono lo stesso preambolo: serve un personaggio completo, e niente
 * di quello che c'è qui dentro si può fare da sloggati.
 */
final class QuartiereVivoController
{
    /** @return array<string,mixed>|null */
    private function mio(): ?array
    {
        $id = Auth::id();
        if ($id === null) {
            return null;
        }
        $pg = Personaggio::perUtente($id);
        return ($pg === null || Personaggio::daCompletare($pg)) ? null : $pg;
    }

    // --- Le voci ---------------------------------------------------------------

    public function voci(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/quartiere');
        }
        return Response::html(view('gioco/voci', [
            'title' => 'Quello che si dice',
            'pg'    => $pg,
            'mondo' => Mondo::adesso(),
            'voci'  => Voci::per((int) $pg['id'], 40),
        ]));
    }

    // --- La bacheca ------------------------------------------------------------

    public function bacheca(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/quartiere');
        }
        $lkey = (string) $pg['luogo'];
        return Response::html(view('gioco/bacheca', [
            'title'      => 'La bacheca',
            'pg'         => $pg,
            'mondo'      => Mondo::adesso(),
            'avvisi'     => Bacheca::avvisi($lkey),
            'biglietti'  => Bacheca::quiPer((int) $pg['id'], $lkey),
            'presenti'   => Personaggio::presenti($lkey, (int) $pg['id']),
        ]));
    }

    public function affiggi(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/quartiere');
        }
        $res = Bacheca::affiggi(
            $pg, (string) $pg['luogo'], $request->str('tipo'),
            $request->str('titolo'), $request->str('testo'), $request->str('firma')
        );
        Session::flash($res['ok'] ? 'info' : 'error',
            $res['ok'] ? 'Il foglio è appeso.' : ($res['error'] ?? 'Non si può.'));
        return redirect('/bacheca');
    }

    public function stacca(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/quartiere');
        }
        $res = Bacheca::stacca($pg, $request->int('avviso'));
        if (!$res['ok']) {
            Session::flash('error', $res['error'] ?? 'Non si può.');
        }
        return redirect('/bacheca');
    }

    // --- I biglietti -----------------------------------------------------------

    public function biglietti(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/quartiere');
        }
        return Response::html(view('gioco/biglietti', [
            'title'   => 'I biglietti',
            'pg'      => $pg,
            'mondo'   => Mondo::adesso(),
            'qui'     => Bacheca::quiPer((int) $pg['id'], (string) $pg['luogo']),
            'letti'   => Bacheca::letti((int) $pg['id']),
            'miei'    => Bacheca::miei((int) $pg['id']),
        ]));
    }

    public function lascia(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/quartiere');
        }
        $res = Bacheca::lascia($pg, $request->int('a'), $request->str('testo'),
            $request->str('anonimo') !== '');
        Session::flash($res['ok'] ? 'info' : 'error',
            $res['ok'] ? 'Lo lasci dove lo troverà.' : ($res['error'] ?? 'Non si può.'));
        return redirect('/biglietti');
    }

    public function leggiBiglietto(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/quartiere');
        }
        $res = Bacheca::leggi($pg, $request->int('biglietto'));
        if (!$res['ok']) {
            Session::flash('error', $res['error'] ?? 'Non si può.');
        }
        return redirect('/biglietti');
    }

    // --- I club ----------------------------------------------------------------

    public function club(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/quartiere');
        }
        $tutti = Club::tutti();
        foreach ($tutti as &$c) {
            $c['iscritti'] = Club::quanti((string) $c['ckey']);
        }
        unset($c);
        return Response::html(view('gioco/club', [
            'title' => 'I club',
            'pg'    => $pg,
            'mondo' => Mondo::adesso(),
            'club'  => $tutti,
            'miei'  => array_column(Club::di((int) $pg['id']), 'ckey'),
        ]));
    }

    public function unClub(Request $request, string $ckey): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/quartiere');
        }
        $club = Club::uno($ckey);
        if ($club === null) {
            Session::flash('error', 'Quel club non esiste.');
            return redirect('/club');
        }
        return Response::html(view('gioco/club_uno', [
            'title'  => (string) $club['nome'],
            'pg'     => $pg,
            'mondo'  => Mondo::adesso(),
            'club'   => $club,
            'membri' => Club::membri($ckey),
            'dentro' => in_array($ckey, array_column(Club::di((int) $pg['id']), 'ckey'), true),
        ]));
    }

    public function iscrivi(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/quartiere');
        }
        $ckey = $request->str('club');
        $res  = Club::iscrivi($pg, $ckey);
        Session::flash($res['ok'] ? 'info' : 'error',
            $res['ok'] ? 'Ci sei dentro.' : ($res['error'] ?? 'Non si può.'));
        return redirect('/club/' . rawurlencode($ckey));
    }

    public function esciClub(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/quartiere');
        }
        $ckey = $request->str('club');
        $res  = Club::esci($pg, $ckey);
        if (!$res['ok']) {
            Session::flash('error', $res['error'] ?? 'Non si può.');
        }
        return redirect('/club');
    }

    // --- Il calendario e gli abitanti ------------------------------------------

    public function calendario(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/quartiere');
        }
        return Response::html(view('gioco/calendario', [
            'title'     => 'Il calendario',
            'pg'        => $pg,
            'mondo'     => Mondo::adesso(),
            'in_corso'  => Eventi::inCorso(),
            'prossimo'  => Eventi::prossimo(),
            'tutti'     => Eventi::calendario(),
            'abitanti'  => Abitanti::tutti(),
        ]));
    }
}
