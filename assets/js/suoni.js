/* ============================================================================
   I suoni.

   Due note, generate con WebAudio: nessun file audio, nessun byte in piu' da
   scaricare, e niente da attribuire a nessuno. Suonano solo quando arriva
   qualcosa di nuovo per te — una voce, un biglietto, un episodio che si apre
   — e solo se li hai accesi.

   **Spenti in partenza, e apposta.** Un sito che fa rumore senza che glielo
   si sia chiesto e' un sito che si chiude. La scelta resta nel browser, non
   sul server: non e' un dato di gioco.
   ========================================================================= */
(function () {
  'use strict';

  var CHIAVE = 'cento-gradini:suoni';
  var acceso = localStorage.getItem(CHIAVE) === 'si';
  var contesto = null;
  var precedente = null;

  function nota(freq, inizio, durata, volume) {
    var osc = contesto.createOscillator();
    var gua = contesto.createGain();
    osc.type = 'sine';
    osc.frequency.value = freq;
    // L'attacco e la coda servono: un'onda che parte e finisce di colpo fa
    // un clic, ed e' molto piu' fastidioso della nota.
    gua.gain.setValueAtTime(0.0001, contesto.currentTime + inizio);
    gua.gain.exponentialRampToValueAtTime(volume, contesto.currentTime + inizio + 0.02);
    gua.gain.exponentialRampToValueAtTime(0.0001, contesto.currentTime + inizio + durata);
    osc.connect(gua).connect(contesto.destination);
    osc.start(contesto.currentTime + inizio);
    osc.stop(contesto.currentTime + inizio + durata + 0.05);
  }

  function suona(tipo) {
    if (!acceso) { return; }
    try {
      contesto = contesto || new (window.AudioContext || window.webkitAudioContext)();
      if (contesto.state === 'suspended') { contesto.resume(); }
    } catch (e) { return; }

    if (tipo === 'episodio') {
      // Terza maggiore ascendente: sta per succedere qualcosa.
      nota(523.25, 0, 0.28, 0.06);
      nota(659.25, 0.16, 0.40, 0.055);
    } else {
      // Due note vicine e brevi: qualcuno ti ha lasciato qualcosa.
      nota(783.99, 0, 0.18, 0.045);
      nota(1046.5, 0.11, 0.26, 0.035);
    }
  }

  // --- L'interruttore -------------------------------------------------------

  var bottone = document.querySelector('[data-suoni]');
  if (bottone) {
    var aggiorna = function () {
      bottone.textContent = acceso ? '♪' : '♪̸';
      bottone.setAttribute('aria-pressed', acceso ? 'true' : 'false');
      bottone.title = acceso ? 'Suoni accesi' : 'Suoni spenti';
    };
    aggiorna();
    bottone.addEventListener('click', function () {
      acceso = !acceso;
      localStorage.setItem(CHIAVE, acceso ? 'si' : 'no');
      aggiorna();
      if (acceso) { suona('avviso'); }     // una prova, cosi' si sa com'e'
    });
  }

  // --- Il confronto col battito ---------------------------------------------

  document.addEventListener('centogradini:battito', function (e) {
    var p = e.detail && e.detail.per_te;
    if (!p) { return; }
    if (precedente === null) { precedente = p; return; }   // il primo giro non suona
    if (p.episodio && !precedente.episodio) {
      suona('episodio');
    } else if (p.voci > precedente.voci || p.biglietti > precedente.biglietti) {
      suona('avviso');
    }
    precedente = p;
  });
})();
