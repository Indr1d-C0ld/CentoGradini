<?php

declare(strict_types=1);

namespace App\Sim;

use App\Core\GameConfig;

/**
 * Il tempo che fa nel quartiere.
 *
 * **Non c'è una sola riga di meteo salvata a database.** Il tempo è una
 * funzione pura di (seme del mondo, istante): chiunque la interroghi due volte
 * per lo stesso istante ottiene lo stesso cielo, e non serve nessun processo
 * che lo faccia avanzare. È la stessa scelta già fatta per l'orologio, e per
 * lo stesso motivo: cio' che si puo' ricalcolare non va conservato.
 *
 * Il modello è un rumore continuo a più scale, piegato sulle medie climatiche
 * reali di Tokyo. Non è una simulazione fisica e non pretende di esserlo: deve
 * produrre un anno che si comporta come un anno giapponese, perche' in questa
 * storia il tempo atmosferico è un attore.
 *
 *  - Da giugno a metà luglio c'è il **tsuyu**, la stagione delle piogge: cielo
 *    coperto quasi tutti i giorni, pioggia insistente e leggera. È il periodo
 *    dell'ombrello condiviso, che nel manga vale piu' di una dichiarazione.
 *  - Agosto è caldo e umido, con temporali di calore brevi e violenti.
 *  - Settembre porta i tifoni: vento vero, per qualche giorno.
 *  - L'inverno a Tokyo è **secco e sereno**, non piovoso. La neve è un evento
 *    raro — qualche giorno fra gennaio e febbraio — e proprio per questo, in
 *    una storia, conta.
 */
final class Meteo
{
    /** Medie mensili di Tokyo: [temperatura media °C, nuvolosita' di fondo 0..1]. */
    private const CLIMA = [
        1  => [ 5.2, 0.28],
        2  => [ 5.7, 0.34],
        3  => [ 8.7, 0.46],
        4  => [14.0, 0.48],
        5  => [18.4, 0.50],
        6  => [21.4, 0.74],   // tsuyu
        7  => [25.0, 0.62],
        8  => [26.4, 0.52],
        9  => [22.8, 0.60],   // tifoni
        10 => [17.5, 0.48],
        11 => [12.1, 0.38],
        12 => [ 7.6, 0.30],
    ];

    /** Escursione giornaliera, in gradi (mezza ampiezza). */
    private const ESCURSIONE = 4.0;

    /** Ora del minimo e del massimo: prima dell'alba e met&agrave; pomeriggio. */
    private const ORA_MINIMO = 5.0;

    private static function seme(): int
    {
        return GameConfig::int('world.seed', 19870406);
    }

    /**
     * Rumore continuo in [0,1): costante a scale lunghe, liscio a scale corte.
     *
     * I valori si estraggono a intervalli regolari di $periodo secondi e si
     * raccordano con una curva a S. Senza il raccordo morbido il cielo
     * cambierebbe di scatto a ogni bucket, e il giocatore che ricarica la
     * pagina vedrebbe il sole diventare temporale in un secondo.
     */
    private static function rumore(string $canale, int $gts, int $periodo): float
    {
        $i = (int) floor($gts / $periodo);
        $f = ($gts - $i * $periodo) / $periodo;
        $a = Rng::for(self::seme(), $canale, $i)->float();
        $b = Rng::for(self::seme(), $canale, $i + 1)->float();
        $s = $f * $f * (3.0 - 2.0 * $f);      // smoothstep
        return $a + ($b - $a) * $s;
    }

    /**
     * Il tempo a un dato istante.
     *
     * @return array{
     *   temperatura:float, percepita:float, nuvolosita:float, pioggia:float,
     *   neve:bool, vento:float, umidita:float, tifone:bool,
     *   cielo:string, descrizione:string, ombrello:bool, icona:string
     * }
     */
    public static function a(?int $gts = null): array
    {
        // Due istanti, apposta. Il **clima** (che mese e', che stagione)
        // viene dalla data avvolta nel ciclo: aprile deve essere aprile ogni
        // anno. Il **rumore** viene dall'istante lineare: cosi' il secondo
        // aprile del mondo non ha giorno per giorno lo stesso cielo del primo.
        // Il calendario si ripete, il tempo che fa no — ed e' la differenza
        // fra un anno che torna e una registrazione che si riavvolge.
        $lin  = $gts ?? Orologio::lineare();
        $gts  = Orologio::avvolgi($lin);
        $d    = Orologio::data($gts);
        $mese = (int) $d->format('n');
        $md   = $d->format('m-d');
        $oraDec = (int) $d->format('G') + (int) $d->format('i') / 60.0;

        [$tMedia, $nubiBase] = self::CLIMA[$mese];

        // Interpolazione fra un mese e l'altro: senza, il 31 luglio e il
        // 1° agosto avrebbero climi diversi di un salto netto.
        $giorno = (int) $d->format('j');
        $giorniMese = (int) $d->format('t');
        $prossimo = self::CLIMA[$mese === 12 ? 1 : $mese + 1];
        $q = ($giorno - 1) / max(1, $giorniMese - 1);
        $tMedia   += ($prossimo[0] - $tMedia) * $q * 0.5;
        $nubiBase += ($prossimo[1] - $nubiBase) * $q * 0.5;

        // --- Nuvolosita' ------------------------------------------------------
        // Due scale: i fronti passano in un paio di giorni, le schiarite locali
        // in qualche ora.
        $fronte = self::rumore('fronte', $lin, 30 * 3600);
        $locale = self::rumore('locale', $lin, 7 * 3600);
        $nubi = $nubiBase * 0.55 + $fronte * 0.32 + $locale * 0.13;

        // Il tsuyu non e' una media alta: e' un cielo chiuso quasi sempre.
        if (Calendario::dentro($md, '06-08', '07-20')) {
            $nubi = 0.55 + $nubi * 0.45;
        }
        $nubi = max(0.0, min(1.0, $nubi));

        // --- Vento e tifoni ----------------------------------------------------
        $vento = 1.0 + self::rumore('vento', $lin, 11 * 3600) * 6.0;
        // In settembre un fronte molto marcato e' un tifone, e si vede.
        $tifone = Calendario::dentro($md, '09-01', '10-05') && $fronte > 0.88;
        if ($tifone) {
            $vento += 14.0;
            $nubi = 1.0;
        }

        // --- Precipitazione ------------------------------------------------------
        // Soglia stagionale: d'inverno serve molta piu' nuvolosita' per piovere,
        // perche' a Tokyo l'inverno e' secco e sereno.
        $soglia = match (true) {
            Calendario::dentro($md, '06-08', '07-20') => 0.56,   // tsuyu
            in_array($mese, [12, 1, 2], true)          => 0.86,   // inverno secco
            $mese === 8                                => 0.78,   // temporali di calore
            default                                    => 0.74,
        };
        $pioggia = 0.0;
        if ($nubi > $soglia) {
            $forza = ($nubi - $soglia) / max(0.01, 1.0 - $soglia);
            // Il tsuyu pioviggina a lungo; agosto scarica tutto in mezz'ora.
            $scala = Calendario::dentro($md, '06-08', '07-20') ? 3.5 : ($mese === 8 ? 16.0 : 6.0);
            $pioggia = round($forza * $forza * $scala, 2);
        }
        if ($tifone) {
            $pioggia = max($pioggia, 22.0);
        }

        // --- Temperatura -----------------------------------------------------------
        // Ciclo giornaliero sfasato: il minimo prima dell'alba, il massimo a
        // meta' pomeriggio. Le nuvole appiattiscono l'escursione: di giorno
        // tengono fuori il sole, di notte tengono dentro il calore.
        $ciclo = -cos(2 * M_PI * ($oraDec - self::ORA_MINIMO) / 24.0);
        $escursione = self::ESCURSIONE * (1.0 - $nubi * 0.55);
        $deriva = (self::rumore('temp', $lin, 40 * 3600) - 0.5) * 5.0;
        $t = $tMedia + $ciclo * $escursione + $deriva - $pioggia * 0.12;

        // --- Umidita' -----------------------------------------------------------------
        $umidita = max(0.2, min(1.0, 0.42 + $nubi * 0.42 + ($pioggia > 0 ? 0.15 : 0.0)
            + (in_array($mese, [6, 7, 8], true) ? 0.12 : 0.0)));

        // Percepita: d'estate pesa l'umidita', d'inverno pesa il vento.
        $percepita = $t >= 24
            ? $t + ($umidita - 0.6) * 6.0
            : $t - max(0.0, $vento - 2.0) * 0.45;

        $neve = $t <= 2.0 && $pioggia > 0.0;

        return [
            'temperatura' => round($t, 1),
            'percepita'   => round($percepita, 1),
            'nuvolosita'  => round($nubi, 2),
            'pioggia'     => $pioggia,
            'neve'        => $neve,
            'vento'       => round($vento, 1),
            'umidita'     => round($umidita, 2),
            'tifone'      => $tifone,
            'cielo'       => self::cielo($nubi, $pioggia, $neve),
            'descrizione' => self::descrizione($gts, $t, $nubi, $pioggia, $neve, $vento, $tifone),
            'ombrello'    => $pioggia >= 0.3,
            'icona'       => self::icona($gts, $nubi, $pioggia, $neve, $tifone),
        ];
    }

    private static function cielo(float $nubi, float $pioggia, bool $neve): string
    {
        if ($neve)            { return 'neve'; }
        if ($pioggia >= 8.0)  { return 'rovescio'; }
        if ($pioggia >= 0.3)  { return 'pioggia'; }
        if ($nubi >= 0.85)    { return 'coperto'; }
        if ($nubi >= 0.55)    { return 'nuvoloso'; }
        if ($nubi >= 0.28)    { return 'poco nuvoloso'; }
        return 'sereno';
    }

    private static function icona(int $gts, float $nubi, float $pioggia, bool $neve, bool $tifone): string
    {
        $notte = self::notte($gts);
        if ($tifone)          { return '🌀'; }
        if ($neve)            { return '🌨'; }
        if ($pioggia >= 8.0)  { return '⛈'; }
        if ($pioggia >= 0.3)  { return '🌧'; }
        if ($nubi >= 0.85)    { return '☁️'; }
        if ($nubi >= 0.55)    { return $notte ? '☁️' : '⛅'; }
        return $notte ? '🌙' : '☀️';
    }

    /** Vero fra il tramonto e l'alba, approssimati sulla latitudine di Tokyo. */
    public static function notte(?int $gts = null): bool
    {
        $gts ??= Orologio::gts();
        $d = Orologio::data($gts);
        $ora = (int) $d->format('G') + (int) $d->format('i') / 60.0;
        // Tokyo, 35,7° N: alba fra le 4:25 di giugno e le 6:50 di gennaio.
        $giorno = (int) $d->format('z');
        $alba     = 5.6 - 1.2 * cos(2 * M_PI * ($giorno - 172) / 365.0);
        $tramonto = 17.6 + 1.4 * cos(2 * M_PI * ($giorno - 172) / 365.0);
        return $ora < $alba || $ora >= $tramonto;
    }

    /**
     * Una riga che si possa leggere, non un bollettino.
     *
     * Le frasi sono scritte perche' stiano in un riquadro accanto al nome del
     * luogo: dicono che tempo fa e che effetto fa, e si fermano li'.
     */
    private static function descrizione(
        int $gts, float $t, float $nubi, float $pioggia, bool $neve, float $vento, bool $tifone
    ): string {
        $notte = self::notte($gts);
        $stagione = Calendario::stagione(Orologio::data($gts));

        if ($tifone) {
            return 'Tifone. Le persiane sbattono e nessuno mette il naso fuori se può evitarlo.';
        }
        if ($neve) {
            return $notte
                ? 'Nevica. A Tokyo succede due o tre volte l\'anno, e ogni volta sembra un\'altra città.'
                : 'Nevica, e la neve attacca. Domani mattina il quartiere sarà irriconoscibile.';
        }
        if ($pioggia >= 8.0) {
            return 'Rovescio. Si corre da una tettoia all\'altra, e chi ha l\'ombrello diventa molto popolare.';
        }
        if ($pioggia >= 0.3) {
            return $stagione === 'estate' && Calendario::dentro(Orologio::data($gts)->format('m-d'), '06-08', '07-20')
                ? 'Pioggia sottile e continua, quella del tsuyu, che non smette mai davvero.'
                : 'Pioviggina. Un ombrello basta per due, volendo.';
        }
        if ($vento >= 9.0) {
            return 'Vento forte: le gonne delle divise e i capelli fanno quello che vogliono.';
        }
        if ($nubi >= 0.85) {
            return 'Cielo chiuso, colore del cemento. Il tipo di giornata in cui non si capisce che ora sia.';
        }
        if ($t >= 30.0) {
            return 'Caldo appiccicoso. Le cicale non smettono un attimo e l\'asfalto ondeggia.';
        }
        if ($t <= 3.0) {
            return 'Gelo secco. Si vede il fiato, e le mani restano in tasca.';
        }
        if ($notte) {
            return $nubi < 0.3
                ? 'Notte limpida. Poche stelle: qui le luci della città se le mangiano quasi tutte.'
                : 'Sera tranquilla, senza vento.';
        }
        return match ($stagione) {
            'primavera' => $nubi < 0.3
                ? 'Sole tiepido e aria ferma. Il tipo di giornata che ti fa arrivare a scuola di buonumore.'
                : 'Qualche nuvola, aria mite.',
            'estate'    => 'Sole pieno e caldo, ma si respira.',
            'autunno'   => $nubi < 0.3
                ? 'Aria limpida e fresca: l\'autunno giapponese sa fare questo meglio di tutti.'
                : 'Cielo velato, aria fresca.',
            default     => $nubi < 0.3
                ? 'Sereno e freddo. L\'inverno di Tokyo è quasi sempre così: pungente e senza una nuvola.'
                : 'Grigio e freddo.',
        };
    }
}
