<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Game\Mondo;
use App\Game\Personaggio;
use App\Game\Scheda;
use App\Game\Segreto;
use App\Sim\Orologio;

final class SegretoController
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

    // --- Il trasloco, e il ritorno -------------------------------------------------

    /**
     * La pagina di chi ha traslocato.
     *
     * Ci arriva da solo: il filtro «quartiere» del router ci manda chiunque
     * provi ad aprire il mondo con un personaggio traslocato. Dice cosa e'
     * successo, cosa si tiene e cosa si perde, e quando si potra' tornare.
     */
    public function trasloco(Request $request): Response
    {
        $uid = Auth::id();
        $pg  = $uid === null ? null : Database::first('SELECT * FROM personaggi WHERE user_id = ?', [$uid]);
        if ($pg === null || (string) $pg['stato'] !== 'trasferito') {
            return redirect('/quartiere');
        }
        $quando = (int) Segreto::ritornoDa($pg);
        $adesso = Orologio::lineare();
        return Response::html(view('gioco/trasloco', [
            'title'  => 'Il trasloco',
            'mondo'  => Mondo::adesso(),
            'pg'     => $pg,
            'quando' => $quando,
            'pronto' => $adesso >= $quando,
            // Minuti VERI all'istante lineare del ritorno. Non realiPerArrivare(),
            // che ragiona sull'istante avvolto e aggiunge un ciclo intero se il
            // bersaglio gli sembra passato: con un istante lineare sbaglierebbe.
            'minutiVeri' => max(0, (int) ceil(($quando - $adesso) / max(1, Orologio::compressione()) / 60)),
            'ricordi' => (int) (Database::first('SELECT COUNT(*) n FROM ricordi WHERE user_id = ?', [$uid])['n'] ?? 0),
        ]));
    }

    public function rientra(Request $request): Response
    {
        $uid = Auth::id();
        $pg  = $uid === null ? null : Database::first('SELECT * FROM personaggi WHERE user_id = ?', [$uid]);
        if ($pg === null) {
            return redirect('/quartiere');
        }
        $res = Segreto::rientra($pg);
        Session::flash($res['ok'] ? 'success' : 'error', $res['ok'] ? $res['racconto'] : ($res['error'] ?? 'Non ancora.'));
        return redirect($res['ok'] ? '/quartiere' : '/trasloco');
    }

    // --- Usare un potere ------------------------------------------------------

    public function usa(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/quartiere');
        }

        $res = Segreto::usa($pg, $request->str('potere'));
        if (!$res['ok']) {
            Session::flash('error', $res['error'] ?? 'Non è successo niente.');
            return redirect('/quartiere');
        }

        if (!$res['notato']) {
            Session::flash('success', $res['racconto']);
            return redirect('/quartiere');
        }

        // Qualcuno ha visto: si apre la scena della copertura, che è il vero
        // momento di gioco. Non la si salta con un messaggio lampo.
        Session::flash('info', $res['racconto']);
        return redirect('/incidente/' . (int) $res['incidente']);
    }

    /** La scena della copertura. */
    public function incidente(Request $request, string $id): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/quartiere');
        }
        $inc = Database::first('SELECT * FROM incidenti WHERE id = ? AND attore_id = ?',
            [(int) $id, (int) $pg['id']]);
        if ($inc === null) {
            return redirect('/quartiere');
        }
        if ((string) $inc['stato'] !== 'aperto') {
            return redirect('/quartiere');
        }

        $testimoni = Database::all(
            'SELECT p.id, p.nome, p.cognome FROM incidente_testimoni it
             JOIN personaggi p ON p.id = it.personaggio_id
             WHERE it.incidente_id = ? AND it.notato = 1',
            [(int) $id]
        );
        $potere = Database::first('SELECT * FROM poteri WHERE pkey = ?', [(string) $inc['pkey']]);

        $scelte = [];
        foreach (Segreto::COPERTURE as $k => $c) {
            $p = $c['base'] + (int) $pg[$c['abilita']] * $c['per'];
            if (Segreto::complicePresente($pg)) {
                $p += 25;
            }
            $scelte[$k] = $c + ['probabilita' => max(5, min(95, $p))];
        }

        return Response::html(view('gioco/incidente', [
            'title'     => 'Ti hanno visto',
            'pg'        => $pg,
            'mondo'     => Mondo::adesso(),
            'incidente' => $inc,
            'potere'    => $potere,
            'testimoni' => $testimoni,
            'scelte'    => $scelte,
            'complice'  => Segreto::complicePresente($pg),
        ]));
    }

    public function copri(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/quartiere');
        }
        $res = Segreto::copri($pg, $request->int('incidente'), $request->str('come', 'niente'));
        if (!$res['ok']) {
            Session::flash('error', $res['error'] ?? 'Non si può.');
            return redirect('/quartiere');
        }
        Session::flash(($res['falliti'] ?? 0) === 0 ? 'success' : 'warning', $res['racconto'] ?? '');
        return redirect('/quartiere');
    }

    // --- Il taccuino -------------------------------------------------------------

    public function taccuino(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/quartiere');
        }
        return Response::html(view('gioco/taccuino', [
            'title'      => 'Il taccuino',
            'pg'         => $pg,
            'mondo'      => Mondo::adesso(),
            'gruppi'     => Segreto::taccuino((int) $pg['id']),
            'intuizione' => Scheda::intuizione($pg),
        ]));
    }

    public function collega(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/quartiere');
        }
        $res = Segreto::collega($pg, $request->int('soggetto'));
        if (!$res['ok']) {
            Session::flash('error', $res['error'] ?? 'Non si può.');
        } else {
            Session::flash(($res['capito'] ?? false) ? 'success' : 'info', $res['racconto'] ?? '');
        }
        return redirect('/taccuino');
    }

    public function confida(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/quartiere');
        }
        $res = Segreto::confida($pg, $request->int('a'));
        if (!$res['ok']) {
            Session::flash('error', $res['error'] ?? 'Non si può.');
        } else {
            Session::flash('success', $res['racconto'] ?? '');
        }
        return redirect('/quartiere');
    }
}
