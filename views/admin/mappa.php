<?php
/** @var list<array<string,mixed>> $luoghi */
/** @var list<array<string,mixed>> $viaggio */
/** @var list<array<string,mixed>> $episodi */
/** @var list<array<string,mixed>> $voci */
/** @var list<array<string,mixed>> $pericolo */
/** @var int $gts */
use App\Sim\Luoghi;
use App\Sim\Orologio;

$giocatori = array_sum(array_map(static fn (array $l): int => count($l['giocatori']), $luoghi));
$abitanti  = array_sum(array_map(static fn (array $l): int => count($l['abitanti']), $luoghi));
?>
<?= partial('admin_nav', ['qui' => '/admin/mappa']) ?>
<h1>La situazione</h1>
<p class="sommario">
  <?= e(Orologio::esteso($gts)) ?>, ora del quartiere.
  <strong><?= $giocatori ?></strong> giocator<?= $giocatori === 1 ? 'e' : 'i' ?> in giro,
  <strong><?= $abitanti ?></strong> abitanti,
  <strong><?= count($viaggio) ?></strong> per strada.
  I luoghi vuoti restano in elenco: un quartiere vuoto è esso stesso un'informazione.
</p>

<?php if ($pericolo !== []): ?>
  <div class="avviso attenzione">
    <strong>Chi rischia il trasloco.</strong>
    <?php foreach ($pericolo as $i => $p): ?>
      <?= $i > 0 ? ' · ' : '' ?><?= e($p['nome'] . ' ' . $p['cognome']) ?>
      (<?= (int) $p['sanno'] ?> l'<?= (int) $p['sanno'] === 1 ? 'ha' : 'hanno' ?> capito)
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if ($episodi !== []): ?>
<div class="carta">
  <h2>Episodi aperti</h2>
  <?php foreach ($episodi as $e): ?>
    <p class="riga-membro">
      <strong><?= e($e['titolo']) ?></strong>
      — <?= e(Luoghi::nome((string) $e['luogo'])) ?>, scena <?= (int) $e['scena'] + 1 ?>,
      <?= (int) $e['quanti'] ?> nel cast
      <span class="aiuto">· la finestra scade fra
        <?= max(0, (int) round(((int) $e['scade_reale'] - time()) / 60)) ?> minuti</span>
    </p>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($viaggio !== []): ?>
<div class="carta">
  <h2>Per strada</h2>
  <?php foreach ($viaggio as $v): ?>
    <p class="riga-membro">
      <?= e($v['nome'] . ' ' . $v['cognome']) ?><?php
        if ($v['png'] !== null): ?> <span class="aiuto">(del quartiere)</span><?php endif; ?>
      — da <?= e(Luoghi::nome((string) $v['luogo'])) ?>
      verso <?= e(Luoghi::nome((string) $v['verso'])) ?>,
      <span class="aiuto">arriva fra <?= max(0, (int) round(((int) $v['arrivo_gts'] - $gts) / 60)) ?> min di gioco</span>
    </p>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="carta">
  <h2>Il quartiere, luogo per luogo</h2>
  <div class="tabella-scorre">
  <table class="tabella">
    <thead><tr>
      <th>luogo</th><th>giocatori</th><th>abitanti</th>
      <th>folla</th><th>calore</th><th>bacheca</th>
    </tr></thead>
    <tbody>
    <?php foreach ($luoghi as $l): ?>
      <tr class="<?= $l['giocatori'] !== [] ? 'riga-viva' : '' ?>">
        <td>
          <?= e($l['nome']) ?>
          <?php if (!$l['aperto']): ?><span class="aiuto">chiuso</span><?php endif; ?>
        </td>
        <td>
          <?php if ($l['giocatori'] === []): ?><span class="aiuto">—</span><?php endif; ?>
          <?php foreach ($l['giocatori'] as $p): ?>
            <strong><?= e($p['nome'] . ' ' . $p['cognome']) ?></strong><?php
              if ((int) $p['esper'] === 1): ?><span class="aiuto">·e</span><?php endif; ?>
          <?php endforeach; ?>
        </td>
        <td class="aiuto">
          <?= $l['abitanti'] === [] ? '—'
              : e(implode(', ', array_map(static fn (array $p): string => (string) $p['nome'], $l['abitanti']))) ?>
        </td>
        <td><?= (int) $l['folla'] ?></td>
        <td><?= (int) $l['calore'] > 0
              ? '<strong>' . (int) $l['calore'] . '</strong>'
              : '<span class="aiuto">0</span>' ?></td>
        <td><?= (int) $l['avvisi'] > 0 ? (int) $l['avvisi'] : '<span class="aiuto">—</span>' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <p class="aiuto">
    Il <strong>calore</strong> è quanto un posto è sorvegliato dopo che ci è successo qualcosa
    di strano: sale quando un potere viene notato e si spegne da solo in un paio di giorni.
  </p>
</div>

<?php if ($voci !== []): ?>
<div class="carta">
  <h2>Le voci che girano</h2>
  <div class="tabella-scorre">
  <table class="tabella">
    <thead><tr><th>nata</th><th>tipo</th><th>dove</th><th>la sanno</th><th>passaggi</th></tr></thead>
    <tbody>
    <?php foreach ($voci as $v): ?>
      <tr>
        <td class="minuto"><?= e(Orologio::esteso((int) $v['gts'])) ?></td>
        <td><?= e($v['tipo']) ?></td>
        <td><?= e(Luoghi::nome((string) $v['luogo'])) ?></td>
        <td><?= (int) $v['quanti'] ?></td>
        <td><?= (int) $v['passaggi'] ?><?= (int) $v['passaggi'] >= 3
              ? ' <span class="aiuto">pettegolezzo</span>' : '' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>
