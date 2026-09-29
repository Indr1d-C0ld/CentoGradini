<?php

declare(strict_types=1);

namespace App\Game;

/**
 * Chi e' parente di chi.
 *
 * Fino al 29 settembre 2026 il gioco non lo sapeva: per il motore Kyosuke e
 * Kurumi erano due persone qualunque del quartiere. Le conseguenze si vedevano
 * nei legami, che sono tutti costruiti come legami fra estranei — il gesto che
 * «si presta a equivoci», la gelosia di chi guarda, la confessione, il secondo
 * bottone, il cappello di paglia. Fra fratelli e cugini nessuna di queste cose
 * ha il significato che il gioco le da'.
 *
 * Le famiglie sono quelle del canone, e stanno nel codice come i poteri e i
 * club degli abitanti (Abitanti::POTERI, Abitanti::CLUB): sono dati d'opera,
 * non stato di gioco, e non cambiano. Fonti in docs/CANONE.md: Kyosuke e le
 * gemelle sono fratelli; Akane e' «la sorella maggiore di Kazuya e cugina di
 * Kyosuke» (madoka.ayukawa.free.fr, koref05); Kazuya e' «il cugino» (capp. 35,
 * 39, 43, 46).
 *
 * Le parentele riguardano gli abitanti canonici. I giocatori arrivano da fuori
 * e non sono parenti di nessuno — ed e' per questo che i cognomi delle famiglie
 * del canone non si possono prendere alla creazione (vedi COGNOMI_DI_FAMIGLIA):
 * un «Kasuga Taro» sarebbe un parente che l'opera non ha, e il gioco non
 * saprebbe trattarlo da tale.
 */
final class Parentele
{
    /** [png, png, tipo]: le coppie si scrivono una volta sola, valgono nei due sensi. */
    private const FAMIGLIE = [
        ['kyosuke', 'manami', 'fratelli'],
        ['kyosuke', 'kurumi', 'fratelli'],
        ['manami',  'kurumi', 'gemelli'],
        ['akane',   'kazuya', 'fratelli'],
        ['kyosuke', 'akane',  'cugini'],
        ['kyosuke', 'kazuya', 'cugini'],
        ['manami',  'akane',  'cugini'],
        ['manami',  'kazuya', 'cugini'],
        ['kurumi',  'akane',  'cugini'],
        ['kurumi',  'kazuya', 'cugini'],
    ];

    /**
     * I cognomi delle case del canone. Chi li sceglie per il proprio
     * personaggio si dichiara parente di gente che nell'opera non ha quel
     * parente: si rifiutano alla creazione, con la spiegazione.
     */
    public const COGNOMI_DI_FAMIGLIA = ['Kasuga', 'Ayukawa', 'Hiyama'];

    /**
     * Che parentela c'e' fra due personaggi, o null se nessuna.
     *
     * @param array<string,mixed> $a
     * @param array<string,mixed> $b
     */
    public static function fra(array $a, array $b): ?string
    {
        $x = (string) ($a['png'] ?? '');
        $y = (string) ($b['png'] ?? '');
        if ($x === '' || $y === '' || $x === $y) {
            return null;
        }
        foreach (self::FAMIGLIE as [$p, $q, $tipo]) {
            if (($p === $x && $q === $y) || ($p === $y && $q === $x)) {
                return $tipo;
            }
        }
        return null;
    }

    /** @param array<string,mixed> $a @param array<string,mixed> $b */
    public static function sonoParenti(array $a, array $b): bool
    {
        return self::fra($a, $b) !== null;
    }

    /**
     * Come si chiama $altro, per $di: «sorella», «fratello gemello»,
     * «cugina».
     *
     * @param array<string,mixed> $di
     * @param array<string,mixed> $altro
     */
    public static function come(array $di, array $altro): ?string
    {
        $donna = (string) ($altro['sesso'] ?? 'm') === 'f';
        return match (self::fra($di, $altro)) {
            'fratelli' => $donna ? 'sorella' : 'fratello',
            'gemelli'  => $donna ? 'sorella gemella' : 'fratello gemello',
            'cugini'   => $donna ? 'cugina' : 'cugino',
            default    => null,
        };
    }

    /**
     * I parenti di un personaggio, con cosa sono per lui.
     *
     * @param array<string,mixed> $pg
     * @return list<array{id:int, nome:string, come:string}>
     */
    public static function di(array $pg): array
    {
        $x = (string) ($pg['png'] ?? '');
        if ($x === '') {
            return [];
        }
        $out = [];
        foreach (self::FAMIGLIE as [$p, $q, $tipo]) {
            if ($p !== $x && $q !== $x) {
                continue;
            }
            $altro = \App\Core\Database::first('SELECT * FROM personaggi WHERE png = ?', [$p === $x ? $q : $p]);
            if ($altro !== null) {
                $out[] = ['id' => (int) $altro['id'], 'nome' => (string) $altro['nome'],
                          'come' => (string) self::come($pg, $altro)];
            }
        }
        return $out;
    }

    /** Il cognome scelto alla creazione e' quello di una famiglia del canone? */
    public static function cognomeDiFamiglia(string $cognome): ?string
    {
        foreach (self::COGNOMI_DI_FAMIGLIA as $c) {
            if (mb_strtolower(trim($cognome)) === mb_strtolower($c)) {
                return $c;
            }
        }
        return null;
    }
}
