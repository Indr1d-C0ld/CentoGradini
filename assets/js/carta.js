/* ============================================================================
   La carta del quartiere.
   
   Disegno su tela, niente librerie. Le coordinate dei luoghi arrivano dal
   server su una griglia fissa di 1000x700 e qui vengono riscalate sulla
   larghezza disponibile: cosi' la stessa carta funziona sul telefono e sullo
   schermo grande senza due insiemi di numeri da tenere allineati.
   
   I colori NON sono scritti qui: si leggono dalle variabili CSS del tema, cosi'
   la carta cambia insieme al resto quando si passa al tema scuro. Una tela
   disegnata con colori fissi sarebbe l'unica cosa della pagina a restare
   chiara di notte, e si vedrebbe.
   ========================================================================= */
(function () {
  'use strict';

  var tela = document.getElementById('mappa');
  if (!tela || !tela.getContext) { return; }

  var ctx = tela.getContext('2d');
  var LARG = 1000, ALT = 700;
  var dati = null;
  var sopra = null;          // nodo sotto il puntatore
  var scala = 1;

  function colore(nome, difetto) {
    var v = getComputedStyle(document.documentElement).getPropertyValue(nome).trim();
    return v || difetto;
  }

  var tavolozza = {};
  function leggiTavolozza() {
    tavolozza = {
      fondo:   colore('--fondo-carta', '#fffdf9'),
      strada:  colore('--bordo', '#e4d3c0'),
      inchiostro: colore('--inchiostro', '#4a3f38'),
      tenue:   colore('--inchiostro-tenue', '#756557'),
      arancio: colore('--arancio', '#b64f1c'),
      azzurro: colore('--azzurro', '#6d9db8'),
      verde:   colore('--verde', '#7d9b72'),
      pallido: colore('--fondo-cavo', '#f5e9dc')
    };
  }

  /* --- Dimensionamento -------------------------------------------------- */
  function ridimensiona() {
    var largheDisponibile = tela.parentElement.clientWidth;
    var dpr = window.devicePixelRatio || 1;
    scala = largheDisponibile / LARG;
    tela.style.width = largheDisponibile + 'px';
    tela.style.height = (ALT * scala) + 'px';
    tela.width = Math.round(largheDisponibile * dpr);
    tela.height = Math.round(ALT * scala * dpr);
    ctx.setTransform(dpr * scala, 0, 0, dpr * scala, 0, 0);
    disegna();
  }

  /* --- Disegno ------------------------------------------------------------ */
  function nodoDi(k) {
    for (var i = 0; i < dati.nodi.length; i++) {
      if (dati.nodi[i].k === k) { return dati.nodi[i]; }
    }
    return null;
  }

  function raggio(n) {
    if (n.qui) { return 13; }
    if (n.tipo === 'scuola' || n.tipo === 'ritrovo') { return 10; }
    return 8;
  }

  function disegna() {
    if (!dati) { return; }
    leggiTavolozza();

    ctx.clearRect(0, 0, LARG, ALT);
    ctx.fillStyle = tavolozza.fondo;
    ctx.fillRect(0, 0, LARG, ALT);

    /* Le strade. Quelle del treno sono tratteggiate: si vede a colpo d'occhio
       che sono un'altra cosa, e che costano un viaggio e non una passeggiata. */
    dati.tratti.forEach(function (t) {
      var a = nodoDi(t.da), b = nodoDi(t.a);
      if (!a || !b) { return; }
      ctx.beginPath();
      ctx.moveTo(a.x, a.y);
      ctx.lineTo(b.x, b.y);
      if (t.mezzo === 'treno') {
        ctx.setLineDash([9, 9]);
        ctx.lineWidth = 2.5;
        ctx.strokeStyle = tavolozza.azzurro;
      } else {
        ctx.setLineDash([]);
        ctx.lineWidth = 5;
        ctx.strokeStyle = tavolozza.strada;
      }
      ctx.stroke();
      ctx.setLineDash([]);
    });

    /* I luoghi. */
    dati.nodi.forEach(function (n) {
      var r = raggio(n);
      var attivo = (sopra === n.k);

      /* Alone di chi sta qui: si vede prima del resto, ed e' la domanda che si
         fa per prima chi apre la carta — dove sono io? */
      if (n.qui) {
        ctx.beginPath();
        ctx.arc(n.x, n.y, r + 9, 0, Math.PI * 2);
        ctx.fillStyle = tavolozza.arancio;
        ctx.globalAlpha = 0.16;
        ctx.fill();
        ctx.globalAlpha = 1;
      }

      ctx.beginPath();
      ctx.arc(n.x, n.y, r + (attivo ? 2 : 0), 0, Math.PI * 2);
      ctx.fillStyle = n.qui ? tavolozza.arancio
                    : (!n.aperto ? tavolozza.pallido : tavolozza.fondo);
      ctx.fill();
      ctx.lineWidth = n.qui ? 0 : 2.5;
      ctx.strokeStyle = n.fuori ? tavolozza.azzurro
                      : (n.privato ? tavolozza.verde : tavolozza.tenue);
      if (!n.qui) { ctx.stroke(); }

      /* Le altre persone: un pallino per testa, in cerchio attorno al luogo.
         Oltre le sei si smette di contarle e si scrive il numero. */
      if (n.gente > 0 && !n.qui) {
        var quanti = Math.min(n.gente, 6);
        for (var i = 0; i < quanti; i++) {
          var ang = -Math.PI / 2 + (i * Math.PI * 2) / Math.max(3, quanti);
          ctx.beginPath();
          ctx.arc(n.x + Math.cos(ang) * (r + 7), n.y + Math.sin(ang) * (r + 7), 2.6, 0, Math.PI * 2);
          ctx.fillStyle = tavolozza.arancio;
          ctx.fill();
        }
      }

      /* L'etichetta. Sotto il pallino se il luogo sta nella metà alta della
         tela, sopra se sta in basso: così non esce mai dal bordo verticale.
         Sull'orizzontale invece si rientra a forza, perché un luogo vicino al
         margine (la montagna, a nord-ovest) avrebbe il nome tagliato a metà.
         Le posizioni sono scelte in modo che le etichette non si accavallino
         — lo verifica tests/test_luoghi.php — ma la rientranza resta come
         rete di sicurezza per quando se ne aggiungerà una. */
      ctx.font = (attivo || n.qui ? '600 ' : '') + '15px -apple-system, "Segoe UI", system-ui, sans-serif';
      ctx.textAlign = 'center';
      ctx.fillStyle = n.qui ? tavolozza.arancio : (n.aperto ? tavolozza.inchiostro : tavolozza.tenue);
      var sotto = n.y < ALT * 0.45;
      ctx.textBaseline = sotto ? 'top' : 'bottom';
      var mezzo = ctx.measureText(n.nome).width / 2;
      var tx = Math.min(LARG - mezzo - 4, Math.max(mezzo + 4, n.x));
      ctx.fillText(n.nome, tx, n.y + (sotto ? r + 9 : -(r + 9)));
    });
  }

  /* --- Interazione ---------------------------------------------------------- */
  function nodoVicino(ev) {
    if (!dati) { return null; }
    var rect = tela.getBoundingClientRect();
    var x = (ev.clientX - rect.left) / scala;
    var y = (ev.clientY - rect.top) / scala;
    var migliore = null, distMin = 32;   /* soglia generosa: si tocca col dito */
    dati.nodi.forEach(function (n) {
      var d = Math.hypot(n.x - x, n.y - y);
      if (d < distMin) { distMin = d; migliore = n; }
    });
    return migliore;
  }

  tela.addEventListener('mousemove', function (ev) {
    var n = nodoVicino(ev);
    var k = n ? n.k : null;
    if (k !== sopra) {
      sopra = k;
      tela.style.cursor = k ? 'pointer' : 'default';
      disegna();
    }
  });
  tela.addEventListener('mouseleave', function () {
    if (sopra !== null) { sopra = null; disegna(); }
  });
  tela.addEventListener('click', function (ev) {
    var n = nodoVicino(ev);
    if (n) { window.location.href = tela.dataset.luogo + n.k; }
  });

  /* --- Caricamento ----------------------------------------------------------- */
  function carica() {
    fetch(tela.dataset.api, { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (d) { if (d) { dati = d; ridimensiona(); } })
      .catch(function () { /* la pagina resta usabile senza carta */ });
  }

  window.addEventListener('resize', ridimensiona);
  /* Il tema puo' cambiare sotto i piedi (impostazione di sistema): la tela non
     se ne accorgerebbe da sola, perche' e' gia' disegnata. */
  if (window.matchMedia) {
    var mq = window.matchMedia('(prefers-color-scheme: dark)');
    if (mq.addEventListener) { mq.addEventListener('change', disegna); }
  }
  carica();
})();
