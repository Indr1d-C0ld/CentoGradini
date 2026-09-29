<?php
/** @var list<array<string,mixed>> $abitanti */
use App\Game\Ritratto;
use App\Sim\Luoghi;

$conFoto = count(array_filter($abitanti, static fn (array $a): bool => Ritratto::di($a) !== null));
?>
<h1>Gli abitanti</h1>
<p class="sommario">
  I tredici del canone, che girano per il quartiere da soli. <strong><?= $conFoto ?></strong>
  su <?= count($abitanti) ?> hanno una fotografia. Si mette dalla pagina di ciascuno, con lo
  stesso riquadro dei giocatori: si sceglie il fotogramma, lo si centra, e diventa la faccia
  che si vede negli elenchi e — più grande — passandoci sopra col mouse.
</p>
<p class="aiuto">
  Sono immagini della serie animata: character design di Akemi Takada, Studio Pierrot. Stanno
  sull'installazione viva e nel backup privato; la copia pubblica non le porta, come non porta
  le fotografie dei giocatori.
</p>

<div class="muro-fotografie">
  <?php foreach ($abitanti as $a): $f = Ritratto::di($a); ?>
    <figure class="carta">
      <a href="<?= e(url('/admin/abitante/' . $a['png'])) ?>">
        <?php if ($f !== null): ?>
          <img class="fotografia fotografia--grande" src="<?= e(asset($f)) ?>" alt="" width="160" height="160" loading="lazy">
        <?php else: ?>
          <div class="fotografia fotografia--grande fotografia--vuota" aria-hidden="true"><?=
            e(mb_strtoupper(mb_substr((string) $a['nome'], 0, 1))) ?></div>
        <?php endif; ?>
      </a>
      <figcaption>
        <strong><a href="<?= e(url('/admin/abitante/' . $a['png'])) ?>"><?= e(trim($a['nome'] . ' ' . $a['cognome'])) ?></a></strong>
        <br><span class="aiuto"><?= e(Luoghi::nome((string) $a['luogo'])) ?></span>
        <?php if ($f === null): ?><br><span class="aiuto">senza fotografia</span><?php endif; ?>
      </figcaption>
    </figure>
  <?php endforeach; ?>
</div>
