<?php
/**
 * @var array<string,mixed> $mondo
 * @var list<array<string,mixed>> $filo
 */
use App\Game\Comunicazioni;
?>

<?= partial('insegna', ['mondo' => $mondo]) ?>

<p class="occhiello"><a href="<?= e(url('/quartiere')) ?>">← il quartiere</a></p>
<h1>Comunicazioni</h1>
<p class="sommario">
  Qui parla <strong>la gestione del gioco</strong>, non il quartiere: non è finzione, non è un
  biglietto lasciato da qualcuno e non ha niente a che vedere con il tuo personaggio. Se
  ricevi un messaggio qui, è una persona vera che ti sta scrivendo — e puoi risponderle.
</p>

<div class="carta">
  <?= partial('filo', ['filo' => $filo, 'ioSonoLaGestione' => false]) ?>
</div>

<div class="carta">
  <p class="occhiello">Scrivi alla gestione</p>
  <form method="post" action="<?= e(url('/comunicazioni')) ?>">
    <?= csrf_field() ?>
    <div class="campo">
      <label for="testo">Un problema, una domanda, una segnalazione</label>
      <textarea id="testo" name="testo" rows="5" maxlength="<?= Comunicazioni::MAX ?>"
                placeholder="Scrivi pure. Non serve essere formali."></textarea>
      <span class="aiuto">
        Per le cose del gioco — un potere che non torna, un luogo che non si apre — è utile
        dire <em>dove eri</em> e <em>che ora era nel quartiere</em>: da qui si risale a quasi
        tutto.
      </span>
    </div>
    <div class="bottoni"><button type="submit">Manda</button></div>
  </form>
</div>
