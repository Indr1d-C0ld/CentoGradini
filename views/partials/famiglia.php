<?php
/**
 * La famiglia di un abitante, in una riga: «In famiglia: Manami (sorella
 * gemella), Kyosuke (fratello), Akane (cugina)…».
 *
 * Senza collegamenti, apposta: il profilo di una persona si apre solo se la
 * si conosce, e sapere che Kurumi ha un cugino non vuol dire conoscerlo.
 *
 * @var array<string,mixed> $chi
 */
$parenti = \App\Game\Parentele::di($chi);
if ($parenti === []) {
    return;
}
?>
<p class="occhiello" style="margin:.35rem 0 0">
  In famiglia: <?= e(implode(', ', array_map(
      static fn (array $p): string => $p['nome'] . ' (' . $p['come'] . ')', $parenti))) ?>.
</p>
