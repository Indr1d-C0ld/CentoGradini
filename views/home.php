<?php /** @var int $abitanti */ ?>

<p class="occhiello">Gioco di ruolo multigiocatore persistente</p>
<h1>Cento Gradini</h1>
<p class="sommario">
  Un quartiere di Tokyo, la primavera del 1987, una scalinata che sale verso il tramonto.
  Qualcuno qui dentro sa fare cose che non dovrebbe saper fare, e il suo unico vero problema
  è che nessuno lo scopra.
</p>

<div class="carta">
  <h2>Il potere è un problema, non una soluzione</h2>
  <p>
    Nell'universo di <em>Kimagure Orange Road</em> gli esper non salvano il mondo: cercano di
    finire l'anno scolastico senza che la famiglia debba traslocare di nuovo. Ogni volta che
    usi un potere davanti a qualcuno che non sa, apri un incidente. Puoi coprirlo con una
    scusa, con un diversivo, con la faccia di bronzo, o con l'aiuto di qualcuno — ma se non lo
    copri, quella persona si porta dietro un'anomalia. Tre anomalie coerenti e non è più un
    sospetto: è una certezza.
  </p>
  <p>
    E si può anche giocare senza alcun potere. Chi non ha niente da nascondere ha in mano
    l'unica cosa che un esper non può permettersi: la possibilità di guardare con attenzione.
  </p>
</div>

<div class="carta">
  <h2>Un anno che ricomincia sempre</h2>
  <p>
    Il calendario gira — i ciliegi, la stagione delle piogge, il mare d'agosto, il festival
    culturale, il Natale, San Valentino — ma l'anno resta il 1987 e alla fine di marzo
    ricomincia da aprile. Kyosuke, Madoka e Hikaru esistono, li incontri, ci parli; il loro
    triangolo non si risolve mai. È lo sfondo immobile su cui scrivi la tua storia, non un
    traguardo da raggiungere.
  </p>
</div>

<div class="carta">
  <h2>Gli altri sono soprattutto voci</h2>
  <p>
    Il quartiere non si svuota quando esci. Gli altri giocatori lasciano tracce nei luoghi in
    cui sono passati, e quelle tracce diventano racconti che si deformano di bocca in bocca:
    dopo tre o quattro passaggi una voce non è più un'informazione, è un pettegolezzo. Da lì
    nascono la gelosia, i malintesi su gente che non hai mai incontrato, e la caccia all'esper.
  </p>
</div>

<div class="griglia" style="margin-top:1.4rem">
  <div class="carta dato">
    <b><?= e((string) $abitanti) ?></b>
    <span><?= $abitanti === 1 ? 'persona nel quartiere' : 'persone nel quartiere' ?></span>
  </div>
  <div class="carta dato">
    <b>1987</b>
    <span>e non cambierà</span>
  </div>
  <div class="carta dato">
    <b>1:4</b>
    <span>un giorno reale, quattro di gioco</span>
  </div>
</div>

<div class="bottoni">
  <a class="bottone" href="<?= e(url('/iscrizione')) ?>">Trasferisciti nel quartiere</a>
  <a class="bottone secondario" href="<?= e(url('/accesso')) ?>">Ho già un indirizzo qui</a>
</div>

<div class="nota calda" style="margin-top:2rem">
  <strong>In costruzione.</strong> Questa è la fase F0: ci si iscrive, si conferma l'indirizzo
  e si entra. Il quartiere comincia a girare con la fase successiva. Il piano completo è nel
  documento di progetto.
</div>
