<?php
/** @var array<string,mixed> $u */
/** @var list<array<string,mixed>> $filo */
use App\Game\Comunicazioni;
?>
<p class="occhiello"><a href="<?= e(url('/admin/comunicazioni')) ?>">← tutte le comunicazioni</a></p>
<h1><?= e((string) $u['username']) ?></h1>
<p class="sommario">
  <a href="<?= e(url('/admin/utente/' . (int) $u['id'])) ?>">la sua scheda</a>
  · <?= e((string) $u['email']) ?>
</p>

<div class="carta">
  <?= partial('filo', ['filo' => $filo, 'ioSonoLaGestione' => true]) ?>
</div>

<div class="carta">
  <p class="occhiello">Scrivi</p>
  <p class="aiuto">
    Gli arriva in gioco, alla voce «Comunicazioni», e — se la posta è accesa — con un'e-mail
    che gli dice di entrare a leggere. L'e-mail non ripete il messaggio per intero: un
    avvertimento letto nella posta, fuori contesto e senza poter rispondere, è il modo più
    rapido di farlo prendere peggio di com'era inteso.
  </p>
  <form method="post" action="<?= e(url('/admin/comunicazioni')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="utente" value="<?= (int) $u['id'] ?>">
    <div class="campo">
      <label for="testo">Messaggio</label>
      <textarea id="testo" name="testo" rows="5" maxlength="<?= Comunicazioni::MAX ?>"></textarea>
    </div>
    <div class="bottoni"><button type="submit">Manda</button></div>
  </form>
</div>
