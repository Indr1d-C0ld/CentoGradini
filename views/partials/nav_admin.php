<?php
/**
 * La barra dell'amministrazione.
 *
 * Sta nel **layout**, non nelle singole pagine: prima esisteva solo dentro
 * /admin, e per arrivarci bisognava sapere l'indirizzo a memoria. Adesso chi
 * e' amministratore ce l'ha sempre sotto la testata, anche mentre gioca — e
 * chi non lo e' non la vede proprio, perche' non viene nemmeno stampata.
 */
$qui = percorso();
$voci = [
    '/admin'              => 'cruscotto',
    '/admin/statistiche'  => 'statistiche',
    '/admin/mappa'        => 'la situazione',
    '/admin/utenti'       => 'utenti',
    '/admin/accessi'      => 'accessi',
    '/admin/impostazioni' => 'le leve',
];
// «utenti» resta acceso anche sulla scheda di un singolo utente.
$attiva = $qui;
if (str_starts_with($qui, '/admin/utente')) {
    $attiva = '/admin/utenti';
}
?>
<nav class="barra-admin" aria-label="Amministrazione">
  <span class="barra-admin-etichetta">admin</span>
  <?php foreach ($voci as $rotta => $nome): ?>
    <?php if ($attiva === $rotta): ?>
      <strong aria-current="page"><?= e($nome) ?></strong>
    <?php else: ?>
      <a href="<?= e(url($rotta)) ?>"><?= e($nome) ?></a>
    <?php endif; ?>
  <?php endforeach; ?>
</nav>
