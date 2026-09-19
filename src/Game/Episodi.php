<?php

declare(strict_types=1);

namespace App\Game;

use App\Core\Database;
use App\Core\GameConfig;
use App\Core\Lock;
use App\Sim\Calendario;
use App\Sim\Luoghi;
use App\Sim\Meteo;
use App\Sim\Orologio;
use App\Sim\Rng;

/**
 * Gli episodi: la vita quotidiana che ogni tanto si organizza in una puntata.
 *
 * **Non si aprono con un pulsante.** Il battito guarda il mondo — chi c'è,
 * dove, che tempo fa, che stagione è, che sospetti girano — e quando le
 * condizioni di un copione sono soddisfatte apre l'episodio addosso a chi si
 * trova lì. Un episodio che si avvia a comando è una missione; uno che ti
 * capita addosso mentre stavi facendo altro è una puntata.
 *
 * **Chi non c'è non blocca nessuno.** Scaduta la finestra, per chi non ha
 * scelto sceglie l'**agente autonomo**, che non tira a caso: pesa le opzioni
 * sul carattere del personaggio. Uno con Cuore alto dirà la cosa difficile;
 * uno con Testa alta cercherà la via d'uscita ragionevole. È la stessa idea
 * del Primo Ufficiale che prende il comando quando il comandante non è alla
 * plancia, e serve alla stessa cosa: un mondo persistente non può fermarsi
 * ad aspettare.
 *
 * **Non si vincono.** Alla fine si guarda cos'è successo, e quello che resta
 * è una scheda nell'album dei ricordi — che sopravvive anche al Trasloco,
 * perché le persone si perdono e i posti si perdono, ma quello che è successo
 * no.
 */
final class Episodi
{
    // --- Apertura -------------------------------------------------------------

    /** Lo chiama il battito. @return int quanti episodi ha aperto */
    public static function verificaAperture(): int
    {
        $max = GameConfig::int('episodi.max_aperti', 6);
        $aperti = (int) (Database::first(
            "SELECT COUNT(*) n FROM episodi WHERE stato = 'aperto'")['n'] ?? 0);
        if ($aperti >= $max) {
            return 0;
        }

        $gts = Orologio::lineare();
        $n = 0;
        foreach (Database::all('SELECT * FROM copioni WHERE attivo = 1') as $c) {
            if (!self::condizioneSoddisfatta((string) $c['condizione'], $gts)) {
                continue;
            }
            foreach (self::luoghiCandidati($c) as $lkey) {
                $cast = self::castDisponibile($lkey, (int) $c['max_cast'], $gts);
                if (count($cast) < (int) $c['min_cast']) {
                    continue;
                }
                // Serve almeno un giocatore. Gli abitanti canonici possono
                // entrare nel cast — e' il bello di averli come personaggi
                // veri — ma una storia recitata da soli PNG non la legge
                // nessuno e consuma la pausa fra un episodio e l'altro.
                if (!self::c_eUnGiocatore($cast)) {
                    continue;
                }
                if (self::apri($c, $lkey, $cast, $gts) !== null) {
                    $n++;
                    if ($aperti + $n >= $max) {
                        return $n;
                    }
                }
                break;   // un episodio per copione per battito: il quartiere non è un teatro
            }
        }
        return $n;
    }

    /**
     * C'e' almeno una persona in carne e ossa in questo cast?
     *
     * @param list<array<string,mixed>> $cast
     */
    private static function c_eUnGiocatore(array $cast): bool
    {
        foreach ($cast as $m) {
            if (($m['png'] ?? null) === null) {
                return true;
            }
        }
        return false;
    }

    private static function condizioneSoddisfatta(string $cond, int $gts): bool
    {
        $cal = Calendario::stato($gts);
        $met = Meteo::a($gts);
        return match ($cond) {
            'sempre'    => true,
            'autunno'   => $cal['stagione'] === 'autunno',
            'estate'    => $cal['stagione'] === 'estate',
            'inverno'   => $cal['stagione'] === 'inverno',
            'primavera' => $cal['stagione'] === 'primavera',
            'pioggia'   => $met['pioggia'] > 0,
            'rovescio'  => $met['pioggia'] >= 8.0,
            'neve'      => $met['neve'],
            'scuola'    => $cal['scuola'],
            'vacanza'   => $cal['vacanza'] !== null,
            // «Sospetto» non guarda il calendario: guarda se in giro c'è
            // qualcuno che ha capito qualcosa che non doveva capire.
            'sospetto'  => Database::first("SELECT 1 FROM sanno WHERE come = 'scoperto' LIMIT 1") !== null,
            // E «pettegolezzo» guarda se c'è una voce che ha fatto abbastanza
            // strada da non somigliare più a quello che è successo.
            'pettegolezzo' => Database::first(
                'SELECT 1 FROM voci_versioni WHERE passaggi >= 3 LIMIT 1') !== null,
            // Qualunque evento stagionale in corso.
            'festa'     => Eventi::inCorso($gts) !== [],
            default     => self::condizioneEvento($cond, $gts),
        };
    }

    /**
     * `evento:festival_estate` e simili: l'episodio si apre solo mentre quel
     * preciso evento del calendario e' in corso.
     *
     * E' il modo per cui una storia puo' succedere «la sera dei fuochi» senza
     * che il copione debba sapere che i fuochi sono il 25 luglio: la data sta
     * in un posto solo, nel seme degli eventi.
     */
    private static function condizioneEvento(string $cond, int $gts): bool
    {
        if (!str_starts_with($cond, 'evento:')) {
            return false;
        }
        $ekey = substr($cond, 7);
        foreach (Eventi::inCorso($gts) as $e) {
            if ((string) $e['ekey'] === $ekey) {
                return true;
            }
        }
        return false;
    }

    /** @return list<string> */
    private static function luoghiCandidati(array $copione): array
    {
        if ($copione['luogo'] !== null) {
            return [(string) $copione['luogo']];
        }
        // Copione senza luogo fisso: si cerca dove c'è abbastanza gente.
        // Si contano i **giocatori**, non le teste: da F6 il liceo ha nove
        // abitanti canonici dentro tutte le mattine, e ordinare per numero
        // di presenti avrebbe aperto ogni episodio senza luogo fisso sempre
        // li', in mezzo a gente che non lo gioca.
        $righe = Database::all(
            "SELECT luogo, COUNT(*) n FROM personaggi
             WHERE verso IS NULL AND stato = 'attivo' AND scheda = 'completa' AND png IS NULL
             GROUP BY luogo HAVING n >= 1 ORDER BY n DESC LIMIT 4",
            []
        );
        return array_map(static fn (array $r): string => (string) $r['luogo'], $righe);
    }

    /**
     * Chi può entrare: presenti, con la scheda chiusa, non già in un episodio,
     * e che non ne abbiano appena finito uno.
     *
     * @return list<array<string,mixed>>
     */
    private static function castDisponibile(string $lkey, int $max, int $gts): array
    {
        $pausa = GameConfig::int('episodi.pausa_ore', 18) * 3600;
        return Database::all(
            "SELECT p.* FROM personaggi p
             WHERE p.luogo = ? AND p.verso IS NULL AND p.stato = 'attivo' AND p.scheda = 'completa'
               AND NOT EXISTS (
                 SELECT 1 FROM episodio_cast ec JOIN episodi e ON e.id = ec.episodio_id
                 WHERE ec.personaggio_id = p.id
                   AND (e.stato = 'aperto' OR e.chiuso_gts > ?))
             ORDER BY (p.png IS NOT NULL), p.visto_gts DESC
             LIMIT ?",
            [$lkey, $gts - $pausa, $max]
        );
    }

    /** @param list<array<string,mixed>> $cast */
    private static function apri(array $copione, string $lkey, array $cast, int $gts): ?int
    {
        $finestra = (int) ($copione['finestra'] ?: GameConfig::int('episodi.finestra', 1800));
        Database::run(
            'INSERT INTO episodi (ckey, titolo, luogo, scena, stato, aperto_gts, scena_gts, scade_reale)
             VALUES (?, ?, ?, 0, ?, ?, ?, ?)',
            [(string) $copione['ckey'], (string) $copione['titolo'], $lkey, 'aperto',
             $gts, $gts, Orologio::adessoReale() + $finestra]
        );
        $id = Database::lastInsertId();
        foreach ($cast as $pg) {
            Database::run(
                'INSERT IGNORE INTO episodio_cast (episodio_id, personaggio_id, entrato_gts) VALUES (?, ?, ?)',
                [$id, (int) $pg['id'], $gts]
            );
        }
        Personaggio::traccia($lkey, null, 'episodio',
            'È cominciata una di quelle giornate: ' . mb_strtolower((string) $copione['occhiello']) . '.',
            $gts, 70);
        return $id;
    }

    // --- Lettura ---------------------------------------------------------------

    /** L'episodio aperto in cui si trova questo personaggio, o null. */
    public static function mio(int $pgId): ?array
    {
        return Database::first(
            "SELECT e.*, c.premessa, c.occhiello, c.scene
             FROM episodi e
             JOIN episodio_cast ec ON ec.episodio_id = e.id
             JOIN copioni c ON c.ckey = e.ckey
             WHERE ec.personaggio_id = ? AND e.stato = 'aperto'
             ORDER BY e.id DESC LIMIT 1",
            [$pgId]
        );
    }

    /** @return array<string,mixed>|null la scena corrente */
    public static function scenaCorrente(array $ep): ?array
    {
        $scene = json_decode((string) $ep['scene'], true);
        return $scene[(int) $ep['scena']] ?? null;
    }

    /** @return list<array<string,mixed>> */
    public static function cast(int $epId): array
    {
        return Database::all(
            'SELECT p.id, p.nome, p.cognome, p.visto_gts FROM episodio_cast ec
             JOIN personaggi p ON p.id = ec.personaggio_id
             WHERE ec.episodio_id = ? ORDER BY p.id',
            [$epId]
        );
    }

    /** @return array<int,array<string,mixed>> scelte della scena corrente, per personaggio */
    public static function scelte(int $epId, int $scena): array
    {
        $out = [];
        foreach (Database::all(
            'SELECT * FROM episodio_scelte WHERE episodio_id = ? AND scena = ?', [$epId, $scena]
        ) as $r) {
            $out[(int) $r['personaggio_id']] = $r;
        }
        return $out;
    }

    // --- Giocare -----------------------------------------------------------------

    /** @return array{ok:bool, error?:string} */
    public static function scegli(array $pg, int $epId, string $opzione): array
    {
        $ep = Database::first(
            "SELECT e.*, c.scene FROM episodi e JOIN copioni c ON c.ckey = e.ckey
             WHERE e.id = ? AND e.stato = 'aperto'", [$epId]
        );
        if ($ep === null) {
            return ['ok' => false, 'error' => 'Quell\'episodio è finito.'];
        }
        if (Database::first('SELECT 1 FROM episodio_cast WHERE episodio_id = ? AND personaggio_id = ?',
                [$epId, (int) $pg['id']]) === null) {
            return ['ok' => false, 'error' => 'Non sei di questa scena.'];
        }
        $scena = self::scenaCorrente($ep);
        if ($scena === null) {
            return ['ok' => false, 'error' => 'Non c\'è nessuna scena da giocare.'];
        }
        $valide = array_column($scena['opzioni'], 'k');
        if (!in_array($opzione, $valide, true)) {
            return ['ok' => false, 'error' => 'Quella scelta non c\'è.'];
        }
        $gia = Database::first(
            'SELECT 1 FROM episodio_scelte WHERE episodio_id = ? AND scena = ? AND personaggio_id = ?',
            [$epId, (int) $ep['scena'], (int) $pg['id']]
        );
        if ($gia !== null) {
            return ['ok' => false, 'error' => 'Hai già scelto per questa scena.'];
        }

        Database::run(
            'INSERT INTO episodio_scelte (episodio_id, scena, personaggio_id, opzione) VALUES (?, ?, ?, ?)',
            [$epId, (int) $ep['scena'], (int) $pg['id'], $opzione]
        );

        // Se hanno scelto tutti, la scena si chiude senza aspettare il tempo.
        self::forseRisolvi($epId);
        return ['ok' => true];
    }

    /** Risolve se tutti hanno scelto, oppure se la finestra è scaduta. */
    public static function forseRisolvi(int $epId, bool $forza = false): bool
    {
        $ep = Database::first("SELECT * FROM episodi WHERE id = ? AND stato = 'aperto'", [$epId]);
        if ($ep === null) {
            return false;
        }
        $cast = self::cast($epId);
        $fatte = count(self::scelte($epId, (int) $ep['scena']));
        $scaduta = Orologio::adessoReale() >= (int) $ep['scade_reale'];

        if (!$forza && !$scaduta && $fatte < count($cast)) {
            return false;
        }
        return Lock::con('episodio:' . $epId, static fn (): bool => self::risolviScena($epId), false);
    }

    private static function risolviScena(int $epId): bool
    {
        $ep = Database::first(
            "SELECT e.*, c.scene FROM episodi e JOIN copioni c ON c.ckey = e.ckey
             WHERE e.id = ? AND e.stato = 'aperto'", [$epId]
        );
        if ($ep === null) {
            return false;
        }
        $scenaN = (int) $ep['scena'];
        $scena  = self::scenaCorrente($ep);
        if ($scena === null) {
            return false;
        }

        $gts   = Orologio::lineare();
        $cast  = self::cast($epId);
        $fatte = self::scelte($epId, $scenaN);
        $opzioni = [];
        foreach ($scena['opzioni'] as $o) {
            $opzioni[(string) $o['k']] = $o;
        }

        foreach ($cast as $membro) {
            $pgId = (int) $membro['id'];
            $pg = Database::first('SELECT * FROM personaggi WHERE id = ?', [$pgId]);
            if ($pg === null) {
                continue;
            }
            if (!isset($fatte[$pgId])) {
                // Non c'era: sceglie l'agente autonomo, in carattere.
                $k = self::agenteAutonomo($pg, $scena['opzioni'], $epId, $scenaN);
                Database::run(
                    'INSERT IGNORE INTO episodio_scelte (episodio_id, scena, personaggio_id, opzione, da_solo)
                     VALUES (?, ?, ?, ?, 1)',
                    [$epId, $scenaN, $pgId, $k]
                );
                $scelta = $k;
            } else {
                $scelta = (string) $fatte[$pgId]['opzione'];
            }
            $o = $opzioni[$scelta] ?? null;
            if ($o === null) {
                continue;
            }

            $riuscita = self::tira($pg, $o, $epId, $scenaN);
            $racconto = $riuscita ? (string) $o['ok'] : (string) ($o['ko'] ?: $o['ok']);
            Database::run(
                'UPDATE episodio_scelte SET riuscita = ?, racconto = ?
                 WHERE episodio_id = ? AND scena = ? AND personaggio_id = ?',
                [$riuscita ? 1 : 0, mb_substr($racconto, 0, 255), $epId, $scenaN, $pgId]
            );
            self::applicaEffetti($pg, $cast,
                $riuscita ? ($o['effetti_ok'] ?? []) : ($o['effetti_ko'] ?? []),
                (string) $ep['luogo'], $gts);
        }

        // Avanti con la prossima scena, o si chiude.
        $tutte = json_decode((string) $ep['scene'], true);
        if ($scenaN + 1 >= count($tutte)) {
            self::concludi($epId);
            return true;
        }
        $finestra = (int) (Database::first('SELECT finestra FROM copioni WHERE ckey = ?',
            [(string) $ep['ckey']])['finestra'] ?? 1800);
        Database::run(
            'UPDATE episodi SET scena = scena + 1, scena_gts = ?, scade_reale = ? WHERE id = ?',
            [$gts, Orologio::adessoReale() + $finestra, $epId]
        );
        return true;
    }

    private static function tira(array $pg, array $o, int $epId, int $scena): bool
    {
        $prova = (string) ($o['prova'] ?? 'nessuna');
        if ($prova === 'nessuna' || $prova === '') {
            return true;
        }
        $valore = (int) ($pg[$prova] ?? 0);
        // Le quattro abilità principali arrivano a 15, le secondarie a 10:
        // il moltiplicatore le porta sulla stessa scala percentuale.
        $passo = in_array($prova, Scheda::ABILITA, true) ? 4 : 6;
        $prob  = max(5, min(95, (int) $o['difficolta'] + $valore * $passo - 20));
        $rng   = Rng::for(GameConfig::int('world.seed', 19870406), 'episodio',
            $epId, $scena, (int) $pg['id']);
        return $rng->int(1, 100) <= $prob;
    }

    /**
     * Sceglie per chi non c'è.
     *
     * Ogni opzione porta dei pesi («questa è una scelta da gente di Cuore»);
     * il punteggio è il peso moltiplicato per quanto il personaggio ha di
     * quella cosa. Chi ha Cuore 12 dirà la verità anche se il giocatore è a
     * cena; chi ha Cuore 3 cambierà discorso. Non è un ripiego: è il
     * personaggio che continua a esistere.
     *
     * @param list<array<string,mixed>> $opzioni
     */
    public static function agenteAutonomo(array $pg, array $opzioni, int $epId = 0, int $scena = 0): string
    {
        $migliore = null;
        $punteggioMigliore = -INF;
        $rng = Rng::for(GameConfig::int('world.seed', 19870406), 'agente', $epId, $scena, (int) $pg['id']);

        foreach ($opzioni as $o) {
            $p = 0.0;
            foreach ((array) ($o['peso'] ?? []) as $campo => $quanto) {
                $p += (float) $quanto * (float) ($pg[$campo] ?? 0);
            }
            // Un pizzico di imprevedibilità: le persone non sono formule.
            $p += $rng->range(-6.0, 6.0);
            if ($p > $punteggioMigliore) {
                $punteggioMigliore = $p;
                $migliore = (string) $o['k'];
            }
        }
        return $migliore ?? (string) $opzioni[0]['k'];
    }

    /**
     * @param list<array<string,mixed>> $cast
     * @param array<string,int> $eff
     */
    private static function applicaEffetti(array $pg, array $cast, array $eff, string $lkey, int $gts): void
    {
        $id = (int) $pg['id'];
        foreach ($eff as $che => $quanto) {
            $quanto = (int) $quanto;
            match ($che) {
                // I legami si muovono in entrambi i versi con tutto il cast:
                // quello che si fa in una scena lo vedono tutti quelli che
                // c'erano, ed è il motivo per cui gli episodi legano le persone.
                'affetto_cast', 'malinteso_cast' => (function () use ($cast, $id, $che, $quanto, $gts): void {
                    foreach ($cast as $altro) {
                        if ((int) $altro['id'] === $id) {
                            continue;
                        }
                        $a = $che === 'affetto_cast' ? $quanto : 0;
                        $f = $che === 'malinteso_cast' ? $quanto : 0;
                        Legami::muovi((int) $altro['id'], $id, $a, $f, $gts);
                        Legami::muovi($id, (int) $altro['id'], (int) round($a / 2), 0, $gts);
                    }
                })(),
                'compostezza' => Database::run(
                    'UPDATE personaggi SET compostezza = GREATEST(0, LEAST(compostezza_max, compostezza + ?))
                     WHERE id = ?', [$quanto, $id]),
                'pf' => Database::run(
                    'UPDATE personaggi SET pf = GREATEST(0, LEAST(pf_max, pf + ?)) WHERE id = ?',
                    [$quanto, $id]),
                'pp' => Database::run(
                    'UPDATE personaggi SET pp = GREATEST(0, LEAST(pp_max, pp + ?)) WHERE id = ?',
                    [$quanto, $id]),
                'calore' => $quanto >= 0
                    ? Segreto::alzaCalore($lkey, $quanto, $gts)
                    : Segreto::alzaCalore($lkey, 0, $gts),
                default => null,
            };
        }
    }

    // --- La fine ------------------------------------------------------------------

    public static function concludi(int $epId): void
    {
        $ep = Database::first('SELECT * FROM episodi WHERE id = ?', [$epId]);
        if ($ep === null || (string) $ep['stato'] === 'concluso') {
            return;
        }
        $gts  = Orologio::lineare();
        $cast = self::cast($epId);

        $scelte = Database::all(
            'SELECT * FROM episodio_scelte WHERE episodio_id = ? ORDER BY scena, personaggio_id', [$epId]);
        $riuscite = count(array_filter($scelte, static fn (array $s): bool => (int) $s['riuscita'] === 1));
        $assenti  = count(array_filter($scelte, static fn (array $s): bool => (int) $s['da_solo'] === 1));

        Database::run(
            'UPDATE episodi SET stato = ?, chiuso_gts = ?, esito = ? WHERE id = ?',
            ['concluso', $gts,
             json_encode(['scelte' => count($scelte), 'riuscite' => $riuscite, 'assenti' => $assenti],
                 JSON_UNESCAPED_UNICODE),
             $epId]
        );

        // Un ricordo per ciascuno, con dentro le proprie scelte: è la propria
        // versione della serata, non un verbale condiviso.
        foreach ($cast as $membro) {
            $pgId = (int) $membro['id'];
            $utente = Database::first('SELECT user_id FROM personaggi WHERE id = ?', [$pgId]);
            // Gli abitanti canonici partecipano agli episodi ma non tengono
            // un album: non hanno un utente a cui intestarlo. Il controllo
            // guardava la riga e non il valore, e da quando esistono i PNG
            // (F6) la INSERT falliva sul vincolo verso `users` — dentro il
            // battito, che quindi si piantava tutto.
            if ($utente === null || $utente['user_id'] === null) {
                continue;
            }
            $mie = array_values(array_filter($scelte,
                static fn (array $s): bool => (int) $s['personaggio_id'] === $pgId));
            $righe = [];
            foreach ($mie as $s) {
                $righe[] = trim((string) $s['racconto'])
                    . ((int) $s['da_solo'] === 1 ? ' (non c\'eri: hai fatto quello che avresti fatto.)' : '');
            }
            $altri = array_values(array_filter(array_map(
                static fn (array $c): string => $c['cognome'] . ' ' . $c['nome'],
                $cast), static fn (string $n): bool => $n !== $membro['cognome'] . ' ' . $membro['nome']));

            Database::run(
                'INSERT INTO ricordi (personaggio_id, user_id, episodio_id, titolo, testo, gts, luogo)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$pgId, (int) $utente['user_id'], $epId, (string) $ep['titolo'],
                 ($altri !== [] ? 'Con ' . implode(', ', $altri) . ".\n\n" : '') . implode("\n\n", $righe),
                 $gts, (string) $ep['luogo']]
            );
        }

        Personaggio::traccia((string) $ep['luogo'], null, 'episodio',
            'Qui è successa una cosa, poco fa, e chi c\'era se la ricorderà.', $gts, 50);
    }

    /** Lo chiama il battito: chiude le scene la cui finestra è scaduta. */
    public static function scadute(): int
    {
        $n = 0;
        foreach (Database::all(
            "SELECT id FROM episodi WHERE stato = 'aperto' AND scade_reale <= ?",
            [Orologio::adessoReale()]
        ) as $e) {
            if (self::forseRisolvi((int) $e['id'], true)) {
                $n++;
            }
        }
        return $n;
    }

    /** @return list<array<string,mixed>> */
    public static function ricordi(int $userId, int $quanti = 50): array
    {
        return Database::all(
            'SELECT r.*, p.nome, p.cognome FROM ricordi r
             LEFT JOIN personaggi p ON p.id = r.personaggio_id
             WHERE r.user_id = ? ORDER BY r.gts DESC LIMIT ?',
            [$userId, $quanti]
        );
    }
}
