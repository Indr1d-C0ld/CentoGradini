<?php

declare(strict_types=1);

namespace App\Sim;

use DateTimeImmutable;

/**
 * Il Kōryō Gakuen, e come funziona una scuola giapponese.
 *
 * **Correzione rispetto a F1.** Avevo modellato un «liceo» con tre anni. È
 * sbagliato: 高陵学園 è un *gakuen*, un istituto che tiene insieme le medie
 * (中等部, tre anni) e le superiori (高等部, altri tre). All'inizio della
 * storia Kyosuke, Madoka e Hikaru non sono al liceo — sono al **terzo anno
 * delle medie**, e passano alle superiori nell'aprile 1985. Nel nostro anno
 * congelato, il 1987-88, sono rispettivamente al terzo anno delle superiori
 * (Kyosuke e Madoka) e al primo (Hikaru, con Manami e Kurumi due anni sotto).
 *
 * **L'età non si sceglie: si deduce.** In Giappone la classe è determinata
 * rigidamente dalla data di nascita — un anno scolastico raccoglie tutti i
 * nati fra il 2 aprile e il 1º aprile successivo. Quindi non si dichiara
 * «ho sedici anni»: si dichiara *quando si è nati*, e la classe fa il resto.
 * Il compleanno diventa così un dato di gioco vero, che cade in una data
 * precisa del calendario e che il quartiere può ricordare — e in
 * Kimagure Orange Road i compleanni contano: Kyosuke e Hikaru sono nati lo
 * stesso giorno, il 15 novembre.
 *
 * Verifica del modello sui tre protagonisti, con le date del canone:
 *
 *   Kyosuke  15/11/1969  ->  coorte 1969  ->  3ª superiore nel 1987-88   ✓
 *   Madoka   25/05/1969  ->  coorte 1969  ->  3ª superiore nel 1987-88   ✓
 *   Hikaru   15/11/1971  ->  coorte 1971  ->  1ª superiore nel 1987-88   ✓
 *
 * Il modello riproduce il canone senza aggiustamenti: è il segno che la
 * regola delle coorti è quella giusta.
 */
final class Scuola
{
    public const MEDIE     = 'medie';
    public const SUPERIORI = 'superiori';
    // Fuori dalle classi giocabili, ma dentro il quartiere: i bambini delle
    // elementari (Kazuya ha otto anni) e gli adulti (il Master dell'ABCB).
    // Non si possono scegliere alla creazione — lo dice GIOCABILI — ma
    // esistono, e il modello deve saperli nominare senza inventarsi una
    // classe che non hanno.
    public const ELEMENTARI = 'elementari';
    public const ADULTI     = 'adulti';

    /** Anno scolastico contenitore: comincia nell'aprile di quest'anno. */
    public const ANNO_COORTE_BASE = 1987;

    /**
     * Classi che un giocatore può scegliere.
     *
     * Si parte dalla terza media — la classe in cui comincia la storia — e si
     * arriva alla terza superiore. Le prime due medie esistono nel mondo (ci
     * sono ragazzini in giro) ma non si giocano: a dodici anni, in una storia
     * che parla di questo, non c'è niente da fare.
     *
     * @var list<array{0:string,1:int}>
     */
    public const GIOCABILI = [
        [self::MEDIE, 3],
        [self::SUPERIORI, 1],
        [self::SUPERIORI, 2],
        [self::SUPERIORI, 3],
    ];

    public static function giocabile(string $sezione, int $anno): bool
    {
        return in_array([$sezione, $anno], self::GIOCABILI, true);
    }

    /** «3ª media», «1ª superiore», «2ª elementare» — e per gli adulti, niente. */
    public static function nomeClasse(string $sezione, int $anno): string
    {
        return match ($sezione) {
            self::ADULTI     => 'fuori dalla scuola',
            self::ELEMENTARI => $anno . 'ª elementare',
            self::MEDIE      => $anno . 'ª media',
            default          => $anno . 'ª superiore',
        };
    }

    /** Come lo direbbero loro: 小2, 中3, 高1. */
    public static function siglaClasse(string $sezione, int $anno): string
    {
        return match ($sezione) {
            self::ADULTI     => '—',
            self::ELEMENTARI => '小' . $anno,
            self::MEDIE      => '中' . $anno,
            default          => '高' . $anno,
        };
    }

    /**
     * Quanti anni di scuola separano due classi. Positivo se la prima è
     * più avanti: serve per i «senpai» e i «kōhai», che in Giappone non
     * sono un dettaglio di cortesia ma una struttura sociale.
     */
    public static function distanza(string $sezA, int $annoA, string $sezB, int $annoB): int
    {
        return self::assoluto($sezA, $annoA) - self::assoluto($sezB, $annoB);
    }

    /**
     * Posizione assoluta nella scala scolastica: -5..0 le elementari,
     * 1..3 le medie, 4..6 le superiori. La scala e' continua apposta, cosi'
     * distanza() funziona anche fra un bambino e un maturando.
     */
    public static function assoluto(string $sezione, int $anno): int
    {
        return match ($sezione) {
            self::ELEMENTARI => $anno - 6,
            self::MEDIE      => $anno,
            default          => $anno + 3,
        };
    }

    /**
     * Anno di nascita di chi frequenta questa classe nell'anno scolastico
     * contenitore.
     *
     * Chi è nato dal 2 aprile in poi appartiene alla coorte dell'anno in cui
     * è nato; chi è nato fra il 1º gennaio e il 1º aprile appartiene alla
     * coorte dell'anno precedente — è il celebre «早生まれ», il nato presto,
     * che si ritrova in classe con chi ha quasi un anno in meno.
     */
    public static function annoDiNascita(string $sezione, int $anno, int $mese, int $giorno): int
    {
        // Chi è in 3ª superiore (assoluto 6) nell'anno scolastico 1987-88 ha
        // aperto la propria coorte nell'aprile 1969: diciotto anni prima.
        // Ogni classe più indietro è una coorte più recente, uno a uno.
        $annoCoorte = self::ANNO_COORTE_BASE - (12 + self::assoluto($sezione, $anno));
        // I nati fra gennaio e il 1º aprile stanno nella coorte precedente,
        // quindi sono venuti al mondo l'anno DOPO l'inizio di quella coorte.
        return self::primaDelTaglio($mese, $giorno) ? $annoCoorte + 1 : $annoCoorte;
    }

    /** Vero per le date dal 1º gennaio al 1º aprile compreso. */
    public static function primaDelTaglio(int $mese, int $giorno): bool
    {
        return $mese < 4 || ($mese === 4 && $giorno <= 1);
    }

    /**
     * Età compiuta a una certa data di gioco.
     *
     * @param int $gts istante di gioco (avvolto o lineare: ci pensa Orologio)
     */
    public static function eta(int $annoNascita, int $mese, int $giorno, ?int $gts = null): int
    {
        $oggi = Orologio::data($gts);
        $eta  = (int) $oggi->format('Y') - $annoNascita;
        $md   = sprintf('%02d-%02d', $mese, $giorno);
        if ($oggi->format('m-d') < $md) {
            $eta--;
        }
        return $eta;
    }

    /** Vero se oggi è il compleanno. */
    public static function compleanno(int $mese, int $giorno, ?int $gts = null): bool
    {
        return Orologio::data($gts)->format('m-d') === sprintf('%02d-%02d', $mese, $giorno);
    }

    /**
     * Quanti giorni di gioco mancano al prossimo compleanno (0 = è oggi).
     */
    public static function giorniAlCompleanno(int $mese, int $giorno, ?int $gts = null): int
    {
        $oggi = Orologio::data($gts);
        $md   = sprintf('%02d-%02d', $mese, $giorno);
        for ($i = 0; $i <= 366; $i++) {
            $d = $oggi->modify("+{$i} day");
            if ($d->format('m-d') === $md) {
                return $i;
            }
        }
        return -1;   // 29 febbraio in un anno non bisestile: non capita nel nostro ciclo
    }

    /**
     * Le classi giocabili con la fascia d'età che comportano, per il modulo
     * di creazione.
     *
     * @return list<array{sezione:string,anno:int,nome:string,sigla:string,eta:string}>
     */
    public static function scelte(): array
    {
        // Le due età si misurano a metà anno scolastico, non a caso: dentro la
        // stessa classe convivono sempre due età, perché il taglio è ad aprile
        // e i compleanni no.
        $meta = (new DateTimeImmutable(self::ANNO_COORTE_BASE . '-10-01', Orologio::fuso()))->getTimestamp();
        $out = [];
        foreach (self::GIOCABILI as [$sez, $anno]) {
            $grande  = self::eta(self::annoDiNascita($sez, $anno, 4, 2), 4, 2, $meta);
            $piccolo = self::eta(self::annoDiNascita($sez, $anno, 4, 1), 4, 1, $meta);
            $out[] = [
                'sezione' => $sez,
                'anno'    => $anno,
                'nome'    => self::nomeClasse($sez, $anno),
                'sigla'   => self::siglaClasse($sez, $anno),
                'eta'     => min($grande, $piccolo) . '-' . max($grande, $piccolo) . ' anni',
            ];
        }
        return $out;
    }
}
