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
    '/admin/comunicazioni'=> 'comunicazioni',
    '/admin/fotografie'   => 'fotografie',
    '/admin/accessi'      => 'accessi',
    '/admin/impostazioni' => 'le leve',
];
// Le sezioni con un dettaglio restano accese anche sul dettaglio. L'ordine
// conta: «/admin/comunicazioni/3» comincia per «/admin/comunicazioni», ma
// anche «/admin/utente/3» comincia per «/admin/utente» — e senza questa
// mappa esplicita la voce si spegnerebbe proprio quando si sta guardando
// qualcosa dentro di lei.
$attiva = $qui;
foreach (['/admin/utente' => '/admin/utenti',
          '/admin/comunicazioni' => '/admin/comunicazioni'] as $ramo => $voce) {
    if (str_starts_with($qui, $ramo)) {
        $attiva = $voce;
        break;
    }
}
?>
<nav class="barra-admin" aria-label="Amministrazione">
  <span class="barra-admin-etichetta">admin</span>
  <?php foreach ($voci as $rotta => $nome): ?>
    <?php
      // Un numero accanto a «comunicazioni» quando qualcuno aspetta: senza,
      // la sezione si guarda solo se ci si ricorda di guardarla.
      $quanti = $rotta === '/admin/comunicazioni' ? \App\Game\Comunicazioni::daLeggere() : 0;
    ?>
    <?php if ($attiva === $rotta): ?>
      <strong aria-current="page"><?= e($nome) ?></strong>
    <?php else: ?>
      <a href="<?= e(url($rotta)) ?>"><?= e($nome) ?></a>
    <?php endif; ?>
    <?php if ($quanti > 0): ?><span class="pastiglia"><?= $quanti ?></span><?php endif; ?>
  <?php endforeach; ?>
</nav>
