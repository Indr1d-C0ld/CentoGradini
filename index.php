<?php

declare(strict_types=1);

/**
 * Cento Gradini — front controller unico.
 * Ogni richiesta applicativa passa da qui (PATH_INFO o RewriteRule).
 */

define('ORANGEROAD', true);
define('APP_START', microtime(true));

$projectRoot = __DIR__;

// Server incorporato di PHP (php -S ... index.php): qui index.php fa da router
// e riceve ANCHE le richieste dei file statici. Restituendo false si dice al
// server di servirli da solo. Sotto Apache non serve — ci pensa la RewriteCond
// -f — ma senza questa riga in sviluppo il foglio di stile arriva come 404
// in HTML e la pagina si vede nuda, con un errore di MIME che non sembra
// affatto un problema di instradamento.
if (PHP_SAPI === 'cli-server') {
    $richiesto = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $file = $projectRoot . '/' . ltrim(rawurldecode($richiesto), '/');
    if ($richiesto !== '/index.php' && is_file($file) && str_starts_with(realpath($file) ?: '', $projectRoot . '/')) {
        return false;
    }
}

require $projectRoot . '/src/autoload.php';
require $projectRoot . '/src/Support/helpers.php';

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;

$GLOBALS['__project_root'] = $projectRoot;

// --- Configurazione ----------------------------------------------------------
try {
    Config::load($projectRoot);
} catch (\Throwable $e) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Cento Gradini — configurazione</title>'
        . '<style>body{font:16px/1.65 system-ui,sans-serif;background:#fdf6ee;color:#4a3f38;'
        . 'max-width:44rem;margin:4rem auto;padding:0 1.5rem}code{background:#f3e3d2;padding:.15em .4em;'
        . 'border-radius:3px}h1{color:#d2622a;font-weight:600}</style>'
        . '<h1>Configurazione mancante</h1><p>Non riesco ad avviare l\'applicazione:</p>'
        . '<pre><code>' . htmlspecialchars($e->getMessage(), ENT_QUOTES) . '</code></pre>'
        . '<p>Esegui <code>sudo bash deploy/00-bootstrap.sh</code>, poi '
        . '<code>php bin/console.php migrate</code>.</p>';
    exit;
}

date_default_timezone_set((string) Config::get('app.timezone', 'UTC'));

$debug = (bool) Config::get('app.debug', false);
ini_set('display_errors', $debug ? '1' : '0');
error_reporting(E_ALL);

View::setPath($projectRoot . '/views');
Session::start();

// --- Richiesta ---------------------------------------------------------------
$forcedBase = Config::get('app.base_path');
$request = new Request(
    (bool) Config::get('app.pretty_urls', false),
    is_string($forcedBase) ? $forcedBase : null,
);
$GLOBALS['__base_path']  = $request->basePath();
$GLOBALS['__url_prefix'] = $request->urlPrefix();
// Il percorso lo calcola gia' Request, che sa districare PATH_INFO, il
// prefisso e /index.php. Lo si deposita qui perche' le viste possano sapere
// dove si trovano — ricalcolarlo in un helper vorrebbe dire una seconda
// copia di quella logica, e prima o poi le due divergono.
$GLOBALS['__percorso']   = $request->path();

// --- Instradamento -----------------------------------------------------------
$router = new Router();
require $projectRoot . '/src/routes.php';

try {
    $response = $router->dispatch($request);
} catch (\Throwable $e) {
    logger(sprintf('%s: %s @ %s:%d', $e::class, $e->getMessage(), $e->getFile(), $e->getLine()), 'error');

    $isDb = str_contains(strtolower($e->getMessage()), 'database')
        || str_contains(strtolower($e->getMessage()), 'connessione al database');

    if ($isDb) {
        $response = Response::html(view('errors/db', [
            'title'  => 'Servizio non disponibile',
            'debug'  => $debug,
            'detail' => $e->getMessage(),
        ]), 503);
    } else {
        $response = Response::html(view('errors/generic', [
            'title'   => 'Errore interno',
            'status'  => 500,
            'message' => $debug
                ? $e::class . ': ' . $e->getMessage()
                : 'Qualcosa e\' andato storto. L\'inconveniente e\' stato annotato.',
        ]), 500);
    }
}

$response->send();
