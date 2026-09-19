<?php
/** @var array<string,mixed> $mondo */
/** @var array<string,mixed> $pg */
/** @var list<array<string,mixed>> $avvisi */
/** @var list<array<string,mixed>> $biglietti */
/** @var list<array<string,mixed>> $presenti */
use App\Sim\Luoghi;
?>
<?= partial('insegna', ['mondo' => $mondo]) ?>
<p class="occhiello">
  <a href="<?= e(url('/quartiere')) ?>">← il quartiere</a>
  · <a href="<?= e(url('/biglietti')) ?>">i biglietti</a>
</p>
<h1>La bacheca</h1>
<p class="sommario">
  <?= e(Luoghi::nome((string) $pg['luogo'])) ?>. Quello che si appende qui lo legge chiunque
  passi, e resta appeso per giorni. È il modo più veloce di far sapere una cosa a tutto il
  quartiere — e il più difficile da ritirare.
</p>

<?php if ($biglietti !== []): ?>
  <div class="avviso attenzione">
    <strong>C'è qualcosa per te.</strong>
    <?= count($biglietti) === 1 ? 'Un biglietto' : count($biglietti) . ' biglietti' ?>
    che ti aspettano proprio qui —
    <a href="<?= e(url('/biglietti')) ?>">vai a prenderli</a>.
  </div>
<?php endif; ?>

<?php if ($avvisi === []): ?>
  <div class="carta"><p>La bacheca è vuota. Capita, dopo qualche giorno di pioggia.</p></div>
<?php endif; ?>

<?php foreach ($avvisi as $a): ?>
  <div class="carta avviso-bacheca tipo-<?= e($a['tipo']) ?>">
    <p class="occhiello">
      <?= e(match ($a['tipo']) {
          'cerca'   => 'cerco',
          'offre'   => 'offro',
          'club'    => 'club',
          'anonimo' => 'senza firma',
          default   => 'avviso',
      }) ?>
      · <?= e($a['mostra_firma']) ?>
      · <?= (int) $a['fa_ore'] < 1 ? 'appeso adesso' : 'appeso ' . (int) $a['fa_ore'] . ' ore fa' ?>
    </p>
    <h2 style="margin-top:0"><?= e($a['titolo']) ?></h2>
    <?php foreach (preg_split('/\n\n+/', (string) $a['testo']) ?: [] as $par): ?>
      <p><?= e(trim($par)) ?></p>
    <?php endforeach; ?>
    <?php if ((int) ($a['autore_id'] ?? 0) === (int) $pg['id']): ?>
      <form method="post" action="<?= e(url('/bacheca/stacca')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="avviso" value="<?= (int) $a['id'] ?>">
        <button class="bottone secondario piccolo" type="submit">Staccalo</button>
      </form>
    <?php endif; ?>
  </div>
<?php endforeach; ?>

<div class="carta">
  <h2>Appendere un foglio</h2>
  <form method="post" action="<?= e(url('/bacheca/affiggi')) ?>">
    <?= csrf_field() ?>
    <label>Che cos'è
      <select name="tipo">
        <option value="avviso">Un avviso</option>
        <option value="cerca">Cerco qualcosa (o qualcuno)</option>
        <option value="offre">Offro qualcosa</option>
        <option value="club">Roba di club</option>
        <option value="anonimo">Senza firma</option>
      </select>
    </label>
    <label>Titolo <input type="text" name="titolo" maxlength="80" required></label>
    <label>Testo <textarea name="testo" rows="4" maxlength="600" required></textarea></label>
    <label>Come ti firmi
      <input type="text" name="firma" maxlength="48"
             placeholder="lascia vuoto per il tuo nome">
    </label>
    <p class="aiuto">
      La firma può essere qualunque cosa, e nel manga succede in continuazione. Un foglio
      senza firma non fa nascere nessuna voce: nessuno ha visto chi lo ha appeso.
    </p>
    <button class="bottone" type="submit">Appendi</button>
  </form>
</div>
