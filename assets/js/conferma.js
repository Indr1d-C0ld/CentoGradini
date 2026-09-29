/* La conferma prima delle azioni che non si possono disfare.

   Prima stava scritta dentro i moduli, come `onsubmit="return confirm(...)"`.
   La nostra CSP (`script-src 'self'`) blocca i gestori scritti in linea, e il
   browser lo segnala solo nella console: il risultato era che «confidati»,
   «daglielo» e «dichiarati» partivano al primo clic, senza chiedere niente —
   proprio le tre azioni che nel gioco non si possono ritirare.

   Adesso il modulo porta la domanda in `data-conferma`, e la pone questo file,
   che la CSP permette perche' sta sul nostro dominio. Senza JavaScript il
   modulo parte e basta, come partiva prima. */
(function () {
  'use strict';
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f || !f.getAttribute) { return; }
    var domanda = f.getAttribute('data-conferma');
    if (domanda && !window.confirm(domanda)) {
      e.preventDefault();
    }
  }, true);
})();
