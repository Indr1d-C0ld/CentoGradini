<?php
/** @var list<array<string,mixed>> $righe */
use App\Game\Ritratto;
?>
<h1>Le fotografie</h1>
<p class="sommario">
  Tutte quelle caricate, dalla più recente. <strong><?= count($righe) ?></strong> in tutto.
  Si guardano insieme perché è così che l'occhio nota quella che non c'entra niente: una alla
  volta, aprendo la scheda di ogni utente, non è moderare — è sperare di inciampare in quella
  sbagliata.
</p>

<?php if ($righe === []): ?>
  <div class="carta"><p>Nessuno ha ancora caricato una fotografia.</p></div>
<?php else: ?>
<div class="muro-fotografie">
  <?php foreach ($righe as $r): ?>
    <figure class="carta">
      <img class="fotografia fotografia--grande" src="<?= e(asset(Ritratto::di($r))) ?>"
           alt="Fotografia di <?= e($r['nome']) ?>" width="160" height="160" loading="lazy">
      <figcaption>
        <strong><?= e($r['nome'] . ' ' . $r['cognome']) ?></strong>
        <?php if ($r['user_id'] !== null): ?>
          <br><a href="<?= e(url('/admin/utente/' . (int) $r['user_id'])) ?>"><?= e((string) $r['username']) ?></a>
          <?php if ((string) $r['status'] !== 'active'): ?>
            <span class="stato stato-<?= e((string) $r['status']) ?>"><?= e((string) $r['status']) ?></span>
          <?php endif; ?>
        <?php else: ?>
          <br><span class="aiuto">del quartiere, non di un giocatore</span>
        <?php endif; ?>
        <br><span class="aiuto">dal <?= e(substr((string) $r['ritratto_at'], 0, 16)) ?></span>
        <?php if ((int) $r['condivisa'] > 1): ?>
          <br><span class="aiuto">stesso file di altri <?= (int) $r['condivisa'] - 1 ?></span>
        <?php endif; ?>
      </figcaption>
      <form method="post" action="<?= e(url('/personaggio/profilo/foto/togli')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="personaggio" value="<?= (int) $r['id'] ?>">
        <input type="hidden" name="torna" value="/admin/fotografie">
        <button class="bottone secondario piccolo" type="submit">Togli</button>
      </form>
    </figure>
  <?php endforeach; ?>
</div>
<p class="aiuto">
  Togliere una fotografia svuota la riga del personaggio e cancella il file dal disco — ma solo
  se non lo sta usando nessun altro: il nome del file è l'impronta del contenuto, e due persone
  che hanno caricato la stessa immagine ne condividono una copia sola. Ogni rimozione fatta a
  un giocatore finisce nel registro della sua scheda.
</p>
<?php endif; ?>
