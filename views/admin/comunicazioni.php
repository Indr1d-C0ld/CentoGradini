<?php
/** @var list<array<string,mixed>> $fili */
$aspettano = array_sum(array_map(static fn (array $f): int => (int) $f['da_leggere'] > 0 ? 1 : 0, $fili));
?>
<h1>Le comunicazioni</h1>
<p class="sommario">
  Il filo diretto con ogni giocatore. <strong><?= count($fili) ?></strong>
  apert<?= count($fili) === 1 ? 'o' : 'i' ?><?php
    if ($aspettano > 0): ?>, di cui <strong><?= $aspettano ?></strong> in attesa di risposta<?php
    endif; ?>.
  In cima stanno quelli che aspettano: in ordine di data sembrerebbero tutti uguali, e la
  domanda di tre giorni fa finirebbe in fondo proprio perché è vecchia.
</p>

<?php if ($fili === []): ?>
  <div class="carta">
    <p>Nessuno ha ancora scritto, e nessuno è stato ancora scritto.</p>
    <p class="aiuto">
      Si comincia dalla scheda di un utente: c'è un riquadro per scrivergli. Gli arriva
      in gioco, e — se la posta è accesa — un'e-mail che gli dice di entrare a leggere.
    </p>
  </div>
<?php else: ?>
<div class="carta">
  <div class="tabella-scorre">
  <table class="tabella">
    <thead><tr><th>giocatore</th><th>messaggi</th><th>ultimo</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($fili as $f): ?>
      <tr class="<?= (int) $f['da_leggere'] > 0 ? 'riga-viva' : '' ?>">
        <td data-etichetta="giocatore">
          <a href="<?= e(url('/admin/comunicazioni/' . (int) $f['user_id'])) ?>"><?=
            e((string) $f['username']) ?></a>
          <?php if ((string) $f['status'] !== 'active'): ?>
            <span class="stato stato-<?= e((string) $f['status']) ?>"><?= e((string) $f['status']) ?></span>
          <?php endif; ?>
        </td>
        <td data-etichetta="messaggi"><?= (int) $f['quanti'] ?></td>
        <td class="minuto" data-etichetta="ultimo"><?= e(substr((string) $f['ultimo_at'], 0, 16)) ?></td>
        <td>
          <?php if ((int) $f['da_leggere'] > 0): ?>
            <span class="pastiglia"><?= (int) $f['da_leggere'] ?> da leggere</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>
