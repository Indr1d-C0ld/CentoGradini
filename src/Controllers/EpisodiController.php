<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Game\Episodi;
use App\Game\Mondo;
use App\Game\Personaggio;

final class EpisodiController
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

    public function corrente(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/quartiere');
        }
        $ep = Episodi::mio((int) $pg['id']);
        if ($ep === null) {
            return Response::html(view('gioco/episodio_nessuno', [
                'title' => 'Nessun episodio',
                'pg'    => $pg,
                'mondo' => Mondo::adesso(),
            ]));
        }
        // Una richiesta è anche l'occasione per accorgersi che la finestra è
        // scaduta: il battito lo fa comunque, ma chi è davanti allo schermo
        // non deve aspettare il minuto.
        Episodi::forseRisolvi((int) $ep['id']);
        $ep = Episodi::mio((int) $pg['id']);
        if ($ep === null) {
            Session::flash('info', 'L\'episodio si è chiuso mentre arrivavi. Lo trovi fra i ricordi.');
            return redirect('/ricordi');
        }

        $scelte = Episodi::scelte((int) $ep['id'], (int) $ep['scena']);
        return Response::html(view('gioco/episodio', [
            'title'   => (string) $ep['titolo'],
            'pg'      => $pg,
            'mondo'   => Mondo::adesso(),
            'ep'      => $ep,
            'scena'   => Episodi::scenaCorrente($ep),
            'cast'    => Episodi::cast((int) $ep['id']),
            'scelte'  => $scelte,
            'mia'     => $scelte[(int) $pg['id']] ?? null,
            'restano' => max(0, (int) $ep['scade_reale'] - \App\Sim\Orologio::adessoReale()),
        ]));
    }

    public function scegli(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/quartiere');
        }
        $res = Episodi::scegli($pg, $request->int('episodio'), $request->str('opzione'));
        if (!$res['ok']) {
            Session::flash('error', $res['error'] ?? 'Non si può.');
        }
        return redirect('/episodio');
    }

    public function ricordi(Request $request): Response
    {
        $pg = $this->mio();
        $uid = Auth::id();
        if ($uid === null) {
            return redirect('/accesso');
        }
        return Response::html(view('gioco/ricordi', [
            'title'   => 'L\'album dei ricordi',
            'pg'      => $pg,
            'mondo'   => Mondo::adesso(),
            'ricordi' => Episodi::ricordi($uid),
        ]));
    }
}
