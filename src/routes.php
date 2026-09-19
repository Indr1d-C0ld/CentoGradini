<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\QuartiereController;
use App\Controllers\EpisodiController;
use App\Controllers\LegamiController;
use App\Controllers\SegretoController;
use App\Core\Router;

/** @var Router $router */

// --- Pubbliche ---------------------------------------------------------------
$router->get('/', [HomeController::class, 'index']);
$router->get('/salute', [HomeController::class, 'salute']);
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

// --- Il personaggio ------------------------------------------------------------
$router->get('/personaggio/nuovo', [QuartiereController::class, 'mostraCreazione'], ['active']);
$router->post('/personaggio/nuovo', [QuartiereController::class, 'crea'], ['active', 'throttle']);
$router->get('/personaggio/abilita', [QuartiereController::class, 'mostraAbilita'], ['active']);
$router->post('/personaggio/abilita', [QuartiereController::class, 'salvaAbilita'], ['active', 'throttle']);
$router->get('/personaggio', [QuartiereController::class, 'scheda'], ['active']);

// --- Il quartiere ----------------------------------------------------------------
$router->get('/quartiere', [QuartiereController::class, 'index'], ['active']);
$router->get('/luogo/{lkey}', [QuartiereController::class, 'luogo'], ['active']);
$router->post('/vai', [QuartiereController::class, 'vai'], ['active', 'throttle']);

// --- Il Segreto ------------------------------------------------------------------
$router->post('/potere', [SegretoController::class, 'usa'], ['active', 'throttle']);
$router->get('/incidente/{id}', [SegretoController::class, 'incidente'], ['active']);
$router->post('/copri', [SegretoController::class, 'copri'], ['active', 'throttle']);
$router->post('/confida', [SegretoController::class, 'confida'], ['active', 'throttle']);
$router->get('/taccuino', [SegretoController::class, 'taccuino'], ['active']);
$router->post('/taccuino/collega', [SegretoController::class, 'collega'], ['active', 'throttle']);

// --- I legami ----------------------------------------------------------------------
$router->get('/legami', [LegamiController::class, 'elenco'], ['active']);
$router->get('/verso/{id}', [LegamiController::class, 'verso'], ['active']);
$router->post('/gesto', [LegamiController::class, 'gesto'], ['active', 'throttle']);
$router->post('/chiarisci', [LegamiController::class, 'chiarisci'], ['active', 'throttle']);
$router->post('/confessa', [LegamiController::class, 'confessa'], ['active', 'throttle']);
$router->post('/oggetto/passa', [LegamiController::class, 'passaOggetto'], ['active', 'throttle']);
$router->post('/oggetto/raccogli', [LegamiController::class, 'raccogliOggetto'], ['active', 'throttle']);

// --- Gli episodi ------------------------------------------------------------------
$router->get('/episodio', [EpisodiController::class, 'corrente'], ['active']);
$router->post('/episodio/scegli', [EpisodiController::class, 'scegli'], ['active', 'throttle']);
$router->get('/ricordi', [EpisodiController::class, 'ricordi'], ['active']);

// Le due chiamate che la pagina fa da sola, senza ricaricarsi.
$router->get('/api/carta', [QuartiereController::class, 'carta'], ['active']);
$router->get('/api/battito', [QuartiereController::class, 'battito'], ['active']);
