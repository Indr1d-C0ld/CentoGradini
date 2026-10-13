<?php

declare(strict_types=1);

namespace App\Cli;

use App\Core\Database;
use App\Core\GameConfig;
use App\Game\Abitanti;
use App\Sim\Orologio;

/**
 * Il mondo da capo: «tutti cominciano adesso».
 *
 * Lo ha chiesto l'utente il 13 ottobre 2026, alla fine del controllo globale:
 * con la revisione nuova si riparte tutti insieme. Si tengono gli **account**
 * e i **personaggi** — nome, scheda, tratti, poteri, fotografia — e si azzera
 * tutto quello che e' successo: legami, voci, ricordi, episodi, anomalie, chi
 * sa cosa, incidenti, tracce, presenze, calore, stati, club, bacheca,
 * biglietti, i passaggi degli oggetti unici. Il calendario riparte dal primo
 * giorno di scuola, il 6 aprile 1987 alle sette di mattina.
 *
 * Restano anche le cose che non sono gioco: le comunicazioni con la gestione,
 * la coda della posta, i registri tecnici, le manopole.
 *
 * **L'ordine conta.** Prima si sposta l'epoca, poi si cancella, poi si
 * rimettono a posto i personaggi con l'istante nuovo: ogni `*_gts` che
 * restasse indietro sarebbe nel futuro del calendario nuovo, e un riposo, una
 * pausa fra episodi o un orologio scarico durerebbero settimane.
 */
final class Ricomincia
{
    /** Le tabelle di quello che e' successo. Nell'ordine in cui le chiavi esterne le lasciano cancellare. */
    public const TABELLE = [
        'episodio_scelte', 'episodio_cast', 'episodi',
        'incidente_testimoni', 'incidenti',
        'gesti_letture', 'gesti_fatti',
        'voci_versioni', 'voci',
        'anomalie', 'sanno', 'legami',
        'oggetti_passaggi', 'presenze', 'ricordi', 'tracce',
        'calore', 'stati', 'club_membri', 'bacheca', 'biglietti',
    ];

    /** Il Controllo con cui nasce un potere: quello dei giocatori, e quello dei canonici. */
    private const CONTROLLO_GIOCATORI = 10;
    private const CONTROLLO_CANONICI  = 55;

    /** @return array<string,int> quante righe per tabella, e quanti personaggi rimessi a posto */
    public static function esegui(?int $adessoReale = null): array
    {
        $adessoReale ??= time();
        $conti = [];

        // 1. L'epoca: da adesso, nel quartiere, sono le 7 del 6 aprile 1987.
        GameConfig::set('clock.epoch_real', (string) $adessoReale);
        $g = Orologio::lineare($adessoReale);

        // 2. Quello che e' successo.
        foreach (self::TABELLE as $t) {
            $conti[$t] = Database::run('DELETE FROM ' . $t)->rowCount();
        }

        // 3. Gli oggetti unici, ciascuno a casa sua.
        Database::run('UPDATE oggetti_unici SET detentore_id = NULL, luogo = COALESCE(dimora, ?), gts = ?, pronto_gts = NULL',
            ['gradini', $g]);

        // 4. I giocatori: in cima ai gradini, riposati, come il primo giorno.
        //    Chi era via per un trasloco torna: si ricomincia tutti.
        $conti['giocatori'] = Database::run(
            "UPDATE personaggi SET stato = 'attivo', luogo = 'gradini', verso = NULL, arrivo_gts = NULL,
                    arrivato_gts = ?, visto_gts = ?, ristoro_gts = ?, ultimo_trasloco_gts = NULL, traslochi = 0,
                    pp = pp_max, pf = pf_max, compostezza = compostezza_max
             WHERE png IS NULL",
            [$g, $g, $g]
        )->rowCount();
        Database::run(
            "INSERT INTO presenze (personaggio_id, luogo, dal_gts)
             SELECT id, 'gradini', ? FROM personaggi WHERE png IS NULL AND stato = 'attivo'",
            [$g]
        );
        Database::run(
            'UPDATE personaggio_poteri pp JOIN personaggi p ON p.id = pp.personaggio_id
             SET pp.controllo = ?, pp.usi = 0 WHERE p.png IS NULL',
            [self::CONTROLLO_GIOCATORI]
        );

        // 5. Gli abitanti: riposati, al loro posto, con i legami del canone.
        $conti['abitanti'] = Database::run(
            "UPDATE personaggi SET stato = 'attivo', verso = NULL, arrivo_gts = NULL,
                    arrivato_gts = ?, visto_gts = ?, ristoro_gts = ?,
                    pp = pp_max, pf = pf_max, compostezza = compostezza_max
             WHERE png IS NOT NULL",
            [$g, $g, $g]
        )->rowCount();
        Database::run(
            'UPDATE personaggio_poteri pp JOIN personaggi p ON p.id = pp.personaggio_id
             SET pp.controllo = ?, pp.usi = 0 WHERE p.png IS NOT NULL',
            [self::CONTROLLO_CANONICI]
        );
        Abitanti::assicura();
        Abitanti::muovi($g);

        return $conti;
    }
}
