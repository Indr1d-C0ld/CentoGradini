<?php
/**
 * La faccia di una persona accanto al suo nome, negli elenchi.
 *
 * Grande abbastanza da riconoscerla, e al passaggio del mouse — o quando ci si
 * arriva con la tastiera — un'anteprima piu' grande. Sul telefono il passaggio
 * del mouse non esiste: l'anteprima non c'e', e la fotografia grande la si
 * vede toccando il nome, nel profilo.
 *
 * Senza fotografia: l'iniziale, come ovunque nel gioco.
 *
 * @var array<string,mixed> $p        la riga della persona (id, nome, ritratto_file)
 * @var string|null         $collega  dove porta un clic, o null
 */
$faccia  = \App\Game\Ritratto::di($p);
$collega = $collega ?? null;
$nome    = trim((string) ($p['cognome'] ?? '') . ' ' . (string) ($p['nome'] ?? ''));
$apri    = $collega !== null ? '<a class="faccia" href="' . e($collega) . '" aria-label="' . e($nome) . '">'
                             : '<span class="faccia"' . ($faccia !== null ? ' tabindex="0"' : '') . '>';
$chiudi  = $collega !== null ? '</a>' : '</span>';
?>
<?= $apri ?>
<?php if ($faccia !== null): ?>
  <img class="fotografia faccia-piccola" src="<?= e(asset($faccia)) ?>" alt="" width="56" height="56" loading="lazy">
  <span class="faccia-grande" aria-hidden="true">
    <img class="fotografia" src="<?= e(asset($faccia)) ?>" alt="" width="208" height="208" loading="lazy">
    <span><?= e($nome) ?></span>
  </span>
<?php else: ?>
  <span class="fotografia faccia-piccola fotografia--vuota" aria-hidden="true"><?=
    e(mb_strtoupper(mb_substr((string) ($p['nome'] ?? '?'), 0, 1))) ?></span>
<?php endif; ?>
<?= $chiudi ?>
