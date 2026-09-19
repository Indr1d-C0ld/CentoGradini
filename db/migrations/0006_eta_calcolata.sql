-- 0006_eta_calcolata : via le colonne superate dal modello scolastico.
--
-- `eta` e `anno_scuola` erano di F1, quando il modello era «un liceo di tre
-- anni» e l'eta' era un numero scelto a mano. Ora la classe e' sezione+anno e
-- l'eta' si calcola dal compleanno: tenerle sarebbe tenere due verita'
-- diverse sulla stessa cosa, ed e' il modo piu' rapido perche' comincino a
-- discordare.
--
-- I personaggi di F1 hanno gia' ricevuto i valori di difetto dalla 0005;
-- Scheda::assicuraTiri completa il resto alla prima occasione.

ALTER TABLE personaggi
  DROP COLUMN IF EXISTS eta,
  DROP COLUMN IF EXISTS anno_scuola;
