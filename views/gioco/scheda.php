<?php
/**
 * @var array<string,mixed> $pg
 * @var array<string,mixed> $mondo
 * @var list<array<string,mixed>> $tratti
 * @var list<array<string,mixed>> $poteri
 */
use App\Game\Personaggio;
use App\Game\Ritratto;
use App\Game\Scheda;
use App\Sim\Orologio;
use App\Sim\Scuola;

$eta = Scuola::eta((int) $pg['anno_nascita'], (int) $pg['nato_mese'], (int) $pg['nato_giorno'], $mondo['gts']);
$alCompleanno = Scuola::giorniAlCompleanno((int) $pg['nato_mese'], (int) $pg['nato_giorno'], $mondo['gts']);
?>

<?= partial('insegna', ['mondo' => $mondo]) ?>

<p class="occhiello"><a href="<?= e(url('/quartiere')) ?>">← il quartiere</a></p>

<?php $faccia = Ritratto::di($pg); ?>
<div class="riga-fotografia">
  <?php if ($faccia !== null): ?>
    <img class="fotografia fotografia--media" src="<?= e(asset($faccia)) ?>"
         alt="La tua fotografia" width="80" height="80">
  <?php else: ?>
    <div class="fotografia fotografia--media fotografia--vuota" aria-hidden="true"><?=
      e(mb_strtoupper(mb_substr((string) $pg['nome'], 0, 1))) ?></div>
  <?php endif; ?>
  <div>
    <h1 style="margin:0"><?= e(Personaggio::nomeCompleto($pg)) ?></h1>
    <p class="sommario" style="margin:.2rem 0 0">
      <?= e(Scuola::nomeClasse((string) $pg['sezione'], (int) $pg['anno'])) ?>
      <span class="tenue">(<?= e(Scuola::siglaClasse((string) $pg['sezione'], (int) $pg['anno'])) ?>)</span>
      · <?= $eta ?> anni
      · <?= (bool) $pg['esper'] ? 'della stirpe' : 'nessun potere' ?>
    </p>
    <p class="occhiello" style="margin:.35rem 0 0">
      <a href="<?= e(url('/personaggio/profilo')) ?>"><?=
        $faccia === null ? 'metti una fotografia' : 'cambia la fotografia' ?></a>
    </p>
  </div>
</div>

<?php if ((string) ($pg['aspetto'] ?? '') !== ''): ?>
  <p class="sommario"><em><?= e((string) $pg['aspetto']) ?></em></p>
<?php endif; ?>

<?php if ($alCompleanno === 0): ?>
  <div class="evento-oggi"><b>Oggi è il tuo compleanno.</b>
    <span>Compi <?= $eta ?> anni. Vedremo se se ne ricorda qualcuno.</span></div>
<?php endif; ?>

<div class="carta">
  <p class="occhiello">Abilità</p>
  <div class="barre">
    <?php foreach (Scheda::ABILITA as $a): $v = (int) $pg[$a]; ?>
      <div class="barra">
        <span class="barra-nome"><?= e(Scheda::NOMI[$a]) ?></span>
        <span class="barra-solco"><i style="width:<?= round($v / 15 * 100) ?>%"></i></span>
        <b class="barra-valore"><?= $v ?></b>
      </div>
    <?php endforeach; ?>
  </div>
  <p class="aiuto" style="margin-top:.9rem">
    Quindici è il grado divino. Il <strong>Cuore</strong> è la capacità di dire quello che
    provi: nell'opera è la statistica più bassa di tutti e tre i protagonisti, ed è il motivo
    per cui la storia dura diciotto volumi.
  </p>
</div>

<div class="griglia">
  <div class="carta dato"><b><?= (int) $pg['pf'] ?>/<?= (int) $pg['pf_max'] ?></b><span>punti ferita</span></div>
  <?php if ((bool) $pg['esper']): ?>
    <div class="carta dato"><b><?= (int) $pg['pp'] ?>/<?= (int) $pg['pp_max'] ?></b><span>punti potere</span></div>
  <?php else: ?>
    <div class="carta dato"><b><?= Scheda::intuizione($pg) ?>%</b><span>intuizione</span></div>
  <?php endif; ?>
  <div class="carta dato"><b><?= (int) $pg['compostezza'] ?>/<?= (int) $pg['compostezza_max'] ?></b><span>compostezza</span></div>
  <div class="carta dato">
    <b><?= $alCompleanno === 0 ? 'oggi' : $alCompleanno ?></b>
    <span><?= $alCompleanno === 0 ? 'è il tuo compleanno' : 'giorni al compleanno' ?></span>
  </div>
</div>

<div class="carta">
  <p class="occhiello">Varie ed eventuali</p>
  <table class="tabellina">
    <?php foreach (Scheda::SECONDARIE as $k): ?>
      <tr>
        <td style="width:7rem"><b><?= e(Scheda::NOMI[$k]) ?></b></td>
        <td><span class="pallini" aria-label="<?= (int) $pg[$k] ?> su 10"><?php
          for ($i = 1; $i <= 10; $i++) { echo $i <= (int) $pg[$k] ? '●' : '○'; }
        ?></span></td>
        <td class="tenue" style="font-size:.86rem"><?= e(Scheda::SPIEGAZIONI[$k]) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php if ($tratti !== []): ?>
<div class="carta">
  <p class="occhiello">Come sei fatto</p>
  <?php foreach ($tratti as $t): ?>
    <div class="tratto">
      <h3><?= e($t['nome']) ?></h3>
      <p><?= e($t['descrizione']) ?></p>
      <p class="obiettivo"><span>il tuo scopo</span> <?= e($t['obiettivo']) ?></p>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($poteri !== []): ?>
<div class="carta">
  <p class="occhiello">Quello che nessuno deve sapere</p>
  <?php foreach ($poteri as $p): ?>
    <div class="tratto">
      <h3>
        <?= e($p['nome']) ?>
        <span class="tenue"><?= e($p['nome_jp']) ?></span>
        <?php if ((int) $p['primario'] === 1): ?><em class="marchio-primario">principale</em><?php endif; ?>
      </h3>
      <p><?= e($p['descrizione']) ?></p>
      <p class="tenue"><strong>Limiti.</strong> <?= e($p['limiti']) ?></p>
      <div class="controllo">
        <span class="barra-solco"><i style="width:<?= (int) $p['controllo'] ?>%"></i></span>
        <span class="tenue">controllo <?= (int) $p['controllo'] ?>%
          · costa <?= Scheda::costo($p) ?> PP
          <?php if ((int) $p['usi'] > 0): ?> · usato <?= (int) $p['usi'] ?> volte<?php endif; ?>
        </span>
      </div>
    </div>
  <?php endforeach; ?>
  <p class="aiuto">
    Il numero dei poteri non cambierà mai: non si guadagnano con l'esperienza, e chi ne ha
    pochi se li tiene. Quello che cresce è il <strong>controllo</strong>, cioè la precisione —
    nel manga Kyosuke non impara poteri nuovi, impara a prendere la mira.
  </p>
</div>
<?php else: ?>
<div class="carta">
  <p class="occhiello">Nessun potere</p>
  <p>
    La tua <strong>Intuizione</strong> è del <b><?= Scheda::intuizione($pg) ?>%</b>: è la
    probabilità di accorgerti che qualcosa non torna. In questo quartiere è l'unico modo per
    scoprire il segreto di qualcun altro — e gli esper, che devono stare bassi, non possono
    permettersi di guardare con la tua attenzione.
  </p>
</div>
<?php endif; ?>
