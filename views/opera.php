<p class="occhiello">Di chi è questa storia</p>
<h1>L'opera originale</h1>

<?php
/** @var list<array{file:string,didascalia:string,gruppo:string}> $immagini */
$immagini = $immagini ?? [];
$perGruppo = ['manga' => [], 'anime' => [], 'altro' => []];
foreach ($immagini as $im) { $perGruppo[$im['gruppo']][] = $im; }
?>
<?php foreach ([
    'manga' => 'Le tavole',
    'anime' => 'La serie animata',
    'altro' => 'Altro',
] as $gruppo => $titolo): ?>
  <?php if ($perGruppo[$gruppo] === []) { continue; } ?>
  <h2><?= e($titolo) ?></h2>
  <div class="galleria">
    <?php foreach ($perGruppo[$gruppo] as $im): ?>
      <figure>
        <img src="<?= e(asset('img/opera/' . $im['file'])) ?>"
             alt="<?= e($im['didascalia'] !== '' ? $im['didascalia'] : 'Kimagure Orange Road') ?>"
             loading="lazy">
        <?php if ($im['didascalia'] !== ''): ?>
          <figcaption><?= e($im['didascalia']) ?></figcaption>
        <?php endif; ?>
      </figure>
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>

<?php if ($immagini !== []): ?>
  <p class="aiuto crediti-foto">
    Le immagini sono tavole del manga e fotogrammi della serie animata: i diritti restano di
    <strong>Izumi Matsumoto</strong>, di Shūeisha e di Studio Pierrot. Sono qui in un omaggio
    senza scopo di lucro, a scopo di commento e riconoscimento dell'opera, e vengono rimosse
    su richiesta di chi ne detiene i diritti.
  </p>
<?php else: ?>
  <div class="nota">
    <strong>Qui ci starebbero delle immagini.</strong> Vanno messe in
    <code>assets/img/opera/</code>: la pagina le trova da sola, e il nome del file decide
    sezione, ordine e didascalia. Le istruzioni sono nel file <code>COME-AGGIUNGERE.txt</code>
    dentro quella cartella.
  </div>
<?php endif; ?>


<p class="sommario">
  <em>Cento Gradini</em> è un gioco amatoriale, gratuito e senza scopo di lucro, costruito per
  affetto verso un'opera che non ci appartiene. Vale la pena dire con precisione di chi è.
</p>

<div class="carta">
  <h2>Kimagure Orange Road</h2>
  <p>
    <strong>Izumi Matsumoto</strong> l'ha scritto e disegnato. È uscito su <em>Weekly Shōnen
    Jump</em> dal 26 marzo 1984 al 28 settembre 1987: 156 capitoli, raccolti da Shūeisha in 18
    volumi, oltre venti milioni di copie.
  </p>
  <p>
    La serie animata è dello <strong>Studio Pierrot</strong> per Nippon Television: 48 episodi
    dal 6 aprile 1987 al 7 marzo 1988, regia di <strong>Osamu Kobayashi</strong>, sceneggiature
    di <strong>Kenji Terada</strong>, musiche di <strong>Shirō Sagisu</strong> e — soprattutto,
    per quanto riguarda l'aspetto di questo sito — character design di
    <strong>Akemi Takada</strong>. Seguono tre film e otto OAV.
  </p>
  <p>
    Il quartiere è ispirato a <strong>Umegaoka</strong>, <strong>Gōtokuji</strong> e
    <strong>Shimokitazawa</strong>, nel municipio di Setagaya a Tokyo. Il bar ABCB è ispirato a
    un locale reale.
  </p>
</div>

<div class="carta">
  <h2>Il regolamento da cui partiamo</h2>
  <p>
    Il sistema di gioco discende da <em>Whimsical Orange Road: The Role-Playing Game</em>,
    versione 3.0 del marzo 1990, scritto da un autore che si firmava «Totoro Hunter Leto II» e
    diffuso via BBS dagli <em>Orange Roadies of San Diego</em>. È un documento del suo tempo:
    ne abbiamo tenuto le tre idee buone — il segreto come unico vero obiettivo, il danno comico
    e non letale, gli scopi individuali non dichiarati — e riprogettato tutto il resto.
  </p>
</div>

<div class="bottoni">
  <a class="bottone" href="<?= e(url('/santuario')) ?>">In memoria di Izumi Matsumoto →</a>
</div>

<div class="nota">
  Questo progetto non è affiliato né autorizzato da Shūeisha, Studio Pierrot o da alcun avente
  diritto, e non riproduce testi, tavole o materiale grafico delle opere originali. Su richiesta
  di chi ne detiene i diritti viene rimosso.
</div>
