/**
 * Il service worker.
 *
 * Fa una cosa sola e la fa bene: tenere in tasca il guscio del sito — il
 * foglio di stile, gli script, l'icona — così l'applicazione si apre subito
 * anche con la rete lenta, e se la rete non c'è dice qualcosa di sensato
 * invece di mostrare il dinosauro.
 *
 * **Non mette in cache le pagine di gioco, mai.** Il quartiere cambia ogni
 * minuto: una pagina salvata è una bugia, e una bugia su dove si trovano gli
 * altri è peggio di un errore di rete. Rete prima, e se manca una pagina di
 * cortesia.
 */

const VERSIONE = 'cento-gradini-v1';
const GUSCIO = [
  'assets/css/kor.css',
  'assets/js/mondo.js',
  'assets/img/icona.svg',
];

self.addEventListener('install', (e) => {
  e.waitUntil(
    caches.open(VERSIONE)
      .then((c) => c.addAll(GUSCIO.map((p) => new URL(p, self.registration.scope).toString())))
      .then(() => self.skipWaiting())
      .catch(() => self.skipWaiting())   // un guscio mancante non blocca l'installazione
  );
});

self.addEventListener('activate', (e) => {
  e.waitUntil(
    caches.keys()
      .then((chiavi) => Promise.all(
        chiavi.filter((k) => k !== VERSIONE).map((k) => caches.delete(k))
      ))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET') { return; }

  const url = new URL(req.url);
  if (url.origin !== self.location.origin) { return; }

  const statico = /\.(css|js|svg|png|jpg|jpeg|webp|woff2?)$/.test(url.pathname);

  if (statico) {
    // Cache prima, e intanto si aggiorna per la volta dopo.
    e.respondWith(
      caches.match(req).then((salvata) => {
        const dalla_rete = fetch(req).then((r) => {
          if (r && r.ok) {
            const copia = r.clone();
            caches.open(VERSIONE).then((c) => c.put(req, copia));
          }
          return r;
        }).catch(() => salvata);
        return salvata || dalla_rete;
      })
    );
    return;
  }

  // Tutto il resto e' gioco: rete, sempre. Se non c'e', si dice.
  e.respondWith(
    fetch(req).catch(() => new Response(
      `<!doctype html><meta charset="utf-8"><title>Senza rete</title>
       <style>body{font:16px/1.6 system-ui;margin:4rem auto;max-width:30rem;padding:0 1.2rem;
       background:#f4efe6;color:#2b2b33}h1{font-size:1.4rem}</style>
       <h1>Il quartiere non risponde</h1>
       <p>Non c'&egrave; rete. Il mondo continua ad andare avanti per conto suo — l'orologio
       non si ferma perch&eacute; tu non guardi — e quando torni trovi quello che &egrave;
       successo nel frattempo.</p>
       <p><a href="">Riprova</a></p>`,
      { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
    ))
  );
});
