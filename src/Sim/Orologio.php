<?php

declare(strict_types=1);

namespace App\Sim;

use App\Core\Config;
use App\Core\GameConfig;
use DateTimeImmutable;
use DateTimeZone;

/**
 * L'orologio dell'«eterno 1987».
 *
 * Due idee, e nient'altro.
 *
 * 1. **Compressione.** Il tempo di gioco scorre piu' in fretta di quello vero,
 *    per difetto quattro volte tanto. Un giorno reale vale quattro giorni di
 *    gioco, e l'intera giornata di gioco passa in sei ore reali: chi entra
 *    sempre alla stessa ora della sera non trova sempre la stessa ora della
 *    scuola, che e' tutto il punto.
 *
 * 2. **Ciclo.** Il calendario gira dentro l'anno scolastico giapponese
 *    1987-88: si apre il 6 aprile 1987 con la cerimonia d'ingresso, arriva ai
 *    diplomi di marzo, e ricomincia da capo. L'anno scolastico attraversa il
 *    capodanno, quindi da gennaio in poi le date dicono 1988 — ed e' giusto
 *    cosi': nell'episodio 38 il Nonno scrive «Merry Xmas 1987» nel cielo, e la
 *    serie finisce nel marzo 1988. Un giro completo dura 366 giorni di gioco
 *    (il 1988 e' bisestile, e il 29 febbraio 1988 e' davvero andato in onda
 *    l'episodio 47), cioe' poco piu' di tre mesi reali.
 *
 * Tutto quello che c'e' qui dentro e' una funzione pura di (istante reale,
 * epoca, compressione). Niente stato salvato, niente avanzamento da ricordare:
 * il tick e la richiesta del giocatore chiedono l'ora e ottengono la stessa
 * risposta. E' la stessa scelta fatta per il meteo, e per lo stesso motivo.
 *
 * **I due orologi.** `lineare()` cresce all'infinito ed e' quello che si salva
 * a database: cosi' «sono passate tre ore» resta vero anche a cavallo del
 * capodanno del ciclo, e un arrivo previsto non finisce mai nel passato.
 * `gts()` e' lo stesso istante avvolto dentro il ciclo, ed e' quello che si
 * mostra e su cui si calcolano calendario e meteo. Regola pratica: **nel
 * database solo istanti lineari, a schermo solo istanti avvolti.**
 *
 * L'ora di gioco e' **sempre** ora di Tokyo. Il Giappone non ha ora legale,
 * quindi JST e' +09:00 tutto l'anno e non ci sono salti da gestire. L'ora
 * reale e' un'altra cosa e non entra mai qui dentro se non come istante Unix,
 * che e' monotono e quindi immune all'ora legale italiana.
 */
final class Orologio
{
    /** Primo giorno del ciclo: cerimonia d'ingresso, lunedi' 6 aprile 1987. */
    public const CICLO_INIZIO = '1987-04-06 00:00:00';

    /** Primo istante FUORI dal ciclo: da qui si riparte dall'inizio. */
    public const CICLO_FINE = '1988-04-06 00:00:00';

    /** Istante reale imposto dalle prove. Null = si usa l'orologio di sistema. */
    private static ?int $adessoFinto = null;

    /** Epoca reale imposta dalle prove. Null = si legge da game_config. */
    private static ?int $epocaFinta = null;

    public static function fuso(): DateTimeZone
    {
        static $tz = null;
        return $tz ??= new DateTimeZone('Asia/Tokyo');
    }

    /** Solo per le prove: congela l'istante reale. */
    public static function fingiAdesso(?int $tsReale): void
    {
        self::$adessoFinto = $tsReale;
    }

    /**
     * Solo per le prove: impone l'epoca invece di leggerla da game_config.
     *
     * Serve perche' l'epoca vera e' l'istante in cui e' stato creato lo schema,
     * cioe' un valore diverso su ogni installazione: senza questo, una prova
     * che fissa l'ora finta darebbe risultati diversi a seconda di quando e'
     * stato installato il gioco.
     */
    public static function fingiEpoca(?int $tsReale): void
    {
        self::$epocaFinta = $tsReale;
    }

    public static function adessoReale(): int
    {
        return self::$adessoFinto ?? time();
    }

    // --- Parametri ------------------------------------------------------------

    public static function compressione(): int
    {
        return max(1, GameConfig::int('clock.compression', 4));
    }

    /**
     * Istante reale (Unix) a cui corrisponde l'inizio dell'epoca di gioco.
     *
     * Lo fissa la migrazione 0003 quando lo schema viene creato, e la 0004 lo
     * normalizza a **istante Unix**. Il formato conta: finche' era una data
     * scritta in chiaro senza fuso, `strtotime()` la interpretava col fuso di
     * default del processo, e processi diversi leggevano mondi diversi — due
     * ore di scarto fra Roma e UTC diventano otto ore di gioco. Un numero non
     * ha fuso.
     *
     * La forma testuale si accetta ancora, per le installazioni piu' vecchie e
     * per chi la scrive a mano, ma la si interpreta **esplicitamente** nel fuso
     * dell'applicazione invece di affidarsi all'ambiente.
     *
     * Se manca del tutto si ricade sull'inizio dell'epoca di gioco: il mondo
     * parte dal primo giorno di scuola invece di piantarsi.
     */
    public static function epocaReale(): int
    {
        if (self::$epocaFinta !== null) {
            return self::$epocaFinta;
        }
        $v = trim((string) GameConfig::get('clock.epoch_real', ''));
        if ($v === '') {
            return self::epocaGioco();
        }
        if (ctype_digit($v)) {
            return (int) $v;
        }
        try {
            $tz = new DateTimeZone((string) Config::get('app.timezone', 'Europe/Rome'));
            return (new DateTimeImmutable($v, $tz))->getTimestamp();
        } catch (\Throwable) {
            return self::epocaGioco();
        }
    }

    /** Istante di gioco (Unix, riferito all'ora di Tokyo) a cui parte il mondo. */
    public static function epocaGioco(): int
    {
        $v = trim((string) GameConfig::get('clock.epoch_game', '1987-04-06 07:00:00'));
        return (new DateTimeImmutable($v, self::fuso()))->getTimestamp();
    }

    public static function cicloInizio(): int
    {
        static $t = null;
        return $t ??= (new DateTimeImmutable(self::CICLO_INIZIO, self::fuso()))->getTimestamp();
    }

    /** Durata di un giro completo, in secondi di gioco. */
    public static function cicloDurata(): int
    {
        static $d = null;
        return $d ??= (new DateTimeImmutable(self::CICLO_FINE, self::fuso()))->getTimestamp()
            - self::cicloInizio();
    }

    // --- Conversioni -----------------------------------------------------------

    /**
     * Istante di gioco corrispondente a un istante reale, **senza** avvolgerlo
     * nel ciclo: cresce all'infinito. Serve per contare i giri.
     */
    public static function lineare(?int $tsReale = null): int
    {
        $tsReale ??= self::adessoReale();
        return self::epocaGioco()
            + (int) floor(($tsReale - self::epocaReale()) * self::compressione());
    }

    /** Istante di gioco, avvolto nel ciclo: e' l'ora che si legge in gioco. */
    public static function gts(?int $tsReale = null): int
    {
        return self::avvolgi(self::lineare($tsReale));
    }

    /**
     * Quante volte il calendario ha gia' fatto il giro. Il primo anno e' 0.
     *
     * Non e' un dettaglio contabile: e' cio' che permette al mondo di sapere
     * che questa e' la terza volta che vedi fiorire i ciliegi, e di dirlo.
     */
    public static function giro(?int $tsReale = null): int
    {
        $delta = self::lineare($tsReale) - self::cicloInizio();
        // Mai negativo: un istante precedente alla nascita del mondo non e'
        // «l'anno prima», e' semplicemente l'inizio. Succede davvero — basta
        // chiedere l'ora con un orologio di sistema indietro di qualche ora —
        // e senza questo si leggerebbe «il 0° anno».
        return max(0, (int) floor($delta / self::cicloDurata()));
    }

    /**
     * Riporta un istante lineare dentro il ciclo.
     *
     * Il modulo di PHP tiene il segno del dividendo, quindi per un istante
     * precedente all'inizio del ciclo darebbe un valore negativo e una data
     * nel 1986. Qui si usa il modulo «a pavimento», che e' quello giusto per
     * un calendario che gira.
     */
    public static function avvolgi(int $gtsLineare): int
    {
        $durata = self::cicloDurata();
        $delta  = $gtsLineare - self::cicloInizio();
        $resto  = $delta % $durata;
        if ($resto < 0) {
            $resto += $durata;
        }
        return self::cicloInizio() + $resto;
    }

    /**
     * Istante di gioco come data e ora di Tokyo.
     *
     * Avvolge sempre l'argomento nel ciclo, quindi accetta indifferentemente un
     * istante lineare (quelli salvati a database) o uno gia' avvolto: in
     * entrambi i casi restituisce la data che si legge in gioco. L'operazione
     * e' idempotente, quindi avvolgere due volte non fa danni.
     */
    public static function data(?int $gts = null): DateTimeImmutable
    {
        $gts = $gts === null ? self::gts() : self::avvolgi($gts);
        return (new DateTimeImmutable('@' . $gts))->setTimezone(self::fuso());
    }

    /**
     * Quanto tempo reale manca perche' l'orologio di gioco arrivi a $gtsTarget.
     * Negativo se e' gia' passato in questo giro.
     */
    public static function realiPerArrivare(int $gtsTarget, ?int $tsReale = null): int
    {
        $ora = self::gts($tsReale);
        $d = $gtsTarget - $ora;
        if ($d < 0) {
            $d += self::cicloDurata();
        }
        return (int) ceil($d / self::compressione());
    }

    // --- Formattazione -----------------------------------------------------------

    private const GIORNI = ['lunedì', 'martedì', 'mercoledì', 'giovedì', 'venerdì', 'sabato', 'domenica'];
    private const MESI = ['', 'gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno',
                          'luglio', 'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre'];

    /** «lunedì 6 aprile 1987, 7:00» */
    public static function esteso(?int $gts = null): string
    {
        $d = self::data($gts);
        return sprintf(
            '%s %d %s %d, %d:%02d',
            self::GIORNI[(int) $d->format('N') - 1],
            (int) $d->format('j'),
            self::MESI[(int) $d->format('n')],
            (int) $d->format('Y'),
            (int) $d->format('G'),
            (int) $d->format('i')
        );
    }

    /** «6 aprile, 7:00» — per le intestazioni, dove l'anno e' scontato. */
    public static function breve(?int $gts = null): string
    {
        $d = self::data($gts);
        return sprintf(
            '%d %s, %d:%02d',
            (int) $d->format('j'),
            self::MESI[(int) $d->format('n')],
            (int) $d->format('G'),
            (int) $d->format('i')
        );
    }

    public static function giornoSettimana(?int $gts = null): string
    {
        return self::GIORNI[(int) self::data($gts)->format('N') - 1];
    }

    public static function nomeMese(int $n): string
    {
        return self::MESI[max(1, min(12, $n))];
    }
}
