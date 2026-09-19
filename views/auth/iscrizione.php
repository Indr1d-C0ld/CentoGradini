<?php /** @var bool $aperta */ /** @var int $minPassword */ ?>
<p class="occhiello">Nuovo in zona</p>
<h1>Trasferisciti nel quartiere</h1>

<?php if (!$aperta): ?>
  <div class="avviso attenzione">Al momento non si accettano nuovi arrivi. Riprova più avanti.</div>
<?php else: ?>

<div class="carta">
  <form method="post" action="<?= e(url('/iscrizione')) ?>">
    <?= csrf_field() ?>

    <div class="campo">
      <label for="username">Come ti chiamano</label>
      <input type="text" id="username" name="username" required autocomplete="nickname"
             maxlength="32" value="<?= e((string) old('username')) ?>">
      <span class="aiuto">Da 3 a 32 caratteri. Sono ammessi gli spazi: puoi usare nome e cognome.</span>
    </div>

    <div class="campo">
      <label for="email">Indirizzo e-mail</label>
      <input type="email" id="email" name="email" required autocomplete="email"
             maxlength="190" value="<?= e((string) old('email')) ?>">
      <span class="aiuto">Serve solo per confermare l'iscrizione e per recuperare l'accesso.</span>
    </div>

    <div class="campo">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" required autocomplete="new-password"
             minlength="<?= e((string) $minPassword) ?>">
      <span class="aiuto">Almeno <?= e((string) $minPassword) ?> caratteri.</span>
    </div>

    <div class="campo">
      <label for="password_confirm">Ripeti la password</label>
      <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">
    </div>

    <div class="bottoni">
      <button type="submit">Manda la conferma</button>
      <a class="bottone tenue" href="<?= e(url('/accesso')) ?>">Ho già un indirizzo qui</a>
    </div>
  </form>
</div>

<div class="nota">
  Ti arriverà un messaggio con un collegamento da aprire. Finché non lo apri l'iscrizione resta
  in sospeso e non si entra: è l'unica porta.
</div>

<?php endif; ?>
