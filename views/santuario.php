<?php
/**
 * Il santuario: la sala dedicata a Izumi Matsumoto.
 *
 * @var list<array{file:string,didascalia:string}> $ritratti
 */
?>
<p class="occhiello">1958 — 2020</p>
<h1>Izumi Matsumoto</h1>
<p class="sommario">
  まつもと泉. Un ragazzo di Takaoka che voleva fare il musicista, disegnò la propria città e le
  diede un altro nome, e ci lasciò dentro una storia che dopo quarant'anni la gente rilegge
  ancora. Questo gioco esiste per causa sua.
</p>

<?php if ($ritratti !== []): ?>
<div class="ritratti">
  <?php foreach ($ritratti as $r): ?>
    <figure>
      <img src="<?= e(asset('img/maestro/' . $r['file'])) ?>" alt="Izumi Matsumoto" loading="lazy">
      <?php if ($r['didascalia'] !== ''): ?>
        <figcaption><?= e($r['didascalia']) ?></figcaption>
      <?php endif; ?>
    </figure>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="carta">
  <h2>Takaoka</h2>
  <p>
    Nasce il <strong>13 ottobre 1958</strong> a Takaoka, nella prefettura di Toyama, sulla
    costa del mar del Giappone. È una città di provincia con una lunga storia di fonderie di
    bronzo, un parco costruito sulle rovine di un castello, e una linea ferroviaria che va
    verso il mare. Frequenta l'istituto tecnico-artistico della città — dove, qualche
    generazione prima, era passato anche Fujiko F. Fujio.
  </p>
  <p>
    Questo dettaglio biografico è anche la cosa più sorprendente che abbiamo trovato mentre
    costruivamo il quartiere di questo gioco. La scalinata dove Kyosuke incontra Madoka è la
    gradinata di pietra del <strong>parco Takaoka Kojō</strong>. La scuola è la <strong>media
    Kōryō</strong>. Il bar ABCB è un ristorante che si chiamava <strong>ABAB</strong>. La
    stazione è <strong>Etchū-Nakagawa</strong>, sulla linea Himi. Matsumoto non ha inventato
    una periferia di Tokyo: ha disegnato i posti in cui era cresciuto e li ha chiamati con un
    altro nome. Chi gioca qui dentro cammina nella sua infanzia.
  </p>
</div>

<div class="carta">
  <h2>La musica che non ha fatto</h2>
  <p>
    A Tokyo ci va per suonare. Voleva fare il musicista rock, e quella strada si chiude presto:
    non sa leggere gli spartiti. Il disegno viene dopo, quasi di ripiego.
  </p>
  <p>
    Solo che la musica non se n'è andata: è entrata tutta dentro l'opera. Madoka che suona la
    chitarra e il sassofono, la band, il concorso per voci nuove, i titoli dei capitoli che
    sono titoli di canzoni — «Jealousy Rain», «Shopping Boogie», «Romantic Night». C'è anche un
    brano nelle colonne sonore della serie in cui alla batteria c'è lui. Chi ha rinunciato a
    fare il musicista ha finito per scrivere il fumetto più musicale degli anni Ottanta.
  </p>
</div>

<div class="carta">
  <h2>Un fumetto sbagliato per la rivista giusta</h2>
  <p>
    <em>Kimagure Orange Road</em> esce su <em>Weekly Shōnen Jump</em> dal <strong>26 marzo
    1984</strong> al <strong>28 settembre 1987</strong>: 156 capitoli, diciotto volumi, oltre
    venti milioni di copie. Il punto è che <em>Jump</em>, allora, andava avanti a «amicizia,
    impegno, vittoria», e questa era una storia in cui non vince nessuno e il protagonista non
    decide niente. Un produttore della televisione che ci lavorò lo disse senza giri di parole:
    era un fumetto d'amore, e per <em>Jump</em> non era affatto tipico.
  </p>
  <p>
    Lo disegnava in un monolocale con tre o quattro assistenti, lavorando a volte in bagno per
    mancanza di spazio. Del protagonista disse che il suo carattere era sempre stato il proprio.
    Per Madoka prese a modello Akina Nakamori dell'epoca di <em>Shōjo A</em>, e il nome lo mise
    insieme da due musicisti che gli piacevano.
  </p>
</div>

<div class="carta">
  <h2>La malattia</h2>
  <p>
    Per anni soffrì di una perdita di liquido cerebrospinale: una condizione che dà dolore,
    vertigini, spossatezza, e che i medici gli riconobbero molto tardi. Lo tenne lontano dal
    tavolo da disegno per lunghi periodi, in un mestiere in cui stare fermi significa sparire.
  </p>
  <p>
    Scrisse di aver combattuto per cinque anni contro una malattia che non riusciva a vincere,
    e che sarebbe tornato comunque a lavorare — perché non riusciva a starne lontano. È la
    frase che spiega meglio di ogni altra il rapporto fra quest'uomo e la cosa che aveva fatto:
    non un successo da amministrare, una cosa da cui non si riusciva a stare separati.
  </p>
  <p>
    È morto a Tokyo il <strong>6 ottobre 2020</strong>, a sessantun anni, una settimana prima
    del suo compleanno.
  </p>
</div>

<div class="carta">
  <h2>Quello che ha lasciato</h2>
  <p>
    A Madoka Ayukawa si fa risalire l'archetipo che vent'anni dopo qualcuno avrebbe chiamato
    <em>tsundere</em>, e che nelle sue mani non era ancora un tipo: era una ragazza che non
    riusciva a dire le cose. <em>Kimagure Orange Road</em> è considerata la prima serie a
    tenere insieme fantascienza e commedia sentimentale, e in Europa — insieme a poche altre —
    è il fumetto e il cartone animato da cui intere generazioni sono entrate in tutto il resto.
  </p>
  <p>
    In Italia arrivò nel gennaio del 1989 con un altro titolo, altri nomi e parecchi tagli.
    Moltissimi di noi lo hanno conosciuto così, e gli hanno voluto bene lo stesso.
  </p>
</div>

<div class="nota calda">
  <strong>Questo gioco non gli appartiene, e lui non c'entra niente con noi.</strong>
  <em>Cento Gradini</em> è un lavoro amatoriale, gratuito, fatto da lettori. Non è approvato
  da nessuno e non riproduce nulla della sua opera: prova soltanto a costruire un posto in cui
  si possa passare del tempo nel quartiere che ha disegnato.
</div>

<?php if ($ritratti !== []): ?>
<p class="aiuto crediti-foto">
  Le fotografie ritraggono Izumi Matsumoto e provengono da materiale giornalistico e da
  interviste: i diritti restano di chi le ha scattate. Sono qui in un omaggio senza scopo di
  lucro e vengono rimosse su richiesta di chi ne detiene i diritti.
</p>
<?php else: ?>
<div class="nota">
  <strong>Le fotografie mancano ancora.</strong> Vanno messe in
  <code>assets/img/maestro/</code>: la pagina le trova da sola.
</div>
<?php endif; ?>

<div class="bottoni">
  <a class="bottone secondario" href="<?= e(url('/opera')) ?>">L'opera originale e le fonti</a>
</div>
