<?php
/**
 * @var array<string,mixed> $pg
 * @var array<string,mixed> $mondo
 * @var list<array<string,mixed>> $tratti
 * @var list<array<string,mixed>> $poteri
 * @var int $punti
 */
use App\Game\Personaggio;
use App\Game\Scheda;
use App\Sim\Scuola;
?>
<p class="occhiello">Quello che ti è toccato</p>
<h1><?= e(Personaggio::nomeCompleto($pg)) ?></h1>
<p class="sommario">
  <?= e(Scuola::nomeClasse((string) $pg['sezione'], (int) $pg['anno'])) ?> ·
  compleanno il <?= (int) $pg['nato_giorno'] ?>
  <?= e(\App\Sim\Orologio::nomeMese((int) $pg['nato_mese'])) ?> ·
  <?= (bool) $pg['esper'] ? 'della stirpe' : 'nessun potere' ?>
</p>

<div class="nota calda">
  I dadi sono già stati tirati e non si ritirano: ricaricare la pagina non cambia niente.
  Quello che puoi fare è distribuire <strong><?= (int) $punti ?> punti</strong> come vuoi,
  fino a un massimo di 15 per abilità.
</div>

<div class="carta">
  <form method="post" action="<?= e(url('/personaggio/abilita')) ?>" id="modulo-abilita">
    <?= csrf_field() ?>

    <table class="tabellina abilita">
      <thead>
        <tr><th>Abilità</th><th style="text-align:right">Tirato</th><th style="width:9rem">Punti</th><th style="text-align:right">Totale</th></tr>
      </thead>
      <tbody>
      <?php foreach (Scheda::ABILITA as $a): $base = (int) $pg[$a]; ?>
        <tr>
          <td>
            <b><?= e(Scheda::NOMI[$a]) ?></b><br>
            <span class="tenue"><?= e(Scheda::SPIEGAZIONI[$a]) ?></span>
          </td>
          <td style="text-align:right"><b><?= $base ?></b></td>
          <td>
            <input type="number" name="p_<?= e($a) ?>" value="0" min="0" max="<?= max(0, 15 - $base) ?>"
                   data-base="<?= $base ?>" class="punti" aria-label="punti su <?= e(Scheda::NOMI[$a]) ?>">
          </td>
          <td style="text-align:right"><b data-totale="<?= e($a) ?>"><?= $base ?></b></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <p class="resto">Restano <b data-resto><?= (int) $punti ?></b> punti.</p>

    <div class="bottoni">
      <button type="submit">Chiudi la scheda</button>
    </div>
  </form>
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
  <p class="occhiello">Quello che sai fare, e che nessuno deve sapere</p>
  <?php foreach ($poteri as $p): ?>
    <div class="tratto">
      <h3>
        <?= e($p['nome']) ?>
        <span class="tenue"><?= e($p['nome_jp']) ?></span>
        <?php if ((int) $p['primario'] === 1): ?><em class="marchio-primario">principale</em><?php endif; ?>
      </h3>
      <p><?= e($p['descrizione']) ?></p>
      <p class="tenue"><strong>Limiti.</strong> <?= e($p['limiti']) ?></p>
    </div>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="carta">
  <p class="occhiello">Nessun potere</p>
  <p>
    E va benissimo. Quello che hai al posto loro è l'<strong>Intuizione</strong>: la probabilità
    di accorgerti di qualcosa che non torna. Con la Testa che hai adesso sarebbe del
    <b><?= Scheda::intuizione($pg) ?>%</b>, e sale se metti punti là.
  </p>
  <p class="tenue">
    È l'unico modo, in questo quartiere, per scoprire il segreto di qualcun altro.
  </p>
</div>
<?php endif; ?>

<script src="<?= e(asset('js/abilita.js')) ?>" defer></script>
