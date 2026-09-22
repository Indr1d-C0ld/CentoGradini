/* Il riquadro di centratura della fotografia del personaggio.

   L'immagine si trascina e si stringe dentro un quadrato; al momento
   dell'invio il riquadro calcola il RETTANGOLO DI RITAGLIO IN PIXEL
   DELL'IMMAGINE ORIGINALE e lo scrive in tre campi nascosti.

   E' il contratto giusto fra pagina e server: «zoom e spostamento» sarebbero
   numeri che significano qualcosa solo conoscendo la misura del riquadro sullo
   schermo di chi carica — misura che il server non conosce e di cui non deve
   fidarsi. Un rettangolo in pixel dell'originale il server lo sa verificare da
   solo, e infatti lo riporta dentro i bordi qualunque cosa arrivi.

   Senza JavaScript il modulo funziona lo stesso: i tre campi restano a zero e
   il server ritaglia centrato sul lato corto, un po' piu' in alto del centro
   geometrico, perche' in un ritratto la testa sta in alto. */
(function () {
  'use strict';

  var box = document.querySelector('[data-ritaglio]');
  if (!box) { return; }

  var scelta  = box.querySelector('input[type=file]');
  var cornice = box.querySelector('.cornice');
  var img     = cornice ? cornice.querySelector('img') : null;
  var zoom    = box.querySelector('input[type=range]');
  var campoX  = box.querySelector('input[name=sx]');
  var campoY  = box.querySelector('input[name=sy]');
  var campoL  = box.querySelector('input[name=lato]');
  var invia   = box.querySelector('button[type=submit]');
  if (!scelta || !cornice || !img || !zoom || !campoX || !campoY || !campoL) { return; }

  /* Il pulsante nasce ATTIVO nel markup, perche' senza JavaScript il modulo
     deve poter partire lo stesso. Lo spegne questo script, che se c'e' si
     prende la responsabilita' di riaccenderlo quando l'anteprima e' pronta. */
  if (invia) { invia.disabled = true; }

  function avvisa(testo) {
    var p = box.querySelector('[data-avviso]');
    if (p) { p.textContent = testo || ''; }
  }

  var nw = 0, nh = 0;   // misure vere dell'immagine
  var base = 1;         // scala minima perche' copra la cornice
  var ox = 0, oy = 0;   // posizione dell'immagine dentro la cornice

  function lato() { return cornice.clientWidth; }

  function limita() {
    var F = lato();
    var s = base * parseFloat(zoom.value || '1');
    /* Guardia: senza misure valide non si scrive niente. Serve perche'
       `campoL` vale `F / s`, e con `s` a zero ci finirebbe dentro `Infinity`
       — che il server legge come zero e ritaglia centrato, ignorando in
       silenzio il riquadro che il giocatore ha appena regolato. */
    if (!nw || !nh || F <= 0 || !(s > 0)) { return; }

    var w = nw * s, h = nh * s;
    ox = Math.min(0, Math.max(F - w, ox));
    oy = Math.min(0, Math.max(F - h, oy));
    img.style.width  = w + 'px';
    img.style.height = h + 'px';
    img.style.left   = ox + 'px';
    img.style.top    = oy + 'px';

    // Il ritaglio, in pixel dell'originale.
    campoX.value = Math.round(-ox / s);
    campoY.value = Math.round(-oy / s);
    campoL.value = Math.round(F / s);
  }

  /* Rimette l'immagine al suo posto: scala minima che copre la cornice, e
     inquadratura di partenza un po' piu' in alto del centro. Va chiamata a
     cornice VISIBILE. */
  function sistema() {
    var F = lato();
    if (!nw || !nh) { return; }
    if (F <= 0) {
      /* La cornice non ha ancora una misura: puo' succedere se la pagina sta
         ancora impaginando. Si riprova al disegno successivo invece di
         rassegnarsi a una scala sbagliata. */
      requestAnimationFrame(sistema);
      return;
    }
    base = F / Math.min(nw, nh);
    zoom.value = '1';
    ox = (F - nw * base) / 2;
    oy = Math.min(0, -(nh * base - F) * 0.18);
    limita();
  }

  function comandi(visibili) {
    var c = box.querySelector('[data-comandi]');
    if (c) { c.hidden = !visibili; }
  }

  scelta.addEventListener('change', function () {
    var f = scelta.files && scelta.files[0];
    if (!f) { return; }
    if (!/^image\/(jpeg|png|webp)$/.test(f.type)) {
      avvisa('Serve un JPEG, un PNG o un WebP.');
      if (invia) { invia.disabled = true; }
      return;
    }
    avvisa('');
    var url = URL.createObjectURL(f);

    /* Se l'anteprima non si carica non si resta muti: senza messaggio il
       modulo sembrerebbe soltanto non funzionare — nessun riquadro, nessuna
       spiegazione, il pulsante spento per sempre. Si dice cos'e' successo e si
       lascia partire l'invio: il server sa ritagliare da solo. */
    img.onerror = function () {
      URL.revokeObjectURL(url);
      cornice.hidden = true;
      comandi(false);
      avvisa('Non riesco a mostrarti l\'anteprima di questa immagine. '
           + 'Puoi caricarla lo stesso: verrà ritagliata quadrata e centrata.');
      if (invia) { invia.disabled = false; }
    };

    img.onload = function () {
      nw = img.naturalWidth;
      nh = img.naturalHeight;

      /* SI SCOPRE LA CORNICE PRIMA DI MISURARLA. Finche' ha l'attributo
         `hidden` vale `display: none`, e `clientWidth` di un elemento non
         disegnato e' ZERO: misurandola prima, la scala dell'anteprima viene
         zero e l'immagine finisce larga zero pixel. Il riquadro comparirebbe
         vuoto, con dentro la foto invisibile — e il sintomo e' tanto piu'
         confondente perche' ridimensionando la finestra si sistema da se',
         dato che il gestore del `resize` ricalcola a cornice ormai visibile. */
      cornice.hidden = false;
      comandi(true);

      sistema();
      if (invia) { invia.disabled = false; }
      URL.revokeObjectURL(url);
    };
    img.src = url;
  });

  zoom.addEventListener('input', limita);

  window.addEventListener('resize', function () {
    if (!nw) { return; }
    var F = lato();
    if (F > 0) { base = F / Math.min(nw, nh); limita(); }
  });

  // Trascinamento, col dito o col mouse: i pointer events li coprono tutti e due.
  var trascino = false, px = 0, py = 0;
  cornice.addEventListener('pointerdown', function (e) {
    if (!nw) { return; }
    trascino = true; px = e.clientX; py = e.clientY;
    cornice.setPointerCapture(e.pointerId);
    e.preventDefault();
  });
  cornice.addEventListener('pointermove', function (e) {
    if (!trascino) { return; }
    ox += e.clientX - px; oy += e.clientY - py;
    px = e.clientX; py = e.clientY;
    limita();
  });
  function molla(e) {
    if (!trascino) { return; }
    trascino = false;
    try { cornice.releasePointerCapture(e.pointerId); } catch (x) {}
  }
  cornice.addEventListener('pointerup', molla);
  cornice.addEventListener('pointercancel', molla);
})();
