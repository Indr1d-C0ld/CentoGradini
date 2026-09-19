<?php
/** @var array<string,mixed> $mondo */
/** @var list<array<string,mixed>> $club */
/** @var list<string> $miei */
$giorniNome = [1 => 'lun', 2 => 'mar', 3 => 'mer', 4 => 'gio', 5 => 'ven', 6 => 'sab', 7 => 'dom'];
?>
<?= partial('insegna', ['mondo' => $mondo]) ?>
<p class="occhiello"><a href="<?= e(url('/quartiere')) ?>">← il quartiere</a></p>
<h1>I club</h1>
<p class="sommario">
  Nel liceo giapponese il club è la struttura sociale che viene subito dopo la classe: è dove
  si passano i pomeriggi e dove si diventa il senpai o il kōhai di qualcuno. Qui dentro serve
  anche a una cosa concreta — <strong>le voci girano fra gli iscritti anche senza
  incontrarsi</strong>, ed è il modo più semplice per sapere cosa succede nel quartiere
  senza dover essere dappertutto.
</p>

<div class="griglia-club">
<?php foreach ($club as $c): ?>
  <?php $dentro = in_array((string) $c['ckey'], $miei, true); ?>
  <div class="carta club<?= $dentro ? ' mio' : '' ?>">
    <p class="occhiello">
      <?= e($c['nome_jp']) ?>
      <?php
        $g = array_filter(array_map('intval', explode(',', (string) $c['giorni'])));
        if ($g !== []) {
            echo ' · ' . e(implode(' ', array_map(static fn (int $d): string => $giorniNome[$d] ?? '', $g)));
        }
      ?>
      · <?= (int) $c['iscritti'] ?>/<?= (int) $c['posti'] ?>
    </p>
    <h2 style="margin-top:0">
      <a href="<?= e(url('/club/' . rawurlencode((string) $c['ckey']))) ?>"><?= e($c['nome']) ?></a>
      <?php if ($dentro): ?><span class="pastiglia">ci sei</span><?php endif; ?>
    </h2>
    <p><?= e($c['descrizione']) ?></p>
    <?php if ((string) $c['confidenza'] === 'canone'): ?>
      <p class="aiuto">Attestato nell'opera.</p>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
</div>

<p class="aiuto">
  Dei club qui sopra l'opera ne attesta uno solo — quello di karate, dove Yusaku si allena da
  quando era bambino, per un motivo che con il karate non c'entra niente. Il resto è nostra
  ricostruzione su com'era davvero un liceo giapponese nel 1987, e sta scritto in
  <code>docs/FONTI.md</code>. Madoka non è iscritta a niente, e non è una dimenticanza: è
  nota a tutte le bande del quartiere e non è mai entrata in nessuna.
</p>
