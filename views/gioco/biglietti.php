<?php
/** @var array<string,mixed> $mondo */
/** @var array<string,mixed> $pg */
/** @var list<array<string,mixed>> $qui */
/** @var list<array<string,mixed>> $letti */
/** @var list<array<string,mixed>> $miei */
use App\Game\Personaggio;
use App\Sim\Luoghi;
use App\Sim\Orologio;

$presenti = Personaggio::presenti((string) $pg['luogo'], (int) $pg['id']);
?>
<?= partial('insegna', ['mondo' => $mondo]) ?>
<p class="occhiello">
  <a href="<?= e(url('/quartiere')) ?>">← il quartiere</a>
  · <a href="<?= e(url('/bacheca')) ?>">la bacheca</a>
</p>
<h1>I biglietti</h1>
<p class="sommario">
  Un biglietto non arriva: si trova. Lo lasci dove sei, e chi deve leggerlo lo legge quando
  ci passa. Se non ci passa, resta lì. È il mezzo di comunicazione di tutta questa storia —
  il foglietto nell'armadietto delle scarpe, quello sotto il banco, quello infilato nella
  borsa — e la sua caratteristica è proprio l'attesa.
</p>

<div class="carta">
  <h2>Qui, per te</h2>
  <?php if ($qui === []): ?>
    <p class="aiuto">Niente, <?= e(Luoghi::nome((string) $pg['luogo'])) ?>. Prova altrove.</p>
  <?php endif; ?>
  <?php foreach ($qui as $b): ?>
    <div class="biglietto chiuso">
      <p class="occhiello">
        <?= (int) $b['anonimo'] === 1
            ? 'nessuna firma'
            : e(trim((string) ($b['nome'] ?? '') . ' ' . (string) ($b['cognome'] ?? ''))) ?>
        · <?= e(Orologio::esteso((int) $b['gts'])) ?>
      </p>
      <form method="post" action="<?= e(url('/biglietto/leggi')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="biglietto" value="<?= (int) $b['id'] ?>">
        <button class="bottone" type="submit">Aprilo</button>
      </form>
    </div>
  <?php endforeach; ?>
</div>

<div class="carta">
  <h2>Lasciarne uno</h2>
  <?php if ($presenti === []): ?>
    <p class="aiuto">
      Non c'è nessuno, <?= e(Luoghi::nome((string) $pg['luogo'])) ?>. Un biglietto si lascia a
      chi è qui: bisogna sapere dove mettergliela, la carta.
    </p>
  <?php else: ?>
    <form method="post" action="<?= e(url('/biglietto/lascia')) ?>">
      <?= csrf_field() ?>
      <label>A chi
        <select name="a">
          <?php foreach ($presenti as $p): ?>
            <option value="<?= (int) $p['id'] ?>">
              <?= e($p['nome'] . ' ' . $p['cognome']) ?> — <?= e($p['classe']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Che gli scrivi <textarea name="testo" rows="4" maxlength="600" required></textarea></label>
      <label class="in_linea">
        <input type="checkbox" name="anonimo" value="1"> senza firma
      </label>
      <button class="bottone" type="submit">Lascialo</button>
    </form>
  <?php endif; ?>
</div>

<?php if ($miei !== []): ?>
<div class="carta">
  <h2>Quelli che hai lasciato tu</h2>
  <?php foreach ($miei as $b): ?>
    <p class="riga-biglietto">
      <strong><?= e($b['nome'] . ' ' . $b['cognome']) ?></strong>
      — <?= e(Luoghi::nome((string) $b['luogo'])) ?>,
      <?= e(Orologio::esteso((int) $b['gts'])) ?>.
      <?= $b['letto_gts'] === null
          ? '<span class="aiuto">non ancora raccolto</span>'
          : '<span class="aiuto">letto</span>' ?>
    </p>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($letti !== []): ?>
<div class="carta">
  <h2>Quelli che hai già letto</h2>
  <?php foreach ($letti as $b): ?>
    <div class="biglietto">
      <p class="occhiello">
        <?= (int) $b['anonimo'] === 1
            ? 'nessuna firma'
            : e(trim((string) ($b['nome'] ?? '') . ' ' . (string) ($b['cognome'] ?? ''))) ?>
        · <?= e(Orologio::esteso((int) $b['gts'])) ?>
      </p>
      <?php foreach (preg_split('/\n\n+/', (string) $b['testo']) ?: [] as $par): ?>
        <p><?= e(trim($par)) ?></p>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
