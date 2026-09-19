<?php

declare(strict_types=1);

namespace App\Game;

use App\Core\Database;
use App\Core\GameConfig;
use App\Core\Lock;
use App\Sim\Calendario;
use App\Sim\Luoghi;
use App\Sim\Orologio;
use App\Sim\Scuola;

/**
 * Il personaggio nel mondo: chi e', dov'e', come ci si sposta.
 *
 * **Attenzione alla scheda.** In questa fase il personaggio ha soltanto un
 * nome e una posizione: abilita', tratti e poteri arrivano con F2. La colonna
 * `scheda` vale `abbozzo` per tutti quelli creati adesso, e in F2 andra'
 * scritto un completamento retroattivo — non un controllo sparso qua e la'
 * che dia per scontato che i campi ci siano. Un personaggio nato in una fase
 * precedente e mai completato e' il modo piu' rapido di rompere la fase
 * successiva.
 *
 * **Attenzione agli istanti.** Tutto quello che finisce in tabella e' un
 * istante *lineare* (`Orologio::lineare()`), che cresce all'infinito. Se si
 * salvasse l'istante avvolto nel ciclo, un viaggio iniziato il 5 aprile e
 * finito il 6 avrebbe l'arrivo prima della partenza, una volta l'anno.
 */
final class Personaggio
{
    /** @return array<string,mixed>|null */
    public static function perUtente(int $userId): ?array
    {
        $pg = Database::first('SELECT * FROM personaggi WHERE user_id = ?', [$userId]);
        if ($pg === null) {
            return null;
        }
        // I tiri si garantiscono qui, una volta sola, per tutti: i personaggi
        // nati in F1 non ce li hanno, e nessun altro pezzo di codice deve
        // doversene ricordare.
        return Scheda::assicuraTiri(self::avanza($pg));
    }

    /** @return array<string,mixed>|null */
    public static function perId(int $id): ?array
    {
        $pg = Database::first('SELECT * FROM personaggi WHERE id = ?', [$id]);
        return $pg === null ? null : self::avanza($pg);
    }

    public static function nomeCompleto(array $pg): string
    {
        return trim($pg['cognome'] . ' ' . $pg['nome']);
    }

    /** Vero finché la scheda non è stata chiusa dal giocatore. */
    public static function daCompletare(array $pg): bool
    {
        return (string) $pg['scheda'] !== 'completa';
    }

    // --- Creazione -------------------------------------------------------------

    /**
     * @return array{ok:bool, error?:string, id?:int}
     */
    public static function crea(
        int $userId, string $nome, string $cognome, string $sesso,
        string $sezione, int $anno, int $mese, int $giorno, bool $esper
    ): array {
        $nome    = self::ripulisciNome($nome);
        $cognome = self::ripulisciNome($cognome);

        if ($nome === '' || $cognome === '') {
            return ['ok' => false, 'error' => 'Servono un nome e un cognome.'];
        }
        if (mb_strlen($nome) > 32 || mb_strlen($cognome) > 32) {
            return ['ok' => false, 'error' => 'Nome e cognome non possono superare i 32 caratteri.'];
        }
        if (!in_array($sesso, ['m', 'f'], true)) {
            return ['ok' => false, 'error' => 'Indica se il personaggio è maschio o femmina.'];
        }
        if (!Scuola::giocabile($sezione, $anno)) {
            return ['ok' => false, 'error' => 'Quella classe non si può giocare.'];
        }
        // Il giorno va controllato due volte, e la seconda è quella che conta.
        // La prima dice se la data esiste in un anno bisestile qualunque; la
        // seconda se esiste nell'anno in cui questo personaggio è davvero
        // nato — che non si sceglie, lo decide la classe. Il 29 febbraio è
        // quindi ammesso solo in alcune classi, e non è un capriccio del
        // modello: chi è nato il 29 febbraio è nato in un anno bisestile, e
        // basta.
        if (!checkdate($mese, $giorno, 1988)) {
            return ['ok' => false, 'error' => 'Quella data non esiste.'];
        }
        $annoNascita = Scuola::annoDiNascita($sezione, $anno, $mese, $giorno);
        if (!checkdate($mese, $giorno, $annoNascita)) {
            return ['ok' => false, 'error' => sprintf(
                'Chi è in %s è nato nel %d, che non è bisestile: il 29 febbraio non può essere '
                . 'il tuo compleanno. Prova un\'altra classe, o un altro giorno.',
                Scuola::nomeClasse($sezione, $anno), $annoNascita
            )];
        }
        if (Database::first('SELECT id FROM personaggi WHERE user_id = ?', [$userId]) !== null) {
            return ['ok' => false, 'error' => 'Hai già un personaggio.'];
        }
        // Due persone con lo stesso nome e cognome nello stesso quartiere sono
        // un problema di gioco prima ancora che di dati: metà delle meccaniche
        // di F3 e F6 girano attorno al «chi era».
        $gemello = Database::first(
            'SELECT id FROM personaggi WHERE nome = ? AND cognome = ?', [$nome, $cognome]
        );
        if ($gemello !== null) {
            return ['ok' => false, 'error' => 'Nel quartiere c\'è già qualcuno con questo nome e cognome.'];
        }

        $adesso = Orologio::lineare();

        Database::run(
            'INSERT INTO personaggi (user_id, nome, cognome, sesso, sezione, anno,
                                     nato_mese, nato_giorno, anno_nascita, esper, scheda,
                                     luogo, arrivato_gts, visto_gts)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$userId, $nome, $cognome, $sesso, $sezione, $anno,
             $mese, $giorno, $annoNascita, $esper ? 1 : 0, 'abbozzo',
             'gradini', $adesso, $adesso]
        );
        $id = Database::lastInsertId();

        Database::run(
            'INSERT INTO presenze (personaggio_id, luogo, dal_gts) VALUES (?, ?, ?)',
            [$id, 'gradini', $adesso]
        );
        self::traccia('gradini', $id, 'arrivo',
            sprintf('%s %s si ferma in cima ai gradini a riprendere fiato: è nuovo da queste parti.',
                $cognome, $nome), $adesso);

        return ['ok' => true, 'id' => $id];
    }

    private static function ripulisciNome(string $v): string
    {
        $v = preg_replace('/[\p{Z}\s]+/u', ' ', $v) ?? $v;
        return trim($v);
    }

    /** Nome valido: lettere (accentate e giapponesi comprese), apostrofo, trattino. */
    public static function nomeValido(string $v): bool
    {
        return preg_match('/^[\p{L}][\p{L}\x27 -]{0,31}$/u', $v) === 1;
    }

    // --- Avanzamento pigro -------------------------------------------------------

    /**
     * Porta il personaggio al presente.
     *
     * Chi e' in viaggio arriva da solo quando e' ora, senza che nessun processo
     * debba occuparsene: basta che qualcuno lo guardi. Il battito fa la stessa
     * identica cosa per chi non e' collegato, chiamando questo stesso metodo —
     * una sola strada, quindi nessun rischio che i due percorsi divergano.
     *
     * @param array<string,mixed> $pg
     * @return array<string,mixed>
     */
    public static function avanza(array $pg): array
    {
        if ($pg['verso'] === null || $pg['arrivo_gts'] === null) {
            return $pg;
        }
        if (Orologio::lineare() < (int) $pg['arrivo_gts']) {
            return $pg;   // ancora per strada
        }

        $id      = (int) $pg['id'];
        $destino = (string) $pg['verso'];
        $arrivo  = (int) $pg['arrivo_gts'];

        // Il lucchetto evita che il battito e la richiesta del giocatore
        // facciano arrivare lo stesso personaggio due volte, scrivendo due
        // presenze e due tracce per lo stesso passo.
        $fatto = Lock::con('arrivo:' . $id, static function () use ($id, $destino, $arrivo): bool {
            $fresco = Database::first('SELECT verso, arrivo_gts FROM personaggi WHERE id = ?', [$id]);
            if ($fresco === null || $fresco['verso'] === null) {
                return false;   // qualcun altro ci e' arrivato prima
            }
            Database::run(
                'UPDATE presenze SET al_gts = ? WHERE personaggio_id = ? AND al_gts IS NULL',
                [$arrivo, $id]
            );
            Database::run(
                'UPDATE personaggi SET luogo = ?, arrivato_gts = ?, verso = NULL, arrivo_gts = NULL
                 WHERE id = ?',
                [$destino, $arrivo, $id]
            );
            Database::run(
                'INSERT INTO presenze (personaggio_id, luogo, dal_gts) VALUES (?, ?, ?)',
                [$id, $destino, $arrivo]
            );
            return true;
        }, false);

        if ($fatto) {
            $pg = Database::first('SELECT * FROM personaggi WHERE id = ?', [$id]) ?? $pg;
        }
        return $pg;
    }

    /** Tutti quelli rimasti per strada: lo chiama il battito. */
    public static function avanzaTutti(): int
    {
        $n = 0;
        foreach (Database::all(
            'SELECT * FROM personaggi WHERE verso IS NOT NULL AND arrivo_gts <= ?',
            [Orologio::lineare()]
        ) as $pg) {
            self::avanza($pg);
            $n++;
        }
        return $n;
    }

    // --- Spostamento ---------------------------------------------------------------

    /**
     * @param array<string,mixed> $pg
     * @return array{ok:bool, error?:string, minuti?:int, arrivo?:int}
     */
    public static function parti(array $pg, string $verso): array
    {
        if ($pg['verso'] !== null) {
            return ['ok' => false, 'error' => 'Sei già per strada: aspetta di arrivare.'];
        }
        $da = (string) $pg['luogo'];
        if ($verso === $da) {
            return ['ok' => false, 'error' => 'Ci sei già.'];
        }
        $minuti = Luoghi::minuti($da, $verso);
        if ($minuti === null) {
            return ['ok' => false, 'error' => 'Da qui non ci si arriva in un colpo solo.'];
        }

        $adesso = Orologio::lineare();
        $arrivo = $adesso + $minuti * 60;

        [$aperto, $motivo] = Luoghi::accessibile($verso, $arrivo);
        if (!$aperto) {
            return ['ok' => false, 'error' => $motivo ?? 'Non ci si può andare adesso.'];
        }

        Database::run(
            'UPDATE personaggi SET verso = ?, arrivo_gts = ?, visto_gts = ? WHERE id = ?',
            [$verso, $arrivo, $adesso, (int) $pg['id']]
        );
        self::traccia($da, (int) $pg['id'], 'partenza',
            sprintf('%s se ne va verso %s.', self::nomeCompleto($pg), Luoghi::nome($verso)), $adesso);

        return ['ok' => true, 'minuti' => $minuti, 'arrivo' => $arrivo];
    }

    public static function segnaVisto(int $id): void
    {
        Database::run('UPDATE personaggi SET visto_gts = ? WHERE id = ?', [Orologio::lineare(), $id]);
    }

    // --- Chi c'e', cosa e' rimasto ------------------------------------------------------

    /**
     * Chi si trova in un luogo adesso, escluso chi guarda.
     *
     * @return list<array<string,mixed>>
     */
    public static function presenti(string $lkey, ?int $escludi = null): array
    {
        $righe = Database::all(
            'SELECT id, nome, cognome, sesso, sezione, anno, anno_nascita, nato_mese, nato_giorno,
                    esper, arrivato_gts, png, png_nota
             FROM personaggi
             WHERE luogo = ? AND verso IS NULL AND id <> ? AND stato = ?
             ORDER BY arrivato_gts',
            [$lkey, $escludi ?? 0, 'attivo']
        );
        $adesso = Orologio::lineare();
        foreach ($righe as &$r) {
            $r['da_minuti'] = (int) floor(($adesso - (int) $r['arrivato_gts']) / 60);
            $r['classe']    = Scuola::nomeClasse((string) $r['sezione'], (int) $r['anno']);
            // Se è il suo compleanno lo sa il quartiere, non solo lei.
            $r['compleanno'] = Scuola::compleanno((int) $r['nato_mese'], (int) $r['nato_giorno']);
        }
        return $righe;
    }

    /**
     * Chi c'è passato di recente ma adesso non c'è più.
     *
     * È la differenza fra un quartiere e una stanza: il posto conserva il
     * ricordo di chi ci è stato, e chi arriva dopo può accorgersene.
     *
     * @return list<array<string,mixed>>
     */
    public static function passatiDiRecente(string $lkey, ?int $escludi = null): array
    {
        $finestra = max(5, GameConfig::int('mondo.presenza_minuti', 45)) * 60;
        $adesso   = Orologio::lineare();
        $righe = Database::all(
            'SELECT p.personaggio_id AS id, g.nome, g.cognome, p.al_gts
             FROM presenze p JOIN personaggi g ON g.id = p.personaggio_id
             WHERE p.luogo = ? AND p.al_gts IS NOT NULL AND p.al_gts >= ? AND p.personaggio_id <> ?
               AND g.stato = ?
             ORDER BY p.al_gts DESC LIMIT 8',
            [$lkey, $adesso - $finestra, $escludi ?? 0, 'attivo']
        );
        foreach ($righe as &$r) {
            $r['fa_minuti'] = (int) floor(($adesso - (int) $r['al_gts']) / 60);
        }
        return $righe;
    }

    /**
     * Scrive una traccia nel luogo.
     *
     * Le tracce sono la materia prima delle voci (F6): un fatto osservabile,
     * legato a un posto e a un istante. Si spengono col tempo, perche' un
     * quartiere che ricorda tutto per sempre non e' un quartiere.
     */
    public static function traccia(
        string $lkey, ?int $personaggioId, string $tipo, string $testo, ?int $gts = null, int $forza = 100
    ): void {
        Database::run(
            'INSERT INTO tracce (luogo, gts, personaggio_id, tipo, testo, forza) VALUES (?, ?, ?, ?, ?, ?)',
            [$lkey, $gts ?? Orologio::lineare(), $personaggioId, $tipo, mb_substr($testo, 0, 255), max(1, min(100, $forza))]
        );
    }

    /**
     * Le tracce ancora leggibili in un luogo, già sbiadite dal tempo.
     *
     * @return list<array{testo:string,tipo:string,fa_minuti:int,forza:int}>
     */
    public static function tracce(string $lkey, int $quante = 6): array
    {
        $ore    = max(1, GameConfig::int('mondo.traccia_durata_ore', 72));
        $adesso = Orologio::lineare();
        $out = [];
        foreach (Database::all(
            'SELECT tipo, testo, gts, forza FROM tracce
             WHERE luogo = ? AND gts >= ? ORDER BY gts DESC LIMIT ?',
            [$lkey, $adesso - $ore * 3600, $quante]
        ) as $t) {
            $eta = ($adesso - (int) $t['gts']) / ($ore * 3600);
            $out[] = [
                'tipo'      => (string) $t['tipo'],
                'testo'     => (string) $t['testo'],
                'fa_minuti' => (int) floor(($adesso - (int) $t['gts']) / 60),
                'forza'     => (int) round((int) $t['forza'] * max(0.0, 1.0 - $eta)),
            ];
        }
        return array_values(array_filter($out, static fn (array $t): bool => $t['forza'] > 0));
    }

    /** Potatura delle tracce spente. La chiama il battito. */
    public static function potaTracce(): int
    {
        $ore = max(1, GameConfig::int('mondo.traccia_durata_ore', 72));
        return Database::run(
            'DELETE FROM tracce WHERE gts < ?', [Orologio::lineare() - $ore * 3600]
        )->rowCount();
    }

    /**
     * «venti minuti fa», «tre ore fa». I minuti di gioco, non quelli veri:
     * dentro la finzione l'unità di misura è quella.
     */
    public static function quantoFa(int $minuti): string
    {
        if ($minuti < 1)   { return 'proprio adesso'; }
        if ($minuti < 60)  { return $minuti . ($minuti === 1 ? ' minuto fa' : ' minuti fa'); }
        $ore = (int) round($minuti / 60);
        if ($ore < 24)     { return $ore === 1 ? 'un\'ora fa' : $ore . ' ore fa'; }
        $giorni = (int) round($ore / 24);
        return $giorni === 1 ? 'ieri' : $giorni . ' giorni fa';
    }
}
