<?php
/** @var list<array<string,mixed>> $luoghi */
/** @var list<array<string,mixed>> $viaggio */
/** @var list<array<string,mixed>> $episodi */
/** @var list<array<string,mixed>> $voci */
/** @var list<array<string,mixed>> $pericolo */
/** @var int $gts */
use App\Sim\Luoghi;
use App\Sim\Orologio;

$giocatori = array_sum(array_map(static fn (array $l): int => count($l['giocatori']), $luoghi));
$abitanti  = array_sum(array_map(static fn (array $l): int => count($l['abitanti']), $luoghi));
?>
<h1>La situazione</h1>
<p class="sommario">
  <?= e(Orologio::esteso($gts)) ?>, ora del quartiere.
  <strong><?= $giocatori ?></strong> giocator<?= $giocatori === 1 ? 'e' : 'i' ?> in giro,
  <strong><?= $abitanti ?></strong> abitanti,
  <strong><?= count($viaggio) ?></strong> per strada.
  I luoghi vuoti restano in elenco: un quartiere vuoto è esso stesso un'informazione.
</p>

<div class="carta carta-mappa">
  <canvas id="mappa" width="1000" height="700" role="img"
          aria-label="Carta del quartiere con le presenze"
          data-api="<?= e(url('/admin/api/carta')) ?>"
          data-luogo="#luogo-"></canvas>
  <p class="aiuto legenda">
    Tocca un luogo per saltare alla sua riga qui sotto. I pallini arancioni sono
    <strong>giocatori</strong>, quelli spenti gli abitanti del quartiere: è l'unica carta in
    cui i due si distinguono, e per questo non esiste fuori da qui.
  </p>
</div>

<?php if ($pericolo !== []): ?>
  <div class="avviso attenzione">
    <strong>Chi rischia il trasloco.</strong>
    <?php foreach ($pericolo as $i => $p): ?>
      <?= $i > 0 ? ' · ' : '' ?><?= e($p['nome'] . ' ' . $p['cognome']) ?>
      (<?= (int) $p['sanno'] ?> l'<?= (int) $p['sanno'] === 1 ? 'ha' : 'hanno' ?> capito)
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if ($episodi !== []): ?>
<div class="carta">
  <h2>Episodi aperti</h2>
  <?php foreach ($episodi as $e): ?>
    <p class="riga-membro">
      <strong><?= e($e['titolo']) ?></strong>
      — <?= e(Luoghi::nome((string) $e['luogo'])) ?>, scena <?= (int) $e['scena'] + 1 ?>,
      <?= (int) $e['quanti'] ?> nel cast
      <span class="aiuto">· la finestra scade fra
        <?= max(0, (int) round(((int) $e['scade_reale'] - time()) / 60)) ?> minuti</span>
    </p>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($viaggio !== []): ?>
<div class="carta">
  <h2>Per strada</h2>
  <?php foreach ($viaggio as $v): ?>
    <p class="riga-membro">
      <?= e($v['nome'] . ' ' . $v['cognome']) ?><?php
        if ($v['png'] !== null): ?> <span class="aiuto">(del quartiere)</span><?php endif; ?>
      — da <?= e(Luoghi::nome((string) $v['luogo'])) ?>
      verso <?= e(Luoghi::nome((string) $v['verso'])) ?>,
      <span class="aiuto">arriva fra <?= max(0, (int) round(((int) $v['arrivo_gts'] - $gts) / 60)) ?> min di gioco</span>
    </p>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="carta">
  <h2>Il quartiere, luogo per luogo</h2>
  <div class="tabella-scorre">
  <table class="tabella">
    <thead><tr>
      <th>luogo</th><th>giocatori</th><th>abitanti</th>
      <th>folla</th><th>calore</th><th>bacheca</th>
    </tr></thead>
    <tbody>
    <?php foreach ($luoghi as $l): ?>
      <tr id="luogo-<?= e($l['lkey']) ?>" class="<?= $l['giocatori'] !== [] ? 'riga-viva' : '' ?>">
        <td data-etichetta="luogo">
          <?= e($l['nome']) ?>
          <?php if (!$l['aperto']): ?><span class="aiuto">chiuso</span><?php endif; ?>
        </td>
        <td data-etichetta="giocatori">
          <?php if ($l['giocatori'] === []): ?><span class="aiuto">—</span><?php endif; ?>
          <?php foreach ($l['giocatori'] as $p): $faccia = \App\Game\Ritratto::di($p); ?>
            <span class="chi-qui">
              <?php if ($faccia !== null): ?>
                <img class="fotografia fotografia--piccola" src="<?= e(asset($faccia)) ?>" alt=""
                     width="34" height="34" loading="lazy">
              <?php endif; ?>
              <?php if ($p['user_id'] !== null): ?>
                <a href="<?= e(url('/admin/utente/' . (int) $p['user_id'])) ?>"><strong><?=
                  e($p['nome'] . ' ' . $p['cognome']) ?></strong></a>
              <?php else: ?>
                <strong><?= e($p['nome'] . ' ' . $p['cognome']) ?></strong>
              <?php endif; ?>
              <?php if ((int) $p['esper'] === 1): ?><span class="aiuto">·e</span><?php endif; ?>
            </span>
          <?php endforeach; ?>
        </td>
        <td class="aiuto" data-etichetta="abitanti">
          <?= $l['abitanti'] === [] ? '—'
              : e(implode(', ', array_map(static fn (array $p): string => (string) $p['nome'], $l['abitanti']))) ?>
        </td>
        <td data-etichetta="folla"><?= (int) $l['folla'] ?></td>
        <td data-etichetta="calore"><?= (int) $l['calore'] > 0
              ? '<strong>' . (int) $l['calore'] . '</strong>'
              : '<span class="aiuto">0</span>' ?></td>
        <td data-etichetta="bacheca"><?= (int) $l['avvisi'] > 0 ? (int) $l['avvisi'] : '<span class="aiuto">—</span>' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <p class="aiuto">
    Il <strong>calore</strong> è quanto un posto è sorvegliato dopo che ci è successo qualcosa
    di strano: sale quando un potere viene notato e si spegne da solo in un paio di giorni.
  </p>
</div>

<?php if ($voci !== []): ?>
<div class="carta">
  <h2>Le voci che girano</h2>
  <div class="tabella-scorre">
  <table class="tabella">
    <thead><tr><th>nata</th><th>tipo</th><th>dove</th><th>la sanno</th><th>passaggi</th></tr></thead>
    <tbody>
    <?php foreach ($voci as $v): ?>
      <tr>
        <td class="minuto" data-etichetta="nata"><?= e(Orologio::esteso((int) $v['gts'])) ?></td>
        <td data-etichetta="tipo"><?= e($v['tipo']) ?></td>
        <td data-etichetta="dove"><?= e(Luoghi::nome((string) $v['luogo'])) ?></td>
        <td data-etichetta="la sanno"><?= (int) $v['quanti'] ?></td>
        <td data-etichetta="passaggi"><?= (int) $v['passaggi'] ?><?= (int) $v['passaggi'] >= 3
              ? ' <span class="aiuto">pettegolezzo</span>' : '' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>

<script src="<?= e(asset('js/carta.js')) ?>" defer></script>
