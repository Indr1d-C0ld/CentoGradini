<?php

declare(strict_types=1);

namespace App\Sim;

use App\Core\Database;

/**
 * I luoghi del quartiere e i collegamenti fra loro.
 *
 * La tabella e' piccola e non cambia mai durante una partita, quindi si carica
 * una volta sola per richiesta e si tiene in memoria. Tutte le domande che il
 * gioco fa — e' aperto? ci arrivo da qui? quanto ci metto? — si rispondono
 * senza toccare il database.
 */
final class Luoghi
{
    /** @var array<string,array<string,mixed>>|null */
    private static ?array $cache = null;
    /** @var array<string,list<array{a:string,minuti:int,mezzo:string}>>|null */
    private static ?array $archi = null;

    public static function dimentica(): void
    {
        self::$cache = null;
        self::$archi = null;
    }

    /** @return array<string,array<string,mixed>> */
    public static function tutti(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $out = [];
        foreach (Database::all('SELECT * FROM luoghi ORDER BY ordine, lkey') as $r) {
            $r['x']       = (int) $r['x'];
            $r['y']       = (int) $r['y'];
            $r['apre']    = $r['apre']   === null ? null : (int) $r['apre'];
            $r['chiude']  = $r['chiude'] === null ? null : (int) $r['chiude'];
            $r['privato'] = (bool) $r['privato'];
            $r['fuori']   = (bool) $r['fuori'];
            $out[(string) $r['lkey']] = $r;
        }
        return self::$cache = $out;
    }

    /** @return array<string,mixed>|null */
    public static function uno(string $lkey): ?array
    {
        return self::tutti()[$lkey] ?? null;
    }

    public static function esiste(string $lkey): bool
    {
        return isset(self::tutti()[$lkey]);
    }

    public static function nome(string $lkey): string
    {
        return (string) (self::uno($lkey)['nome'] ?? $lkey);
    }

    /** @return array<string,list<array{a:string,minuti:int,mezzo:string}>> */
    public static function archi(): array
    {
        if (self::$archi !== null) {
            return self::$archi;
        }
        $out = [];
        foreach (Database::all('SELECT da, a, minuti, mezzo FROM luogo_archi') as $r) {
            $out[(string) $r['da']][] = [
                'a'      => (string) $r['a'],
                'minuti' => (int) $r['minuti'],
                'mezzo'  => (string) $r['mezzo'],
            ];
        }
        foreach ($out as &$lista) {
            usort($lista, static fn (array $x, array $y): int => $x['minuti'] <=> $y['minuti']);
        }
        return self::$archi = $out;
    }

    /**
     * Da dove si trova, dove puo' andare in un passo.
     *
     * @return list<array{a:string,minuti:int,mezzo:string,luogo:array<string,mixed>,aperto:bool,motivo:?string}>
     */
    public static function uscite(string $da, ?int $gts = null): array
    {
        $gts ??= Orologio::gts();
        $out = [];
        foreach (self::archi()[$da] ?? [] as $arco) {
            $luogo = self::uno($arco['a']);
            if ($luogo === null) {
                continue;
            }
            // Si valuta l'apertura all'ORA D'ARRIVO, non a quella di partenza:
            // partire alle 21:55 per un posto che chiude alle 22:00 e trovarlo
            // chiuso all'arrivo e' esattamente cio' che deve succedere, ma il
            // giocatore ha diritto di saperlo prima di incamminarsi.
            $allArrivo = Orologio::avvolgi($gts + $arco['minuti'] * 60);
            [$aperto, $motivo] = self::accessibile($arco['a'], $allArrivo);
            $out[] = $arco + ['luogo' => $luogo, 'aperto' => $aperto, 'motivo' => $motivo];
        }
        return $out;
    }

    /** Minuti di gioco per andare da un luogo all'altro in un passo, o null. */
    public static function minuti(string $da, string $a): ?int
    {
        foreach (self::archi()[$da] ?? [] as $arco) {
            if ($arco['a'] === $a) {
                return $arco['minuti'];
            }
        }
        return null;
    }

    /** @return array{0:bool,1:?string} aperto, e se no il perche' */
    public static function accessibile(string $lkey, ?int $gts = null): array
    {
        $l = self::uno($lkey);
        if ($l === null) {
            return [false, 'Questo posto non esiste.'];
        }
        $gts ??= Orologio::gts();
        $cal = Calendario::stato($gts);

        if ($l['stagione'] !== null && $cal['stagione'] !== $l['stagione']) {
            return [false, 'Ci si va solo d\'' . $l['stagione'] . '.'];
        }

        // La scuola non e' "aperta": ci si va quando c'e' scuola. Nei giorni di
        // vacanza restano solo i club, e non tutti i giorni.
        if ($l['tipo'] === 'scuola' && !$cal['scuola'] && $cal['vacanza'] !== null) {
            return [false, 'È chiusa: siamo in ' . $cal['vacanza'] . '.'];
        }

        if ($l['apre'] !== null && $l['chiude'] !== null) {
            $m = $cal['ora'] * 60 + $cal['minuto'];
            $dentro = $l['apre'] <= $l['chiude']
                ? ($m >= $l['apre'] && $m < $l['chiude'])
                : ($m >= $l['apre'] || $m < $l['chiude']);   // a cavallo della mezzanotte
            if (!$dentro) {
                return [false, sprintf('A quest\'ora è chiuso (%s–%s).',
                    self::orario($l['apre']), self::orario($l['chiude']))];
            }
        }

        return [true, null];
    }

    /**
     * Cosa si sta guardando da qui, adesso — o stringa vuota.
     *
     * Serve l'orientamento (colonna `guarda`) più l'ora. Un luogo che si apre
     * a ovest, al tramonto, ha il sole dentro la veduta; lo stesso luogo a
     * mezzogiorno ha solo la veduta. Una stanza chiusa non ha né l'una né
     * l'altro, e infatti non dice niente.
     *
     * Viene dalla ricostruzione del grande escalier (CANONE §7-ter): dal
     * fatto che nell'episodio 42 il sole tramonta sulla destra di chi guarda
     * giù dai gradini si deduce che la scalinata è orientata nord-sud, e che
     * dalla cima si guarda la città verso ovest. È l'unico pezzo di quella
     * ricostruzione che poteva diventare una meccanica invece di una nota, e
     * allora è diventato una meccanica.
     */
    public static function veduta(string $lkey, ?int $gts = null): string
    {
        $l = Luoghi::uno($lkey);
        if ($l === null) {
            return '';
        }
        $che = trim((string) ($l['veduta'] ?? ''));
        if ($che === '') {
            return '';
        }

        // La veduta e' una **frase intera**, e l'ora ne aggiunge un'altra
        // accanto. Il primo tentativo incollava un prefisso davanti a un
        // sintagma — «Da qui si vede» + «i tetti bassi» — e produceva «si
        // vede i tetti», che non e' italiano: il verbo non puo' accordarsi
        // con un pezzo di frase che non conosce. Due frasi separate non
        // hanno questo problema, e si leggono anche meglio.
        $dove = (string) ($l['guarda'] ?? '');
        $ora  = (int) Orologio::data($gts ?? Orologio::lineare())->format('G');

        $quando = match (true) {
            $dove === 'ovest' && $ora >= 17 && $ora < 20 => ' Il sole sta calando proprio lì in fondo.',
            $dove === 'est'   && $ora >= 5  && $ora < 8  => ' Il sole sta salendo proprio da lì.',
            $ora < 5 || $ora >= 20                       => ' Adesso, al buio, si intuisce appena.',
            default                                       => '',
        };
        return $che . $quando;
    }

    public static function orario(int $minuti): string
    {
        return sprintf('%d:%02d', intdiv($minuti, 60) % 24, $minuti % 60);
    }

    /**
     * Percorso piu' breve in minuti, con Dijkstra. Serve per due cose: dire
     * «sono venti minuti» prima di partire, e piu' avanti far muovere i
     * personaggi non giocanti verso una meta.
     *
     * @return array{minuti:int,passi:list<string>}|null
     */
    public static function percorso(string $da, string $a): ?array
    {
        if ($da === $a) {
            return ['minuti' => 0, 'passi' => [$da]];
        }
        $dist = [$da => 0];
        $prev = [];
        $coda = [$da => 0];

        while ($coda !== []) {
            asort($coda);
            $u = (string) array_key_first($coda);
            $du = $coda[$u];
            unset($coda[$u]);

            if ($u === $a) {
                break;
            }
            foreach (self::archi()[$u] ?? [] as $arco) {
                $v  = $arco['a'];
                $nd = $du + $arco['minuti'];
                if (!isset($dist[$v]) || $nd < $dist[$v]) {
                    $dist[$v] = $nd;
                    $prev[$v] = $u;
                    $coda[$v] = $nd;
                }
            }
        }

        if (!isset($dist[$a])) {
            return null;
        }
        $passi = [$a];
        for ($n = $a; isset($prev[$n]); $n = $prev[$n]) {
            array_unshift($passi, $prev[$n]);
        }
        return ['minuti' => $dist[$a], 'passi' => $passi];
    }

    /**
     * Il grafo e' tutto raggiungibile? Se un luogo resta isolato, un giocatore
     * che ci finisce non ne esce piu'. Lo verifica la prova unitaria.
     *
     * @return list<string> i luoghi non raggiungibili dalla scalinata
     */
    public static function isolati(string $da = 'gradini'): array
    {
        $visti = [$da => true];
        $coda  = [$da];
        while ($coda !== []) {
            $u = array_shift($coda);
            foreach (self::archi()[$u] ?? [] as $arco) {
                if (!isset($visti[$arco['a']])) {
                    $visti[$arco['a']] = true;
                    $coda[] = $arco['a'];
                }
            }
        }
        return array_values(array_diff(array_keys(self::tutti()), array_keys($visti)));
    }
}
