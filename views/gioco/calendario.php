<?php
/** @var array<string,mixed> $mondo */
/** @var list<array<string,mixed>> $in_corso */
/** @var array<string,mixed>|null $prossimo */
/** @var list<array<string,mixed>> $tutti */
/** @var list<array<string,mixed>> $abitanti */
use App\Sim\Luoghi;
use App\Sim\Scuola;

$mesi = [1 => 'gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno',
         'luglio', 'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre'];
?>
<?= partial('insegna', ['mondo' => $mondo]) ?>
<p class="occhiello"><a href="<?= e(url('/quartiere')) ?>">← il quartiere</a></p>
<h1>Il calendario</h1>
<p class="sommario">
  L'anno gira sempre uguale e non finisce mai: si apre il 6 aprile e si chiude il 5, e poi
  ricomincia. Questi sono i giorni in cui il quartiere sta tutto nello stesso posto — e sono
  anche quelli in cui si chiacchiera di più, e in cui conviene meno di tutti farsi vedere a
  fare qualcosa di strano.
</p>

<?php if ($in_corso !== []): ?>
  <?php foreach ($in_corso as $e): ?>
    <div class="carta evento in-corso">
      <p class="occhiello">
        adesso · giorno <?= (int) $e['giorno_di'] ?> di <?= (int) $e['su_giorni'] ?>
        <?php if ((string) $e['luogo'] !== ''): ?>
          · <?= e(Luoghi::nome((string) $e['luogo'])) ?>
        <?php else: ?>
          · in tutto il quartiere
        <?php endif; ?>
      </p>
      <h2 style="margin-top:0"><?= e($e['nome']) ?></h2>
      <p><?= e($e['descrizione']) ?></p>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <div class="carta"><p>Un giorno come un altro. Sono la maggior parte.</p></div>
<?php endif; ?>

<?php if ($prossimo !== null): ?>
  <p class="aiuto">
    Poi arriva <strong><?= e($prossimo['nome']) ?></strong>,
    fra <?= (int) $prossimo['fra_giorni'] ?>
    <?= (int) $prossimo['fra_giorni'] === 1 ? 'giorno' : 'giorni' ?>.
  </p>
<?php endif; ?>

<div class="carta">
  <h2>L'anno intero</h2>
  <div class="tabella-scorre">
  <table class="tabella">
    <thead><tr><th>quando</th><th>cosa</th><th>dove</th></tr></thead>
    <tbody>
    <?php foreach ($tutti as $e): ?>
      <tr>
        <td data-etichetta="quando"><?= (int) $e['giorno'] ?> <?= e($mesi[(int) $e['mese']] ?? '') ?><?php
          if ((int) $e['durata'] > 1): ?> <span class="aiuto">· <?= (int) $e['durata'] ?> giorni</span><?php
          endif; ?></td>
        <td data-etichetta="cosa"><?= e($e['nome']) ?></td>
        <td data-etichetta="dove"><?= (string) $e['luogo'] === ''
              ? '<span class="aiuto">ovunque</span>'
              : e(Luoghi::nome((string) $e['luogo'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<div class="carta">
  <h2>Chi abita qui</h2>
  <p class="aiuto">
    Gente del quartiere, che gira per conto suo che tu ci sia o no. Li incontri dove capita,
    e sono loro a portare in giro quello che si dice.
  </p>
  <?php foreach ($abitanti as $a): ?>
    <p class="riga-membro">
      <strong><?= e($a['nome'] . ' ' . $a['cognome']) ?></strong>
      — <?= e(Scuola::nomeClasse((string) $a['sezione'], (int) $a['anno'])) ?>.
      <?= e((string) $a['png_nota']) ?>
    </p>
  <?php endforeach; ?>
</div>
