<?php
/**
 * Un filo di comunicazioni, visto da una delle due parti.
 *
 * @var list<array<string,mixed>> $filo
 * @var bool $ioSonoLaGestione
 */
$ioSonoLaGestione = (bool) ($ioSonoLaGestione ?? false);
?>
<?php if ($filo === []): ?>
  <p class="aiuto">Nessun messaggio, per ora.</p>
<?php else: ?>
<div class="filo">
  <?php foreach ($filo as $m): $daAdmin = (int) $m['da_admin'] === 1; ?>
    <div class="filo-riga <?= $daAdmin === $ioSonoLaGestione ? 'filo-mio' : 'filo-altro' ?>">
      <p class="filo-chi">
        <?php if ($daAdmin): ?>
          <strong>La gestione</strong><?php
            if ($ioSonoLaGestione && $m['autore'] !== null): ?>
            <span class="aiuto">(<?= e((string) $m['autore']) ?>)</span><?php
            endif; ?>
        <?php else: ?>
          <strong><?= e((string) ($m['autore'] ?? 'il giocatore')) ?></strong>
        <?php endif; ?>
        <span class="aiuto"><?= e(substr((string) $m['created_at'], 0, 16)) ?></span>
        <?php if ($daAdmin !== $ioSonoLaGestione && $m['letto_at'] === null): ?>
          <span class="pastiglia">nuovo</span>
        <?php endif; ?>
      </p>
      <p class="filo-testo"><?= nl2br(e((string) $m['testo'])) ?></p>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
