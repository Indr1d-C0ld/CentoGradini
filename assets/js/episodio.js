/* Il conto alla rovescia della scena.
   Quando scade, la pagina si ricarica: e' il server a chiudere la scena, non
   il browser — qui si aspetta soltanto il momento buono per andare a vedere. */
(function () {
  'use strict';
  var p = document.querySelector('[data-attesa]');
  if (!p) { return; }
  var restano = parseInt(p.dataset.restano, 10);
  if (isNaN(restano)) { return; }

  function dillo(s) {
    if (s <= 0) { return 'Il tempo è scaduto: la scena sta andando avanti…'; }
    if (s < 60) { return 'Hai ' + s + ' secondi per decidere.'; }
    var m = Math.round(s / 60);
    return 'Hai ' + m + (m === 1 ? ' minuto' : ' minuti') + ' per decidere.';
  }

  var coda = ' Scaduto il tempo, la scena va avanti lo stesso e il tuo personaggio fa quello che avrebbe fatto.';
  setInterval(function () {
    restano -= 1;
    p.textContent = dillo(restano) + (restano > 0 ? coda : '');
    if (restano === -3) { window.location.reload(); }
  }, 1000);
})();
