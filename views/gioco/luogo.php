<?php
/**
 * @var array<string,mixed> $pg
 * @var array<string,mixed> $mondo
 * @var array<string,mixed> $dati
 * @var bool $sono_qui
 * @var array{verso:string,nome:string,restano:int}|null $viaggio
 * @var array{minuti:int,passi:list<string>}|null $quanto
 */
use App\Game\Personaggio;
use App\Sim\Luoghi;
$luogo = $dati['luogo'];
?>

<?= partial('insegna', ['mondo' => $mondo]) ?>

<p class="occhiello">
  <a href="<?= e(url('/quartiere')) ?>">← il quartiere</a>
</p>
<h1><?= e($luogo['nome']) ?></h1>
<?php if ($luogo['sottotitolo'] !== null): ?>
  <p class="sommario"><?= e($luogo['sottotitolo']) ?></p>
<?php endif; ?>

<?php $foto = Luoghi::foto((string) $luogo['lkey']); ?>
<div class="carta">
  <?php if ($foto !== null): ?>
    <figure class="foto-luogo">
      <img src="<?= e(asset('img/luoghi/' . $foto['file'])) ?>"
           alt="<?= e((($foto['tipo'] ?? '') === 'generata' ? 'Immagine generata. ' : '')
                       . $foto['didascalia']) ?>" loading="lazy">
      <figcaption>
        <?= e($foto['didascalia']) ?>
        <span class="aiuto">
          <?php if (($foto['tipo'] ?? '') === 'fotografia'): ?>
            Fotografia di <?= e($foto['autore']) ?>,
            <a href="<?= e($foto['licenza_url']) ?>" rel="license noopener"><?= e($foto['licenza']) ?></a>,
            da <a href="<?= e($foto['origine']) ?>" rel="noopener">Wikimedia Commons</a>.
          <?php else: ?>
            <em>Immagine generata, non una fotografia:</em> questo posto non esiste, e i
            cartelli che ci si leggono sono nomi del gioco.
          <?php endif; ?>
        </span>
      </figcaption>
    </figure>
  <?php else: ?>
    <?= \App\Game\Illustrazione::perLuogo((string) $luogo['lkey'], $mondo['lineare']) ?>
  <?php endif; ?>
  <p><?= e($luogo['descrizione']) ?></p>
  <?php $veduta = Luoghi::veduta((string) $luogo['lkey'], $mondo['lineare']); ?>
  <?php if ($veduta !== ''): ?>
    <p class="veduta"><?= e($veduta) ?></p>
  <?php endif; ?>
  <p class="tenue">
    Qui <?= e($dati['folla_dice'] ?? '') ?>.
    <?php if (($dati['calore_dice'] ?? '') !== ''): ?><em><?= e($dati['calore_dice']) ?></em><?php endif; ?>
  </p>
  <?php if (!$dati['aperto']): ?>
    <p class="tenue"><em><?= e((string) (Luoghi::accessibile($luogo['lkey'], $mondo['lineare'])[1] ?? '')) ?></em></p>
  <?php endif; ?>
</div>

<?php if ($sono_qui): ?>

  <?= partial('poteri_qui', ['pg' => $pg, 'dati' => $dati]) ?>

  <?php if ($dati['presenti'] !== []): ?>
  <div class="carta">
    <p class="occhiello">Chi c'è adesso</p>
    <ul class="elenco-persone">
      <?php foreach ($dati['presenti'] as $p): $faccia = \App\Game\Ritratto::di($p); ?>
        <li>
          <?php if ($faccia !== null): ?>
            <img class="fotografia fotografia--piccola" src="<?= e(asset($faccia)) ?>" alt=""
                 width="40" height="40" loading="lazy">
          <?php endif; ?>
          <b><a href="<?= e(url('/verso/' . (int) $p['id'])) ?>"><?= e($p['cognome'] . ' ' . $p['nome']) ?></a></b>
          <span class="tenue">
            <?= e($p['classe']) ?> · da <?= e(Personaggio::quantoFa((int) $p['da_minuti'])) ?>
            <?php if ($p['compleanno']): ?> · <em>oggi è il suo compleanno</em><?php endif; ?>
          </span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php else: ?>
  <div class="nota">Non c'è nessun altro, adesso.</div>
  <?php endif; ?>

  <?php if ($dati['passati'] !== []): ?>
  <div class="carta">
    <p class="occhiello">C'era, poco fa</p>
    <ul class="elenco-persone">
      <?php foreach ($dati['passati'] as $p): ?>
        <li>
          <b><a href="<?= e(url('/verso/' . (int) $p['id'])) ?>"><?= e($p['cognome'] . ' ' . $p['nome']) ?></a></b>
          <span class="tenue"><?= e(Personaggio::quantoFa((int) $p['fa_minuti'])) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

<?php else: ?>

  <div class="carta">
    <p class="occhiello">Non sei qui</p>
    <?php if ($viaggio !== null): ?>
      <p>Sei per strada verso <?= e($viaggio['nome']) ?>: prima bisogna arrivare.</p>
    <?php elseif ($quanto !== null): ?>
      <p>
        Da <?= e(Luoghi::nome((string) $pg['luogo'])) ?> ci vogliono
        <b><?= (int) $quanto['minuti'] ?> minuti</b>,
        <?= count($quanto['passi']) > 2 ? 'passando per ' . e(implode(', ', array_map(
              static fn (string $k): string => Luoghi::nome($k),
              array_slice($quanto['passi'], 1, -1)
            ))) : 'in un tratto solo' ?>.
      </p>
      <?php $passo = $quanto['passi'][1] ?? null; ?>
      <?php if ($passo !== null): ?>
        <form method="post" action="<?= e(url('/vai')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="verso" value="<?= e($passo) ?>">
          <button type="submit">Incamminati verso <?= e(Luoghi::nome($passo)) ?></button>
        </form>
      <?php endif; ?>
    <?php else: ?>
      <p>Da dove sei adesso non c'è modo di arrivarci.</p>
    <?php endif; ?>
  </div>

<?php endif; ?>

<?php if ($dati['tracce'] !== []): ?>
<div class="carta">
  <p class="occhiello">Cosa è rimasto qui</p>
  <ul class="tracce">
    <?php foreach ($dati['tracce'] as $t): ?>
      <li style="opacity:<?= max(0.35, $t['forza'] / 100) ?>">
        <?= e($t['testo']) ?>
        <span class="tenue"><?= e(Personaggio::quantoFa((int) $t['fa_minuti'])) ?></span>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>
