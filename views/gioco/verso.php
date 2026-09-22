<?php
/**
 * @var array<string,mixed> $pg
 * @var array<string,mixed> $altro
 * @var array<string,mixed> $mondo
 * @var list<array<string,mixed>> $gesti
 * @var array{affetto:int,fraintendimento:int} $mio
 * @var array{affetto:int,fraintendimento:int} $loro
 * @var array<string,mixed>|null $oggetto
 */
use App\Game\Legami;
use App\Game\Ritratto;
use App\Game\Scheda;
use App\Game\Segreto;
use App\Sim\Scuola;
use App\Core\GameConfig;

$soglia = GameConfig::int('legami.soglia_confessione', 45);
?>

<?= partial('insegna', ['mondo' => $mondo]) ?>

<p class="occhiello"><a href="<?= e(url('/quartiere')) ?>">← il quartiere</a></p>

<?php $faccia = Ritratto::di($altro); ?>
<div class="riga-fotografia">
  <?php if ($faccia !== null): ?>
    <img class="fotografia fotografia--media" src="<?= e(asset($faccia)) ?>"
         alt="" width="80" height="80">
  <?php else: ?>
    <div class="fotografia fotografia--media fotografia--vuota" aria-hidden="true"><?=
      e(mb_strtoupper(mb_substr((string) $altro['nome'], 0, 1))) ?></div>
  <?php endif; ?>
  <div>
    <h1 style="margin:0"><?= e($altro['cognome'] . ' ' . $altro['nome']) ?></h1>
    <p class="sommario" style="margin:.2rem 0 0">
      <?= e(Scuola::nomeClasse((string) $altro['sezione'], (int) $altro['anno'])) ?>
      <?php if (Segreto::sa((int) $pg['id'], (int) $altro['id'])): ?>
        · <em>sa il tuo segreto</em>
      <?php endif; ?>
    </p>
    <?php if ((string) ($altro['aspetto'] ?? '') !== ''): ?>
      <p class="occhiello" style="margin:.35rem 0 0"><em><?= e((string) $altro['aspetto']) ?></em></p>
    <?php endif; ?>
    <p class="occhiello" style="margin:.35rem 0 0">
      <a href="<?= e(url('/chi/' . (int) $altro['id'])) ?>">il suo profilo</a>
    </p>
  </div>
</div>

<div class="carta">
  <p class="occhiello">Come stanno le cose</p>
  <p class="sommario" style="margin-bottom:1rem">
    <?= e(Legami::descrizione($mio['affetto'], $mio['fraintendimento'])) ?>
  </p>
  <div class="barre">
    <div class="barra assi">
      <span class="barra-nome">Affetto</span>
      <span class="barra-solco doppio"><i class="<?= $mio['affetto'] < 0 ? 'negativo' : '' ?>"
        style="width:<?= abs($mio['affetto']) / 2 ?>%; <?= $mio['affetto'] < 0 ? 'right' : 'left' ?>:50%"></i></span>
      <b class="barra-valore"><?= $mio['affetto'] > 0 ? '+' : '' ?><?= $mio['affetto'] ?></b>
    </div>
    <div class="barra assi">
      <span class="barra-nome">Malinteso</span>
      <span class="barra-solco"><i class="fraint" style="width:<?= (int) $mio['fraintendimento'] ?>%"></i></span>
      <b class="barra-valore"><?= (int) $mio['fraintendimento'] ?></b>
    </div>
  </div>
  <p class="aiuto">
    Questi due numeri sono <strong>i tuoi</strong>. Quello che prova lui per te non lo sai, e
    non lo saprai mai da una barra: si capisce da come ti guarda, o non si capisce affatto.
  </p>
</div>

<?php if ($loro['fraintendimento'] >= 5): ?>
<div class="carta">
  <p class="occhiello">C'è qualcosa in mezzo</p>
  <p>
    Ti sei accorto che <?= e($altro['nome']) ?> si è fatto un'idea sbagliata su qualcosa che ti
    riguarda. Puoi provare a tirare fuori l'argomento — ma non è detto che vada bene: certe
    volte parlarne è proprio quello che peggiora le cose.
  </p>
  <form method="post" action="<?= e(url('/chiarisci')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="verso" value="<?= (int) $altro['id'] ?>">
    <button type="submit" class="secondario">Prova a chiarire</button>
    <span class="tenue">costa un punto di compostezza (ne hai <?= (int) $pg['compostezza'] ?>)</span>
  </form>
</div>
<?php endif; ?>

<div class="carta">
  <p class="occhiello">Cosa fai</p>
  <?php foreach ($gesti as $g): ?>
    <div class="riga-scelta<?= $g['possibile'] ? '' : ' spento' ?>">
      <div>
        <b><?= e($g['nome']) ?></b>
        <span class="tenue">
          <?= e($g['descrizione']) ?>
          <?php if ($g['motivo'] !== null): ?><br><em><?= e($g['motivo']) ?></em><?php endif; ?>
        </span>
      </div>
      <?php if ($g['possibile']): ?>
        <form method="post" action="<?= e(url('/gesto')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="verso" value="<?= (int) $altro['id'] ?>">
          <input type="hidden" name="gesto" value="<?= e($g['gkey']) ?>">
          <button type="submit" class="secondario"><?= (int) $g['affetto'] > 0 ? 'fallo' : 'fallo' ?></button>
        </form>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  <p class="aiuto">
    Quasi tutti questi gesti si possono leggere in due modi, e chi passa di qui ne vedrà solo
    un pezzo. Se ci tieni che non nascano malintesi, guarda prima chi c'è.
  </p>
</div>

<?php if ($oggetto !== null): ?>
<div class="carta">
  <p class="occhiello">Hai una cosa in mano</p>
  <h3 style="margin-top:0"><?= e($oggetto['nome']) ?></h3>
  <p><?= e($oggetto['descrizione']) ?></p>
  <form method="post" action="<?= e(url('/oggetto/passa')) ?>"
        onsubmit="return confirm('Darglielo è definitivo. Sicuro?')">
    <?= csrf_field() ?>
    <input type="hidden" name="verso" value="<?= (int) $altro['id'] ?>">
    <input type="hidden" name="oggetto" value="<?= e($oggetto['okey']) ?>">
    <button type="submit">Daglielo</button>
  </form>
</div>
<?php endif; ?>

<?php if ($mio['affetto'] >= $soglia): ?>
<div class="carta confessione">
  <p class="occhiello">E poi c'è quell'altra cosa</p>
  <p>
    Potresti dirglielo. Adesso, qui. Non c'è un momento giusto e non ci sarà: c'è solo che
    prima o poi qualcuno lo dice, oppure non lo dice nessuno e finisce lì.
  </p>
  <form method="post" action="<?= e(url('/confessa')) ?>"
        onsubmit="return confirm('Una volta detto non si torna indietro. Sicuro?')">
    <?= csrf_field() ?>
    <input type="hidden" name="verso" value="<?= (int) $altro['id'] ?>">
    <button type="submit">Dirglielo</button>
    <span class="tenue">
      Cuore <?= (int) $pg['cuore'] ?> · costa due punti di compostezza
      <?php if ($loro['fraintendimento'] >= 25): ?>
        · <em>e fra voi c'è un malinteso grosso, che non aiuta</em>
      <?php endif; ?>
    </span>
  </form>
</div>
<?php else: ?>
<div class="nota">
  Per dichiararsi serve un affetto di almeno <?= $soglia ?>. Il tuo è <?= (int) $mio['affetto'] ?>:
  non ci siete ancora, e dirlo adesso non sarebbe vero.
</div>
<?php endif; ?>
