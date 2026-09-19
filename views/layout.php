<?php
/** @var string $content */
/** @var string $title */
/** @var string $ambiente */
$nomeGioco = (string) config('app.name', 'Cento Gradini');
$ambiente  = $ambiente ?? '';
?>
<!doctype html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light dark">
<?php
// Sulla soglia il titolo della pagina E' il nome del gioco: ripeterlo due volte
// nella barra del browser fa «Cento Gradini — Cento Gradini».
$titoloPagina = (string) ($title ?? $nomeGioco);
?>
<title><?= e($titoloPagina === $nomeGioco ? $nomeGioco : $titoloPagina . ' — ' . $nomeGioco) ?></title>
<meta name="description" content="Gioco di ruolo multigiocatore persistente nell'universo di Kimagure Orange Road di Izumi Matsumoto.">
<meta name="robots" content="noindex">
<link rel="stylesheet" href="<?= e(asset('css/kor.css')) ?>">
</head>
<body<?= $ambiente !== '' ? ' data-ambiente="' . e($ambiente) . '"' : '' ?>>

<header class="testata">
  <a class="marchio" href="<?= e(url('/')) ?>">
    <span class="gradini" aria-hidden="true">≡≡≡</span>
    <span><?= e($nomeGioco) ?></span>
    <small>Orange Road</small>
  </a>
  <nav>
    <?php if (auth_check()): ?>
      <a href="<?= e(url('/quartiere')) ?>">Il quartiere</a>
      <span class="tenue"><?= e((string) (auth_user()['username'] ?? '')) ?></span>
      <form method="post" action="<?= e(url('/esci')) ?>" style="display:inline">
        <?= csrf_field() ?>
        <button type="submit" class="tenue">esci</button>
      </form>
    <?php else: ?>
      <a href="<?= e(url('/opera')) ?>">L'opera</a>
      <a href="<?= e(url('/accesso')) ?>">Accedi</a>
      <a class="bottone" href="<?= e(url('/iscrizione')) ?>">Trasferisciti</a>
    <?php endif; ?>
  </nav>
</header>

<main class="contenuto <?= e($larghezza ?? '') ?>">
  <?= partial('flash') ?>
  <?= $content ?>
</main>

<footer class="piede">
  <p><?= e($nomeGioco) ?> — gioco di ruolo amatoriale, senza scopo di lucro, nell'universo di
     <em>Kimagure Orange Road</em> di <strong>Izumi Matsumoto</strong>.</p>
  <p>Non affiliato a Shueisha, Studio Pierrot o agli aventi diritto.
     <a href="<?= e(url('/opera')) ?>">L'opera originale e le fonti</a> ·
     <a href="<?= e(url('/santuario')) ?>">In memoria del maestro</a>.</p>
</footer>

</body>
</html>
