/* Il contatore dei punti da distribuire.
   Il server ricontrolla tutto: questo serve solo a non far compilare il
   modulo alla cieca. Senza JavaScript la pagina resta usabile — si scrivono
   i numeri e si manda, e se non tornano il server lo dice. */
(function () {
  'use strict';
  var modulo = document.getElementById('modulo-abilita');
  if (!modulo) { return; }

  var campi = [].slice.call(modulo.querySelectorAll('.punti'));
  var resto = modulo.querySelector('[data-resto]');
  var totale = parseInt(resto.textContent, 10);

  function aggiorna() {
    var usati = 0;
    campi.forEach(function (c) {
      var v = parseInt(c.value, 10);
      if (isNaN(v) || v < 0) { v = 0; }
      usati += v;
      var base = parseInt(c.dataset.base, 10);
      var out = modulo.querySelector('[data-totale="' + c.name.slice(2) + '"]');
      if (out) { out.textContent = base + v; }
    });
    var rimasti = totale - usati;
    resto.textContent = rimasti;
    resto.parentElement.classList.toggle('sbagliato', rimasti !== 0);
  }

  campi.forEach(function (c) {
    c.addEventListener('input', aggiorna);
    c.addEventListener('change', aggiorna);
  });
  aggiorna();
})();
