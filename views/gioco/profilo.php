<?php
/**
 * @var array<string,mixed> $pg
 * @var array<string,mixed> $mondo
 * @var string|null $ritratto
 * @var int $lato
 */
?>

<?= partial('insegna', ['mondo' => $mondo]) ?>

<p class="occhiello"><a href="<?= e(url('/personaggio')) ?>">← la tua scheda</a></p>
<h1>Come ti vedono</h1>
<p class="sommario">
  Due cose che scrivi tu invece di tirarle ai dadi: la fotografia e la riga che gli altri
  leggono vedendoti arrivare.
</p>

<div class="carta">
  <p class="occhiello">La fotografia</p>
  <?= partial('ritaglio', ['pg' => $pg, 'ritratto' => $ritratto, 'altrui' => false]) ?>
  <p class="aiuto" style="margin-top:1rem">
    Diventa un quadrato di <?= (int) $lato ?> pixel per lato: quello che carichi non viene
    conservato, viene riaperto, ritagliato come hai scelto e riscritto. La vedono tutti quelli
    che ti incontrano in giro, come una foto della classe — quindi mettici quello che
    metteresti in una foto della classe.
  </p>
</div>

<div class="carta">
  <p class="occhiello">Com'è fatto</p>
  <form method="post" action="<?= e(url('/personaggio/profilo/aspetto')) ?>">
    <?= csrf_field() ?>
    <div class="campo">
      <label for="aspetto">Una riga, non una biografia</label>
      <input type="text" id="aspetto" name="aspetto" maxlength="255"
             value="<?= e((string) ($pg['aspetto'] ?? '')) ?>"
             placeholder="capelli corti, sempre con la borsa a tracolla, cammina veloce">
      <span class="aiuto">
        È quello che nota chi ti incrocia per strada, e finisce nel tuo diario. Nel manga
        nessuno viene descritto da un narratore: di una persona si sa com'è vestita e come
        cammina, e il resto lo si capisce da come gli altri la guardano.
      </span>
    </div>
    <div class="bottoni"><button type="submit">Salva</button></div>
  </form>
</div>
