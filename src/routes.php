<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\ProfiloController;
use App\Controllers\QuartiereController;
use App\Controllers\AdminController;
use App\Controllers\ComunicazioniController;
use App\Controllers\EpisodiController;
use App\Controllers\QuartiereVivoController;
use App\Controllers\LegamiController;
use App\Controllers\SegretoController;
use App\Core\Router;

/** @var Router $router */

// --- Pubbliche ---------------------------------------------------------------
$router->get('/', [HomeController::class, 'index']);
$router->get('/salute', [HomeController::class, 'salute']);
$router->get('/sw.js', [HomeController::class, 'serviceWorker']);
$router->get('/manifest.webmanifest', [HomeController::class, 'manifesto']);
$router->get('/opera', [HomeController::class, 'opera']);
$router->get('/santuario', [HomeController::class, 'santuario']);

// --- Iscrizione e conferma dell'indirizzo ------------------------------------
$router->get('/iscrizione', [AuthController::class, 'mostraIscrizione'], ['guest']);
$router->post('/iscrizione', [AuthController::class, 'iscrivi'], ['guest', 'throttle']);
$router->get('/conferma-inviata', [AuthController::class, 'confermaInviata']);
$router->get('/conferma', [AuthController::class, 'conferma']);
$router->post('/rinvia-conferma', [AuthController::class, 'rinvia'], ['throttle']);

// --- Accesso -----------------------------------------------------------------
$router->get('/accesso', [AuthController::class, 'mostraAccesso'], ['guest']);
$router->post('/accesso', [AuthController::class, 'accedi'], ['guest', 'throttle']);
$router->post('/esci', [AuthController::class, 'esci'], ['auth']);

// --- Password dimenticata -------------------------------------------------------
// Il modulo d'iscrizione lo prometteva («serve per recuperare l'accesso») e non
// esisteva. Aperto anche a chi e' collegato: lo si puo' voler usare per cambiare
// la password, e comunque non rivela niente.
$router->get('/password-dimenticata', [AuthController::class, 'mostraRecupero']);
$router->post('/password-dimenticata', [AuthController::class, 'chiediRecupero'], ['throttle']);
$router->get('/recupero', [AuthController::class, 'mostraRifai']);
$router->post('/recupero', [AuthController::class, 'rifai'], ['throttle']);

// --- Il personaggio ------------------------------------------------------------
// Il filtro 'quartiere' chiude le rotte che agiscono nel mondo (o lo mostrano
// dal vivo) a chi ha il personaggio traslocato: vede /trasloco finche' non
// torna. Restano aperti la scheda, il profilo, i ricordi e il diario — cose
// che il trasloco non porta via — e le comunicazioni con la gestione.
$router->get('/personaggio/nuovo', [QuartiereController::class, 'mostraCreazione'], ['active']);
$router->post('/personaggio/nuovo', [QuartiereController::class, 'crea'], ['active', 'throttle']);
$router->get('/personaggio/abilita', [QuartiereController::class, 'mostraAbilita'], ['active']);
$router->post('/personaggio/abilita', [QuartiereController::class, 'salvaAbilita'], ['active', 'throttle']);
$router->get('/personaggio', [QuartiereController::class, 'scheda'], ['active']);

// La faccia e l'aspetto. Le stesse rotte le usa l'amministratore su un altro
// personaggio, passando `personaggio=<id>`: il diritto di farlo lo controlla
// ProfiloController::bersaglio(), una volta sola per tutte e quattro.
$router->get('/personaggio/profilo', [ProfiloController::class, 'mostra'], ['active']);
$router->post('/personaggio/profilo/foto', [ProfiloController::class, 'carica'], ['active', 'throttle']);
$router->post('/personaggio/profilo/foto/togli', [ProfiloController::class, 'togli'], ['active', 'throttle']);
$router->post('/personaggio/profilo/aspetto', [ProfiloController::class, 'aspetto'], ['active', 'throttle']);

// --- Il quartiere ----------------------------------------------------------------
$router->get('/quartiere', [QuartiereController::class, 'index'], ['active', 'quartiere']);
$router->get('/luogo/{lkey}', [QuartiereController::class, 'luogo'], ['active', 'quartiere']);
$router->post('/vai', [QuartiereController::class, 'vai'], ['active', 'quartiere', 'throttle']);

// --- Il Segreto ------------------------------------------------------------------
// Il trasloco e il ritorno: SENZA il filtro 'quartiere', per forza — e' la
// pagina verso cui quel filtro manda.
$router->get('/trasloco', [SegretoController::class, 'trasloco'], ['active']);
$router->post('/trasloco/rientra', [SegretoController::class, 'rientra'], ['active', 'throttle']);
$router->post('/potere', [SegretoController::class, 'usa'], ['active', 'quartiere', 'throttle']);
$router->get('/incidente/{id}', [SegretoController::class, 'incidente'], ['active', 'quartiere']);
$router->post('/copri', [SegretoController::class, 'copri'], ['active', 'quartiere', 'throttle']);
$router->post('/confida', [SegretoController::class, 'confida'], ['active', 'quartiere', 'throttle']);
$router->get('/taccuino', [SegretoController::class, 'taccuino'], ['active', 'quartiere']);
$router->post('/taccuino/collega', [SegretoController::class, 'collega'], ['active', 'quartiere', 'throttle']);

// --- I legami ----------------------------------------------------------------------
$router->get('/legami', [LegamiController::class, 'elenco'], ['active', 'quartiere']);
$router->get('/chi/{id}', [LegamiController::class, 'profilo'], ['active', 'quartiere']);
$router->get('/verso/{id}', [LegamiController::class, 'verso'], ['active', 'quartiere']);
$router->post('/gesto', [LegamiController::class, 'gesto'], ['active', 'quartiere', 'throttle']);
$router->post('/chiarisci', [LegamiController::class, 'chiarisci'], ['active', 'quartiere', 'throttle']);
$router->post('/confessa', [LegamiController::class, 'confessa'], ['active', 'quartiere', 'throttle']);
$router->post('/oggetto/passa', [LegamiController::class, 'passaOggetto'], ['active', 'quartiere', 'throttle']);
$router->post('/oggetto/raccogli', [LegamiController::class, 'raccogliOggetto'], ['active', 'quartiere', 'throttle']);

// --- Gli episodi ------------------------------------------------------------------
$router->get('/episodio', [EpisodiController::class, 'corrente'], ['active', 'quartiere']);
$router->post('/episodio/scegli', [EpisodiController::class, 'scegli'], ['active', 'quartiere', 'throttle']);
$router->get('/ricordi', [EpisodiController::class, 'ricordi'], ['active']);
$router->get('/diario', [EpisodiController::class, 'diario'], ['active']);

// --- F6: il quartiere vivo ---------------------------------------------------
$router->get('/voci', [QuartiereVivoController::class, 'voci'], ['active', 'quartiere']);

$router->get('/bacheca', [QuartiereVivoController::class, 'bacheca'], ['active', 'quartiere']);
$router->post('/bacheca/affiggi', [QuartiereVivoController::class, 'affiggi'], ['active', 'quartiere', 'throttle']);
$router->post('/bacheca/stacca', [QuartiereVivoController::class, 'stacca'], ['active', 'quartiere', 'throttle']);

$router->get('/biglietti', [QuartiereVivoController::class, 'biglietti'], ['active', 'quartiere']);
$router->post('/biglietto/lascia', [QuartiereVivoController::class, 'lascia'], ['active', 'quartiere', 'throttle']);
$router->post('/biglietto/leggi', [QuartiereVivoController::class, 'leggiBiglietto'], ['active', 'quartiere', 'throttle']);

$router->get('/club', [QuartiereVivoController::class, 'club'], ['active', 'quartiere']);
$router->post('/club/iscrivi', [QuartiereVivoController::class, 'iscrivi'], ['active', 'quartiere', 'throttle']);
$router->post('/club/esci', [QuartiereVivoController::class, 'esciClub'], ['active', 'quartiere', 'throttle']);
$router->get('/club/{ckey}', [QuartiereVivoController::class, 'unClub'], ['active', 'quartiere']);

$router->get('/calendario', [QuartiereVivoController::class, 'calendario'], ['active']);

// --- Le comunicazioni con la gestione ----------------------------------------
// Non sono finzione e non stanno fra i biglietti: e' la gestione che parla, e
// a schermo si vede che e' un'altra cosa.
$router->get('/comunicazioni', [ComunicazioniController::class, 'mie'], ['auth']);
$router->post('/comunicazioni', [ComunicazioniController::class, 'rispondi'], ['auth', 'throttle']);

// --- F7: amministrazione -----------------------------------------------------
$router->get('/admin', [AdminController::class, 'index'], ['active', 'admin']);
$router->post('/admin/battito', [AdminController::class, 'battito'], ['active', 'admin', 'throttle']);
$router->post('/admin/config', [AdminController::class, 'config'], ['active', 'admin', 'throttle']);
$router->get('/admin/utenti', [AdminController::class, 'utenti'], ['active', 'admin']);
$router->get('/admin/fotografie', [AdminController::class, 'fotografie'], ['active', 'admin']);
$router->get('/admin/comunicazioni', [ComunicazioniController::class, 'elenco'], ['active', 'admin']);
$router->post('/admin/comunicazioni', [ComunicazioniController::class, 'scrivi'], ['active', 'admin', 'throttle']);
$router->get('/admin/comunicazioni/{id}', [ComunicazioniController::class, 'filo'], ['active', 'admin']);
$router->get('/admin/mappa', [AdminController::class, 'mappa'], ['active', 'admin']);
$router->get('/admin/api/carta', [AdminController::class, 'carta'], ['active', 'admin']);
$router->get('/admin/utente/{id}', [AdminController::class, 'utente'], ['active', 'admin']);
$router->post('/admin/moderazione', [AdminController::class, 'moderazione'], ['active', 'admin', 'throttle']);
$router->get('/admin/statistiche', [AdminController::class, 'statistiche'], ['active', 'admin']);
$router->get('/admin/accessi', [AdminController::class, 'accessi'], ['active', 'admin']);
$router->get('/admin/impostazioni', [AdminController::class, 'impostazioni'], ['active', 'admin']);

// Le due chiamate che la pagina fa da sola, senza ricaricarsi.
$router->get('/api/carta', [QuartiereController::class, 'carta'], ['active', 'quartiere']);
$router->get('/api/battito', [QuartiereController::class, 'battito'], ['active', 'quartiere']);
