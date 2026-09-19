<?php
/**
 * La fascia del mondo: che giorno è, che ora, che tempo fa.
 * Si aggiorna da sola senza ricaricare la pagina (assets/js/mondo.js).
 *
 * @var array<string,mixed> $mondo
 */
$cal = $mondo['calendario'];
$met = $mondo['meteo'];
?>
<div class="insegna<?= $mondo['notte'] ? ' notte' : '' ?>" data-insegna>
  <div class="insegna-quando">
    <b data-quando title="<?= e($mondo['quando']) ?>"><?= e($mondo['quando_breve']) ?></b>
    <span class="anno"><?= e($cal['data']->format('Y')) ?></span>
    <span data-contesto><?= e(\App\Game\Mondo::insegna()) ?></span>
  </div>
  <div class="insegna-tempo" title="<?= e($met['descrizione']) ?>">
    <span class="icona" aria-hidden="true"><?= e($met['icona']) ?></span>
    <b><?= e((string) $met['temperatura']) ?>°</b>
    <span><?= e($met['cielo']) ?></span>
    <?php if ($met['ombrello']): ?><em class="ombrello">ombrello</em><?php endif; ?>
  </div>
</div>

<?php if ($cal['evento'] !== null): ?>
  <div class="evento-oggi">
    <b><?= e($cal['evento']['titolo']) ?></b>
    <span><?= e($cal['evento']['nota']) ?></span>
  </div>
<?php elseif ($cal['festa'] !== null): ?>
  <div class="evento-oggi tenue-box"><b><?= e($cal['festa']) ?></b> <span>Niente scuola.</span></div>
<?php endif; ?>
