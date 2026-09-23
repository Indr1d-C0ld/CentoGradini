<?php /** @var array<string,mixed> $mondo */ ?>
<?= partial('insegna', ['mondo' => $mondo]) ?>
<p class="occhiello"><a href="<?= e(url('/quartiere')) ?>">← il quartiere</a></p>
<h1>Non sta succedendo niente</h1>
<div class="carta">
  <p>
    Gli episodi non si aprono a comando: capitano. Il motore guarda chi c'è, dove, che tempo
    fa, che stagione è e che voci girano, e quando le cose si allineano una storia comincia
    addosso a chi si trova lì.
  </p>
  <p class="tenue">
    Il modo per farne capitare di più è semplice e non è un trucco: stare in giro dove c'è
    gente, nei momenti in cui succede qualcosa. Un pomeriggio d'autunno all'ABCB vale più di
    tre serate passate da soli sull'argine.
  </p>
  <div class="bottoni">
    <a class="bottone secondario" href="<?= e(url('/ricordi')) ?>">L'album dei ricordi</a>
  </div>
</div>
