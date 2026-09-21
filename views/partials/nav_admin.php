<?php /** La barra fra le pagine dell'amministrazione. @var string $qui */ ?>
<p class="occhiello nav-admin">
  <a href="<?= e(url('/quartiere')) ?>">← il quartiere</a>
  <?php foreach ([
      '/admin'              => 'cruscotto',
      '/admin/statistiche'  => 'statistiche',
      '/admin/mappa'        => 'la situazione',
      '/admin/utenti'       => 'utenti',
      '/admin/accessi'      => 'accessi',
      '/admin/impostazioni' => 'le leve',
  ] as $rotta => $nome): ?>
    · <?php if ($qui === $rotta): ?><strong><?= e($nome) ?></strong>
      <?php else: ?><a href="<?= e(url($rotta)) ?>"><?= e($nome) ?></a><?php endif; ?>
  <?php endforeach; ?>
</p>
