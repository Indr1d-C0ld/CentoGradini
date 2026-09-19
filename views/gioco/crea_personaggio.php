<?php
/**
 * @var array<string,mixed> $mondo
 * @var list<array{sezione:string,anno:int,nome:string,sigla:string,eta:string}> $classi
 */
$mesi = ['', 'gennaio','febbraio','marzo','aprile','maggio','giugno',
         'luglio','agosto','settembre','ottobre','novembre','dicembre'];
?>
<p class="occhiello">Primo giorno</p>
<h1>Chi sei</h1>

<p class="sommario">
  Sei appena arrivato in questo quartiere e non ci conosci ancora nessuno. Qui si decide solo
  chi sei all'anagrafe: quanto vali, cosa sai fare e cosa nascondi lo decidono i dadi, subito
  dopo.
</p>

<div class="carta">
  <form method="post" action="<?= e(url('/personaggio/nuovo')) ?>">
    <?= csrf_field() ?>

    <div class="coppia">
      <div class="campo">
        <label for="cognome">Cognome</label>
        <input type="text" id="cognome" name="cognome" required maxlength="32"
               value="<?= e((string) old('cognome')) ?>" placeholder="Kasuga">
      </div>
      <div class="campo">
        <label for="nome">Nome</label>
        <input type="text" id="nome" name="nome" required maxlength="32"
               value="<?= e((string) old('nome')) ?>" placeholder="Kyosuke">
      </div>
    </div>
    <p class="aiuto" style="margin-top:-.6rem">
      In Giappone il cognome viene prima, e così comparirà in gioco.
    </p>

    <div class="campo">
      <span class="etichetta">Sesso</span>
      <div class="scelte">
        <label class="scelta"><input type="radio" name="sesso" value="f" <?= old('sesso') === 'f' ? 'checked' : '' ?> required> femmina</label>
        <label class="scelta"><input type="radio" name="sesso" value="m" <?= old('sesso') === 'm' ? 'checked' : '' ?>> maschio</label>
      </div>
    </div>

    <div class="campo">
      <label for="classe">Classe</label>
      <select id="classe" name="classe">
        <?php foreach ($classi as $c): $v = $c['sezione'] . '-' . $c['anno']; ?>
          <option value="<?= e($v) ?>" <?= old('classe', 'superiori-2') === $v ? 'selected' : '' ?>>
            <?= e($c['nome']) ?> (<?= e($c['sigla']) ?>) — <?= e($c['eta']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <span class="aiuto">
        Il Kōryō Gakuen tiene insieme le medie e le superiori. La terza media è la classe in cui
        comincia la storia.
      </span>
    </div>

    <div class="campo">
      <span class="etichetta">Compleanno</span>
      <div class="coppia">
        <select name="nato_giorno" aria-label="giorno">
          <?php for ($g = 1; $g <= 31; $g++): ?>
            <option value="<?= $g ?>" <?= (int) old('nato_giorno', 2) === $g ? 'selected' : '' ?>><?= $g ?></option>
          <?php endfor; ?>
        </select>
        <select name="nato_mese" aria-label="mese">
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= (int) old('nato_mese', 4) === $m ? 'selected' : '' ?>><?= e($mesi[$m]) ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <span class="aiuto">
        L'anno non si sceglie: in Giappone la classe dipende dalla data di nascita, quindi lo
        decide la classe. Il compleanno cade in un giorno vero del calendario, e il quartiere
        se lo ricorda.
      </span>
    </div>

    <div class="campo">
      <span class="etichetta">Della stirpe?</span>
      <label class="scelta-larga">
        <input type="radio" name="stirpe" value="esper" <?= old('stirpe', 'esper') === 'esper' ? 'checked' : '' ?>>
        <span>
          <b>Sì: sono un parente dei Kasuga</b>
          Hai un potere principale e tre secondari, e un problema che non ti lascia mai: nessuno
          deve scoprirlo. Ogni volta che risolvi qualcosa con i poteri, apri un guaio nuovo.
        </span>
      </label>
      <label class="scelta-larga">
        <input type="radio" name="stirpe" value="umano" <?= old('stirpe') === 'umano' ? 'checked' : '' ?>>
        <span>
          <b>No: sono una persona normale</b>
          Niente poteri, e non è una scelta di ripiego. Hai più punti da distribuire, un tratto
          in più, e soprattutto la cosa che un esper non si può permettere: <em>guardare con
          attenzione</em>. Madoka e Hikaru non hanno poteri e reggono tutta la storia.
        </span>
      </label>
    </div>

    <div class="bottoni">
      <button type="submit">Sali i cento gradini</button>
    </div>
  </form>
</div>

<div class="nota">
  Oggi nel quartiere è <strong><?= e($mondo['quando']) ?></strong>.
  <?= e($mondo['meteo']['descrizione']) ?>
</div>
