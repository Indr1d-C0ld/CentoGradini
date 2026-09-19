<?php

declare(strict_types=1);

namespace App\Game;

use App\Core\Database;
use App\Sim\Calendario;
use App\Sim\Orologio;

/**
 * Gli eventi stagionali del server.
 *
 * Non sono episodi e non sono eventi di giocatore: sono del **mondo**, e
 * capitano a tutti insieme perché il calendario è lo stesso per tutti. È il
 * pezzo che manca a un mondo persistente asincrono: senza, ognuno gioca in
 * una bolla e non esiste un «ti ricordi quella volta al festival».
 *
 * Come il meteo, sono una **funzione pura dell'istante**: non c'è uno stato
 * da far avanzare, non c'è una riga da scrivere quando l'evento comincia, e
 * due richieste ravvicinate vedono lo stesso quartiere. La tabella `eventi`
 * contiene il calendario, non lo svolgimento.
 *
 * Toccano due cose sole, e sono quelle giuste:
 *
 *   RICHIAMO     alza la Folla nel luogo dell'evento, e quindi il numero di
 *                testimoni e il calore. Usare un potere al festival d'estate
 *                è la cosa più imprudente dell'anno, e non perché una regola
 *                lo vieti: perché c'è tutto il quartiere.
 *   CHIACCHIERA  accelera la propagazione delle voci. Le feste e i lutti
 *                radunano la stessa gente e la fanno parlare in modo molto
 *                diverso.
 */
final class Eventi
{
    /** @return list<array<string,mixed>> il calendario completo */
    public static function calendario(): array
    {
        return Database::all('SELECT * FROM eventi ORDER BY ordine, mese, giorno');
    }

    /**
     * Gli eventi in corso adesso.
     *
     * La finestra si calcola sul calendario avvolto, perché un evento che
     * comincia il 24 dicembre e dura due giorni deve valere anche il 25 —
     * e a cavallo del 5 aprile, che è il giorno in cui l'anno ricomincia,
     * deve smettere di valere invece di scavallare nel 1987 di nuovo.
     *
     * @return list<array<string,mixed>>
     */
    public static function inCorso(?int $gts = null): array
    {
        $c     = Calendario::stato($gts);
        $oggi  = $c['data'];
        $out   = [];

        foreach (self::calendario() as $e) {
            $giorni = self::giorniDa($e, $oggi);
            if ($giorni === null) {
                continue;
            }
            $e['giorno_di']  = $giorni + 1;
            $e['su_giorni']  = (int) $e['durata'];
            $e['ultimo']     = $giorni + 1 === (int) $e['durata'];
            $out[] = $e;
        }
        return $out;
    }

    /**
     * Da quanti giorni è cominciato questo evento, o null se non è in corso.
     *
     * Si confronta con l'anno della data di gioco, non con quello reale.
     */
    private static function giorniDa(array $e, \DateTimeImmutable $oggi): ?int
    {
        // NIENTE DateTime::diff() qui. Con il formato '%r%a' una differenza
        // di mezza giornata diventa «-0», che castato a intero e' 0: un
        // evento del 5 agosto risultava in corso il 4. Si contano i giorni
        // come interi, che e' l'unica aritmetica che non mente.
        $meta = self::giornoIntero($oggi);
        $anno = (int) $oggi->format('Y');
        foreach ([$anno, $anno - 1] as $a) {
            $inizio = self::giornoIntero($oggi->setDate($a, (int) $e['mese'], (int) $e['giorno']));
            $diff   = $meta - $inizio;
            if ($diff >= 0 && $diff < max(1, (int) $e['durata'])) {
                return $diff;
            }
        }
        return null;
    }

    /** Il giorno come numero intero, per contarli senza sorprese. */
    private static function giornoIntero(\DateTimeImmutable $d): int
    {
        return (int) floor($d->setTime(12, 0)->getTimestamp() / 86400);
    }

    /** Il prossimo evento che arriva, con quanti giorni mancano. */
    public static function prossimo(?int $gts = null): ?array
    {
        $c     = Calendario::stato($gts);
        $oggi  = $c['data'];
        $meta  = self::giornoIntero($oggi);
        $anno  = (int) $oggi->format('Y');
        $best  = null;

        foreach (self::calendario() as $e) {
            foreach ([$anno, $anno + 1] as $a) {
                $giorni = self::giornoIntero($oggi->setDate($a, (int) $e['mese'], (int) $e['giorno'])) - $meta;
                if ($giorni <= 0) {
                    continue;
                }
                if ($best === null || $giorni < $best['fra_giorni']) {
                    $e['fra_giorni'] = $giorni;
                    $best = $e;
                }
            }
        }
        return $best;
    }

    /**
     * Quanta gente in più tira un evento in questo luogo, 0-100.
     * La usa Folla: un evento non inventa una meccanica nuova, alza un numero
     * che c'era già.
     */
    public static function richiamo(string $lkey, ?int $gts = null): int
    {
        $max = 0;
        foreach (self::inCorso($gts) as $e) {
            $luogo = (string) $e['luogo'];
            if ($luogo !== '' && $luogo !== $lkey) {
                continue;
            }
            // Un evento senza luogo tocca tutto il quartiere, ma più piano.
            $q = $luogo === '' ? (int) round((int) $e['richiamo'] * 0.4) : (int) $e['richiamo'];
            $max = max($max, $q);
        }
        return $max;
    }

    /**
     * Il moltiplicatore sulla propagazione delle voci, adesso.
     * 1.0 = giornata qualunque; 2.0 = San Valentino.
     */
    public static function chiacchiera(?int $gts = null): float
    {
        $max = 0;
        foreach (self::inCorso($gts) as $e) {
            $max = max($max, (int) $e['chiacchiera']);
        }
        return 1.0 + $max / 100.0;
    }
}
