<?php
/**
 * @var array<string,mixed> $pg
 * @var array<string,mixed> $mondo
 * @var array<string,mixed> $carta
 * @var array<string,mixed> $qui
 * @var array{verso:string,nome:string,restano:int}|null $viaggio
 * @var list<array{md:string,titolo:string,nota:string,giorni:int}> $prossimi
 */
use App\Game\Personaggio;
$luogo = $qui['luogo'];
?>

<?= partial('insegna', ['mondo' => $mondo]) ?>

<div class="riga-titolo">
  <div>
    <p class="occhiello">
      <a href="<?= e(url('/personaggio')) ?>"><?= e(Personaggio::nomeCompleto($pg)) ?></a>
      · <?= e(\App\Game\Scheda::riga($pg)) ?>
      · <a href="<?= e(url('/legami')) ?>">legami</a>
      · <a href="<?= e(url('/ricordi')) ?>">ricordi</a>
      · <a href="<?= e(url('/taccuino')) ?>">taccuino</a>
    </p>
    <p class="occhiello">
      <?php $quanteVoci = \App\Game\Voci::quante((int) $pg['id']); ?>
      <a href="<?= e(url('/voci')) ?>">quello che si dice<?php
        if ($quanteVoci > 0): ?> <span class="pastiglia"><?= $quanteVoci ?></span><?php
        endif; ?></a>
      · <a href="<?= e(url('/bacheca')) ?>">bacheca<?php
        $quantiAvvisi = \App\Game\Bacheca::quantiAvvisi((string) $pg['luogo']);
        if ($quantiAvvisi > 0): ?> <span class="pastiglia"><?= $quantiAvvisi ?></span><?php
        endif; ?></a>
      <?php $bigliettiQui = \App\Game\Bacheca::quiPer((int) $pg['id'], (string) $pg['luogo']); ?>
      · <a href="<?= e(url('/biglietti')) ?>">biglietti<?php
        if ($bigliettiQui !== []): ?> <span class="pastiglia"><?= count($bigliettiQui) ?></span><?php
        endif; ?></a>
      · <a href="<?= e(url('/club')) ?>">club</a>
      · <a href="<?= e(url('/calendario')) ?>">calendario</a>
    </p>
    <h1>Il quartiere</h1>
  </div>
</div>

<?php $episodio = \App\Game\Episodi::mio((int) $pg['id']); ?>
<?php if ($episodio !== null): ?>
  <div class="avviso attenzione">
    <strong>Sta succedendo qualcosa.</strong>
    «<?= e($episodio['titolo']) ?>» —
    <a href="<?= e(url('/episodio')) ?>">vai a vedere</a>.
  </div>
<?php endif; ?>

<?php $pericolo = \App\Game\Segreto::pericolo($pg); ?>
<?php if ($pericolo['stato'] !== 'quieto'): ?>
  <div class="avviso <?= $pericolo['stato'] === 'trasloco' ? 'errore' : 'attenzione' ?>">
    <?php if ($pericolo['scoperti'] > 0): ?>
      <strong><?= $pericolo['scoperti'] ?> <?= $pericolo['scoperti'] === 1 ? 'persona ha' : 'persone hanno' ?> capito.</strong>
    <?php else: ?>
      <strong>Si parla di te.</strong>
    <?php endif; ?>
    <?= e($pericolo['messaggio']) ?>
    <?php if ($pericolo['stato'] !== 'trasloco' && $pericolo['scoperti'] > 0): ?>
      Ne basta <?= $pericolo['soglia'] - $pericolo['scoperti'] ?> in più.
    <?php endif; ?>
    <?php if ($pericolo['dominio'] > 0): ?>
      <span class="tenue">Sui tuoi poteri ne hanno sentito parlare in <?= (int) $pericolo['dominio'] ?>,
        su <?= (int) $pericolo['soglia_dominio'] ?> che basterebbero a farne una cosa che sanno tutti.</span>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php foreach (\App\Core\Database::all(
    "SELECT id, luogo FROM incidenti WHERE attore_id = ? AND stato = 'aperto' ORDER BY id", [(int) $pg['id']]) as $inc): ?>
  <div class="avviso errore">
    <strong>Ti hanno visto, <?= e(\App\Sim\Luoghi::dove((string) $inc['luogo'])) ?>.</strong>
    Qualcuno aspetta una spiegazione, e se non gliela dai si terrà quello che ha visto.
    <a href="<?= e(url('/incidente/' . (int) $inc['id'])) ?>">Rispondi →</a>
  </div>
<?php endforeach; ?>

<?php $sogno = \App\Core\Database::first(
    "SELECT testo FROM ricordi WHERE personaggio_id = ? AND episodio_id IS NULL
       AND titolo IN ('Un sogno', 'Il primo sogno dell''anno') AND gts > ?
     ORDER BY gts DESC LIMIT 1", [(int) $pg['id'], \App\Sim\Orologio::lineare() - 12 * 3600]); ?>
<?php if ($sogno !== null): ?>
  <div class="nota calda">
    <strong>Stanotte hai sognato.</strong> <?= e((string) $sogno['testo']) ?>
    <span class="tenue">Lo trovi fra i <a href="<?= e(url('/ricordi')) ?>">ricordi</a>.</span>
  </div>
<?php endif; ?>

<?php foreach (\App\Game\Segreto::affrontatoDa((int) $pg['id']) as $chi): ?>
  <div class="avviso attenzione">
    <strong><?= e(trim($chi['nome'] . ' ' . $chi['cognome'])) ?> ti ha pres<?= ($pg['sesso'] ?? 'm') === 'f' ? 'a' : 'o' ?> da parte.</strong>
    Sa dei tuoi poteri, e te l'ha detto in faccia. Puoi fidarti — e allora diventa qualcuno che ti
    copre — oppure guardarl<?= $chi['sesso'] === 'f' ? 'a' : 'o' ?> negli occhi e negare.
    <?php if (!\App\Game\Segreto::presenteCon($pg, (int) $chi['id'])): ?>
      <span class="tenue">Adesso non è qui: risponderai quando vi incontrate di nuovo.</span>
    <?php else: ?>
    <div class="scelte-sospetto">
      <form method="post" action="<?= e(url('/confida')) ?>" data-conferma="Dirgli tutto è irreversibile. Sicuro?">
        <?= csrf_field() ?>
        <input type="hidden" name="a" value="<?= (int) $chi['id'] ?>">
        <button type="submit" class="secondario">Confidati</button>
      </form>
      <form method="post" action="<?= e(url('/nega')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="chi" value="<?= (int) $chi['id'] ?>">
        <button type="submit" class="secondario">Nega</button>
      </form>
    </div>
    <?php endif; ?>
  </div>
<?php endforeach; ?>

<?php if ($viaggio !== null): ?>
  <div class="in-viaggio" data-viaggio data-restano="<?= (int) $viaggio['restano'] ?>">
    <span class="passi" aria-hidden="true">›››</span>
    <div>
      <b>Sei per strada, verso <?= e($viaggio['nome']) ?>.</b>
      <?php if (($viaggio['chiuso'] ?? null) !== null): ?>
        <span><?= e($viaggio['chiuso']) ?></span>
      <?php endif; ?>
      <span data-conto>Arrivi fra <?= e(App\Game\Personaggio::quantoFa(0)) ?></span>
    </div>
  </div>
<?php endif; ?>

<div class="carta carta-mappa">
  <canvas id="mappa" width="1000" height="700" role="img"
          aria-label="Carta del quartiere"
          data-api="<?= e(url('/api/carta')) ?>"
          data-luogo="<?= e(url('/luogo/')) ?>"></canvas>
  <p class="aiuto legenda">
    Tocca un luogo per guardarlo da vicino. Il punto arancione sei tu; i cerchietti
    sono le altre persone che ci si trovano adesso.
  </p>
</div>

<noscript>
  <div class="nota">
    La carta ha bisogno di JavaScript. Sotto c'è comunque tutto quello che serve per muoversi.
  </div>
</noscript>

<div class="carta">
  <p class="occhiello">Dove sei</p>
  <h2 style="margin-top:0"><?= e($luogo['nome']) ?></h2>
  <?php if ($luogo['sottotitolo'] !== null): ?>
    <p class="sommario" style="margin-top:-.3rem"><?= e($luogo['sottotitolo']) ?></p>
  <?php endif; ?>
  <p><?= e($luogo['descrizione']) ?></p>
  <?php if (($qui['calore_dice'] ?? '') !== ''): ?>
    <p class="tenue"><em><?= e($qui['calore_dice']) ?></em></p>
  <?php endif; ?>
  <p class="tenue">Qui <?= e($qui['folla_dice'] ?? '') ?>.</p>
  <p><a href="<?= e(url('/luogo/' . $luogo['lkey'])) ?>">Guarda meglio →</a></p>
</div>

<?php foreach (\App\Game\Legami::oggettiDi((int) $pg['id']) as $o): if ($o['okey'] !== 'orologio') { continue; } ?>
  <div class="carta">
    <p class="occhiello">In tasca</p>
    <h2 style="margin-top:0"><?= e($o['nome']) ?></h2>
    <?php if ($o['pronto_gts'] !== null && (int) $o['pronto_gts'] > \App\Sim\Orologio::lineare()): ?>
      <p class="tenue">È scarico. Si ricarica da solo, ma ci vogliono giorni.</p>
    <?php else: ?>
      <p class="tenue">Un minuto in cui nessuno vede niente di quello che fai. Poi va ricaricato.</p>
      <form method="post" action="<?= e(url('/oggetto/usa')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="oggetto" value="orologio">
        <button type="submit" class="secondario">Premi il pulsante</button>
      </form>
    <?php endif; ?>
  </div>
<?php endforeach; ?>

<?php foreach (\App\Game\Legami::oggettiQui((string) $pg['luogo']) as $o): ?>
  <div class="carta">
    <p class="occhiello">C'è una cosa, qui</p>
    <h2 style="margin-top:0"><?= e($o['nome']) ?></h2>
    <p><?= e($o['descrizione']) ?></p>
    <form method="post" action="<?= e(url('/oggetto/raccogli')) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="oggetto" value="<?= e($o['okey']) ?>">
      <button type="submit" class="secondario">Prendilo</button>
    </form>
  </div>
<?php endforeach; ?>

<?= partial('poteri_qui', ['pg' => $pg, 'dati' => $qui]) ?>

<?php if ($qui['presenti'] !== []): ?>
<div class="carta">
  <p class="occhiello">Chi c'è</p>
  <ul class="elenco-persone">
    <?php foreach ($qui['presenti'] as $p): ?>
      <li>
        <?= partial('faccia', ['p' => $p, 'collega' => url('/chi/' . (int) $p['id'])]) ?>
        <div>
          <b><a href="<?= e(url('/verso/' . (int) $p['id'])) ?>"><?= e(trim($p['cognome'] . ' ' . $p['nome'])) ?></a></b>
          <span class="tenue">
            <?= e($p['classe']) ?> · <?= $p['sempre'] ? 'c\'è sempre' : 'da ' . e(Personaggio::quantoFa((int) $p['da_minuti'])) ?>
            <?php if ($p['compleanno']): ?> · <em>oggi è il suo compleanno</em><?php endif; ?>
            <?php if (($p['addosso'] ?? '') !== ''): ?> · <?= e($p['addosso']) ?><?php endif; ?>
            <?php if (\App\Game\Segreto::sa((int) $pg['id'], (int) $p['id'])): ?>
              · <em>sa di te</em>
            <?php endif; ?>
          </span>
        </div>
        <?php if ((bool) $pg['esper'] && !\App\Game\Segreto::sa((int) $pg['id'], (int) $p['id'])): ?>
          <form method="post" action="<?= e(url('/confida')) ?>"
                data-conferma="Dirgli il tuo segreto è irreversibile. Sicuro?">
            <?= csrf_field() ?>
            <input type="hidden" name="a" value="<?= (int) $p['id'] ?>">
            <button type="submit" class="tenue">confidati</button>
          </form>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<?php if ($viaggio === null): ?>
<div class="carta">
  <p class="occhiello">Dove puoi andare</p>
  <ul class="uscite">
    <?php foreach ($qui['uscite'] as $u): ?>
      <li class="uscita<?= $u['aperto'] ? '' : ' chiusa' ?>">
        <div class="uscita-testo">
          <b><?= e($u['luogo']['nome']) ?></b>
          <span class="tenue">
            <?= (int) $u['minuti'] ?> min<?= $u['mezzo'] === 'treno' ? ' di treno' : '' ?>
            <?php if (!$u['aperto']): ?> · <?= e((string) $u['motivo']) ?><?php endif; ?>
          </span>
        </div>
        <?php if ($u['aperto']): ?>
          <form method="post" action="<?= e(url('/vai')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="verso" value="<?= e($u['a']) ?>">
            <button type="submit" class="secondario">vai</button>
          </form>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<?php if ($qui['tracce'] !== []): ?>
<div class="carta">
  <p class="occhiello">Cosa è rimasto qui</p>
  <ul class="tracce">
    <?php foreach ($qui['tracce'] as $t): ?>
      <li style="opacity:<?= max(0.35, $t['forza'] / 100) ?>">
        <?= e($t['testo']) ?>
        <span class="tenue"><?= e(Personaggio::quantoFa((int) $t['fa_minuti'])) ?></span>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<?php if ($prossimi !== []): ?>
<div class="carta">
  <p class="occhiello">Sta per succedere</p>
  <?php foreach ($prossimi as $ev): ?>
    <p style="margin-bottom:.6rem">
      <b><?= e($ev['titolo']) ?></b>
      <span class="tenue">
        <?= $ev['giorni'] === 0 ? 'oggi' : ($ev['giorni'] === 1 ? 'domani' : 'fra ' . (int) $ev['giorni'] . ' giorni') ?>
      </span><br>
      <span class="tenue"><?= e($ev['nota']) ?></span>
    </p>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<script src="<?= e(asset('js/carta.js')) ?>" defer></script>
<script src="<?= e(asset('js/mondo.js')) ?>"
        data-battito="<?= e(url('/api/battito')) ?>" defer></script>
