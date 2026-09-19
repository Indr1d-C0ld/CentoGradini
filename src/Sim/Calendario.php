<?php

declare(strict_types=1);

namespace App\Sim;

use DateTimeImmutable;

/**
 * Il calendario scolastico giapponese dell'anno 1987-88, e la giornata dentro
 * di esso.
 *
 * Non e' contorno: in Kimagure Orange Road il calendario **e'** la trama. Il
 * festival culturale, il ritiro estivo del club, il Natale che si ripete, il
 * San Valentino, i diplomi di marzo — sono episodi interi. Un mondo persistente
 * ambientato qui deve sapere che giorno e', o non sa nemmeno di cosa parlare.
 *
 * Tre cose che valgono la pena di essere dette, perche' sono giuste e non
 * ovvie:
 *
 * - **Si va a scuola il sabato.** In Giappone la settimana scolastica di sei
 *   giorni e' rimasta la norma fino agli anni Novanta: nel 1987 il sabato si
 *   fa mezza giornata e si esce a mezzogiorno. Toglierlo per comodita'
 *   sarebbe stato sbagliato proprio nell'unico giorno della settimana che ha
 *   un sapore suo.
 * - **Il 29 aprile e' il compleanno dell'Imperatore.** Hirohito e' vivo nel
 *   1987 e muore nel gennaio 1989: quella festa sta li', e sparira' con lui.
 *   Lo stesso vale per il 23 dicembre, che nel 1987 NON e' festa.
 * - **Il 29 febbraio 1988 esiste.** Il 1988 e' bisestile, e in quel giorno e'
 *   andato in onda l'episodio 47. Il ciclo dura 366 giorni apposta.
 *
 * Tutto quanto e' funzione pura della data: nessuno stato, nessuna tabella.
 */
final class Calendario
{
    // --- Feste nazionali del 1987-88 (giorni senza scuola) ---------------------
    // Nella forma 'MM-GG'. Valgono per l'anno scolastico che va da aprile 1987
    // a marzo 1988, quindi comprendono le date del 1988 da gennaio in poi.
    private const FESTE = [
        '01-01' => 'Capodanno',
        '01-15' => 'Festa della maggiore età',
        '02-11' => 'Festa della fondazione nazionale',
        '03-20' => 'Equinozio di primavera',
        '04-29' => 'Compleanno dell\'Imperatore',
        '05-03' => 'Festa della Costituzione',
        '05-04' => 'Giorno del popolo',
        '05-05' => 'Festa dei bambini',
        '09-15' => 'Festa degli anziani',
        '09-23' => 'Equinozio d\'autunno',
        '10-10' => 'Giornata dello sport',
        '11-03' => 'Festa della cultura',
        '11-23' => 'Festa del ringraziamento al lavoro',
    ];

    // --- Vacanze scolastiche ---------------------------------------------------
    /** @var list<array{0:string,1:string,2:string}> inizio, fine (inclusi), nome */
    private const VACANZE = [
        ['07-21', '08-31', 'vacanze estive'],
        ['12-25', '01-07', 'vacanze invernali'],
        ['03-25', '04-05', 'vacanze di primavera'],
    ];

    // --- Periodi lunghi che colorano la stagione --------------------------------
    /** @var list<array{0:string,1:string,2:string}> */
    private const PERIODI = [
        ['04-01', '04-15', 'fioritura dei ciliegi'],
        ['06-08', '07-20', 'stagione delle piogge'],
        ['08-01', '08-25', 'caldo umido'],
        ['09-01', '09-30', 'stagione dei tifoni'],
        ['11-10', '12-05', 'foglie rosse'],
        ['01-10', '02-20', 'il cuore dell\'inverno'],
    ];

    // --- Giorni che valgono un episodio -------------------------------------------
    /** @var array<string,array{titolo:string,nota:string}> */
    private const EVENTI = [
        '04-06' => ['titolo' => 'Cerimonia d\'ingresso',      'nota' => 'Comincia l\'anno. Facce nuove, divise nuove, nessuno sa ancora niente di nessuno.'],
        '04-10' => ['titolo' => 'Reclutamento dei club',      'nota' => 'Volantini, insistenze, promesse. Oggi si decide con chi si passeranno i pomeriggi.'],
        '04-13' => ['titolo' => 'Hanami',                      'nota' => 'Teli sotto i ciliegi del parco, e petali dappertutto.'],
        '05-18' => ['titolo' => 'Esami di metà trimestre',     'nota' => 'Tre giorni in cui nessuno ha voglia di parlare d\'altro.'],
        '06-15' => ['titolo' => 'Gita scolastica',             'nota' => 'Il pullman, le coppie sui sedili in fondo, il maestro che conta le teste.'],
        '07-07' => ['titolo' => 'Tanabata',                     'nota' => 'Desideri scritti su strisce di carta appese al bambù. Qualcuno legge quello degli altri.'],
        '07-20' => ['titolo' => 'Chiusura del primo trimestre','nota' => 'Pagelle. Poi due mesi di niente.'],
        '08-01' => ['titolo' => 'Festival d\'estate',           'nota' => 'Yukata, bancarelle, pesca dei pesciolini rossi e fuochi d\'artificio sul fiume.'],
        '08-13' => ['titolo' => 'Obon',                         'nota' => 'Si torna al paese dei nonni. Chi resta in città trova il quartiere mezzo vuoto.'],
        '08-20' => ['titolo' => 'Ritiro del club',              'nota' => 'Quattro giorni in montagna a fare la stessa cosa dalla mattina alla sera.'],
        '09-01' => ['titolo' => 'Apertura del secondo trimestre','nota' => 'Tutti abbronzati e nessuno ha fatto i compiti.'],
        '09-26' => ['titolo' => 'Festival sportivo',            'nota' => 'Corsa a tre gambe, tiro alla fune, fasce colorate in testa.'],
        '10-24' => ['titolo' => 'Festival culturale',           'nota' => 'Le classi diventano caffè e case infestate. Due giorni in cui la scuola non è la scuola.'],
        '11-20' => ['titolo' => 'Foglie rosse',                 'nota' => 'Il viale degli alberi diventa arancione, che è poi il colore di tutta questa storia.'],
        '12-14' => ['titolo' => 'Esami di fine trimestre',      'nota' => 'Gli ultimi prima delle feste.'],
        '12-24' => ['titolo' => 'Vigilia di Natale',            'nota' => 'In Giappone non è una festa di famiglia: è la sera in cui si esce con qualcuno. Chiunque abbia qualcosa da dire, la dice stasera.'],
        '12-31' => ['titolo' => 'Ultimo dell\'anno',            'nota' => 'Soba di mezzanotte e le campane del tempio, centootto rintocchi.'],
        '01-01' => ['titolo' => 'Capodanno',                    'nota' => 'Prima visita al tempio, primo sogno dell\'anno, e le buste rosse per i più piccoli.'],
        '01-08' => ['titolo' => 'Apertura del terzo trimestre', 'nota' => 'L\'ultimo pezzo dell\'anno, e il più corto.'],
        '02-03' => ['titolo' => 'Setsubun',                      'nota' => 'Fagioli tirati addosso ai demoni, e ai fratelli minori.'],
        '02-14' => ['titolo' => 'San Valentino',                 'nota' => 'Qui il cioccolato lo danno le ragazze. Quello di cortesia e quello vero non si somigliano affatto, e tutti lo sanno.'],
        '02-20' => ['titolo' => 'Esami d\'ammissione',           'nota' => 'Chi è all\'ultimo anno sparisce dalla circolazione.'],
        '03-03' => ['titolo' => 'Festa delle bambole',           'nota' => 'Le bambole sui ripiani rossi, e la superstizione di rimetterle via in fretta.'],
        '03-14' => ['titolo' => 'White Day',                     'nota' => 'Un mese dopo, tocca rispondere. Tacere è una risposta anche quello.'],
        '03-15' => ['titolo' => 'Cerimonia di diploma',          'nota' => 'Il secondo bottone della divisa si dà a chi si ama. Qualcuno lo chiede, qualcuno no.'],
        '03-24' => ['titolo' => 'Chiusura dell\'anno',           'nota' => 'Ultimo giorno. Fra due settimane si ricomincia, e sarà di nuovo aprile.'],
    ];

    /**
     * Fotografia completa del momento.
     *
     * @return array{
     *   gts:int, data:DateTimeImmutable, iso:string, md:string,
     *   giorno:string, feriale:bool, festa:?string, vacanza:?string,
     *   stagione:string, trimestre:?int, periodo:?string,
     *   evento:?array{titolo:string,nota:string},
     *   scuola:bool, fase:string, fase_nome:string, ora:int, minuto:int
     * }
     */
    public static function stato(?int $gts = null): array
    {
        // Si accetta anche un istante lineare preso dal database: avvolgerlo
        // qui evita che un `gts` non convertito produca una data nel 1994.
        $gts = $gts === null ? Orologio::gts() : Orologio::avvolgi($gts);
        $d   = Orologio::data($gts);
        $md  = $d->format('m-d');
        $dow = (int) $d->format('N');           // 1 = lunedì … 7 = domenica

        $festa   = self::FESTE[$md] ?? null;
        $vacanza = self::vacanza($md);
        // Si va a scuola dal lunedì al sabato, se non è festa e non sono vacanze.
        $scuola  = $dow <= 6 && $festa === null && $vacanza === null;

        [$fase, $faseNome] = self::fase($d, $scuola, $dow === 6);

        return [
            'gts'       => $gts,
            'data'      => $d,
            'iso'       => $d->format('Y-m-d'),
            'md'        => $md,
            'giorno'    => Orologio::giornoSettimana($gts),
            'feriale'   => $dow <= 5,
            'festa'     => $festa,
            'vacanza'   => $vacanza,
            'stagione'  => self::stagione($d),
            'trimestre' => self::trimestre($md, $vacanza),
            'periodo'   => self::periodo($md),
            'evento'    => self::EVENTI[$md] ?? null,
            'scuola'    => $scuola,
            'fase'      => $fase,
            'fase_nome' => $faseNome,
            'ora'       => (int) $d->format('G'),
            'minuto'    => (int) $d->format('i'),
        ];
    }

    // --- Pezzi -------------------------------------------------------------------

    /**
     * Le stagioni seguono il calendario giapponese, che le fa cominciare un mese
     * prima del nostro: marzo è già primavera, giugno è già estate. Non è un
     * dettaglio da poco quando metà delle scene sono all'aperto.
     */
    public static function stagione(DateTimeImmutable $d): string
    {
        return match ((int) $d->format('n')) {
            3, 4, 5    => 'primavera',
            6, 7, 8    => 'estate',
            9, 10, 11  => 'autunno',
            default    => 'inverno',
        };
    }

    /** Nome della vacanza in corso, o null. */
    public static function vacanza(string $md): ?string
    {
        foreach (self::VACANZE as [$da, $a, $nome]) {
            if (self::dentro($md, $da, $a)) {
                return $nome;
            }
        }
        return null;
    }

    public static function periodo(string $md): ?string
    {
        foreach (self::PERIODI as [$da, $a, $nome]) {
            if (self::dentro($md, $da, $a)) {
                return $nome;
            }
        }
        return null;
    }

    /** 1, 2 o 3. Null durante le vacanze. */
    public static function trimestre(string $md, ?string $vacanza): ?int
    {
        if ($vacanza !== null) {
            return null;
        }
        if (self::dentro($md, '04-06', '07-20')) {
            return 1;
        }
        if (self::dentro($md, '09-01', '12-24')) {
            return 2;
        }
        return 3;   // 08/01 - 24/03
    }

    /**
     * Confronto fra date «MM-GG» che sa scavallare il capodanno: l'intervallo
     * 12-25 → 01-07 è una vacanza sola, non due pezzi.
     */
    public static function dentro(string $md, string $da, string $a): bool
    {
        return $da <= $a
            ? ($md >= $da && $md <= $a)
            : ($md >= $da || $md <= $a);
    }

    // --- La giornata ------------------------------------------------------------

    /**
     * In che momento della giornata siamo.
     *
     * Gli orari sono quelli di un liceo giapponese dell'epoca: appello alle
     * 8:30, sei ore da cinquanta minuti, pausa pranzo con il bentō, venti
     * minuti di pulizie fatte dagli studenti (che in Giappone è la norma, non
     * una punizione) e poi i club fino a sera. Il sabato si chiude a mezzogiorno
     * dopo quattro ore.
     *
     * @return array{0:string,1:string} chiave, nome leggibile
     */
    private static function fase(DateTimeImmutable $d, bool $scuola, bool $sabato): array
    {
        $m = (int) $d->format('G') * 60 + (int) $d->format('i');

        if ($m < 5 * 60)  { return ['notte', 'notte fonda']; }
        if ($m < 7 * 60)  { return ['alba', 'prima mattina']; }

        if (!$scuola) {
            if ($m < 12 * 60) { return ['libero_mattina',   'mattina libera']; }
            if ($m < 18 * 60) { return ['libero_pomeriggio','pomeriggio libero']; }
            if ($m < 22 * 60) { return ['sera',             'sera']; }
            return ['notte', 'notte'];
        }

        if ($m < 8 * 60 + 25)  { return ['tragitto',  'tragitto verso scuola']; }
        if ($m < 8 * 60 + 45)  { return ['appello',   'appello in classe']; }

        $fine = $sabato ? 12 * 60 + 30 : 15 * 60 + 10;
        if ($m < $fine) {
            // Dentro le lezioni: dieci minuti di intervallo ogni ora, e la
            // pausa pranzo lunga a metà giornata (solo nei giorni pieni).
            if (!$sabato && $m >= 12 * 60 + 35 && $m < 13 * 60 + 20) {
                return ['pranzo', 'pausa pranzo'];
            }
            $daInizio = $m - (8 * 60 + 45);
            if ($daInizio % 60 >= 50) {
                return ['intervallo', 'intervallo'];
            }
            return ['lezione', 'lezione'];
        }

        if ($sabato)              { return ['libero_pomeriggio', 'sabato pomeriggio']; }
        if ($m < 15 * 60 + 30)    { return ['pulizie', 'pulizie di classe']; }
        if ($m < 18 * 60)         { return ['club',    'attività dei club']; }
        if ($m < 22 * 60)         { return ['sera',    'sera']; }
        return ['notte', 'notte'];
    }

    /**
     * I prossimi giorni che valgono un episodio, a partire da adesso.
     *
     * @return list<array{md:string,titolo:string,nota:string,giorni:int}>
     */
    public static function prossimiEventi(?int $gts = null, int $quanti = 3): array
    {
        $gts ??= Orologio::gts();
        $out = [];
        for ($i = 0; $i <= 366 && count($out) < $quanti; $i++) {
            $g = Orologio::avvolgi($gts + $i * 86400);
            $md = Orologio::data($g)->format('m-d');
            if (isset(self::EVENTI[$md]) && ($i > 0 || Orologio::data($gts)->format('m-d') === $md)) {
                $out[] = self::EVENTI[$md] + ['md' => $md, 'giorni' => $i];
            }
        }
        return $out;
    }

    /** @return array<string,array{titolo:string,nota:string}> */
    public static function tuttiGliEventi(): array
    {
        return self::EVENTI;
    }
}
