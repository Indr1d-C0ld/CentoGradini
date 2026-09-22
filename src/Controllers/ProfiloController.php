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
use App\Game\Ritratto;
use App\Support\Audit;

/**
 * Il profilo: la faccia e l'aspetto del personaggio.
 *
 * Due cose sole, ma sono le due che il giocatore scrive di suo pugno invece di
 * tirarle ai dadi — la fotografia e la riga che descrive come lo si vede
 * arrivare. Tutto il resto della scheda e' conseguenza della creazione e del
 * gioco, e non si modifica da qui.
 *
 * Le stesse quattro azioni servono il giocatore su se stesso e
 * l'amministratore su un altro: la differenza sta tutta in `bersaglio()`, e
 * sta li' apposta. Sparpagliare il controllo dei permessi dentro le azioni
 * vuol dire che prima o poi se ne aggiunge una e ci si dimentica — e quella
 * sarebbe una porta aperta a chiunque.
 */
final class ProfiloController
{
    /** Il limite della colonna `personaggi.aspetto`. */
    private const ASPETTO_MAX = 255;

    public function mostra(Request $request): Response
    {
        [$pg, $errore] = $this->bersaglio($request);
        if ($pg === null) {
            return $errore;
        }
        return Response::html(view('gioco/profilo', [
            'title'    => 'La tua fotografia',
            'mondo'    => Mondo::adesso(),
            'pg'       => $pg,
            'ritratto' => Ritratto::di($pg),
            'lato'     => Ritratto::LATO,
        ]));
    }

    public function carica(Request $request): Response
    {
        [$pg, $errore] = $this->bersaglio($request);
        if ($pg === null) {
            return $errore;
        }

        $res = Ritratto::carica(
            (int) $pg['id'],
            $request->file('foto') ?? [],
            $request->int('sx'),
            $request->int('sy'),
            $request->int('lato'),
            (string) ($GLOBALS['__project_root'] ?? '')
        );

        if ($res['ok']) {
            $this->registra($request, $pg, 'profilo.ritratto');
            Session::flash('success', 'Fotografia messa.');
        } else {
            Session::flash('error', $res['error'] ?? 'Caricamento non riuscito.');
        }
        return $this->indietro($request, $pg);
    }

    public function togli(Request $request): Response
    {
        [$pg, $errore] = $this->bersaglio($request);
        if ($pg === null) {
            return $errore;
        }
        Ritratto::togli((int) $pg['id'], (string) ($GLOBALS['__project_root'] ?? ''));
        $this->registra($request, $pg, 'profilo.ritratto_tolto');
        Session::flash('success', 'Fotografia tolta.');
        return $this->indietro($request, $pg);
    }

    public function aspetto(Request $request): Response
    {
        [$pg, $errore] = $this->bersaglio($request);
        if ($pg === null) {
            return $errore;
        }
        // Una riga sola: e' quello che gli altri leggono vedendoti arrivare,
        // non la tua biografia. Il taglio a 255 e' la colonna, ma il limite
        // vero e' il buon senso di chi scrive.
        $testo = trim(preg_replace('/\s+/u', ' ', $request->str('aspetto')) ?? '');
        Database::run(
            'UPDATE personaggi SET aspetto = ? WHERE id = ?',
            [$testo === '' ? null : mb_substr($testo, 0, self::ASPETTO_MAX), (int) $pg['id']]
        );
        $this->registra($request, $pg, 'profilo.aspetto');
        Session::flash('success', 'Com\'è fatto, aggiornato.');
        return $this->indietro($request, $pg);
    }

    /**
     * Su quale personaggio si sta agendo, e con quale diritto.
     *
     * Il proprio, sempre. Quello di un altro solo se si e' amministratori — e
     * il controllo si fa QUI, una volta.
     *
     * @return array{0:?array<string,mixed>,1:?Response}
     */
    private function bersaglio(Request $request): array
    {
        $id = $request->int('personaggio');
        if ($id > 0) {
            if (!Auth::isAdmin()) {
                return [null, Response::html(view('errors/generic', [
                    'title'   => 'Non è affar tuo',
                    'status'  => 403,
                    'message' => 'Il profilo di un\'altra persona lo modifica solo chi amministra.',
                ]), 403)];
            }
            $pg = Database::first('SELECT * FROM personaggi WHERE id = ?', [$id]);
            if ($pg === null) {
                Session::flash('error', 'Nessun personaggio con quel numero.');
                return [null, redirect('/admin/utenti')];
            }
            return [$pg, null];
        }

        $uid = Auth::id();
        $pg  = $uid === null ? null : Personaggio::perUtente($uid);
        if ($pg === null) {
            Session::flash('info', 'Prima si arriva in quartiere.');
            return [null, redirect('/personaggio/nuovo')];
        }
        return [$pg, null];
    }

    /**
     * Dove si torna dopo aver agito: alla propria pagina, o alla scheda
     * dell'utente da cui l'amministratore e' partito. Un personaggio non
     * giocante non ha un utente a cui tornare, e allora si torna all'elenco.
     *
     * @param array<string,mixed> $pg
     */
    private function indietro(Request $request, array $pg): Response
    {
        if ($request->int('personaggio') <= 0) {
            return redirect('/personaggio/profilo');
        }
        // Chi arriva dal muro delle fotografie vuole tornare al muro: dice da
        // dove viene con `torna`. Il valore non si prende sulla fiducia — un
        // indirizzo scelto da chi manda il modulo e' un rimbalzo verso
        // qualunque sito — quindi si accetta solo un percorso interno, senza
        // schema, senza doppia sbarra e senza coda.
        $torna = $request->str('torna');
        if ($torna !== '' && preg_match('#^/[A-Za-z0-9/_-]{0,64}$#', $torna) === 1
            && !str_contains($torna, '//')) {
            return redirect($torna);
        }
        return redirect($pg['user_id'] === null
            ? '/admin/utenti'
            : '/admin/utente/' . (int) $pg['user_id']);
    }

    /**
     * Nel registro finisce solo quello che un amministratore fa a qualcun
     * altro: le modifiche che uno fa a se stesso sono gioco, non moderazione,
     * e riempirebbero il registro di righe senza informazione.
     *
     * @param array<string,mixed> $pg
     */
    private function registra(Request $request, array $pg, string $azione): void
    {
        if (!Auth::isAdmin() || (int) ($pg['user_id'] ?? 0) === (int) Auth::id()) {
            return;
        }
        Audit::log(
            'admin.' . $azione,
            Auth::id(),
            'user',
            $pg['user_id'] === null ? null : (int) $pg['user_id'],
            ['personaggio' => (int) $pg['id']],
            $request->ip()
        );
    }
}
