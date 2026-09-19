<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Game\Mondo;
use App\Game\Personaggio;
use App\Sim\Calendario;
use App\Sim\Luoghi;
use App\Sim\Orologio;
use App\Sim\Scuola;
use App\Game\Scheda;

final class QuartiereController
{
    /**
     * Il personaggio di chi sta guardando, oppure null.
     * @return array<string,mixed>|null
     */
    private function mio(): ?array
    {
        $id = Auth::id();
        return $id === null ? null : Personaggio::perUtente($id);
    }

    // --- Creazione del personaggio -----------------------------------------------

    public function mostraCreazione(Request $request): Response
    {
        if ($this->mio() !== null) {
            return redirect('/quartiere');
        }
        return Response::html(view('gioco/crea_personaggio', [
            'title'  => 'Chi sei',
            'mondo'  => Mondo::adesso(),
            'classi' => Scuola::scelte(),
        ]));
    }

    public function crea(Request $request): Response
    {
        $userId = Auth::id();
        if ($userId === null) {
            return redirect('/accesso');
        }

        $nome    = $request->str('nome');
        $cognome = $request->str('cognome');

        // Si controlla la forma qui e il contenuto nel modello: così il
        // messaggio d'errore parla del campo giusto.
        foreach (['nome' => $nome, 'cognome' => $cognome] as $campo => $v) {
            if ($v !== '' && !Personaggio::nomeValido($v)) {
                Session::flashInput($request->all());
                Session::flash('error', "Il {$campo} può contenere solo lettere, spazi, apostrofo e trattino.");
                return redirect('/personaggio/nuovo');
            }
        }

        // La classe arriva come «superiori-2»: una sola casella invece di due
        // che si possono contraddire.
        $classe = explode('-', $request->str('classe', 'superiori-2'));
        $sezione = $classe[0] ?? 'superiori';
        $anno    = (int) ($classe[1] ?? 2);

        $res = Personaggio::crea(
            $userId, $nome, $cognome, $request->str('sesso'),
            $sezione, $anno,
            $request->int('nato_mese', 4), $request->int('nato_giorno', 2),
            $request->str('stirpe') === 'esper'
        );

        if (!$res['ok']) {
            Session::flashInput($request->all());
            Session::flash('error', $res['error'] ?? 'Non è stato possibile creare il personaggio.');
            return redirect('/personaggio/nuovo');
        }

        return redirect('/personaggio/abilita');
    }

    // --- La distribuzione dei punti ------------------------------------------

    public function mostraAbilita(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/personaggio/nuovo');
        }
        if (!Personaggio::daCompletare($pg)) {
            return redirect('/personaggio');
        }
        return Response::html(view('gioco/abilita', [
            'title'  => 'Le tue abilità',
            'pg'     => $pg,
            'mondo'  => Mondo::adesso(),
            'tratti' => Scheda::tratti((int) $pg['id']),
            'poteri' => Scheda::poteri((int) $pg['id']),
            'punti'  => Scheda::puntiDaDistribuire($pg),
        ]));
    }

    public function salvaAbilita(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/personaggio/nuovo');
        }
        if (!Personaggio::daCompletare($pg)) {
            return redirect('/personaggio');
        }

        $punti = [];
        foreach (Scheda::ABILITA as $a) {
            $punti[$a] = $request->int('p_' . $a, 0);
        }
        $res = Scheda::finalizza($pg, $punti);
        if (!$res['ok']) {
            Session::flash('error', $res['error'] ?? 'Distribuzione non valida.');
            return redirect('/personaggio/abilita');
        }

        Session::flash('success', 'La scheda è chiusa. Adesso il quartiere ti riguarda.');
        return redirect('/personaggio');
    }

    /** La scheda, a pagina intera. */
    public function scheda(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/personaggio/nuovo');
        }
        if (Personaggio::daCompletare($pg)) {
            return redirect('/personaggio/abilita');
        }
        return Response::html(view('gioco/scheda', [
            'title'  => Personaggio::nomeCompleto($pg),
            'pg'     => $pg,
            'mondo'  => Mondo::adesso(),
            'tratti' => Scheda::tratti((int) $pg['id']),
            'poteri' => Scheda::poteri((int) $pg['id']),
        ]));
    }

    // --- La mappa ---------------------------------------------------------------------

    public function index(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/personaggio/nuovo');
        }
        if (Personaggio::daCompletare($pg)) {
            return redirect('/personaggio/abilita');
        }
        Personaggio::segnaVisto((int) $pg['id']);

        $mondo = Mondo::adesso();
        return Response::html(view('gioco/quartiere', [
            'title'    => 'Il quartiere',
            'pg'       => $pg,
            'mondo'    => $mondo,
            'carta'    => Mondo::carta($pg),
            'qui'      => Mondo::luogo((string) $pg['luogo'], $pg),
            'viaggio'  => $this->viaggio($pg),
            'prossimi' => Calendario::prossimiEventi($mondo['gts'], 2),
        ]));
    }

    /** Il luogo in cui ci si trova, a pagina intera. */
    public function luogo(Request $request, string $lkey): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/personaggio/nuovo');
        }
        if (!Luoghi::esiste($lkey)) {
            return redirect('/quartiere');
        }
        Personaggio::segnaVisto((int) $pg['id']);

        $dati = Mondo::luogo($lkey, $pg);
        return Response::html(view('gioco/luogo', [
            'title'   => Luoghi::nome($lkey),
            'pg'      => $pg,
            'mondo'   => Mondo::adesso(),
            'dati'    => $dati,
            'sono_qui'=> $pg['verso'] === null && $pg['luogo'] === $lkey,
            'viaggio' => $this->viaggio($pg),
            'quanto'  => Luoghi::percorso((string) $pg['luogo'], $lkey),
        ]));
    }

    public function vai(Request $request): Response
    {
        $pg = $this->mio();
        if ($pg === null) {
            return redirect('/personaggio/nuovo');
        }
        $verso = $request->str('verso');
        $res = Personaggio::parti($pg, $verso);

        if (!$res['ok']) {
            Session::flash('error', $res['error'] ?? 'Non si può andare lì.');
            return redirect('/quartiere');
        }

        $reali = (int) ceil($res['minuti'] * 60 / Orologio::compressione());
        Session::flash('success', sprintf(
            'Ti incammini verso %s: %d minuti di strada, cioè %s di attesa.',
            Luoghi::nome($verso),
            $res['minuti'],
            $reali < 60 ? $reali . ' secondi' : (int) round($reali / 60) . ' minuti'
        ));
        return redirect('/quartiere');
    }

    /** I dati della carta per il disegno su tela. */
    public function carta(Request $request): Response
    {
        $pg = $this->mio();
        return Response::json(Mondo::carta($pg));
    }

    /**
     * Lo stato del mondo in forma leggera: la pagina lo richiama ogni tanto
     * per aggiornare l'orologio e accorgersi di essere arrivata a destinazione,
     * senza ricaricare tutto.
     */
    public function battito(Request $request): Response
    {
        $pg = $this->mio();
        $m  = Mondo::adesso();
        return Response::json([
            'quando'   => $m['quando_breve'],
            'insegna'  => Mondo::insegna(),
            'meteo'    => $m['meteo']['icona'] . ' ' . $m['meteo']['temperatura'] . '°',
            'viaggio'  => $pg === null ? null : $this->viaggio($pg),
            'luogo'    => $pg === null ? null : $pg['luogo'],
            // Quanta roba nuova c'e' per te. Il client li confronta con i
            // numeri di prima e, se e' cresciuto qualcosa, lo dice — con un
            // pallino, e con un suono se l'hai chiesto.
            'per_te'   => $pg === null ? null : [
                'voci'      => \App\Game\Voci::quante((int) $pg['id']),
                'biglietti' => count(\App\Game\Bacheca::quiPer((int) $pg['id'], (string) $pg['luogo'])),
                'episodio'  => \App\Game\Episodi::mio((int) $pg['id']) !== null,
            ],
        ]);
    }

    /**
     * @param array<string,mixed> $pg
     * @return array{verso:string,nome:string,restano:int}|null
     */
    private function viaggio(array $pg): ?array
    {
        if ($pg['verso'] === null || $pg['arrivo_gts'] === null) {
            return null;
        }
        $restanoGioco = max(0, (int) $pg['arrivo_gts'] - Orologio::lineare());
        return [
            'verso'   => (string) $pg['verso'],
            'nome'    => Luoghi::nome((string) $pg['verso']),
            'restano' => (int) ceil($restanoGioco / Orologio::compressione()),   // secondi reali
        ];
    }
}
