<?php
/**
 * Il riquadro per mettere (o togliere) la fotografia di un personaggio.
 *
 * Lo stesso pezzo serve il giocatore sulla propria pagina e l'amministratore
 * sulla scheda di un altro: cambia solo `$altrui`, che aggiunge il numero del
 * personaggio ai moduli. Un markup solo perché un markup solo si corregge una
 * volta — e perché il riquadro ha già abbastanza trappole senza doverle
 * riscoprire in due copie.
 *
 * @var array<string,mixed> $pg
 * @var string|null $ritratto  indirizzo della fotografia, o null
 * @var bool $altrui           vero se ad agire è l'amministratore su un altro
 */
$altrui   = (bool) ($altrui ?? false);
$iniziale = mb_strtoupper(mb_substr((string) ($pg['nome'] ?? '?'), 0, 1));
$campoPg  = $altrui
    ? '<input type="hidden" name="personaggio" value="' . (int) $pg['id'] . '">'
    : '';
?>
<div class="riquadro-fotografia">
  <div>
    <?php if ($ritratto !== null): ?>
      <img class="fotografia fotografia--grande" src="<?= e(asset($ritratto)) ?>"
           alt="Fotografia di <?= e((string) $pg['nome']) ?>"
           width="<?= (int) \App\Game\Ritratto::LATO ?>" height="<?= (int) \App\Game\Ritratto::LATO ?>">
      <form method="post" action="<?= e(url('/personaggio/profilo/foto/togli')) ?>" style="margin-top:.7rem">
        <?= csrf_field() ?><?= $campoPg ?>
        <button class="bottone secondario piccolo" type="submit">Togli la fotografia</button>
      </form>
    <?php else: ?>
      <div class="fotografia fotografia--grande fotografia--vuota" aria-hidden="true"><?= e($iniziale) ?></div>
      <p class="aiuto" style="margin-top:.6rem">
        Nessuna fotografia: in giro per il quartiere compare l'iniziale.
      </p>
    <?php endif; ?>
  </div>

  <form method="post" action="<?= e(url('/personaggio/profilo/foto')) ?>"
        enctype="multipart/form-data" data-ritaglio>
    <?= csrf_field() ?><?= $campoPg ?>
    <div class="campo">
      <label for="foto">Scegli una fotografia</label>
      <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp" required>
      <span class="aiuto">JPEG, PNG o WebP, fino a 6 MB. Gli SVG no: possono contenere codice.</span>
      <span class="aiuto" style="color:var(--arancio-cupo)" data-avviso></span>
    </div>

    <div class="cornice" hidden><img alt="" draggable="false"></div>

    <div data-comandi hidden>
      <div class="campo" style="margin-top:.8rem">
        <label for="zoom">Stringi o allarga</label>
        <input type="range" id="zoom" name="zoom" min="1" max="4" step="0.02" value="1">
        <span class="aiuto">Trascina la fotografia dentro il quadrato: quello che sta dentro è
          quello che si vedrà.</span>
      </div>
    </div>

    <input type="hidden" name="sx" value="0">
    <input type="hidden" name="sy" value="0">
    <input type="hidden" name="lato" value="0">

    <div class="bottoni"><button type="submit">Metti questa</button></div>
    <noscript>
      <p class="aiuto">Senza JavaScript il riquadro non c'è: la fotografia viene ritagliata
         quadrata e centrata da sola.</p>
    </noscript>
  </form>
</div>
