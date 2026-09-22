<?php
/**
 * @var array<string,mixed> $pg
 * @var array<string,mixed> $altro
 * @var array<string,mixed> $mondo
 * @var bool $qui
 * @var list<array<string,mixed>> $club
 * @var array{affetto:int,fraintendimento:int,esiste:bool} $mio
 */
use App\Game\Legami;
use App\Game\Ritratto;
use App\Sim\Luoghi;
use App\Sim\Scuola;

$faccia = Ritratto::di($altro);
$via    = (string) $altro['stato'] !== 'attivo';
?>

<?= partial('insegna', ['mondo' => $mondo]) ?>

<p class="occhiello"><a href="<?= e(url('/legami')) ?>">← i legami</a></p>

<div class="riga-fotografia">
  <?php if ($faccia !== null): ?>
    <img class="fotografia fotografia--grande" src="<?= e(asset($faccia)) ?>"
         alt="Fotografia di <?= e((string) $altro['nome']) ?>" width="160" height="160">
  <?php else: ?>
    <div class="fotografia fotografia--grande fotografia--vuota" aria-hidden="true"><?=
      e(mb_strtoupper(mb_substr((string) $altro['nome'], 0, 1))) ?></div>
  <?php endif; ?>
  <div>
    <h1 style="margin:0"><?= e($altro['cognome'] . ' ' . $altro['nome']) ?></h1>
    <p class="sommario" style="margin:.25rem 0 0">
      <?= e(Scuola::nomeClasse((string) $altro['sezione'], (int) $altro['anno'])) ?>
      <?php if ($via): ?> · <em>se n'è andato dal quartiere</em><?php endif; ?>
    </p>
    <?php if ((string) ($altro['aspetto'] ?? '') !== ''): ?>
      <p style="margin:.5rem 0 0"><em><?= e((string) $altro['aspetto']) ?></em></p>
    <?php endif; ?>
    <?php if ($qui): ?>
      <p class="occhiello" style="margin:.5rem 0 0">
        È qui con te, <?= e(Luoghi::dove((string) $altro['luogo'])) ?>.
        <a href="<?= e(url('/verso/' . (int) $altro['id'])) ?>">cosa puoi fare →</a>
      </p>
    <?php endif; ?>
  </div>
</div>

<?php if ($club !== []): ?>
<div class="carta">
  <p class="occhiello">Che club frequenta</p>
  <p><?= e(implode(' · ', array_column($club, 'nome'))) ?></p>
  <p class="aiuto">
    Il club è una cosa che si sa di una persona senza bisogno di chiederglielo: ci si passa
    davanti e la si vede lì dentro tre volte alla settimana.
  </p>
</div>
<?php endif; ?>

<div class="carta">
  <p class="occhiello">Cosa provi</p>
  <?php if ($mio['esiste']): ?>
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
      <div class="barra">
        <span class="barra-nome">Malinteso</span>
        <span class="barra-solco"><i style="width:<?= (int) $mio['fraintendimento'] ?>%"></i></span>
        <b class="barra-valore"><?= (int) $mio['fraintendimento'] ?></b>
      </div>
    </div>
  <?php else: ?>
    <p>Niente, ancora. Vi siete solo incrociati.</p>
  <?php endif; ?>
  <p class="aiuto" style="margin-top:.9rem">
    Quello che <strong>lei o lui</strong> prova per te non è scritto da nessuna parte, e non è
    una dimenticanza: in questa storia nessuno lo sa mai, ed è esattamente il motivo per cui
    dura diciotto volumi.
  </p>
</div>
