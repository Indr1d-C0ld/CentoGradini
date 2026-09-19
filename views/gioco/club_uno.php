<?php
/** @var array<string,mixed> $mondo */
/** @var array<string,mixed> $pg */
/** @var array<string,mixed> $club */
/** @var list<array<string,mixed>> $membri */
/** @var bool $dentro */
use App\Sim\Luoghi;
use App\Sim\Scuola;
?>
<?= partial('insegna', ['mondo' => $mondo]) ?>
<p class="occhiello"><a href="<?= e(url('/club')) ?>">← tutti i club</a></p>
<h1><?= e($club['nome']) ?></h1>
<p class="occhiello">
  <?= e($club['nome_jp']) ?> · si ritrova <?= e(Luoghi::nome((string) $club['luogo'])) ?>
  · <?= count($membri) ?>/<?= (int) $club['posti'] ?> iscritti
</p>
<p class="sommario"><?= e($club['descrizione']) ?></p>

<div class="carta">
  <h2>Chi c'è dentro</h2>
  <?php if ($membri === []): ?>
    <p class="aiuto">Nessuno. Un club senza iscritti chiude a fine trimestre.</p>
  <?php endif; ?>
  <?php foreach ($membri as $m): ?>
    <p class="riga-membro">
      <strong><?= e($m['nome'] . ' ' . $m['cognome']) ?></strong>
      — <?= e(Scuola::nomeClasse((string) $m['sezione'], (int) $m['anno'])) ?>
      <?php if ((string) $m['ruolo'] === 'capitano'): ?>
        <span class="pastiglia">capitano</span>
      <?php endif; ?>
      <?php if ($m['png'] !== null): ?>
        <span class="aiuto">del quartiere</span>
      <?php endif; ?>
    </p>
  <?php endforeach; ?>
</div>

<div class="bottoni">
<?php if ($dentro): ?>
  <form method="post" action="<?= e(url('/club/esci')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="club" value="<?= e($club['ckey']) ?>">
    <button class="bottone secondario" type="submit">Lascia il club</button>
  </form>
<?php else: ?>
  <form method="post" action="<?= e(url('/club/iscrivi')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="club" value="<?= e($club['ckey']) ?>">
    <button class="bottone" type="submit">Iscriviti</button>
  </form>
<?php endif; ?>
</div>
