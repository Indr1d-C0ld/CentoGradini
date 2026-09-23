<?php
/**
 * @var array<string,mixed> $pg
 * @var array<string,mixed> $mondo
 * @var int $quando
 * @var bool $pronto
 * @var int $minutiVeri
 * @var int $ricordi
 */
use App\Game\Personaggio;
use App\Sim\Orologio;

$donna = (string) ($pg['sesso'] ?? 'm') === 'f';
$ore = intdiv($minutiVeri, 60);
$min = $minutiVeri % 60;
$fra = $ore > 0 ? sprintf('%d or%s e %d minut%s', $ore, $ore === 1 ? 'a' : 'e', $min, $min === 1 ? 'o' : 'i')
                : sprintf('%d minut%s', $min, $min === 1 ? 'o' : 'i');
?>

<?= partial('insegna', ['mondo' => $mondo]) ?>

<p class="occhiello">Il trasloco</p>
<h1>La famiglia di <?= e(Personaggio::nomeCompleto($pg)) ?> se n'è andata</h1>
<p class="sommario">
  Qualcuno aveva capito, e poi qualcun altro. Il camioncino è arrivato di mattina presto, quando
  la strada era ancora vuota, e nessuno ha discusso: si fa così, si è sempre fatto così. Nel
  manga la famiglia Kasuga si era già trasferita sette volte prima che la storia cominciasse.
</p>

<div class="carta">
  <p class="occhiello">Cosa resta, cosa no</p>
  <p>
    Ti porti dietro <strong>i poteri e il Controllo</strong>, <strong>il carattere</strong> — i
    tratti, le abilità — e <strong>l'album dei ricordi</strong><?php if ($ricordi > 0): ?>, con
    le <?= $ricordi ?> scene che ci sono dentro<?php endif; ?>.
  </p>
  <p>
    Lasci qui <strong>i legami</strong>: chi ti conosceva non ti riconoscerà come prima, e tu non
    saprai più cosa prova per te. Lasci <strong>chi aveva capito</strong> il tuo segreto — quando
    tornerai sarai <?= $donna ? "un'altra ragazza" : 'un altro ragazzo' ?> in un'altra casa, e la loro sarà solo una
    storia vecchia. E
    lasci i club.
  </p>
  <p class="aiuto">
    Si torna in stazione, <strong>trasferiti di recente</strong>: è un tratto del regolamento del
    1990, e chiude il cerchio.
  </p>
</div>

<div class="carta">
  <?php if ($pronto): ?>
    <p class="occhiello">Si può tornare</p>
    <p>Il treno per Nakagawa parte quando vuoi.</p>
    <form method="post" action="<?= e(url('/trasloco/rientra')) ?>">
      <?= csrf_field() ?>
      <div class="bottoni"><button type="submit">Torna nel quartiere</button></div>
    </form>
  <?php else: ?>
    <p class="occhiello">Non ancora</p>
    <p>
      Si potrà tornare <strong><?= e(Orologio::esteso($quando)) ?></strong>, ora del quartiere —
      fra circa <strong><?= e($fra) ?></strong> di tempo vero.
    </p>
    <p class="aiuto">
      L'attesa è quella che rende la perdita vera. Nel frattempo i ricordi e il diario sono tuoi.
    </p>
  <?php endif; ?>
  <p class="occhiello" style="margin-top:1rem">
    <a href="<?= e(url('/ricordi')) ?>">l'album dei ricordi</a>
    · <a href="<?= e(url('/diario')) ?>">il diario</a>
    · <a href="<?= e(url('/personaggio')) ?>">la scheda</a>
  </p>
</div>
