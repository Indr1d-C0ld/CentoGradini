<?php

declare(strict_types=1);

namespace App\Cli;

use App\Core\Database;

/**
 * Carica i dati di ambientazione da db/seed/ dentro le tabelle. Idempotente.
 *
 * Ogni file db/seed/<nome>.php restituisce:
 *   ['tabella' => 'luoghi', 'chiave' => 'lkey', 'descrizione' => 'luoghi del quartiere',
 *    'righe' => [ ['lkey' => 'gradini', ...], ... ]]
 *
 * Il formato e' generico di proposito: i seed di questo gioco sono contenuto
 * d'ambientazione che cambiera' spesso, e non vale la pena scrivere un metodo
 * dedicato per ciascuno come si era fatto altrove.
 */
final class Seeder
{
    public function __construct(private string $projectRoot)
    {
    }

    /** @return list<string> */
    public function all(): array
    {
        $log = [];
        $files = glob(rtrim($this->projectRoot, '/') . '/db/seed/*.php') ?: [];
        sort($files);

        if ($files === []) {
            return ['  nessun file di seed in db/seed/'];
        }

        foreach ($files as $file) {
            $log[] = '  ' . $this->uno($file);
        }
        return $log;
    }

    private function uno(string $file): string
    {
        /** @var array{tabella:string,chiave:string,righe:list<array<string,mixed>>,descrizione?:string} $def */
        $def = require $file;
        $nome = basename($file, '.php');

        foreach (['tabella', 'chiave', 'righe'] as $obbligatorio) {
            if (!isset($def[$obbligatorio])) {
                return "{$nome}: SALTATO (manca la voce '{$obbligatorio}')";
            }
        }

        $tabella = (string) $def['tabella'];
        $chiave  = (string) $def['chiave'];
        $righe   = $def['righe'];

        if ($righe === []) {
            return "{$nome}: nessuna riga";
        }

        foreach ($righe as $r) {
            $campi = array_keys($r);
            // La chiave naturale non si aggiorna: e' il perno su cui si riconosce la riga.
            $aggiorna = array_filter($campi, static fn (string $c): bool => $c !== $chiave);
            $sql = 'INSERT INTO ' . $tabella . ' (' . implode(', ', $campi) . ') VALUES ('
                . implode(', ', array_fill(0, count($campi), '?')) . ')'
                . ($aggiorna === [] ? ' ON DUPLICATE KEY UPDATE ' . $chiave . ' = VALUES(' . $chiave . ')'
                    : ' ON DUPLICATE KEY UPDATE ' . implode(', ', array_map(
                        static fn (string $c): string => "{$c} = VALUES({$c})",
                        $aggiorna
                    )));
            Database::run($sql, array_values($r));
        }

        $che = (string) ($def['descrizione'] ?? $tabella);
        return sprintf('%-22s %4d righe  (%s)', $nome, count($righe), $che);
    }
}
