<?php
/** @var list<array<string,mixed>> $indirizzi */
/** @var list<array<string,mixed>> $recenti */
/** @var list<array<string,mixed>> $collegati */
/** @var list<array<string,mixed>> $freni */
$etichetta = static fn (string $a): string => match ($a) {
    'auth.login'        => 'entrato',
    'auth.login_failed' => 'tentativo fallito',
    'auth.logout'       => 'uscito',
    'auth.register'     => 'iscritto',
    'auth.verify_email' => 'indirizzo confermato',
    default             => $a,
};
?>
<h1>Accessi e origini</h1>
<p class="sommario">
  Tutto quello che c'è in questa pagina viene dal registro, che annota già accessi riusciti,
  tentativi falliti, iscrizioni, conferme e uscite. Non serviva una tabella nuova: serviva
  guardare quella che c'era.
</p>

<?php if ($collegati !== []): ?>
<div class="carta">
  <h2>Collegati adesso</h2>
  <?php foreach ($collegati as $c): ?>
    <p class="riga-membro">
      <span class="pallino acceso"></span>
      <strong><?= e($c['username']) ?></strong>
      <?php if ((string) $c['role'] === 'admin'): ?><span class="pastiglia">admin</span><?php endif; ?>
      <span class="aiuto minuto">
        visto <?= e(substr((string) $c['last_seen_at'], 11, 5)) ?>
        <?php if ($c['last_login_ip'] !== null): ?>
          · da <?= e((string) (@inet_ntop($c['last_login_ip']) ?: '?')) ?>
        <?php endif; ?>
      </span>
    </p>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="carta">
  <h2>Per indirizzo</h2>
  <p class="aiuto">
    È la vista che conta davvero. Un indirizzo con venti tentativi falliti, zero riusciti e
    tre account diversi non è un utente distratto.
  </p>
  <div class="tabella-scorre">
  <table class="tabella">
    <thead><tr>
      <th>indirizzo</th><th>tentativi</th><th>riusciti</th><th>falliti</th>
      <th>account</th><th>primo</th><th>ultimo</th>
    </tr></thead>
    <tbody>
    <?php foreach ($indirizzi as $r): ?>
      <?php $sospetto = (int) $r['falliti'] >= 5 && (int) $r['riusciti'] === 0; ?>
      <tr class="<?= $sospetto ? 'riga-allarme' : '' ?>">
        <td class="minuto"><?= e($r['indirizzo']) ?></td>
        <td><?= (int) $r['tentativi'] ?></td>
        <td><?= (int) $r['riusciti'] ?></td>
        <td><?= (int) $r['falliti'] > 0
              ? '<strong>' . (int) $r['falliti'] . '</strong>'
              : '<span class="aiuto">0</span>' ?></td>
        <td><?= (int) $r['account'] ?></td>
        <td class="minuto"><?= e(substr((string) $r['primo'], 5, 11)) ?></td>
        <td class="minuto"><?= e(substr((string) $r['ultimo'], 5, 11)) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if ($indirizzi === []): ?>
      <tr><td colspan="7" class="aiuto">Nessun accesso registrato.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
  </div>
</div>

<?php if ($freni !== []): ?>
<div class="carta">
  <h2>Freni attivi</h2>
  <p class="aiuto">
    Chi ha bussato troppo in fretta è rallentato fino all'ora indicata. I freni si contano
    per indirizzo <em>e</em> per nome utente: uno solo dei due non basterebbe.
  </p>
  <?php foreach ($freni as $f): ?>
    <p class="riga-membro minuto">
      <?= e($f['rkey']) ?> — <strong><?= (int) $f['hits'] ?></strong> colpi,
      fino alle <?= e(substr((string) $f['reset_at'], 11, 5)) ?>
    </p>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="carta">
  <h2>Ultimi movimenti</h2>
  <div class="tabella-scorre">
  <table class="tabella">
    <thead><tr><th>quando</th><th>cosa</th><th>chi</th><th>da dove</th></tr></thead>
    <tbody>
    <?php foreach ($recenti as $r): ?>
      <tr>
        <td class="minuto"><?= e(substr((string) $r['created_at'], 5, 14)) ?></td>
        <td><?= e($etichetta((string) $r['action'])) ?></td>
        <td><?= $r['username'] === null
              ? '<span class="aiuto">—</span>' : e((string) $r['username']) ?></td>
        <td class="minuto"><?= e($r['indirizzo']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if ($recenti === []): ?>
      <tr><td colspan="4" class="aiuto">Il registro è vuoto.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
  </div>
</div>
