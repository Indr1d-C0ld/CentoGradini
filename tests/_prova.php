<?php

declare(strict_types=1);

/**
 * Impalcatura minima per le prove unitarie. Nessuna dipendenza esterna:
 * sono tre funzioni, e si leggono tutte in una schermata.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$projectRoot = dirname(__DIR__);
$GLOBALS['__project_root'] = $projectRoot;
$GLOBALS['__base_path']    = '';
$GLOBALS['__url_prefix']   = '';
require $projectRoot . '/src/autoload.php';
require $projectRoot . '/src/Support/helpers.php';

// Su quale installazione stiamo per scrivere? Config::load sceglie il primo
// file che trova, e quello di produzione vince sul config di progetto: senza
// ORANGEROAD_CONFIG le prove creerebbero e cancellerebbero personaggi sul
// mondo vero — e quella del «mondo da capo» lo azzererebbe. La prova
// end-to-end aveva gia' questo controllo; le unitarie no, fino al 13/10/2026.
\App\Core\Config::load($projectRoot);
if (\App\Core\Config::get('app.env') === 'production' && getenv('PROVE_IN_PRODUZIONE') !== '1') {
    fwrite(STDERR, "\n  Questa e' l'installazione di produzione (" . \App\Core\Config::sourceFile() . ").\n"
        . "  Le prove scrivono sul database: in locale si lanciano con\n"
        . "    ORANGEROAD_CONFIG=config/config.php php " . ($argv[0] ?? 'tests/...') . "\n\n");
    exit(2);
}

$GLOBALS['__prove'] = ['ok' => 0, 'ko' => 0, 'errori' => []];

function prova(string $nome, callable $f): void
{
    try {
        $f();
        $GLOBALS['__prove']['ok']++;
        fwrite(STDOUT, "  \033[0;32m✓\033[0m {$nome}\n");
    } catch (\Throwable $e) {
        $GLOBALS['__prove']['ko']++;
        $GLOBALS['__prove']['errori'][] = $nome . ': ' . $e->getMessage();
        fwrite(STDOUT, "  \033[0;31m✗\033[0m {$nome} — " . $e->getMessage() . "\n");
    }
}

/** Un'intestazione di gruppo, per leggere l'esito a colpo d'occhio. */
function titolo(string $testo): void
{
    fwrite(STDOUT, "\n\033[1;36m{$testo}\033[0m\n");
}

function vero(mixed $v, string $perche = ''): void
{
    if (!$v) {
        throw new RuntimeException($perche !== '' ? $perche : 'atteso vero, ottenuto falso');
    }
}

function uguale(mixed $atteso, mixed $ottenuto, string $perche = ''): void
{
    if ($atteso !== $ottenuto) {
        throw new RuntimeException(
            ($perche !== '' ? $perche . ' — ' : '')
            . 'atteso ' . var_export($atteso, true) . ', ottenuto ' . var_export($ottenuto, true)
        );
    }
}

function vicino(float $atteso, float $ottenuto, float $tolleranza = 1e-9, string $perche = ''): void
{
    if (abs($atteso - $ottenuto) > $tolleranza) {
        throw new RuntimeException(
            ($perche !== '' ? $perche . ' — ' : '')
            . "atteso {$atteso} ± {$tolleranza}, ottenuto {$ottenuto}"
        );
    }
}

function riepilogo(): void
{
    $p = $GLOBALS['__prove'];
    fwrite(STDOUT, "\n  {$p['ok']} passate, {$p['ko']} fallite\n");
    exit($p['ko'] === 0 ? 0 : 1);
}
