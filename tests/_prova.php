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
