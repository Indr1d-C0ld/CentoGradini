<?php

declare(strict_types=1);

namespace App\Game;

use App\Core\Database;
use App\Core\GameConfig;
use App\Sim\Orologio;

/**
 * Il riposo: le forze che tornano.
 *
 * Punti potere, punti ferita e compostezza scendevano e non risalivano mai —
 * nessuna riga del motore li restituiva — e un personaggio che aveva vissuto
 * una settimana di quartiere si ritrovava senza poter più coprire un
 * incidente, chiarire un malinteso o dichiararsi. Il regolamento del 1990 il
 * recupero lo aveva, e qui si fa com'era scritto:
 *
 *   «I PP si recuperano al ritmo di 1 ogni mezz'ora di riposo, 1 ogni ora di
 *    stress lieve [...] Una notte intera di sonno rigenera tutti i PP.»
 *   «Ogni due ore si acquisisce un ulteriore PF. Per guarire completamente
 *    tutti i punti ferita serve una notte di sonno.»
 *
 * La compostezza nel 1990 non c'era — è la nostra riscrittura dell'emorragia
 * nasale — e segue la stessa logica: torna piano durante il giorno, e tutta
 * con una notte di sonno. Chi ha passato una giornata d'inferno la mattina
 * dopo è di nuovo in piedi, che è esattamente quello che succede a Kyosuke
 * all'inizio di ogni capitolo.
 *
 * **Senza stato da far avanzare passo passo.** `ristoro_gts` dice fin dove il
 * recupero è già stato contato; il battito conta quanti confini d'ora e
 * quante albe ci sono fra lì e adesso. Un battito saltato non fa perdere
 * niente, uno doppio non regala niente, e un giocatore che torna dopo tre
 * giorni trova il personaggio riposato come deve.
 */
final class Riposo
{
    /** Lo chiama il battito. @return int quanti personaggi hanno recuperato qualcosa */
    public static function giro(?int $adesso = null): int
    {
        $adesso ??= Orologio::lineare();
        $n = 0;
        foreach (Database::all(
            "SELECT id, ristoro_gts, pp, pp_max, pf, pf_max, compostezza, compostezza_max
             FROM personaggi WHERE stato = 'attivo' AND scheda = 'completa'"
        ) as $pg) {
            if (self::ristora($pg, $adesso)) {
                $n++;
            }
        }
        return $n;
    }

    /**
     * Quanto recupera un personaggio fra l'ultimo conto e adesso, e lo scrive.
     *
     * @param array<string,mixed> $pg
     * @return bool se qualcosa è cambiato
     */
    public static function ristora(array $pg, int $adesso): bool
    {
        $da = $pg['ristoro_gts'] === null ? null : (int) $pg['ristoro_gts'];
        if ($da === null || $da > $adesso) {
            // Primo conto, o un orologio tornato indietro: si comincia da qui,
            // senza regalare il tempo che non si è visto passare.
            Database::run('UPDATE personaggi SET ristoro_gts = ? WHERE id = ?', [$adesso, (int) $pg['id']]);
            return false;
        }
        $quanto = self::quanto($da, $adesso, self::haUsatoPoteri((int) $pg['id'], $adesso));
        // Aggiornamenti RELATIVI, calcolati dal database nel momento in cui
        // scrive: prima si scrivevano valori assoluti letti all'inizio del giro,
        // e una spesa fatta nel frattempo da una richiesta web veniva
        // cancellata (punti restituiti due volte). E solo se nessun altro ha
        // gia' contato lo stesso tratto di tempo (ristoro_gts = quello letto):
        // due battiti sovrapposti non regalano niente. Mai verso il basso: se
        // un valore e' sopra il massimo, lo si lascia dov'e'.
        $tutto = $quanto['notte'] ? 1000 : 0;
        $n = Database::run(
            'UPDATE personaggi SET
                pp = GREATEST(pp, LEAST(pp_max, pp + ?)),
                pf = GREATEST(pf, LEAST(pf_max, pf + ?)),
                compostezza = GREATEST(compostezza, LEAST(compostezza_max, compostezza + ?)),
                ristoro_gts = ?
             WHERE id = ? AND ristoro_gts = ?',
            [$tutto + $quanto['pp'], $tutto + $quanto['pf'], $tutto + $quanto['compostezza'],
             $adesso, (int) $pg['id'], $da]
        );
        return $n->rowCount() === 1 && ($tutto > 0 || $quanto['pp'] + $quanto['pf'] + $quanto['compostezza'] > 0);
    }

    /**
     * Quanto si recupera fra due istanti, senza toccare il database.
     *
     * L'«ora di stress» del regolamento è quella in cui si è usato un potere:
     * in quella non si recuperano punti potere, nelle altre sì.
     *
     * @return array{notte:bool, pp:int, pf:int, compostezza:int}
     */
    public static function quanto(int $da, int $a, bool $sottoStress = false): array
    {
        if ($a <= $da) {
            return ['notte' => false, 'pp' => 0, 'pf' => 0, 'compostezza' => 0];
        }
        $confini = static function (int $ore) use ($da, $a): int {
            $passo = max(1, $ore) * 3600;
            return intdiv($a, $passo) - intdiv($da, $passo);
        };
        $pp = $confini(GameConfig::int('riposo.pp_ore', 1));
        if ($sottoStress && $pp > 0) {
            $pp--;
        }
        return [
            'notte'       => self::albe($da, $a) > 0,
            'pp'          => $pp,
            'pf'          => $confini(GameConfig::int('riposo.pf_ore', 2)),
            'compostezza' => $confini(GameConfig::int('riposo.compostezza_ore', 3)),
        ];
    }

    /**
     * Quante albe ci sono fra due istanti (il primo escluso, il secondo
     * compreso). L'alba è l'ora `riposo.alba_ora` di Tokyo, che non ha ora
     * legale: lo scarto è fisso, e il conto si fa con una divisione.
     */
    public static function albe(int $da, int $a): int
    {
        $alba   = max(0, min(23, GameConfig::int('riposo.alba_ora', 6)));
        $scarto = Orologio::fuso()->getOffset(new \DateTimeImmutable('@' . $a)) - $alba * 3600;
        return intdiv($a + $scarto, 86400) - intdiv($da + $scarto, 86400);
    }

    private static function haUsatoPoteri(int $pgId, int $adesso): bool
    {
        return Database::first(
            'SELECT 1 x FROM incidenti WHERE attore_id = ? AND gts > ? LIMIT 1',
            [$pgId, $adesso - 3600]
        ) !== null;
    }
}
