<?php

declare(strict_types=1);

namespace App\Game;

use App\Core\Database;
use App\Core\GameConfig;
use App\Sim\Calendario;
use App\Sim\Luoghi;
use App\Sim\Orologio;
use App\Sim\Rng;

/**
 * Gli abitanti: i personaggi canonici che vivono nel quartiere da soli.
 *
 * Non sono comparse e non sono la Folla — la Folla è un numero, questi sono
 * righe di `personaggi` a tutti gli effetti. Ne discende, gratis, che un PNG
 * può vedere un potere e annotarselo, ricevere un gesto, finire nel cast di
 * un episodio e soprattutto **portare in giro le voci**, che è il motivo per
 * cui esistono: senza qualcuno che attraversi la mappa, una voce resta dove è
 * nata e il quartiere non è un quartiere.
 *
 * Il loro movimento non è casuale e non è simulato passo per passo: ognuno ha
 * un **giro** (`png_giro`), e la posizione si ricava dall'ora di gioco. È lo
 * stesso principio del meteo — una funzione pura dell'istante invece di uno
 * stato da far avanzare — e ha lo stesso vantaggio: due richieste ravvicinate
 * vedono lo stesso quartiere, e saltare un battito non sfasa nessuno.
 */
final class Abitanti
{
    /**
     * I poteri dei canonici, dal canone e non dal regolamento del 1990.
     *
     * Kyosuke NON ha la telepatia: è la correzione più pesante della
     * rilettura (CANONE §2.1), ed è il motore della storia — passa duecento
     * pagine a non capire cosa pensa Madoka, e se leggesse nel pensiero il
     * fumetto finirebbe al terzo capitolo. Kazuya è il solo telepate della
     * famiglia, e ha il potere di bloccare quelli degli altri.
     *
     * @var array<string, array{primario:string, secondari:list<string>}>
     */
    private const POTERI = [
        'kyosuke' => ['primario' => 'teletrasporto',
                      'secondari' => ['telecinesi', 'sogni', 'scambio_corpo', 'autoipnosi']],
        'manami'  => ['primario' => 'teletrasporto', 'secondari' => ['telecinesi']],
        'kurumi'  => ['primario' => 'teletrasporto', 'secondari' => ['telecinesi', 'ipnosi']],
        'akane'   => ['primario' => 'cambio_identita', 'secondari' => ['teletrasporto', 'fantasmi']],
        // «blocco» non e' sorteggiabile da nessuno (banda 0-0 nel seme): e'
        // suo, e basta. E' la ragione per cui un bambino di otto anni e' il
        // piu' forte della famiglia.
        'kazuya'  => ['primario' => 'telepatia',
                      'secondari' => ['teletrasporto', 'telecinesi', 'scambio_corpo', 'blocco']],
    ];

    /**
     * I club dei canonici.
     *
     * Solo Yusaku e' canone — fa karate, e ha cominciato perche' da bambino
     * Hikaru gli aveva detto che se fosse diventato forte l'avrebbe sposato
     * (FAQ 36). Il resto e' nostra ricostruzione e sta scritto in FONTI.md.
     *
     * **Madoka non compare in questo elenco, e non e' una dimenticanza.** La
     * FAQ 40 e' esplicita: e' nota a molte bande, non ha mai aderito a
     * nessuna, ed e' precisamente la sua immagine di solitaria. Metterla in
     * un club per simmetria sarebbe la cosa piu' sbagliata che si possa fare
     * a questo personaggio.
     *
     * @var array<string, array<string,string>>
     */
    private const CLUB = [
        'yusaku'  => ['karate'     => 'capitano'],
        'hikaru'  => ['atletica'   => 'socio'],
        'hatta'   => ['fotografia' => 'socio'],
        'komatsu' => ['giornalino' => 'socio'],
        'manami'  => ['cucina'     => 'socio'],
        'kurumi'  => ['tennis'     => 'socio'],
        'akane'   => ['musica'     => 'socio'],
    ];

    /** Controllo di partenza: i canonici usano i poteri da una vita. */
    private const CONTROLLO = 55;

    /**
     * Mette a posto tutto quello che dei canonici non sta nella tabella
     * `personaggi`: i poteri e le iscrizioni ai club. Idempotente: la chiama
     * la semina, e il battito la richiama senza fare danni.
     *
     * @return array{poteri:int, club:int}
     */
    public static function assicura(): array
    {
        return ['poteri' => self::assicuraPoteri(), 'club' => self::assicuraClub()];
    }

    /** Iscrive i canonici ai loro club. */
    public static function assicuraClub(): int
    {
        $gts   = Orologio::lineare();
        $fatti = 0;
        foreach (self::CLUB as $png => $club) {
            $pg = Database::first('SELECT id FROM personaggi WHERE png = ?', [$png]);
            if ($pg === null) {
                continue;
            }
            foreach ($club as $ckey => $ruolo) {
                if (Database::first('SELECT 1 x FROM club WHERE ckey = ?', [$ckey]) === null) {
                    continue;   // il club non e' ancora seminato
                }
                Database::run(
                    'INSERT INTO club_membri (ckey, personaggio_id, ruolo, dal_gts) VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE ruolo = VALUES(ruolo)',
                    [$ckey, (int) $pg['id'], $ruolo, $gts]
                );
                $fatti++;
            }
        }
        return $fatti;
    }

    // --- Insediamento ----------------------------------------------------------

    /**
     * Dà ai canonici i poteri che gli spettano. Idempotente: la si può
     * chiamare a ogni semina e a ogni battito senza fare danni.
     */
    public static function assicuraPoteri(): int
    {
        $fatti = 0;
        foreach (self::POTERI as $png => $set) {
            $pg = Database::first('SELECT id FROM personaggi WHERE png = ?', [$png]);
            if ($pg === null) {
                continue;
            }
            $id = (int) $pg['id'];
            // array_merge e non «+»: l'unione fra array lavora sulle chiavi, e
            // con «+» il primo potere secondario verrebbe mangiato dalla
            // chiave 0 del primario.
            $righe = array_merge(
                [[$set['primario'], 1]],
                array_map(static fn (string $k): array => [$k, 0], $set['secondari'])
            );
            foreach ($righe as $riga) {
                [$pkey, $primario] = $riga;
                Database::run(
                    'INSERT INTO personaggio_poteri (personaggio_id, pkey, primario, controllo)
                     VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE primario = VALUES(primario)',
                    [$id, $pkey, $primario, self::CONTROLLO]
                );
                $fatti++;
            }
        }
        return $fatti;
    }

    /** Gli abitanti canonici, in ordine. @return list<array<string,mixed>> */
    public static function tutti(): array
    {
        return Database::all(
            'SELECT * FROM personaggi WHERE png IS NOT NULL AND stato = ? ORDER BY id', ['attivo']
        );
    }

    // --- Il giro ---------------------------------------------------------------

    /**
     * Dove si trova un abitante a un certo istante.
     *
     * Il giro si percorre a passo fisso: `png_passo` ore di gioco per tappa,
     * uguali per tutti, così due persone con giri diversi si incrociano in
     * modo irregolare invece di marciare in colonna. Di notte si sta a casa —
     * la prima tappa del giro, che per convenzione è il posto di ognuno.
     */
    public static function dove(array $png, ?int $gts = null): string
    {
        $gts   = $gts ?? Orologio::lineare();
        $tappe = self::giro($png);
        if ($tappe === []) {
            return (string) $png['luogo'];
        }

        // A che ora si rientra. Un bambino di otto anni non sta al parco a
        // mezzanotte, e non serve una regola sofisticata per saperlo: basta
        // stringergli la finestra.
        [$sveglia, $rientro] = (string) ($png['sezione'] ?? '') === 'elementari'
            ? [8, 19]
            : [7, 23];

        $ora = (int) Orologio::data($gts)->format('G');
        if ($ora < $sveglia || $ora >= $rientro) {
            return $tappe[0];    // di notte, ognuno a casa propria
        }

        // Il calendario batte l'abitudine. Un mercoledì alle otto e mezza i
        // ragazzi sono in classe, non in sala giochi: senza questo il giro
        // produceva un quartiere pieno di studenti a scuola finita e vuoto
        // durante le lezioni, che è esattamente il rovescio.
        $c = Calendario::stato($gts);
        $aScuola = $c['scuola'] && in_array($c['fase'], self::FASI_A_SCUOLA, true);

        // I bambini delle elementari vanno a una scuola che sulla nostra
        // mappa non c'e', quindi durante le lezioni semplicemente non sono
        // in giro: restano al loro posto di casa. Mandarli al Koryo perche'
        // e' l'unica scuola che abbiamo darebbe un bambino di otto anni in
        // corridoio al liceo, che si nota.
        if ($aScuola && (string) ($png['sezione'] ?? '') === 'elementari') {
            return $tappe[0];
        }

        if (self::studente($png)) {
            if ($aScuola) {
                // Il Koryo e' l'unica scuola sulla mappa e ospita medie e
                // superiori insieme: e' una semplificazione nostra, e la
                // classe giocabile piu' bassa e' la terza media.
                return 'liceo';
            }
            // E nel pomeriggio, chi ha un club sta dove si ritrova il club.
            $ritrovo = Club::ritrovoAdesso((int) $png['id'], $gts);
            if ($ritrovo !== null && Luoghi::esiste($ritrovo)) {
                return $ritrovo;
            }
        }

        $passo = max(1, GameConfig::int('abitanti.passo_ore', 2));
        // L'ancora è l'identità più l'ora: lo stesso abitante allo stesso
        // istante è sempre nello stesso posto, anche se glielo si chiede
        // da due richieste diverse.
        $rng   = Rng::for(GameConfig::int('world.seed', 19870406), 'giro',
            (int) $png['id'], intdiv($gts, $passo * 3600));

        $i = intdiv($gts, $passo * 3600) + (int) $png['id'];
        // Una tappa su cinque viene saltata: un giro perfettamente regolare
        // si impara a memoria in due giorni e il quartiere diventa un orario
        // ferroviario.
        if ($rng->chance(0.2)) {
            $i++;
        }

        // E si scarta quello che a quest'ora e' chiuso. Senza, il giro
        // metteva gente dentro il luna park alle sette di mattina: gli orari
        // sono dati del luogo, e l'abitudine non li scavalca. Se non e'
        // aperto niente, si torna a casa.
        // Il resto di una divisione con dividendo negativo, in PHP, e'
        // negativo — e un indice negativo su una lista non esiste. Oggi $i e'
        // sempre positivo perche' l'istante di gioco cresce dall'epoca in
        // avanti, ma basta un orologio di sistema indietro rispetto all'epoca
        // registrata (una macchina nuova, un ripristino) per mandarlo sotto
        // zero, e allora il giro degli abitanti si riempie di avvisi invece di
        // ripiegare sulla prima tappa. Si normalizza qui, una volta.
        $quante = count($tappe);
        for ($k = 0; $k < $quante; $k++) {
            $forse = $tappe[(($i + $k) % $quante + $quante) % $quante];
            if (Luoghi::accessibile($forse, $gts)[0]) {
                return $forse;
            }
        }
        return $tappe[0];
    }

    /**
     * Le fasi in cui uno studente sta dentro la scuola. Il tragitto no: per
     * strada ci si incontra, ed e' meta' del gioco.
     *
     * @var list<string>
     */
    private const FASI_A_SCUOLA = ['appello', 'lezione', 'intervallo', 'pranzo', 'pulizie'];

    /** Chi frequenta il Koryo: le medie e le superiori, non gli adulti. */
    private static function studente(array $png): bool
    {
        return in_array((string) ($png['sezione'] ?? ''), ['medie', 'superiori'], true);
    }

    /** @return list<string> le tappe valide del giro */
    public static function giro(array $png): array
    {
        $grezzo = array_filter(array_map('trim', explode(',', (string) ($png['png_giro'] ?? ''))));
        $tappe  = array_values(array_filter($grezzo, static fn (string $l): bool => Luoghi::esiste($l)));
        return $tappe === [] ? [] : $tappe;
    }

    /**
     * Porta tutti gli abitanti dove devono essere. La chiama il battito.
     *
     * Si scrive sul database anche se la posizione è calcolabile, perché
     * tutto il resto del gioco interroga `personaggi.luogo`: presenze,
     * testimoni, cast. Calcolare e poi scrivere costa un UPDATE ogni tanto e
     * risparmia di riscrivere mezzo motore.
     */
    public static function muovi(?int $gts = null): int
    {
        $gts     = $gts ?? Orologio::lineare();
        $spostati = 0;
        foreach (self::tutti() as $png) {
            $dove = self::dove($png, $gts);
            if ($dove === (string) $png['luogo']) {
                continue;
            }
            Database::run(
                'UPDATE personaggi SET luogo = ?, arrivato_gts = ?, verso = NULL, arrivo_gts = NULL WHERE id = ?',
                [$dove, $gts, (int) $png['id']]
            );
            $spostati++;
        }
        return $spostati;
    }

    // --- Presentazione ---------------------------------------------------------

    /**
     * Come si presenta un abitante a chi se lo trova davanti: una riga, e
     * quello che si vede addosso. Serve alla scheda del luogo.
     */
    public static function presentazione(array $png): string
    {
        $n = trim((string) ($png['png_nota'] ?? ''));
        return $n !== '' ? $n : trim((string) $png['nome'] . ' ' . (string) $png['cognome']);
    }
}
