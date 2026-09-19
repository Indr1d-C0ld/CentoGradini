/**
 * Registrazione del service worker.
 *
 * Sta in un file e non dentro la pagina perché la Content-Security-Policy
 * dice `script-src 'self'`: uno script inline non verrebbe mai eseguito, e
 * il browser lo direbbe soltanto in console. È il tipo di guasto che non si
 * vede finché non si va a cercarlo.
 *
 * L'indirizzo del service worker arriva da un attributo `data-sw` sul tag,
 * perché il gioco può stare sotto un sottopercorso e il JavaScript non ha
 * modo di saperlo da solo.
 */
(function () {
  if (!('serviceWorker' in navigator)) { return; }
  // Su http il service worker non si registra, e non è un errore.
  if (location.protocol !== 'https:' && location.hostname !== 'localhost'
      && location.hostname !== '127.0.0.1') { return; }

  var tag = document.currentScript || document.querySelector('script[data-sw]');
  var sw  = tag && tag.getAttribute('data-sw');
  if (!sw) { return; }

  addEventListener('load', function () {
    navigator.serviceWorker.register(sw).catch(function () { /* pazienza */ });
  });
})();
