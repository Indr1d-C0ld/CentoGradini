<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Game\Legami;
use App\Game\Mondo;
use App\Game\Personaggio;

final class LegamiController
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

    public function elenco(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/quartiere');
        }
        return Response::html(view('gioco/legami', [
            'title'   => 'I legami',
            'pg'      => $pg,
            'mondo'   => Mondo::adesso(),
            'legami'  => Legami::miei((int) $pg['id']),
            'oggetto' => Legami::oggettoDi((int) $pg['id']),
        ]));
    }

    /** La scheda di una persona che è qui: gesti, chiarimenti, confessione. */
    public function verso(Request $request, string $id): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/quartiere');
        }
        $altro = Database::first(
            'SELECT * FROM personaggi WHERE id = ? AND luogo = ? AND verso IS NULL AND stato = ?',
            [(int) $id, (string) $pg['luogo'], 'attivo']
        );
        if ($altro === null || (int) $id === (int) $pg['id']) {
            Session::flash('error', 'Quella persona non è qui.');
            return redirect('/quartiere');
        }
        return Response::html(view('gioco/verso', [
            'title'   => $altro['cognome'] . ' ' . $altro['nome'],
            'pg'      => $pg,
            'altro'   => $altro,
            'mondo'   => Mondo::adesso(),
            'gesti'   => Legami::disponibili($pg),
            'mio'     => Legami::fra((int) $pg['id'], (int) $altro['id']),
            'loro'    => Legami::fra((int) $altro['id'], (int) $pg['id']),
            'oggetto' => Legami::oggettoDi((int) $pg['id']),
        ]));
    }

    public function gesto(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/quartiere');
        }
        $verso = $request->int('verso');
        $res = Legami::compi($pg, $verso, $request->str('gesto'));
        if (!$res['ok']) {
            Session::flash('error', $res['error'] ?? 'Non si può.');
            return redirect('/quartiere');
        }
        $testo = $res['racconto'] ?? '';
        if (($res['visto_da'] ?? 0) > 0) {
            $testo .= ' ' . ((int) $res['visto_da'] === 1
                ? 'C\'era qualcun altro, e ha guardato.'
                : 'C\'erano altre ' . (int) $res['visto_da'] . ' persone, e hanno guardato.');
        }
        Session::flash('success', $testo);
        return redirect('/verso/' . $verso);
    }

    public function chiarisci(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/quartiere');
        }
        $verso = $request->int('verso');
        $res = Legami::chiarisci($pg, $verso);
        if (!$res['ok']) {
            Session::flash('error', $res['error'] ?? 'Non si può.');
            return redirect('/verso/' . $verso);
        }
        Session::flash(($res['riuscito'] ?? false) ? 'success' : 'warning', $res['racconto'] ?? '');
        return redirect('/verso/' . $verso);
    }

    public function confessa(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/quartiere');
        }
        $verso = $request->int('verso');
        $res = Legami::confessa($pg, $verso);
        if (!$res['ok']) {
            Session::flash('error', $res['error'] ?? 'Non si può.');
            return redirect('/verso/' . $verso);
        }
        Session::flash(match ($res['esito'] ?? '') {
            'accolta'  => 'success',
            'respinta' => 'error',
            default    => 'warning',
        }, $res['racconto'] ?? '');
        return redirect('/verso/' . $verso);
    }

    public function passaOggetto(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/quartiere');
        }
        $verso = $request->int('verso');
        $res = Legami::passaOggetto($pg, $verso, $request->str('oggetto', 'cappello'));
        Session::flash($res['ok'] ? 'success' : 'error', $res['racconto'] ?? $res['error'] ?? '');
        return redirect('/verso/' . $verso);
    }

    public function raccogliOggetto(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/quartiere');
        }
        $res = Legami::raccogli($pg, $request->str('oggetto', 'cappello'));
        Session::flash($res['ok'] ? 'success' : 'error', $res['racconto'] ?? $res['error'] ?? '');
        return redirect('/quartiere');
    }
}
