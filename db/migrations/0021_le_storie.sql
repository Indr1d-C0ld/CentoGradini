-- Le storie: il terzo passo del controllo del 9 ottobre 2026.
--
-- Gli oggetti unici diventano piu' di uno — al cappello si aggiungono
-- l'orologio del nonno, il suo pappagallo e il ciondolo di Akemi — e ognuno ha
-- una casa a cui torna: prima, chi lo lasciava a un abitante lo ritrovava
-- sempre sui gradini, che va bene per il cappello e per nessun altro.
-- `pronto_gts` e' quando un oggetto che si scarica (l'orologio, che ferma il
-- tempo per un minuto e poi va ricaricato) torna a funzionare.
ALTER TABLE oggetti_unici
  ADD COLUMN IF NOT EXISTS dimora VARCHAR(32) NULL
    COMMENT 'dove torna quando nessuno lo tiene piu''',
  ADD COLUMN IF NOT EXISTS pronto_gts BIGINT NULL
    COMMENT 'da quando si puo'' usare di nuovo, per gli oggetti che si scaricano';

INSERT INTO game_config (ckey, cvalue, ctype, note) VALUES
  ('oggetti.orologio_giorni', '3', 'int',
   'Ogni quanti giorni di gioco l''orologio del nonno si ricarica'),
  ('legami.soglia_coppia', '25', 'int',
   'Quanto affetto deve avere per te chi ti accompagna alla Moebius, dove si entra solo in coppia')
ON DUPLICATE KEY UPDATE note = VALUES(note);
