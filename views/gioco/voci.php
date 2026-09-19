<?php
/** @var array<string,mixed> $mondo */
/** @var array<string,mixed> $pg */
/** @var list<array<string,mixed>> $voci */
?>
<?= partial('insegna', ['mondo' => $mondo]) ?>
<p class="occhiello"><a href="<?= e(url('/quartiere')) ?>">← il quartiere</a></p>
<h1>Quello che si dice</h1>
<p class="sommario">
  Non è quello che è successo: è quello che ti è arrivato. Ogni bocca che una storia
  attraversa le toglie un pezzo e ci mette del suo, e dopo tre o quattro passaggi non è più
  un'informazione — è un pettegolezzo. Sotto ogni riga c'è scritto da chi l'hai sentita e
  quanto ci puoi contare.
</p>

<?php if ($voci === []): ?>
  <div class="carta">
    <p>Non ti è arrivato niente. Succede a chi sta poco in giro: le voci viaggiano fra
    persone che si trovano nello stesso posto, e dentro i club.
    <a href="<?= e(url('/club')) ?>">Iscriversi a un club</a> è il modo più veloce per
    sapere le cose senza dover essere dappertutto.</p>
  </div>
<?php endif; ?>

<?php foreach ($voci as $v): ?>
  <?php
    $fascia = match (true) {
        $v['precisione'] >= 80 => ['certa',   'l\'hai vista tu'],
        $v['precisione'] >= 55 => ['buona',   'te l\'ha detta qualcuno che c\'era'],
        $v['precisione'] >= 30 => ['incerta', 'ha già fatto un po\' di strada'],
        default                => ['nebbia',  'chiacchiere, ormai'],
    };
  ?>
  <div class="carta voce voce-<?= e($fascia[0]) ?>">
    <p class="voce-testo"><?= e($v['testo']) ?></p>
    <p class="aiuto">
      <?php if ($v['vista']): ?>
        <strong>C'eri.</strong>
      <?php elseif ($v['da'] !== null): ?>
        Te l'ha detta <strong><?= e($v['da']) ?></strong>.
      <?php else: ?>
        Non ti ricordi da chi.
      <?php endif; ?>
      <?= e($fascia[1]) ?> ·
      <?= $v['passaggi'] === 0 ? 'di prima mano' : 'passata per ' . (int) $v['passaggi']
          . ($v['passaggi'] === 1 ? ' altra persona' : ' altre persone') ?> ·
      <?= $v['fa_ore'] < 1 ? 'adesso' : ($v['fa_ore'] . ' ore fa') ?>
    </p>
  </div>
<?php endforeach; ?>
