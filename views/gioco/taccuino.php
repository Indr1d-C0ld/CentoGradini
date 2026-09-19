<?php
/**
 * @var array<string,mixed> $pg
 * @var array<string,mixed> $mondo
 * @var list<array<string,mixed>> $gruppi
 * @var int $intuizione
 */
use App\Game\Personaggio;
use App\Sim\Luoghi;
use App\Sim\Scuola;
?>

<?= partial('insegna', ['mondo' => $mondo]) ?>

<p class="occhiello"><a href="<?= e(url('/quartiere')) ?>">← il quartiere</a></p>
<h1>Il taccuino</h1>
<p class="sommario">
  Le cose che hai visto e che non tornano. Da sole non dicono niente: una persona che sparisce
  dietro un angolo è una persona che sparisce dietro un angolo. Tre cose del genere sulla stessa
  persona, però, cominciano a somigliare a qualcosa.
</p>

<div class="nota">
  La tua <strong>Intuizione</strong> è del <b><?= (int) $intuizione ?>%</b>: è la probabilità di
  riuscire, quando decidi di metterle in fila. Ogni tentativo consuma un'annotazione: non si può
  insistere all'infinito sugli stessi indizi, serve qualcosa di nuovo.
</div>

<?php if ($gruppi === []): ?>
  <div class="carta">
    <p>Il taccuino è vuoto. Non hai ancora visto niente che valga la pena di annotare.</p>
    <p class="tenue">
      Si riempie da solo: basta essere nel posto giusto quando qualcuno fa qualcosa che non
      dovrebbe poter fare. Aiuta stare dove c'è poca gente, e avere la Testa per accorgersene.
    </p>
  </div>
<?php endif; ?>

<?php foreach ($gruppi as $g): $s = $g['soggetto']; ?>
<div class="carta">
  <div class="riga-scelta" style="border:0;padding:0 0 .6rem">
    <div>
      <h2 style="margin:0"><?= e($s['cognome'] . ' ' . $s['nome']) ?></h2>
      <span class="tenue">
        <?= e(Scuola::nomeClasse((string) $s['sezione'], (int) $s['anno'])) ?>
        · <?= (int) $g['quante'] ?> <?= $g['quante'] === 1 ? 'annotazione' : 'annotazioni' ?>
      </span>
    </div>
    <?php if ($g['fondato']): ?>
      <em class="marchio-primario">lo sai</em>
    <?php elseif ($g['basta']): ?>
      <form method="post" action="<?= e(url('/taccuino/collega')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="soggetto" value="<?= (int) $s['id'] ?>">
        <button type="submit">Mettile in fila</button>
      </form>
    <?php endif; ?>
  </div>

  <ul class="tracce">
    <?php foreach ($g['anomalie'] as $a): ?>
      <li<?= (int) $a['collegata'] === 1 ? ' class="consumata"' : '' ?>>
        <?= e($a['testo']) ?>
        <span class="tenue">
          <?= e(Luoghi::nome((string) $a['luogo'])) ?>
          · <?= e(Personaggio::quantoFa((int) $a['fa_minuti'])) ?>
          <?= (int) $a['collegata'] === 1 ? ' · ci hai già ragionato sopra' : '' ?>
        </span>
      </li>
    <?php endforeach; ?>
  </ul>

  <?php if ($g['fondato']): ?>
    <p class="nota calda" style="margin-top:.8rem">
      Hai capito. Quello che ne fai è affare tuo: puoi tenertelo, puoi dirglielo in faccia,
      puoi raccontarlo in giro. Sappi solo che a forza di gente che capisce, una famiglia
      trasloca.
    </p>
  <?php elseif (!$g['basta']): ?>
    <p class="aiuto">Ti serve qualche annotazione in più prima di poterci ragionare sopra.</p>
  <?php endif; ?>
</div>
<?php endforeach; ?>
