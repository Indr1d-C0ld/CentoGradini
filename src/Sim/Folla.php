<?php

declare(strict_types=1);

namespace App\Sim;

use App\Game\Eventi;
/**
 * Quanta gente c'è in giro.
 *
 * Non sono personaggi: sono **passanti**, e servono a una cosa sola — decidere
 * se una cosa vistosa è successa davanti a qualcuno. Un teletrasporto in
 * corridoio durante l'intervallo e un teletrasporto sull'argine alle due di
 * notte sono due azioni diverse, e senza questo numero il gioco non saprebbe
 * dirlo.
 *
 * Non generano anomalie personali — per quelle serve un testimone con un nome
 * — ma alzano il **calore** del luogo e fanno nascere le voci. È la differenza
 * fra «Hino ti ha visto» e «girano storie strane su quella scalinata».
 *
 * I numeri sono a occhio e vanno guardati con quell'occhio: sono contenuto
 * d'ambientazione, non fisica.
 */
final class Folla
{
    /**
     * Gente di base per tipo di luogo, nelle ore in cui il luogo vive.
     * @var array<string,int>
     */
    private const PER_TIPO = [
        'scuola'  => 25,
        'ritrovo' => 10,
        'strada'  => 6,
        'natura'  => 3,
        'casa'    => 0,
        'fuori'   => 8,
    ];

    /** Scostamenti per luoghi che non si comportano come il loro tipo. */
    private const PER_LUOGO = [
        'casa_kasuga'  => 0,
        'casa_ayukawa' => 0,
        'argine'       => 1,    // ci si va apposta per non trovare nessuno
        'albero'       => 0,    // in fondo al parco, dove non passa quasi nessuno
        'tempio'       => 2,
        'montagna'     => 1,
        // La grande scalinata NON la usa nessuno, ed e' canone: nella serie
        // «hormis Kyosuke, aucun badaud ne passe par cet escalier», e in Shin
        // KOR Hikaru glielo dice in faccia — c'e' un'altra strada, piu'
        // comoda, che fa un giro. Quella strada e' la scaletta di dietro.
        // Prima avevamo 7 con scritto «ci passano tutti»: era il contrario,
        // ed e' il motivo per cui in cima Kyosuke e Madoka riescono sempre a
        // stare da soli in un quartiere pieno di gente.
        'gradini'      => 1,
        'scaletta'     => 8,    // di qui scende e sale il quartiere
        'area_giochi'  => 2,    // i bambini di giorno, nessuno dopo cena
        'giardini'     => 0,    // ci si viene per non essere sentiti
    ];

    /**
     * Quante persone anonime ci sono in questo luogo adesso.
     *
     * @param int|null $gts istante (lineare o avvolto: ci pensa il calendario)
     */
    public static function a(string $lkey, ?int $gts = null): int
    {
        $l = Luoghi::uno($lkey);
        if ($l === null) {
            return 0;
        }
        $cal = Calendario::stato($gts);
        $base = self::PER_LUOGO[$lkey] ?? self::PER_TIPO[(string) $l['tipo']] ?? 4;
        if ($base === 0) {
            return 0;
        }

        $f = (float) $base;

        // --- La scuola vive di suo ------------------------------------------
        if ((string) $l['tipo'] === 'scuola') {
            $f *= match ($cal['fase']) {
                'intervallo'        => 1.6,
                'pranzo'            => 1.4,
                'lezione'           => 1.0,
                'pulizie'           => 0.9,
                'appello'           => 1.2,
                'tragitto'          => 0.8,
                'club'              => 0.5,
                default             => 0.05,   // di notte e in vacanza è vuota
            };
        } else {
            // --- Tutto il resto segue l'ora --------------------------------
            $f *= match ($cal['fase']) {
                'notte'             => 0.05,
                'alba'              => 0.25,
                'tragitto'          => 1.5,    // il flusso casa-scuola
                'lezione', 'appello', 'intervallo', 'pranzo', 'pulizie' => 0.45,
                'club'              => 0.9,
                'sera'              => 1.0,
                default             => 1.1,    // pomeriggi e giorni liberi
            };
            // Nei giorni senza scuola il quartiere si riempie di ragazzi.
            if (!$cal['scuola'] && in_array($cal['fase'], ['libero_mattina', 'libero_pomeriggio'], true)) {
                $f *= 1.4;
            }
        }

        // --- Il tempo che fa --------------------------------------------------
        $m = Meteo::a($gts);
        if ($m['pioggia'] >= 8.0 || $m['tifone']) {
            $f *= 0.3;
        } elseif ($m['pioggia'] > 0) {
            $f *= 0.65;
        }
        if ($m['temperatura'] < 3 || $m['temperatura'] > 32) {
            $f *= 0.8;
        }

        // --- I giorni che contano ----------------------------------------------
        if ($cal['evento'] !== null) {
            // Il festival, il capodanno al tempio, i fuochi d'artificio: il
            // quartiere si sposta tutto nello stesso posto.
            $f *= match ($lkey) {
                'tempio'    => Calendario::dentro($cal['md'], '12-31', '01-03') ? 12.0 : 1.5,
                'liceo'     => in_array($cal['md'], ['09-26', '10-24'], true) ? 2.0 : 1.2,
                'argine'    => $cal['md'] === '08-01' ? 10.0 : 1.2,
                default     => 1.3,
            };
        }

        // --- Gli eventi stagionali del server ----------------------------------
        // Non inventano una meccanica: alzano questo numero, e tutto il resto
        // — testimoni, calore, probabilità di essere notati — segue da solo.
        // È il motivo per cui usare un potere al festival d'estate è la cosa
        // più imprudente dell'anno.
        $richiamo = Eventi::richiamo($lkey, $gts);
        if ($richiamo > 0) {
            $f *= 1.0 + $richiamo / 50.0;
        }

        return max(0, (int) round($f));
    }

    /**
     * Una parola per dirlo, che è quello che serve a schermo.
     */
    public static function descrizione(int $quanti): string
    {
        return match (true) {
            $quanti === 0 => 'non c\'è nessuno',
            $quanti <= 2  => 'c\'è qualcuno, poco distante',
            $quanti <= 6  => 'c\'è un po\' di gente',
            $quanti <= 15 => 'c\'è parecchia gente',
            $quanti <= 30 => 'è pieno di gente',
            default       => 'è una folla',
        };
    }
}
