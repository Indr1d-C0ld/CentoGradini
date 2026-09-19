<?php

declare(strict_types=1);

namespace App\Cli;

use App\Core\Database;
use App\Core\GameConfig;
use App\Game\Scheda;
use App\Game\Segreto;
use App\Sim\Folla;
use App\Sim\Luoghi;
use App\Sim\Meteo;
use App\Sim\Orologio;

/**
 * Il rapporto di bilanciamento.
 *
 * Serve a rispondere alle domande che una prova unitaria non sa porre. Una
 * prova dice «la vistosità morde»; questo dice *quanto*, e cioè se un esper
 * prudente riesce davvero a campare o se traslocherà comunque entro un mese
 * di gioco. Sono cose che si vedono solo su grandi numeri.
 *
 * **Usa il codice vero, non una sua copia.** I personaggi di prova vengono
 * creati davvero e cancellati subito dopo, perché una riscrittura delle
 * formule qui dentro divergerebbe dal gioco alla prima modifica e il rapporto
 * comincerebbe a mentire senza che nessuno se ne accorga. Il prezzo è che gli
 * id auto-incrementali avanzano: è innocuo, ma va detto, ed è il motivo per
 * cui il comando vuole una conferma esplicita.
 */
final class Bilancio
{
    private const COGNOME = 'Zzbilancio';

    /** @var list<string> */
    private array $righe = [];

    /** @var list<string> finalizzazioni rifiutate, che falserebbero il campione */
    private array $falliti = [];

    public function __construct(private int $quanti = 400)
    {
        $this->quanti = max(20, min(5000, $quanti));
    }

    /** @return list<string> */
    public function esegui(): array
    {
        $this->righe = [];
        $schede = $this->generaSchede();
        try {
            $this->sezioneSchede($schede);
            $this->sezionePoteri($schede);
            $this->sezioneVistosita();
            $this->sezioneFolla();
            $this->sezioneTrasloco($schede);
        } finally {
            Database::run('DELETE FROM personaggi WHERE cognome = ?', [self::COGNOME]);
        }
        return $this->righe;
    }

    // --- Raccolta ---------------------------------------------------------------

    /**
     * Crea, legge e butta via. Metà esper e metà no: non è la proporzione del
     * mondo, è il campione che serve per guardarli separatamente.
     *
     * @return list<array<string,mixed>>
     */
    private function generaSchede(): array
    {
        Database::run('DELETE FROM personaggi WHERE cognome = ?', [self::COGNOME]);
        $out = [];
        for ($i = 0; $i < $this->quanti; $i++) {
            Database::run(
                'INSERT INTO personaggi (user_id, nome, cognome, sesso, sezione, anno, scheda, stato,
                     luogo, anno_nascita, nato_mese, nato_giorno, esper)
                 VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                ['B' . $i, self::COGNOME, $i % 2 === 0 ? 'm' : 'f', 'superiori', 2,
                 'abbozzo', 'attivo', 'casa_kasuga', 1970, 5, 5, $i % 2]
            );
            $id = (int) Database::lastInsertId();
            $pg = Database::first('SELECT * FROM personaggi WHERE id = ?', [$id]);
            Scheda::assicuraTiri($pg);

            $pg  = Database::first('SELECT * FROM personaggi WHERE id = ?', [$id]);
            $res = Scheda::finalizza($pg, $this->distribuisci($pg, $i));
            if (!($res['ok'] ?? false)) {
                // Se la finalizzazione fallisce, il campione resta a zero e
                // il rapporto mente senza dirlo. Meglio fermarsi.
                $this->falliti[] = (string) ($res['error'] ?? 'motivo ignoto');
            }
            $out[] = Database::first('SELECT * FROM personaggi WHERE id = ?', [$id]);
        }
        return $out;
    }

    /**
     * Distribuisce i punti abilità in modo **legale**: nessuna abilità può
     * superare `pg.abilita_max`, e i punti vanno spesi tutti.
     *
     * Il primo tentativo di questo rapporto li scaricava tutti su una sola
     * abilità a rotazione, per coprire i casi estremi. Sbagliato: sforava il
     * tetto, `finalizza()` rifiutava, e le schede restavano a zero — con il
     * risultato che il rapporto dichiarava punti ferita mediani a zero e dava
     * la colpa al gioco. Si riempie a giri, partendo da un'abilità diversa
     * ogni volta, così il campione resta vario senza diventare illegale.
     *
     * @param array<string,mixed> $pg
     * @return array<string,int>
     */
    private function distribuisci(array $pg, int $i): array
    {
        $max    = GameConfig::int('pg.abilita_max', 15);
        $quali  = ['rissa', 'testa', 'dai_suki', 'cuore'];
        $restano = Scheda::puntiDaDistribuire($pg);
        $punti  = array_fill_keys($quali, 0);

        $giro = 0;
        while ($restano > 0 && $giro < 400) {
            $a = $quali[($i + $giro) % 4];
            if ((int) $pg[$a] + $punti[$a] < $max) {
                $punti[$a]++;
                $restano--;
            }
            $giro++;
        }
        return $punti;
    }

    // --- Sezioni -----------------------------------------------------------------

    /** @param list<array<string,mixed>> $schede */
    private function sezioneSchede(array $schede): void
    {
        $this->titolo('Le schede', sprintf('%d personaggi generati col codice vero', count($schede)));

        if ($this->falliti !== []) {
            $this->nota(sprintf('!! %d schede su %d non si sono chiuse — il campione è falsato.',
                count($this->falliti), count($schede)));
            $this->riga('       primo motivo: ' . $this->falliti[0]);
        }

        foreach (['pf' => 'Punti ferita', 'compostezza' => 'Compostezza'] as $campo => $nome) {
            $v = array_map(static fn (array $p): int => (int) $p[$campo], $schede);
            $this->distribuzione($nome, $v);
        }
        $pp = array_map(static fn (array $p): int => (int) $p['pp'],
            array_filter($schede, static fn (array $p): bool => (int) $p['esper'] === 1));
        $this->distribuzione('Punti potere (esper)', array_values($pp));

        // Il regolamento del 1990 dichiara 7-11 per i PP. Se il minimo esce 6
        // vuol dire che la divisione è tornata a troncare.
        $min = $pp === [] ? 0 : min($pp);
        $max = $pp === [] ? 0 : max($pp);
        $this->nota($min >= 7 && $max <= 11
            ? "PP dentro il 7-11 dichiarato dal regolamento. Va bene."
            : "!! PP fuori dal 7-11 dichiarato: trovato {$min}-{$max}.");

        $tratti = [];
        foreach ($schede as $p) {
            $tratti[] = count(Scheda::tratti((int) $p['id']));
        }
        $this->distribuzione('Tratti a testa', $tratti);
        // Il tetto è «quanti se ne tirano, più tre»: il ritiro può aggiungere
        // tratti, ma non all'infinito. Va letto dalla configurazione, non
        // indovinato — la prima versione di questo rapporto aveva scritto 6 a
        // mano e denunciava come difetto un 7 perfettamente regolare.
        $tetto = max(GameConfig::int('pg.tratti', 3), GameConfig::int('pg.tratti_umano', 4)) + 3;
        $this->nota(max($tratti) <= $tetto
            ? sprintf('Il tetto sui tratti regge: %d su un massimo di %d.', max($tratti), $tetto)
            : sprintf('!! Qualcuno ha %d tratti su un tetto di %d.', max($tratti), $tetto));
    }

    /** @param list<array<string,mixed>> $schede */
    private function sezionePoteri(array $schede): void
    {
        $esper = array_values(array_filter($schede, static fn (array $p): bool => (int) $p['esper'] === 1));
        $this->titolo('I poteri', sprintf('su %d esper', count($esper)));

        $primari = $quanti = [];
        $conta   = [];
        foreach ($esper as $p) {
            $poteri = Scheda::poteri((int) $p['id']);
            $quanti[] = count($poteri);
            foreach ($poteri as $pot) {
                $conta[(string) $pot['pkey']] = ($conta[(string) $pot['pkey']] ?? 0) + 1;
                if ((int) $pot['primario'] === 1) {
                    $primari[(string) $pot['pkey']] = ($primari[(string) $pot['pkey']] ?? 0) + 1;
                }
            }
        }
        $this->distribuzione('Poteri a testa', $quanti);
        $this->nota(max($quanti) <= 7
            ? 'Il tetto sui poteri regge: al massimo ' . max($quanti) . '.'
            : '!! Qualcuno ha ' . max($quanti) . ' poteri: il tetto non morde.');

        arsort($primari);
        $tot = max(1, count($esper));
        $this->riga('');
        $this->riga('  Potere primario:');
        foreach ($primari as $k => $n) {
            $this->riga(sprintf('    %-16s %5.1f%%  %s', $k, 100 * $n / $tot, $this->barra($n / $tot)));
        }

        // La telepatia deve restare rara: è la regola che ha sostituito
        // l'esclusione reciproca ritirata il 19/09/2026.
        $tele = 100 * ($primari['telepatia'] ?? 0) / $tot;
        $this->nota($tele <= 12
            ? sprintf('Telepatia primaria al %.1f%%: resta rara, come dev\'essere.', $tele)
            : sprintf('!! Telepatia primaria al %.1f%%: troppo comune.', $tele));

        arsort($conta);
        $this->riga('');
        $this->riga('  Poteri posseduti (primari e secondari):');
        foreach (array_slice($conta, 0, 8, true) as $k => $n) {
            $this->riga(sprintf('    %-16s %5.1f%%  %s', $k, 100 * $n / $tot, $this->barra($n / $tot)));
        }
    }

    /**
     * Quanto morde la vistosità, condizione per condizione. È la tabella che
     * un giocatore vorrebbe avere e che il gioco non gli dà: qui serve a noi
     * per vedere se esiste davvero un modo prudente di usare i poteri.
     */
    private function sezioneVistosita(): void
    {
        $this->titolo('Farsi notare', 'probabilità che un testimone se ne accorga, in percento');

        // Un istante diurno e asciutto, e uno notturno, cercati sul calendario
        // invece che calcolati: l'ora che conta è quella di Tokyo.
        [$giorno, $notte] = $this->dueIstanti();

        $poteri = Database::all('SELECT pkey, nome, vistosita FROM poteri ORDER BY vistosita DESC, pkey');
        $this->riga(sprintf('    %-16s %5s  %6s %6s %6s %6s',
            'potere', 'vist.', 'sveglio', 'medio', 'notte', 'esperto'));
        foreach ($poteri as $p) {
            $v = (int) $p['vistosita'];
            $this->riga(sprintf('    %-16s %5d  %6d %6d %6d %6d',
                $p['pkey'], $v,
                Segreto::probabilitaNota($v, 15, 0, $giorno),
                Segreto::probabilitaNota($v, 8, 0, $giorno),
                Segreto::probabilitaNota($v, 8, 0, $notte),
                Segreto::probabilitaNota($v, 8, 100, $giorno)));
        }
        $this->nota('«esperto» è Controllo 100, che è il massimo: il Controllo dimezza, non annulla.');
    }

    private function sezioneFolla(): void
    {
        $this->titolo('Quanta gente c\'è', 'passanti medi per luogo, su un giro d\'anno');

        [$giorno] = $this->dueIstanti();
        $righe = [];
        foreach (Luoghi::tutti() as $lkey => $l) {
            $somma = $picco = 0;
            $n = 0;
            for ($g = 0; $g < 366; $g += 7) {
                for ($h = 8; $h <= 22; $h += 7) {
                    $t = $giorno + $g * 86400 + $h * 3600;
                    $q = Folla::a($lkey, $t);
                    $somma += $q;
                    $picco = max($picco, $q);
                    $n++;
                }
            }
            $righe[] = [$lkey, $somma / max(1, $n), $picco];
        }
        usort($righe, static fn (array $a, array $b): int => $b[1] <=> $a[1]);
        $max = max(array_map(static fn (array $r): float => (float) $r[1], $righe)) ?: 1.0;
        foreach ($righe as [$lkey, $media, $picco]) {
            $this->riga(sprintf('    %-14s media %5.1f  picco %3d  %s',
                $lkey, $media, $picco, $this->barra($media / $max)));
        }
        $this->nota('Un luogo con media zero è un posto dove si può fare qualunque cosa: '
            . 'devono essercene pochi, e devono essere scomodi da raggiungere.');
    }

    /**
     * La domanda che conta: quanti usi imprudenti servono per essere
     * costretti a traslocare?
     *
     * @param list<array<string,mixed>> $schede
     */
    private function sezioneTrasloco(array $schede): void
    {
        $servono  = GameConfig::int('segreto.anomalie_per_sospetto', 3);
        $soglia   = GameConfig::int('segreto.sospetti_per_trasloco', 2);
        $this->titolo('Il Trasloco', 'la penalità vera del gioco');

        $this->riga(sprintf('    annotazioni per un sospetto fondato : %d', $servono));
        $this->riga(sprintf('    sospetti fondati distinti per traslocare : %d', $soglia));

        [$giorno, $notte] = $this->dueIstanti();
        // Un teletrasporto (vistosità 9) davanti a un testimone sveglio, di
        // giorno: è il caso peggiore, e serve a dare l'ordine di grandezza.
        $p = Segreto::probabilitaNota(9, 15, 0, $giorno) / 100.0;
        $this->riga('');
        $this->riga(sprintf('    Un teletrasporto davanti a un testimone sveglio, di giorno,'));
        $this->riga(sprintf('    viene notato il %.0f%% delle volte.', $p * 100));
        if ($p > 0) {
            $this->riga(sprintf('    Per accumulare %d annotazioni su di te servono in media', $servono));
            $this->riga(sprintf('    %.1f usi; e perché %d persone diverse arrivino a un sospetto', $servono / $p, $soglia));
            $this->riga(sprintf('    fondato, nell\'ordine di %.0f usi imprudenti.', $servono / $p * $soglia));
        }
        $pn = Segreto::probabilitaNota(9, 8, 60, $notte) / 100.0;
        $this->riga('');
        $this->riga(sprintf('    Lo stesso teletrasporto di notte, con Controllo 60 e un testimone'));
        $this->riga(sprintf('    distratto: %.0f%%. Fra prudenza e imprudenza ci sono %.1f volte.',
            $pn * 100, $pn > 0 ? $p / $pn : 0));
        $this->nota('Se questo rapporto scende sotto il doppio, la prudenza non paga '
            . 'abbastanza e tanto vale usare i poteri in piazza.');
    }

    // --- Utilità -----------------------------------------------------------------

    /**
     * Un istante diurno e asciutto e uno notturno, cercati scorrendo il
     * calendario. Calcolarli a mano dall'epoca sarebbe un modo elegante di
     * sbagliare: l'ora che conta è quella di Tokyo.
     *
     * @return array{0:int, 1:int}
     */
    private function dueIstanti(): array
    {
        $base = Orologio::lineare();
        $giorno = $notte = null;
        for ($h = 0; $h < 24 * 14 && ($giorno === null || $notte === null); $h++) {
            $t = $base + $h * 3600;
            $m = Meteo::a($t);
            if ($m['pioggia'] > 0.0 || $m['tifone']) {
                continue;
            }
            if (!Meteo::notte($t) && $giorno === null) {
                $giorno = $t;
            }
            if (Meteo::notte($t) && $notte === null) {
                $notte = $t;
            }
        }
        return [$giorno ?? $base, $notte ?? $base];
    }

    /** @param list<int> $valori */
    private function distribuzione(string $nome, array $valori): void
    {
        if ($valori === []) {
            $this->riga(sprintf('    %-22s (nessun dato)', $nome));
            return;
        }
        sort($valori);
        $n = count($valori);
        $media = array_sum($valori) / $n;
        $this->riga(sprintf('    %-22s min %2d  mediana %2d  media %5.2f  max %2d',
            $nome, $valori[0], $valori[intdiv($n, 2)], $media, $valori[$n - 1]));
    }

    private function barra(float $frazione): string
    {
        return str_repeat('█', max(0, min(30, (int) round($frazione * 30))));
    }

    private function titolo(string $t, string $sottotitolo = ''): void
    {
        $this->riga('');
        $this->riga('  ' . mb_strtoupper($t) . ($sottotitolo !== '' ? "  — {$sottotitolo}" : ''));
        $this->riga('  ' . str_repeat('─', 68));
    }

    private function nota(string $t): void
    {
        $this->riga('');
        $this->riga('    ' . $t);
    }

    private function riga(string $t): void
    {
        $this->righe[] = $t;
    }
}
