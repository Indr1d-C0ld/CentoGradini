<?php
/** @var array<string,mixed> $s_mondo, $popolazione, $segreto, $legami, $voci, $episodi, $quartiere, $macchina */
use App\Sim\Luoghi;
use App\Sim\Orologio;
use App\Sim\Scuola;

$numeri = static function (array $voci): string {
    $out = '<div class="griglia-numeri">';
    foreach ($voci as $nome => $n) {
        $out .= '<div class="numero"><span class="numero-valore">' . e((string) $n)
              . '</span><span class="numero-nome">' . e($nome) . '</span></div>';
    }
    return $out . '</div>';
};
$barra = static function (float $frazione): string {
    $p = max(0.0, min(1.0, $frazione));
    return '<span class="barra"><span style="width:' . round($p * 100, 1) . '%"></span></span>';
};
?>
<h1>Statistiche</h1>
<p class="sommario">
  Non sono un cruscotto di vanità: servono a rispondere alle domande che dicono se il gioco
  sta funzionando. Il Segreto morde davvero? I fraintendimenti si sciolgono mai? Le voci
  arrivano a qualcuno o muoiono dove nascono? Ci sono posti dove non va mai nessuno?
</p>

<div class="carta">
  <h2>Il mondo adesso</h2>
  <p>
    <strong><?= e($s_mondo['quando']) ?></strong> — <?= e($s_mondo['fase']) ?>,
    <?= e($s_mondo['stagione']) ?><?php
      if ($s_mondo['festa'] !== null): ?>, <?= e($s_mondo['festa']) ?><?php endif; ?>.
    <?= e(sprintf('%.1f °C', (float) $s_mondo['meteo']['temperatura'])) ?>,
    pioggia <?= e(sprintf('%.1f', (float) $s_mondo['meteo']['pioggia'])) ?>.
  </p>
  <?php if ($s_mondo['eventi'] !== []): ?>
    <p>In corso: <strong><?= e(implode(', ', array_column($s_mondo['eventi'], 'nome'))) ?></strong>.</p>
  <?php endif; ?>
  <p class="aiuto">
    <?php if ($s_mondo['battito_da'] === null): ?>
      <strong>Il battito non è mai partito.</strong>
    <?php else: ?>
      Ultimo battito <?= (int) $s_mondo['battito_da'] ?> secondi fa
      (<?= (int) $s_mondo['battito_ms'] ?> ms<?= $s_mondo['battito_ok'] ? '' : ', <strong>con errore</strong>' ?>).
      Nelle ultime 24 ore: <?= (int) $macchina['battiti_24h'] ?> battiti,
      <?= (int) $macchina['guasti_24h'] ?> guasti,
      media <?= (int) round((float) ($macchina['durata']['media'] ?? 0)) ?> ms.
    <?php endif; ?>
  </p>
</div>

<div class="carta">
  <h2>La gente</h2>
  <?= $numeri([
      'utenti'     => $popolazione['utenti'],
      'attivi'     => $popolazione['attivi'],
      'collegati'  => $popolazione['collegati'],
      'oggi'       => $popolazione['oggi'],
      'in 7 giorni'=> $popolazione['settimana'],
      'personaggi' => $popolazione['personaggi'],
      'traslocati' => $popolazione['traslocati'],
      'abitanti'   => $popolazione['abitanti'],
  ]) ?>
  <p class="aiuto">
    <?= (int) $popolazione['esper'] ?> esper e <?= (int) $popolazione['non_esper'] ?> non esper
    <?php if ((int) $popolazione['abbozzi'] > 0): ?>
      · <?= (int) $popolazione['abbozzi'] ?> schede da finire
    <?php endif; ?>
    <?php if ((int) $popolazione['fermati'] > 0): ?>
      · <?= (int) $popolazione['fermati'] ?> account fermati
    <?php endif; ?>
    <?php if ($popolazione['per_classe'] !== []): ?>
      ·
      <?php foreach ($popolazione['per_classe'] as $c): ?>
        <?= e(Scuola::nomeClasse((string) $c['sezione'], (int) $c['anno'])) ?>
        <?= (int) $c['n'] ?><?= $c === end($popolazione['per_classe']) ? '' : ',' ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </p>
</div>

<div class="carta">
  <h2>Il Segreto</h2>
  <?= $numeri([
      'usi di poteri' => $segreto['usi'],
      'notati'        => $segreto['notati'],
      'anomalie'      => $segreto['anomalie'],
      'collegate'     => $segreto['collegate'],
      'hanno capito'  => $segreto['scoperti'],
      'confidato a'   => $segreto['confidati'],
      'traslochi'     => $segreto['traslochi'],
  ]) ?>
  <p>
    <strong><?= e((string) $segreto['quota_notati']) ?>%</strong> degli usi viene notato.
    <?= $barra((float) $segreto['quota_notati'] / 100) ?>
  </p>
  <p class="aiuto">
    È la riga che conta. Se scende vicino a zero il gioco perde la tensione; se sale troppo
    nessuno usa più i poteri, e metà del gioco smette di esistere.
  </p>

  <?php if ($segreto['per_potere'] !== []): ?>
    <h3>Per potere</h3>
    <div class="tabella-scorre">
    <table class="tabella">
      <thead><tr><th>potere</th><th>usi</th><th>notati</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($segreto['per_potere'] as $p): ?>
        <tr>
          <td data-etichetta="potere"><?= e((string) ($p['nome'] ?? $p['pkey'])) ?></td>
          <td data-etichetta="usi"><?= (int) $p['n'] ?></td>
          <td data-etichetta="notati"><?= (int) $p['notati'] ?></td>
          <td><?= $barra((int) $p['n'] > 0 ? (int) $p['notati'] / (int) $p['n'] : 0) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  <?php endif; ?>

  <?php if ($segreto['a_rischio'] !== []): ?>
    <p><strong>Vicini al trasloco:</strong>
    <?php foreach ($segreto['a_rischio'] as $i => $a): ?>
      <?= $i > 0 ? ' · ' : '' ?><?= e($a['nome'] . ' ' . $a['cognome']) ?> (<?= (int) $a['sanno'] ?>)
    <?php endforeach; ?></p>
  <?php endif; ?>
</div>

<div class="carta">
  <h2>I legami</h2>
  <?= $numeri([
      'legami'            => $legami['quanti'],
      'affetto medio'     => $legami['affetto'],
      'malinteso medio'   => $legami['malinteso'],
      'affetti positivi'  => $legami['positivi'],
      'affetti negativi'  => $legami['negativi'],
      'malintesi grossi'  => $legami['grossi'],
      'il peggiore'       => $legami['peggiore'],
  ]) ?>
  <p class="aiuto">
    «Malintesi grossi» sono quelli sopra cinquanta. Il fraintendimento <strong>non decade col
    tempo</strong>: se questo numero cresce e non scende mai, vuol dire che nessuno sta
    chiarendo — e il quartiere si sta ingolfando.
  </p>
  <?php if ($legami['gesti'] !== []): ?>
    <h3>I gesti</h3>
    <div class="tabella-scorre">
    <table class="tabella">
      <thead><tr><th>gesto</th><th>fatti</th><th>riusciti</th><th>visti da</th></tr></thead>
      <tbody>
      <?php foreach ($legami['gesti'] as $g): ?>
        <tr>
          <td data-etichetta="gesto"><?= e((string) ($g['nome'] ?? $g['gkey'])) ?></td>
          <td data-etichetta="fatti"><?= (int) $g['n'] ?></td>
          <td data-etichetta="riusciti"><?= (int) $g['riusciti'] ?></td>
          <td data-etichetta="visti da"><?= (int) $g['visti'] ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  <?php endif; ?>
</div>

<div class="carta">
  <h2>Le voci</h2>
  <?= $numeri([
      'fatti'            => $voci['fatti'],
      'versioni in giro' => $voci['versioni'],
      'precisione media' => $voci['precisione'],
      'passaggi medi'    => $voci['passaggi'],
      'più passata'      => $voci['max_passaggi'],
      'pettegolezzi'     => $voci['pettegolezzi'],
  ]) ?>
  <p class="aiuto">
    Se i passaggi medi restano a zero la propagazione non sta funzionando, e le voci sono
    solo un registro di fatti. Il bello comincia dal secondo passaggio, quando il racconto
    non somiglia più a quello che è successo. «Pettegolezzi» sono le versioni oltre il terzo.
  </p>
  <?php if ($voci['per_tipo'] !== []): ?>
    <div class="tabella-scorre">
    <table class="tabella">
      <thead><tr><th>tipo</th><th>fatti</th><th>versioni</th></tr></thead>
      <tbody>
      <?php foreach ($voci['per_tipo'] as $t): ?>
        <tr><td data-etichetta="tipo"><?= e($t['tipo']) ?></td><td data-etichetta="fatti"><?= (int) $t['fatti'] ?></td><td data-etichetta="versioni"><?= (int) $t['versioni'] ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  <?php endif; ?>
</div>

<div class="carta">
  <h2>Gli episodi</h2>
  <?= $numeri([
      'aperti'        => $episodi['aperti'],
      'conclusi'      => $episodi['conclusi'],
      'scelte fatte'  => $episodi['scelte'],
      'dal motore'    => $episodi['da_agente'],
      '% dal motore'  => $episodi['quota_agente'],
      '% riuscite'    => $episodi['quota_riuscite'],
      'ricordi'       => $episodi['ricordi'],
  ]) ?>
  <p class="aiuto">
    «Dal motore» sono le scelte fatte dall'agente autonomo per chi non si è fatto vivo entro
    la finestra. Se la percentuale è altissima, gli episodi si stanno giocando da soli.
  </p>
  <?php if ($episodi['per_copione'] !== []): ?>
    <?php foreach ($episodi['per_copione'] as $c): ?>
      <p class="riga-membro"><strong><?= (int) $c['n'] ?>×</strong>
        <?= e((string) ($c['titolo'] ?? $c['ckey'])) ?></p>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<div class="carta">
  <h2>Il quartiere</h2>
  <p class="aiuto">
    Visite di tutti i tempi, e quanta gente c'è adesso.
    <?php if ((int) $quartiere['mai_visti'] > 0): ?>
      <strong><?= (int) $quartiere['mai_visti'] ?></strong> luoghi non li ha mai visitati
      nessuno: qualcuno va bene — la montagna è lontana apposta — ma se sono tanti vuol dire
      che la mappa è più grande di quanto il gioco riesca a riempire.
    <?php endif; ?>
  </p>
  <div class="tabella-scorre">
  <table class="tabella">
    <thead><tr><th>luogo</th><th>visite</th><th>persone</th><th>adesso</th><th>folla</th></tr></thead>
    <tbody>
    <?php $max = max(1, (int) ($quartiere['luoghi'][0]['visite'] ?? 1)); ?>
    <?php foreach ($quartiere['luoghi'] as $l): ?>
      <tr class="<?= (int) $l['visite'] === 0 ? 'riga-spenta' : '' ?>">
        <td data-etichetta="luogo"><?= e($l['nome']) ?> <?= $barra((int) $l['visite'] / $max) ?></td>
        <td data-etichetta="visite"><?= (int) $l['visite'] ?></td>
        <td data-etichetta="persone"><?= (int) $l['persone'] ?></td>
        <td data-etichetta="adesso"><?= (int) $l['ora'] > 0 ? '<strong>' . (int) $l['ora'] . '</strong>' : '—' ?></td>
        <td data-etichetta="folla"><?= (int) $l['folla'] ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
