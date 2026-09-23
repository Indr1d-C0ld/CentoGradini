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

        // Le colonne che il seme scrive solo quando la riga NASCE. Il resto lo
        // riallinea a ogni giro, ed e' giusto per i dati di ambientazione — i
        // nomi dei luoghi, le frasi dei gesti, le bande dei poteri — ma non per
        // lo stato che il gioco modifica. Prima questa distinzione non c'era, e
        // poiche' il seme gira a ogni deploy, ogni pubblicazione toglieva il
        // cappello di paglia a chi lo teneva e lo rimetteva sui gradini, e
        // riportava i tredici abitanti al punto e ai valori di partenza.
        $soloAllaNascita = array_map('strval', (array) ($def['solo_alla_nascita'] ?? []));

        if ($righe === []) {
            return "{$nome}: nessuna riga";
        }

        foreach ($righe as $r) {
            $campi = array_keys($r);
            // La chiave naturale non si aggiorna: e' il perno su cui si riconosce la
            // riga. E nemmeno lo stato di partenza: quello e' del gioco, adesso.
            $aggiorna = array_filter($campi, static fn (string $c): bool
                => $c !== $chiave && !in_array($c, $soloAllaNascita, true));
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
