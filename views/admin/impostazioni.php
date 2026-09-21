<?php
/** @var array<string, list<array<string,mixed>>> $famiglie */
/** @var array<string,string> $titoli */
/** @var string $cerca */
/** @var int $quante */
?>
<h1>Le leve del mondo</h1>
<p class="sommario">
  <?= (int) $quante ?> manopole, raggruppate per famiglia. Cambiano il gioco <strong>per
  tutti e subito</strong>: non c'è un riavvio e non c'è una conferma. Le chiavi si creano
  con una migrazione, non da qui — questo modulo cambia solo il valore di quelle che
  esistono.
</p>

<form method="get" action="<?= e(url('/admin/impostazioni')) ?>" class="riga-config">
  <label>Cerca <input type="text" name="cerca" value="<?= e($cerca) ?>"
         placeholder="nome della chiave, o una parola della spiegazione"></label>
  <button class="bottone secondario piccolo" type="submit">Cerca</button>
  <?php if ($cerca !== ''): ?>
    <a class="bottone secondario piccolo" href="<?= e(url('/admin/impostazioni')) ?>">Tutte</a>
  <?php endif; ?>
</form>

<?php if ($famiglie === []): ?>
  <div class="carta"><p>Nessuna manopola con «<?= e($cerca) ?>».</p></div>
<?php endif; ?>

<?php foreach ($famiglie as $fam => $righe): ?>
<div class="carta">
  <h2><?= e($titoli[$fam] ?? $fam) ?> <span class="aiuto minuto"><?= e($fam) ?>.*</span></h2>

  <?php foreach ($righe as $r): ?>
    <?php $tipo = (string) $r['ctype']; ?>
    <form method="post" action="<?= e(url('/admin/config')) ?>" class="leva">
      <?= csrf_field() ?>
      <input type="hidden" name="chiave" value="<?= e($r['ckey']) ?>">
      <div class="leva-nome">
        <code><?= e($r['ckey']) ?></code>
        <span class="aiuto"><?= e((string) ($r['note'] ?? '')) ?></span>
      </div>
      <div class="leva-valore">
        <?php if ($tipo === 'bool'): ?>
          <select name="valore">
            <?php foreach (['1' => 'sì', '0' => 'no'] as $v => $et): ?>
              <option value="<?= e($v) ?>" <?= (string) $r['cvalue'] === $v ? 'selected' : '' ?>>
                <?= e($et) ?>
              </option>
            <?php endforeach; ?>
          </select>
        <?php elseif ($tipo === 'int' || $tipo === 'float'): ?>
          <input type="number" name="valore" value="<?= e((string) $r['cvalue']) ?>"
                 <?= $tipo === 'float' ? 'step="any"' : 'step="1"' ?>>
        <?php else: ?>
          <input type="text" name="valore" value="<?= e((string) $r['cvalue']) ?>">
        <?php endif; ?>
        <button class="bottone secondario piccolo" type="submit">Cambia</button>
      </div>
      <div class="leva-meta aiuto minuto">
        <?= e($tipo) ?>
        <?php if ($r['updated_at'] !== null): ?>
          · toccata <?= e(substr((string) $r['updated_at'], 0, 16)) ?>
        <?php endif; ?>
      </div>
    </form>
  <?php endforeach; ?>
</div>
<?php endforeach; ?>
