<?php
/** @var array<string,mixed> $mondo */
/** @var array<string,mixed> $calendario */
/** @var array<string,mixed> $meteo */
/** @var list<array<string,mixed>> $eventi */
/** @var array<string,mixed>|null $prossimo */
/** @var array<string,int> $numeri */
/** @var list<array<string,mixed>> $battiti */
/** @var array<string,mixed> $posta */
/** @var array<string,mixed> $config */
use App\Sim\Luoghi;
use App\Sim\Orologio;

$ultimo   = $battiti[0] ?? null;
$ritardo  = $ultimo === null ? null : (time() - strtotime((string) $ultimo['started_at']));
?>
<h1>Amministrazione</h1>

<?php if ($ritardo === null): ?>
  <div class="avviso errore"><strong>Il battito non è mai partito.</strong>
    Manca la riga di cron: <code>* * * * * /usr/bin/php .../bin/tick.php</code>.</div>
<?php elseif ($ritardo > 300): ?>
  <div class="avviso errore"><strong>Il battito è fermo da <?= (int) floor($ritardo / 60) ?> minuti.</strong>
    Il mondo avanza lo stesso a ogni richiesta web, ma chi non è collegato è congelato.</div>
<?php elseif ((int) $ultimo['ok'] !== 1): ?>
  <div class="avviso attenzione"><strong>L'ultimo battito è tornato con un errore.</strong>
    <?= e((string) ($ultimo['note'] ?? '')) ?></div>
<?php else: ?>
  <div class="avviso"><strong>Il mondo gira.</strong>
    Ultimo battito <?= (int) $ritardo ?> secondi fa, in <?= (int) $ultimo['duration_ms'] ?> ms.</div>
<?php endif; ?>

<div class="carta">
  <h2>Il mondo adesso</h2>
  <p>
    <strong><?= e(Orologio::esteso()) ?></strong> —
    <?= e($calendario['fase_nome']) ?>,
    <?= e($calendario['stagione']) ?>.
    <?php if ($calendario['festa'] !== null): ?> Festa: <?= e($calendario['festa']) ?>.<?php endif; ?>
    <?php if ($calendario['vacanza'] !== null): ?> Vacanze: <?= e($calendario['vacanza']) ?>.<?php endif; ?>
  </p>
  <p class="aiuto">
    <?= e(sprintf('%.1f °C', (float) $meteo['temperatura'])) ?>,
    pioggia <?= e(sprintf('%.1f', (float) $meteo['pioggia'])) ?><?php
      if ($meteo['tifone']): ?>, <strong>tifone</strong><?php endif; ?>.
    <?php if ($eventi !== []): ?>
      In corso: <?= e(implode(', ', array_column($eventi, 'nome'))) ?>.
    <?php endif; ?>
    <?php if ($prossimo !== null): ?>
      Poi <?= e($prossimo['nome']) ?> fra <?= (int) $prossimo['fra_giorni'] ?> giorni.
    <?php endif; ?>
  </p>
  <form method="post" action="<?= e(url('/admin/battito')) ?>">
    <?= csrf_field() ?>
    <button class="bottone secondario piccolo" type="submit">Batti adesso</button>
  </form>
</div>

<div class="carta">
  <h2>I numeri</h2>
  <div class="griglia-numeri">
    <?php foreach ($numeri as $nome => $n): ?>
      <div class="numero">
        <span class="numero-valore"><?= (int) $n ?></span>
        <span class="numero-nome"><?= e($nome) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
  <p class="aiuto">
    Posta: <?= (int) $posta['in_coda'] ?> in coda,
    <?= (int) $posta['inviate_24h'] ?>/<?= (int) $posta['tetto'] ?> inviate nelle ultime 24 ore,
    <?= (int) $posta['rinunciate'] ?> rinunciate.
  </p>
</div>

<div class="carta">
  <h2>Gli ultimi battiti</h2>
  <div class="tabella-scorre">
  <table class="tabella">
    <thead><tr><th>quando</th><th>esito</th><th>ms</th><th>cosa ha fatto</th></tr></thead>
    <tbody>
    <?php foreach ($battiti as $b): ?>
      <tr>
        <td data-etichetta="quando"><?= e(substr((string) $b['started_at'], 5, 14)) ?></td>
        <td data-etichetta="esito"><?= (int) $b['ok'] === 1 ? 'ok' : '<strong>errore</strong>' ?></td>
        <td data-etichetta="ms"><?= (int) $b['duration_ms'] ?></td>
        <td class="minuto" data-etichetta="cosa ha fatto">
          <?php
            $t = json_decode((string) ($b['tasks'] ?? '{}'), true);
            $pezzi = [];
            foreach (is_array($t) ? $t : [] as $k => $v) {
                if (is_array($v)) { $v = array_sum(array_filter($v, 'is_int')); }
                if ((int) $v > 0) { $pezzi[] = $k . ' ' . (int) $v; }
            }
            echo $pezzi === [] ? '<span class="aiuto">niente da fare</span>' : e(implode(' · ', $pezzi));
          ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<div class="carta">
  <h2>Le manopole del mondo</h2>
  <p class="aiuto">
    Cambiano il gioco per tutti, subito. Le chiavi si creano con una migrazione, non da qui:
    questo modulo cambia solo il valore di quelle che esistono già.
  </p>
  <form method="post" action="<?= e(url('/admin/config')) ?>" class="riga-config">
    <?= csrf_field() ?>
    <label>Chiave
      <select name="chiave">
        <?php foreach ($config as $k => $v): ?>
          <option value="<?= e($k) ?>"><?= e($k) ?> — <?= e((string) $v['value']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Nuovo valore <input type="text" name="valore" required></label>
    <button class="bottone" type="submit">Cambia</button>
  </form>

  <div class="tabella-scorre">
  <table class="tabella">
    <thead><tr><th>chiave</th><th>valore</th></tr></thead>
    <tbody>
    <?php /* GameConfig::all() da' ['chiave' => ['value' => …, 'type' => …]], non uno
             scalare: trattarlo come tale stampava «Array» in tutte e cinquanta le
             righe e riempiva il log di avvisi. La pagina rispondeva 200, quindi
             nessuna prova se n'e' accorta finche' non si e' guardato il log. */ ?>
    <?php foreach ($config as $k => $v): ?>
      <tr>
        <td class="minuto" data-etichetta="chiave"><?= e($k) ?></td>
        <td data-etichetta="valore"><?= e((string) $v['value']) ?>
          <span class="aiuto"><?= e((string) $v['type']) ?></span></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<p class="aiuto">
  Le cose serie stanno nella console, non qui: <code>php bin/console.php</code> per migrazioni,
  semina, account e <code>bilancio --conferma</code> per il rapporto di bilanciamento.
</p>
