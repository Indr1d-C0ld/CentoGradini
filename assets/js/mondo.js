/* ============================================================================
   L'orologio vivo.

   Due lavori piccoli: far scorrere l'ora di gioco senza ricaricare la pagina, e
   accorgersi di essere arrivati a destinazione.

   Il conto alla rovescia del viaggio gira in locale (e' solo aritmetica), ma
   l'arrivo lo decide il server: quando il conto finisce si chiede conferma e
   solo allora si ricarica. Fidarsi del cronometro del browser vorrebbe dire
   ricaricare la pagina un secondo prima che il server sia d'accordo, e vedere
   la pagina dire «sei ancora per strada».
   ========================================================================= */
(function () {
  'use strict';

  var copione = document.querySelector('script[data-battito]');
  if (!copione) { return; }
  var urlBattito = copione.dataset.battito;

  var quando   = document.querySelector('[data-quando]');
  var contesto = document.querySelector('[data-contesto]');
  var viaggio  = document.querySelector('[data-viaggio]');

  function testoAttesa(sec) {
    if (sec <= 0)  { return 'stai arrivando…'; }
    if (sec < 60)  { return 'arrivi fra ' + sec + ' secondi'; }
    var m = Math.round(sec / 60);
    return 'arrivi fra ' + m + (m === 1 ? ' minuto' : ' minuti');
  }

  /* --- Conto alla rovescia ---------------------------------------------- */
  var restano = viaggio ? parseInt(viaggio.dataset.restano, 10) : 0;
  var conto = viaggio ? viaggio.querySelector('[data-conto]') : null;
  var giaChiesto = false;

  if (viaggio && conto) {
    if (conto) { conto.textContent = testoAttesa(restano); }
    setInterval(function () {
      restano -= 1;
      conto.textContent = testoAttesa(restano);
      if (restano <= 0 && !giaChiesto) {
        giaChiesto = true;
        chiedi(true);
      }
    }, 1000);
  }

  /* --- Interrogazione periodica ------------------------------------------- */
  function chiedi(forseArrivato) {
    fetch(urlBattito, { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (d) {
        if (!d) { return; }
        if (quando)   { quando.textContent = d.quando; }
        if (contesto) { contesto.textContent = d.insegna; }
        /* Arrivato: il server non parla piu' di un viaggio in corso. */
        if (forseArrivato && !d.viaggio) { window.location.reload(); }
        /* Se il viaggio c'e' ancora, si riallinea il cronometro al server. */
        if (d.viaggio && conto) {
          restano = d.viaggio.restano;
          giaChiesto = false;
        }
        /* Il battito lo ascolta anche chi vuole: i suoni, il pallino delle
           voci, e quello che verra'. Si passa l'evento invece di chiamarli
           direttamente, cosi' questo file non deve sapere chi c'e'. */
        document.dispatchEvent(new CustomEvent('centogradini:battito', { detail: d }));
      })
      .catch(function () { /* meglio un orologio fermo che una pagina rotta */ });
  }

  /* Ogni venti secondi reali passano oltre un minuto di gioco: piu' spesso di
     cosi' non servirebbe a niente e sarebbe solo traffico. */
  setInterval(function () { chiedi(false); }, 20000);

  /* Tornando su una scheda lasciata aperta da ore, l'ora mostrata e' vecchia. */
  document.addEventListener('visibilitychange', function () {
    if (!document.hidden) { chiedi(restano <= 0 && viaggio !== null); }
  });
})();
