<?php
/**
 * @var array<string,mixed> $pg
 * @var array<string,mixed> $mondo
 * @var array<string,mixed> $ep
 * @var array<string,mixed>|null $scena
 * @var list<array<string,mixed>> $cast
 * @var array<int,array<string,mixed>> $scelte
 * @var array<string,mixed>|null $mia
 * @var int $restano
 */
use App\Game\Scheda;
use App\Sim\Luoghi;
$scene = json_decode((string) $ep['scene'], true);
?>

<?= partial('insegna', ['mondo' => $mondo]) ?>

<p class="occhiello"><?= e($ep['occhiello']) ?> · <?= e(Luoghi::nome((string) $ep['luogo'])) ?></p>
<h1><?= e($ep['titolo']) ?></h1>

<div class="carta episodio-premessa">
  <p><?= e($ep['premessa']) ?></p>
  <p class="tenue">
    Scena <?= (int) $ep['scena'] + 1 ?> di <?= count($scene) ?> ·
    <?= count($cast) ?> in scena ·
    <?= count($scelte) ?> <?= count($scelte) === 1 ? 'ha' : 'hanno' ?> già scelto
  </p>
</div>

<?php if ($scena !== null): ?>
<div class="carta scena">
  <p style="font-size:1.04rem"><?= e($scena['testo']) ?></p>
</div>

<?php if ($mia === null): ?>
<div class="carta">
  <p class="occhiello">Cosa fai</p>
  <?php foreach ($scena['opzioni'] as $o): ?>
    <form method="post" action="<?= e(url('/episodio/scegli')) ?>" class="riga-scelta">
      <?= csrf_field() ?>
      <input type="hidden" name="episodio" value="<?= (int) $ep['id'] ?>">
      <input type="hidden" name="opzione" value="<?= e($o['k']) ?>">
      <div>
        <b><?= e($o['testo']) ?></b>
        <span class="tenue">
          <?php if (($o['prova'] ?? 'nessuna') === 'nessuna'): ?>
            riesce comunque
          <?php else: ?>
            si tira su <?= e(Scheda::NOMI[$o['prova']] ?? $o['prova']) ?>
            (ne hai <?= (int) ($pg[$o['prova']] ?? 0) ?>)
          <?php endif; ?>
        </span>
      </div>
      <button type="submit" class="secondario">scegli</button>
    </form>
  <?php endforeach; ?>
  <p class="aiuto" data-attesa data-restano="<?= (int) $restano ?>">
    Hai <?= $restano >= 60 ? (int) round($restano / 60) . ' minuti' : (int) $restano . ' secondi' ?>
    per decidere. Scaduto il tempo, la scena va avanti lo stesso e il tuo personaggio fa quello
    che avrebbe fatto.
  </p>
</div>
<?php else: ?>
<div class="carta">
  <p class="occhiello">Hai scelto</p>
  <p><b><?php
    foreach ($scena['opzioni'] as $o) {
        if ($o['k'] === $mia['opzione']) { echo e($o['testo']); break; }
    }
  ?></b></p>
  <p class="tenue">
    Adesso si aspetta. La scena va avanti quando hanno scelto tutti, o quando scade il tempo.
  </p>
</div>
<?php endif; ?>
<?php endif; ?>

<div class="carta">
  <p class="occhiello">In scena</p>
  <ul class="elenco-persone">
    <?php foreach ($cast as $c): ?>
      <li>
        <div>
          <b><?= e($c['cognome'] . ' ' . $c['nome']) ?></b>
          <?php if ((int) $c['id'] === (int) $pg['id']): ?><span class="tenue">— tu</span><?php endif; ?>
        </div>
        <span class="tenue"><?= isset($scelte[(int) $c['id']]) ? 'ha scelto' : 'sta decidendo' ?></span>
      </li>
    <?php endforeach; ?>
  </ul>
</div>

<script src="<?= e(asset('js/episodio.js')) ?>" defer></script>
