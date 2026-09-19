<?php

declare(strict_types=1);

namespace App\Game;

use App\Core\Database;
use App\Sim\Calendario;
use App\Sim\Luoghi;
use App\Sim\Folla;
use App\Sim\Meteo;
use App\Sim\Orologio;

/**
 * Una sola porta d'ingresso allo stato del mondo.
 *
 * Orologio, calendario e meteo sono tre moduli puri e indipendenti; le viste
 * pero' vogliono una cosa sola da leggere, e soprattutto vogliono che quella
 * cosa sia **coerente**: se la testata dice le 17:04 e il riquadro del tempo
 * calcola il proprio istante un secondo dopo, prima o poi capita la pagina in
 * cui sono le 17:04 e sta piovendo mentre due righe sotto sono le 17:05 e c'e'
 * il sole. Qui l'istante si prende una volta e si passa a tutti.
 */
final class Mondo
{
    /** @var array<string,mixed>|null fotografia per richiesta */
    private static ?array $ora = null;

    public static function dimentica(): void
    {
        self::$ora = null;
    }

    /**
     * Lo stato del mondo adesso: un istante solo, condiviso da tutti.
     *
     * @return array{
     *   lineare:int, gts:int, giro:int, compressione:int,
     *   calendario:array<string,mixed>, meteo:array<string,mixed>,
     *   quando:string, quando_breve:string, notte:bool
     * }
     */
    public static function adesso(): array
    {
        if (self::$ora !== null) {
            return self::$ora;
        }
        $lin = Orologio::lineare();
        $gts = Orologio::avvolgi($lin);

        return self::$ora = [
            'lineare'      => $lin,
            'gts'          => $gts,
            'giro'         => Orologio::giro(),
            'compressione' => Orologio::compressione(),
            'calendario'   => Calendario::stato($gts),
            'meteo'        => Meteo::a($lin),
            'quando'       => Orologio::esteso($gts),
            'quando_breve' => Orologio::breve($gts),
            'notte'        => Meteo::notte($gts),
        ];
    }

    /**
     * Una riga sola che dica dove siamo nel tempo: la si legge in testata.
     * «Il primo anno · secondo trimestre · pausa pranzo»
     */
    public static function insegna(): string
    {
        $m   = self::adesso();
        $cal = $m['calendario'];

        $pezzi = [];
        $pezzi[] = match ($m['giro']) {
            0       => 'il primo anno',
            1       => 'il secondo anno',
            2       => 'il terzo anno',
            default => 'il ' . ($m['giro'] + 1) . '° anno',
        };
        if ($cal['vacanza'] !== null) {
            $pezzi[] = $cal['vacanza'];
        } elseif ($cal['trimestre'] !== null) {
            $pezzi[] = match ($cal['trimestre']) {
                1 => 'primo trimestre', 2 => 'secondo trimestre', default => 'terzo trimestre',
            };
        }
        $pezzi[] = $cal['festa'] ?? $cal['fase_nome'];

        return implode(' · ', $pezzi);
    }

    /**
     * Tutto quello che serve per disegnare un luogo.
     *
     * @param array<string,mixed>|null $pg
     * @return array<string,mixed>
     */
    public static function luogo(string $lkey, ?array $pg = null): array
    {
        $m     = self::adesso();
        $luogo = Luoghi::uno($lkey);
        if ($luogo === null) {
            return [];
        }
        $io = $pg === null ? null : (int) $pg['id'];

        $folla  = Folla::a($lkey, $m['lineare']);
        $calore = Segreto::calore($lkey, $m['lineare']);

        return [
            'luogo'     => $luogo,
            'uscite'    => Luoghi::uscite($lkey, $m['lineare']),
            'presenti'  => Personaggio::presenti($lkey, $io),
            'passati'   => Personaggio::passatiDiRecente($lkey, $io),
            'tracce'    => Personaggio::tracce($lkey),
            'aperto'    => Luoghi::accessibile($lkey, $m['lineare'])[0],
            'folla'     => $folla,
            'folla_dice'=> Folla::descrizione($folla),
            'calore'    => $calore,
            'calore_dice' => Segreto::descrizioneCalore($calore),
        ];
    }

    /**
     * I dati della carta, come li vuole il disegno su tela.
     *
     * @return array<string,mixed>
     */
    public static function carta(?array $pg = null): array
    {
        $m = self::adesso();
        $qui = $pg === null ? null : (string) $pg['luogo'];

        // Quante persone in ciascun luogo: la carta deve far vedere dov'e' la
        // gente, altrimenti e' una piantina e non un mondo abitato.
        $teste = [];
        foreach (Database::all(
            "SELECT luogo, COUNT(*) AS n FROM personaggi
             WHERE verso IS NULL AND stato = 'attivo' GROUP BY luogo"
        ) as $r) {
            $teste[(string) $r['luogo']] = (int) $r['n'];
        }

        $nodi = [];
        foreach (Luoghi::tutti() as $lkey => $l) {
            [$aperto] = Luoghi::accessibile($lkey, $m['lineare']);
            $nodi[] = [
                'k'       => $lkey,
                'nome'    => $l['nome'],
                'x'       => $l['x'],
                'y'       => $l['y'],
                'tipo'    => $l['tipo'],
                'fuori'   => $l['fuori'],
                'privato' => $l['privato'],
                'aperto'  => $aperto,
                'gente'   => $teste[$lkey] ?? 0,
                'qui'     => $lkey === $qui,
            ];
        }

        // Un solo tratto per coppia: la carta disegna strade, non frecce.
        $viste = [];
        $tratti = [];
        foreach (Luoghi::archi() as $da => $lista) {
            foreach ($lista as $arco) {
                $coppia = $da < $arco['a'] ? $da . '|' . $arco['a'] : $arco['a'] . '|' . $da;
                if (isset($viste[$coppia])) {
                    continue;
                }
                $viste[$coppia] = true;
                $tratti[] = ['da' => $da, 'a' => $arco['a'], 'mezzo' => $arco['mezzo']];
            }
        }

        return [
            'nodi'   => $nodi,
            'tratti' => $tratti,
            'notte'  => $m['notte'],
            'pioggia'=> $m['meteo']['pioggia'] > 0,
            'qui'    => $qui,
            'verso'  => $pg === null ? null : $pg['verso'],
        ];
    }
}
