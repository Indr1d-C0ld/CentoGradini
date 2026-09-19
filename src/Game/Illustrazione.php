<?php

declare(strict_types=1);

namespace App\Game;

use App\Core\GameConfig;
use App\Sim\Calendario;
use App\Sim\Luoghi;
use App\Sim\Meteo;
use App\Sim\Orologio;
use App\Sim\Rng;

/**
 * L'illustrazione di un ricordo.
 *
 * Un SVG generato dal **luogo**, dalla **stagione**, dall'**ora** e dal
 * **tempo che faceva** in quell'istante: le stesse quattro cose che il motore
 * già sa, messe in figura invece che in parole.
 *
 * Non è arte e non pretende di esserlo — sono silhouette piatte in
 * controluce, che è esattamente il registro in cui funzionano. Ma non sono
 * decorazione neutra: l'albero dei ricordi ha un albero, i Cento Gradini
 * hanno una scalinata, il tempio ha un torii, e a giugno piove davvero. Due
 * ricordi nello stesso posto in due stagioni diverse danno due figure
 * diverse, e si riconoscono a colpo d'occhio scorrendo l'album.
 *
 * Tutto è **deterministico**: stesso ricordo, stessa immagine, sempre. Il
 * seme è l'id del ricordo, quindi due persone che hanno vissuto la stessa
 * scena vedono la stessa illustrazione.
 */
final class Illustrazione
{
    private const L = 640;
    private const H = 260;

    /** Palette del cielo per fascia oraria. @var array<string, array{0:string,1:string}> */
    private const CIELI = [
        'notte'      => ['#101728', '#24304d'],
        'alba'       => ['#3b3a5c', '#e9a170'],
        'mattina'    => ['#9fd3ea', '#e8f4f8'],
        'pomeriggio' => ['#7fc4e6', '#dcedf5'],
        'tramonto'   => ['#f0895c', '#f6d09a'],
        'sera'       => ['#2f3a5e', '#7b6a8a'],
    ];

    /** Tinta di stagione, che va sopra tutto con poca opacità. */
    private const STAGIONI = [
        'primavera' => '#f6c8d8',
        'estate'    => '#bde6a8',
        'autunno'   => '#e8a95f',
        'inverno'   => '#cfe0ea',
    ];

    /**
     * L'SVG di un ricordo.
     *
     * @param array{id?:int|string, luogo?:string, gts?:int|string} $ricordo
     */
    public static function per(array $ricordo): string
    {
        $gts   = (int) ($ricordo['gts'] ?? Orologio::lineare());
        $lkey  = (string) ($ricordo['luogo'] ?? 'gradini');
        $seme  = (int) ($ricordo['id'] ?? 0) + GameConfig::int('world.seed', 19870406);
        $rng   = Rng::for($seme, 'illustrazione', $lkey);

        $cal   = Calendario::stato($gts);
        $meteo = Meteo::a($gts);
        $fascia = self::fascia((int) $cal['ora'], Meteo::notte($gts));
        [$alto, $basso] = self::CIELI[$fascia];
        $tinta = self::STAGIONI[(string) $cal['stagione']] ?? '#ffffff';
        $buio  = in_array($fascia, ['notte', 'sera'], true);

        $p = [];
        $p[] = sprintf('<svg class="illustrazione" viewBox="0 0 %d %d" role="img" aria-label="%s" '
            . 'xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">',
            self::L, self::H, self::e(self::descrizione($lkey, $cal, $meteo)));

        $gid = 'c' . substr(md5($lkey . $gts . $seme), 0, 8);
        $p[] = '<defs>';
        $p[] = sprintf('<linearGradient id="%s" x1="0" y1="0" x2="0" y2="1">'
            . '<stop offset="0" stop-color="%s"/><stop offset="1" stop-color="%s"/></linearGradient>',
            $gid, $alto, $basso);
        $p[] = '</defs>';
        $p[] = sprintf('<rect width="%d" height="%d" fill="url(#%s)"/>', self::L, self::H, $gid);

        $p[] = self::astro($fascia, $rng);
        $p[] = self::stelle($buio, $rng);
        $p[] = self::sfondo($rng);
        $p[] = self::scena($lkey, $buio);
        $p[] = self::tempo($meteo, (string) $cal['stagione'], $rng);
        $p[] = sprintf('<rect width="%d" height="%d" fill="%s" opacity="0.10"/>', self::L, self::H, $tinta);
        $p[] = '</svg>';

        return implode('', array_filter($p));
    }

    /** La riga che descrive la figura, per chi non la vede. */
    public static function descrizione(string $lkey, array $cal, array $meteo): string
    {
        $pezzi = [Luoghi::nome($lkey), (string) $cal['stagione'], (string) $cal['fase_nome']];
        if ($meteo['tifone']) {
            $pezzi[] = 'tifone';
        } elseif ((float) $meteo['pioggia'] >= 8.0) {
            $pezzi[] = 'pioggia forte';
        } elseif ((float) $meteo['pioggia'] > 0) {
            $pezzi[] = 'pioggia';
        }
        return implode(', ', $pezzi);
    }

    // --- Cielo -------------------------------------------------------------------

    private static function fascia(int $ora, bool $notte): string
    {
        return match (true) {
            $ora < 5            => 'notte',
            $ora < 7            => 'alba',
            $ora < 12           => 'mattina',
            $ora < 16           => 'pomeriggio',
            $ora < 19           => 'tramonto',
            $notte || $ora >= 22 => 'notte',
            default             => 'sera',
        };
    }

    private static function astro(string $fascia, Rng $rng): string
    {
        $x = $rng->int(90, self::L - 90);
        return match ($fascia) {
            'notte', 'sera' => sprintf(
                '<circle cx="%d" cy="52" r="20" fill="#f4f0dc" opacity="0.92"/>'
                . '<circle cx="%d" cy="46" r="18" fill="#000" opacity="0.0"/>', $x, $x + 7),
            'alba', 'tramonto' => sprintf(
                '<circle cx="%d" cy="120" r="34" fill="#ffd9a0" opacity="0.85"/>', $x),
            default => sprintf('<circle cx="%d" cy="46" r="24" fill="#fff6d8" opacity="0.65"/>', $x),
        };
    }

    private static function stelle(bool $buio, Rng $rng): string
    {
        if (!$buio) {
            return '';
        }
        $s = '';
        for ($i = 0; $i < 46; $i++) {
            $s .= sprintf('<circle cx="%d" cy="%d" r="%s" fill="#fff" opacity="%.2f"/>',
                $rng->int(4, self::L - 4), $rng->int(4, 130),
                $rng->chance(0.2) ? '1.5' : '1', $rng->range(0.25, 0.9));
        }
        return $s;
    }

    /** Le colline dietro a tutto: ci sono sempre, e danno profondità. */
    private static function sfondo(Rng $rng): string
    {
        $y = 176;
        $d = 'M0,' . self::H;
        for ($x = 0; $x <= self::L; $x += 80) {
            $d .= sprintf(' L%d,%d', $x, $y + $rng->int(-16, 10));
        }
        $d .= sprintf(' L%d,%d Z', self::L, self::H);
        return sprintf('<path d="%s" fill="#000" opacity="0.13"/>', $d);
    }

    // --- Il posto ----------------------------------------------------------------

    /**
     * La silhouette del luogo. Un posto che non ha una figura sua ricade sul
     * tipo: è meglio una sagoma generica giusta che una sbagliata specifica.
     */
    private static function scena(string $lkey, bool $buio): string
    {
        $inch = $buio ? '#0d1220' : '#2b2b33';
        $o    = $buio ? 0.94 : 0.86;
        $g    = static fn (string $corpo): string
            => sprintf('<g fill="%s" opacity="%.2f">%s</g>', $inch, $o, $corpo);

        $suolo = sprintf('<rect x="0" y="214" width="%d" height="%d"/>', self::L, self::H - 214);

        return match ($lkey) {
            'gradini' => $g($suolo . self::scalinata()),
            'albero'  => $g($suolo . self::albero(430)),
            'tempio'  => $g($suolo . self::torii(300) . self::albero(120) . self::albero(540)),
            'liceo'   => $g($suolo . self::edificio(120, 140, 400, 74, 5)),
            'stazione', 'passaggio' => $g($suolo . self::binari() . self::palo(90) . self::palo(540)),
            'abcb', 'dischi', 'sala_giochi', 'commerciale'
                      => $g($suolo . self::vetrine()),
            'casa_kasuga' => $g($suolo . self::edificio(200, 150, 240, 64, 3)),
            'casa_ayukawa' => $g($suolo . self::casetta(240)),
            'argine'  => $g(self::fiume() . self::argine()),
            'spiaggia'=> $g(self::mare() . sprintf('<rect x="0" y="228" width="%d" height="%d"/>', self::L, self::H - 228)),
            'montagna'=> $g($suolo . self::monti()),
            'luna_park' => $g($suolo . self::ruota(320)),
            'parco'   => $g($suolo . self::albero(140) . self::albero(300) . self::albero(470) . self::panchina(220)),
            'viale'   => $g($suolo . self::albero(80) . self::albero(230) . self::albero(390) . self::albero(550)),
            default   => $g($suolo . self::albero(200) . self::edificio(360, 160, 200, 54, 3)),
        };
    }

    private static function scalinata(): string
    {
        $s = '';
        $y = 214;
        $x = 190;
        $w = 260;
        for ($i = 0; $i < 11; $i++) {
            $s .= sprintf('<rect x="%d" y="%d" width="%d" height="7"/>',
                $x + $i * 3, $y - $i * 11, $w - $i * 6, 7);
        }
        // I due corrimano, che sono quello che rende riconoscibile la scala.
        $s .= '<path d="M186,214 L214,100 L220,100 L192,214 Z"/>';
        $s .= '<path d="M454,214 L426,100 L420,100 L448,214 Z"/>';
        return $s;
    }

    private static function albero(int $x): string
    {
        return sprintf(
            '<rect x="%d" y="150" width="10" height="66"/>'
            . '<ellipse cx="%d" cy="140" rx="46" ry="34"/>'
            . '<ellipse cx="%d" cy="122" rx="30" ry="24"/>',
            $x - 5, $x, $x - 8);
    }

    private static function torii(int $x): string
    {
        return sprintf(
            '<rect x="%d" y="120" width="12" height="96"/>'
            . '<rect x="%d" y="120" width="12" height="96"/>'
            . '<rect x="%d" y="112" width="%d" height="12" rx="4"/>'
            . '<rect x="%d" y="138" width="%d" height="8"/>',
            $x - 70, $x + 58, $x - 92, 184, $x - 76, 152);
    }

    private static function edificio(int $x, int $y, int $w, int $h, int $finestre): string
    {
        // L'edificio arriva sempre fino a terra: l'altezza dichiarata serve
        // solo a decidere dove comincia il tetto.
        $s = sprintf('<rect x="%d" y="%d" width="%d" height="%d"/>', $x, $y, $w, 214 - $y);
        for ($r = 0; $r < 2; $r++) {
            for ($i = 0; $i < $finestre; $i++) {
                $s .= sprintf('<rect x="%d" y="%d" width="18" height="14" fill="#f4e6b8" opacity="0.45"/>',
                    $x + 16 + $i * ((int) (($w - 32) / max(1, $finestre))), $y + 14 + $r * 26);
            }
        }
        return $s;
    }

    private static function casetta(int $x): string
    {
        return sprintf('<path d="M%d,164 L%d,126 L%d,164 Z"/><rect x="%d" y="164" width="120" height="52"/>'
            . '<rect x="%d" y="180" width="20" height="18" fill="#f4e6b8" opacity="0.5"/>',
            $x, $x + 60, $x + 120, $x, $x + 46);
    }

    private static function vetrine(): string
    {
        $s = '';
        for ($i = 0; $i < 5; $i++) {
            $x = 30 + $i * 120;
            $s .= sprintf('<rect x="%d" y="%d" width="96" height="%d"/>', $x, 128 + ($i % 2) * 14, 88 - ($i % 2) * 14);
            $s .= sprintf('<rect x="%d" y="%d" width="70" height="26" fill="#f4e6b8" opacity="0.5"/>',
                $x + 13, 172 + ($i % 2) * 8);
        }
        return $s;
    }

    private static function binari(): string
    {
        $s = sprintf('<rect x="0" y="196" width="%d" height="5"/><rect x="0" y="208" width="%d" height="5"/>',
            self::L, self::L);
        for ($x = 10; $x < self::L; $x += 34) {
            $s .= sprintf('<rect x="%d" y="192" width="8" height="24" opacity="0.8"/>', $x);
        }
        return $s;
    }

    private static function palo(int $x): string
    {
        return sprintf('<rect x="%d" y="96" width="7" height="120"/><rect x="%d" y="104" width="46" height="6"/>',
            $x, $x - 20);
    }

    private static function fiume(): string
    {
        return sprintf('<rect x="0" y="186" width="%d" height="%d" fill="#3a5a74" opacity="0.55"/>',
            self::L, self::H - 186);
    }

    private static function argine(): string
    {
        return '<path d="M0,214 L640,186 L640,196 L0,224 Z"/>';
    }

    private static function mare(): string
    {
        $s = sprintf('<rect x="0" y="170" width="%d" height="58" fill="#2e6f8e" opacity="0.6"/>', self::L);
        for ($i = 0; $i < 7; $i++) {
            $s .= sprintf('<rect x="%d" y="%d" width="54" height="3" fill="#fff" opacity="0.35"/>',
                20 + $i * 88, 186 + ($i % 3) * 12);
        }
        return $s;
    }

    private static function monti(): string
    {
        return '<path d="M-20,214 L120,96 L240,214 Z"/><path d="M180,214 L340,74 L500,214 Z"/>'
            . '<path d="M420,214 L540,120 L660,214 Z"/>'
            . '<path d="M300,110 L340,74 L380,110 L340,126 Z" fill="#fff" opacity="0.7"/>';
    }

    private static function ruota(int $x): string
    {
        $s = sprintf('<g><circle cx="%d" cy="130" r="7"/>', $x);
        for ($i = 0; $i < 12; $i++) {
            $a  = $i * M_PI / 6;
            $px = $x + (int) round(cos($a) * 66);
            $py = 130 + (int) round(sin($a) * 66);
            $s .= sprintf('<rect x="%d" y="130" width="5" height="66" transform="rotate(%d %d 130)"/>',
                $x - 2, (int) round($i * 30), $x);
            $s .= sprintf('<circle cx="%d" cy="%d" r="8"/>', $px, $py);
        }
        $s .= sprintf('<rect x="%d" y="130" width="10" height="86"/></g>', $x - 5);
        return $s;
    }

    private static function panchina(int $x): string
    {
        return sprintf('<rect x="%d" y="192" width="72" height="6"/><rect x="%d" y="198" width="6" height="18"/>'
            . '<rect x="%d" y="198" width="6" height="18"/>', $x, $x + 4, $x + 62);
    }

    // --- Il tempo che faceva ------------------------------------------------------

    private static function tempo(array $meteo, string $stagione, Rng $rng): string
    {
        $s = '';
        $pioggia = (float) $meteo['pioggia'];

        if ($meteo['tifone'] || $pioggia >= 8.0) {
            for ($i = 0; $i < 150; $i++) {
                $x = $rng->int(-20, self::L);
                $y = $rng->int(0, self::H);
                $s .= sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#dbe7f0" stroke-width="1.4" opacity="0.5"/>',
                    $x, $y, $x + 9, $y + 22);
            }
        } elseif ($pioggia > 0) {
            for ($i = 0; $i < 60; $i++) {
                $x = $rng->int(0, self::L);
                $y = $rng->int(0, self::H);
                $s .= sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="#dbe7f0" stroke-width="1" opacity="0.35"/>',
                    $x, $y, $x + 4, $y + 14);
            }
        }

        if ($stagione === 'inverno' && $pioggia > 0) {
            for ($i = 0; $i < 70; $i++) {
                $s .= sprintf('<circle cx="%d" cy="%d" r="%s" fill="#fff" opacity="%.2f"/>',
                    $rng->int(0, self::L), $rng->int(0, self::H),
                    $rng->chance(0.3) ? '2.4' : '1.6', $rng->range(0.4, 0.9));
            }
        }

        // I petali di ciliegio in primavera, che è la cosa più riconoscibile
        // che abbia questo racconto.
        if ($stagione === 'primavera') {
            for ($i = 0; $i < 34; $i++) {
                $s .= sprintf('<ellipse cx="%d" cy="%d" rx="4" ry="2.5" transform="rotate(%d %d %d)" '
                    . 'fill="#ffd3e2" opacity="%.2f"/>',
                    $x = $rng->int(0, self::L), $y = $rng->int(0, self::H - 40),
                    $rng->int(0, 180), $x, $y, $rng->range(0.5, 0.95));
            }
        }
        return $s;
    }

    private static function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
