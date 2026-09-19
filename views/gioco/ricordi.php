<?php
/** @var array<string,mixed> $mondo */
/** @var list<array<string,mixed>> $ricordi */
use App\Sim\Luoghi;
use App\Sim\Orologio;
?>
<?= partial('insegna', ['mondo' => $mondo]) ?>
<p class="occhiello"><a href="<?= e(url('/quartiere')) ?>">← il quartiere</a></p>
<h1>L'album dei ricordi</h1>
<p class="sommario">
  Quello che è successo, nella tua versione. Resta qui anche se traslochi: le persone si
  perdono e i posti si perdono, quello che è successo no.
</p>

<?php if ($ricordi === []): ?>
  <div class="carta"><p>L'album è vuoto. Si riempie vivendo.</p></div>
<?php endif; ?>

<?php foreach ($ricordi as $r): ?>
<div class="carta ricordo">
  <p class="occhiello">
    <?= e(Orologio::esteso((int) $r['gts'])) ?> · <?= e(Luoghi::nome((string) $r['luogo'])) ?>
    <?php if ($r['cognome'] !== null): ?> · <?= e($r['cognome'] . ' ' . $r['nome']) ?><?php endif; ?>
  </p>
  <h2 style="margin-top:0"><?= e($r['titolo']) ?></h2>
  <?php foreach (preg_split('/\n\n+/', (string) $r['testo']) ?: [] as $par): ?>
    <p><?= e(trim($par)) ?></p>
  <?php endforeach; ?>
</div>
<?php endforeach; ?>
