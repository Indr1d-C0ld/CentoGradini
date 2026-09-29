<?php
/** @var array<string,mixed> $pg */
use App\Game\Ritratto;
use App\Sim\Luoghi;
use App\Sim\Scuola;

$torna = '/admin/abitante/' . (string) $pg['png'];
?>
<p class="occhiello"><a href="<?= e(url('/admin/abitanti')) ?>">← gli abitanti</a></p>
<h1><?= e(trim($pg['nome'] . ' ' . $pg['cognome'])) ?></h1>
<p class="sommario">
  <?= e(Scuola::nomeClasse((string) $pg['sezione'], (int) $pg['anno'])) ?>
  · adesso <?= e(Luoghi::dove((string) $pg['luogo'])) ?>
</p>
<?= partial('famiglia', ['chi' => $pg]) ?>

<div class="carta">
  <h2>La fotografia</h2>
  <p class="aiuto">
    Un fotogramma della serie, centrato sul volto. Diventa un quadrato di <?= Ritratto::LATO ?>
    pixel: negli elenchi si vede piccolo, e passandoci sopra col mouse più grande.
  </p>
  <?= partial('ritaglio', ['pg' => $pg, 'ritratto' => Ritratto::di($pg), 'altrui' => true, 'torna' => $torna]) ?>
</div>

<p class="aiuto">
  L'aspetto di un abitante («<?= e((string) ($pg['aspetto'] ?? '')) ?>») è un dato dell'opera e
  sta in <code>db/seed/png.php</code>: da qui non si cambia, perché il seme lo riscriverebbe alla
  prossima pubblicazione. La fotografia invece il seme non la tocca.
</p>
