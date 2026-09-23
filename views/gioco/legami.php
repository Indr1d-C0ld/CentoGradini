<?php
/**
 * @var array<string,mixed> $pg
 * @var array<string,mixed> $mondo
 * @var list<array<string,mixed>> $legami
 * @var array<string,mixed>|null $oggetto
 */
use App\Game\Ritratto;
use App\Sim\Scuola;
?>

<?= partial('insegna', ['mondo' => $mondo]) ?>

<p class="occhiello"><a href="<?= e(url('/quartiere')) ?>">← il quartiere</a></p>
<h1>I legami</h1>
<p class="sommario">
  Le persone che conosci, e cosa provi per loro. Quello che loro provano per te non c'è, e non
  è una dimenticanza: in questa storia nessuno lo sa mai.
</p>

<?php if ($oggetto !== null): ?>
<div class="evento-oggi">
  <b>Hai <?= e(mb_strtolower($oggetto['nome'])) ?>.</b>
  <span>Puoi darlo a qualcuno che sia dove sei tu. Una volta dato, è dato.</span>
</div>
<?php endif; ?>

<?php if ($legami === []): ?>
  <div class="carta">
    <p>Non conosci ancora nessuno. È normale: sei <?= e(accorda('arrivat{o}', $pg)) ?> da poco.</p>
    <p class="tenue">
      Si comincia stando negli stessi posti delle stesse persone, e scambiando due parole. Il
      resto viene o non viene.
    </p>
  </div>
<?php endif; ?>

<?php foreach ($legami as $l): ?>
<div class="carta">
  <div class="riga-scelta" style="border:0;padding:0">
    <div class="riga-fotografia">
      <?php $faccia = Ritratto::di($l); ?>
      <?php if ($faccia !== null): ?>
        <img class="fotografia fotografia--media" src="<?= e(asset($faccia)) ?>" alt=""
             width="80" height="80" loading="lazy">
      <?php else: ?>
        <div class="fotografia fotografia--media fotografia--vuota" aria-hidden="true"><?=
          e(mb_strtoupper(mb_substr((string) $l['nome'], 0, 1))) ?></div>
      <?php endif; ?>
      <div>
        <h2 style="margin:0">
          <a href="<?= e(url('/chi/' . (int) $l['a_id'])) ?>"><?= e($l['cognome'] . ' ' . $l['nome']) ?></a>
          <?php if ((string) $l['stato'] !== 'attivo'): ?>
            <span class="tenue">— se n'è andato dal quartiere</span>
          <?php endif; ?>
        </h2>
        <span class="tenue"><?= e(Scuola::nomeClasse((string) $l['sezione'], (int) $l['anno'])) ?></span>
        <?php if ((string) ($l['aspetto'] ?? '') !== ''): ?>
          <br><span class="tenue"><em><?= e((string) $l['aspetto']) ?></em></span>
        <?php endif; ?>
      </div>
    </div>
    <div style="text-align:right">
      <b class="<?= (int) $l['affetto'] < 0 ? 'rischio-alto' : '' ?>" style="font-size:1.2rem">
        <?= (int) $l['affetto'] > 0 ? '+' : '' ?><?= (int) $l['affetto'] ?>
      </b>
      <?php if ((int) $l['fraintendimento'] >= 15): ?>
        <br><span class="tenue">malinteso <?= (int) $l['fraintendimento'] ?></span>
      <?php endif; ?>
    </div>
  </div>
  <p style="margin:.7rem 0 0"><?= e($l['dice']) ?></p>
</div>
<?php endforeach; ?>
