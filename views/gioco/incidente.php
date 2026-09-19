<?php
/**
 * @var array<string,mixed> $pg
 * @var array<string,mixed> $incidente
 * @var array<string,mixed> $potere
 * @var list<array<string,mixed>> $testimoni
 * @var array<string,array<string,mixed>> $scelte
 * @var bool $complice
 */
use App\Sim\Luoghi;
?>
<p class="occhiello">Un attimo fa</p>
<h1>Ti hanno visto</h1>

<div class="carta scena">
  <p class="sommario">
    Hai usato <strong><?= e($potere['nome']) ?></strong> <?= e(Luoghi::nome((string) $incidente['luogo'])) ?>,
    e <?= count($testimoni) === 1 ? 'una persona se n\'è accorta' : count($testimoni) . ' persone se ne sono accorte' ?>.
  </p>
  <ul class="elenco-persone">
    <?php foreach ($testimoni as $t): ?>
      <li><b><?= e($t['cognome'] . ' ' . $t['nome']) ?></b> <span class="tenue">ti sta guardando</span></li>
    <?php endforeach; ?>
  </ul>
  <p>
    Adesso conta quello che dici nei prossimi tre secondi. Se non convinci, quella persona non
    penserà «è un esper» — penserà «c'è qualcosa che non torna», e se lo ricorderà.
  </p>
</div>

<?php if ($complice): ?>
  <div class="nota calda">
    Qui c'è qualcuno che sa già il tuo segreto: coprire in due è un'altra cosa.
    <strong>+25</strong> su qualunque strada tu scelga.
  </div>
<?php endif; ?>

<div class="carta">
  <p class="occhiello">Cosa fai</p>
  <?php foreach ($scelte as $k => $c): ?>
    <form method="post" action="<?= e(url('/copri')) ?>" class="riga-scelta">
      <?= csrf_field() ?>
      <input type="hidden" name="incidente" value="<?= (int) $incidente['id'] ?>">
      <input type="hidden" name="come" value="<?= e($k) ?>">
      <div>
        <b><?= e($c['nome']) ?></b>
        <span class="tenue">
          <?= e(\App\Game\Scheda::NOMI[$c['abilita']]) ?> <?= (int) $pg[$c['abilita']] ?>
          · costa un punto di compostezza (ne hai <?= (int) $pg['compostezza'] ?>)
        </span>
      </div>
      <button type="submit" class="secondario"><?= (int) $c['probabilita'] ?>%</button>
    </form>
  <?php endforeach; ?>

  <form method="post" action="<?= e(url('/copri')) ?>" class="riga-scelta">
    <?= csrf_field() ?>
    <input type="hidden" name="incidente" value="<?= (int) $incidente['id'] ?>">
    <input type="hidden" name="come" value="niente">
    <div>
      <b>Non dire niente</b>
      <span class="tenue">A volte è la scelta giusta. Quasi mai.</span>
    </div>
    <button type="submit" class="tenue">lascia perdere</button>
  </form>
</div>

<?php if ((int) $pg['compostezza'] < 1): ?>
  <div class="avviso attenzione">
    Sei a zero di compostezza: in questo stato non ti viene fuori una frase che stia in piedi.
    Puoi solo lasciar perdere.
  </div>
<?php endif; ?>
