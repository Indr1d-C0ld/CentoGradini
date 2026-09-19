<?php

declare(strict_types=1);

namespace App\Game;

use App\Core\Database;
use App\Sim\Luoghi;
use App\Sim\Orologio;
use App\Sim\Scuola;

/**
 * L'esportazione del diario.
 *
 * Un gioco persistente che non ti lascia portare via niente è un gioco che
 * ti chiede di fidarti che il server campi per sempre. Questo mette tutto
 * quello che è successo a un personaggio in un file di testo che si legge
 * anche fra vent'anni, senza il gioco e senza il database.
 *
 * Il formato è **Markdown semplice**: si legge com'è, in un blocco note, e si
 * apre bene ovunque. Niente JSON — non è un backup per una macchina, è un
 * diario per una persona.
 *
 * Un solo criterio su *cosa* ci finisce: quello che il personaggio **sa**.
 * Le voci ci vanno nella versione che gli è arrivata, deformazioni comprese,
 * non nella verità del database; i biglietti che non ha ancora raccolto non
 * ci sono. Il diario è suo, non è il registro del mondo.
 */
final class Diario
{
    public static function nomeFile(array $pg): string
    {
        $base = mb_strtolower(trim((string) $pg['nome'] . '-' . (string) $pg['cognome']));
        $base = preg_replace('/[^a-z0-9]+/u', '-', $base) ?? 'diario';
        return 'cento-gradini-' . trim($base, '-') . '.md';
    }

    public static function componi(array $pg): string
    {
        $id  = (int) $pg['id'];
        $out = [];

        $out[] = self::intestazione($pg);
        $out[] = self::laScheda($pg);
        $out[] = self::iPoteri($pg);
        $out[] = self::iLegami($id);
        $out[] = self::iRicordi($id);
        $out[] = self::ilSegreto($id);
        $out[] = self::leVoci($id);
        $out[] = self::laCorrispondenza($id);
        $out[] = self::congedo();

        return implode("\n", array_filter($out, static fn (string $s): bool => trim($s) !== ''));
    }

    // --- Pezzi -------------------------------------------------------------------

    private static function intestazione(array $pg): string
    {
        $eta = Scuola::eta((int) $pg['anno_nascita'], (int) $pg['nato_mese'], (int) $pg['nato_giorno']);
        $r = [];
        $r[] = '# ' . Personaggio::nomeCompleto($pg);
        $r[] = '';
        $r[] = '> Diario di *Cento Gradini*, gioco di ruolo nell\'universo di';
        $r[] = '> **Kimagure Orange Road** di Izumi Matsumoto.';
        $r[] = '> Esportato il ' . Orologio::esteso() . ', ora del quartiere.';
        $r[] = '';
        $r[] = sprintf('%s, %d anni, %s. Nato il %d/%d/%d.',
            (string) $pg['sesso'] === 'f' ? 'Ragazza' : 'Ragazzo',
            $eta,
            Scuola::nomeClasse((string) $pg['sezione'], (int) $pg['anno']),
            (int) $pg['nato_giorno'], (int) $pg['nato_mese'], (int) $pg['anno_nascita']);
        if ((string) ($pg['aspetto'] ?? '') !== '') {
            $r[] = '';
            $r[] = (string) $pg['aspetto'];
        }
        if ((string) $pg['stato'] === 'trasferito') {
            $r[] = '';
            $r[] = '**Se n\'è andato.** Il camioncino è arrivato di mattina presto, e dalla curva';
            $r[] = 'in fondo al viale si vedevano ancora i tetti, e poi nemmeno quelli.';
        }
        return implode("\n", $r) . "\n";
    }

    private static function laScheda(array $pg): string
    {
        $r = ['## La scheda', ''];
        foreach (['rissa' => 'Rissa', 'testa' => 'Testa', 'dai_suki' => 'Dai suki', 'cuore' => 'Cuore'] as $k => $n) {
            $r[] = sprintf('- **%s** %d', $n, (int) $pg[$k]);
        }
        $r[] = '';
        $r[] = sprintf('Punti ferita %d/%d · Compostezza %d/%d%s.',
            (int) $pg['pf'], (int) $pg['pf_max'], (int) $pg['compostezza'], (int) $pg['compostezza_max'],
            (int) $pg['esper'] === 1 ? sprintf(' · Punti potere %d/%d', (int) $pg['pp'], (int) $pg['pp_max']) : '');

        $tratti = Scheda::tratti((int) $pg['id']);
        if ($tratti !== []) {
            $r[] = '';
            $r[] = '### Com\'è fatto';
            $r[] = '';
            foreach ($tratti as $t) {
                $r[] = sprintf('- **%s.** %s', (string) $t['nome'], (string) $t['descrizione']);
            }
        }
        return implode("\n", $r) . "\n";
    }

    private static function iPoteri(array $pg): string
    {
        if ((int) $pg['esper'] !== 1) {
            return "## I poteri\n\nNessuno, e non è una mancanza: la metà delle persone che\n"
                . "reggono questa storia non ne ha.\n";
        }
        $r = ['## I poteri', ''];
        foreach (Scheda::poteri((int) $pg['id']) as $p) {
            $r[] = sprintf('- **%s**%s — Controllo %d, usato %d volte.',
                (string) $p['nome'],
                (int) $p['primario'] === 1 ? ' *(primario)*' : '',
                (int) $p['controllo'], (int) $p['usi']);
        }
        return implode("\n", $r) . "\n";
    }

    private static function iLegami(int $id): string
    {
        $righe = Database::all(
            'SELECT l.affetto, l.fraintendimento, p.nome, p.cognome, p.png
             FROM legami l JOIN personaggi p ON p.id = l.a_id
             WHERE l.da_id = ? ORDER BY ABS(l.affetto) DESC, l.fraintendimento DESC',
            [$id]
        );
        if ($righe === []) {
            return '';
        }
        $r = ['## Le persone', '',
              'Quello che prova lui. Quello che provano loro è un\'altra cosa, e non sta qui:',
              'non l\'ha mai saputo con certezza nemmeno lui.', ''];
        foreach ($righe as $l) {
            $r[] = sprintf('- **%s %s**%s — affetto %+d, fraintendimento %d.%s',
                (string) $l['nome'], (string) $l['cognome'],
                $l['png'] !== null ? ' *(del quartiere)*' : '',
                (int) $l['affetto'], (int) $l['fraintendimento'],
                (int) $l['fraintendimento'] >= 50 ? ' *C\'è qualcosa di grosso da chiarire.*' : '');
        }
        return implode("\n", $r) . "\n";
    }

    private static function iRicordi(int $id): string
    {
        $righe = Database::all(
            'SELECT r.titolo, r.testo, r.gts, r.luogo FROM ricordi r
             WHERE r.personaggio_id = ? ORDER BY r.gts',
            [$id]
        );
        if ($righe === []) {
            return "## L'album\n\nVuoto. Si riempie vivendo.\n";
        }
        $r = ['## L\'album dei ricordi', ''];
        foreach ($righe as $ric) {
            $r[] = '### ' . (string) $ric['titolo'];
            $r[] = '';
            $r[] = sprintf('*%s, %s.*', Orologio::esteso((int) $ric['gts']),
                Luoghi::nome((string) $ric['luogo']));
            $r[] = '';
            $r[] = trim((string) $ric['testo']);
            $r[] = '';
        }
        return implode("\n", $r);
    }

    private static function ilSegreto(int $id): string
    {
        $inc = Database::all(
            'SELECT i.gts, i.luogo, i.stato, i.notato_da, p.nome AS potere
             FROM incidenti i LEFT JOIN poteri p ON p.pkey = i.pkey
             WHERE i.attore_id = ? ORDER BY i.gts DESC LIMIT 40',
            [$id]
        );
        $sanno = Database::all(
            'SELECT p.nome, p.cognome, s.come, s.gts FROM sanno s
             JOIN personaggi p ON p.id = s.chi_sa_id WHERE s.esper_id = ? ORDER BY s.gts',
            [$id]
        );
        if ($inc === [] && $sanno === []) {
            return '';
        }
        $r = ['## Il Segreto', ''];
        if ($sanno !== []) {
            $r[] = 'Chi lo sa:';
            $r[] = '';
            foreach ($sanno as $s) {
                $r[] = sprintf('- **%s %s** — %s, dal %s.',
                    (string) $s['nome'], (string) $s['cognome'],
                    (string) $s['come'] === 'confidato' ? 'gliel\'ha detto lui' : 'l\'ha scoperto da solo',
                    Orologio::esteso((int) $s['gts']));
            }
            $r[] = '';
        }
        if ($inc !== []) {
            $r[] = sprintf('Ha usato un potere %d volte. Le ultime:', count($inc));
            $r[] = '';
            foreach (array_slice($inc, 0, 15) as $i) {
                $r[] = sprintf('- %s, %s — %s%s.',
                    Orologio::esteso((int) $i['gts']), Luoghi::nome((string) $i['luogo']),
                    mb_strtolower((string) ($i['potere'] ?? 'qualcosa')),
                    (int) $i['notato_da'] > 0 ? sprintf(', visto da %d', (int) $i['notato_da']) : ', nessuno se n\'è accorto');
            }
        }
        return implode("\n", $r) . "\n";
    }

    private static function leVoci(int $id): string
    {
        $voci = Voci::per($id, 40);
        if ($voci === []) {
            return '';
        }
        $r = ['## Quello che gli è arrivato', '',
              'Le voci come le ha sentite lui, deformazioni comprese. Non sono la verità:',
              'sono quello che gli hanno raccontato, e alcune sono passate per quattro bocche.', ''];
        foreach ($voci as $v) {
            $r[] = sprintf('- %s *(%s)*', (string) $v['testo'],
                $v['vista'] ? 'l\'ha vista' : ($v['da'] !== null ? 'da ' . (string) $v['da'] : 'non ricorda da chi'));
        }
        return implode("\n", $r) . "\n";
    }

    private static function laCorrispondenza(int $id): string
    {
        $letti = Bacheca::letti($id, 60);
        if ($letti === []) {
            return '';
        }
        $r = ['## I biglietti', ''];
        foreach ($letti as $b) {
            $r[] = sprintf('**%s**, %s:',
                (int) $b['anonimo'] === 1 ? 'senza firma'
                    : trim((string) ($b['nome'] ?? '') . ' ' . (string) ($b['cognome'] ?? '')),
                Orologio::esteso((int) $b['gts']));
            $r[] = '';
            foreach (preg_split('/\n\n+/', (string) $b['testo']) ?: [] as $par) {
                $r[] = '> ' . trim($par);
            }
            $r[] = '';
        }
        return implode("\n", $r);
    }

    private static function congedo(): string
    {
        return "---\n\n"
            . "*Cento Gradini* è un progetto amatoriale e senza scopo di lucro.\n"
            . "*Kimagure Orange Road* è di **Izumi Matsumoto** e degli aventi diritto.\n";
    }
}
