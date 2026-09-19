-- Il versante della collina: quello che si vede da un luogo, e da che parte.
--
-- Viene dalla ricostruzione del grande escalier (CANONE §7-ter): dall'episodio
-- 42 si deduce che la scalinata e' orientata **nord-sud**, perche' il sole
-- tramonta all'orizzonte sulla destra di chi guarda giu'. Chi sta in cima ha
-- la citta' a ovest, sotto di se'.
--
-- Non e' decorazione: la scheda del luogo la usa per dire, all'ora giusta,
-- cosa si sta guardando. Un tramonto visto dai Cento Gradini e un tramonto
-- visto dentro la sala giochi sono due cose diverse, e il gioco lo sa dire
-- solo se glielo si scrive da qualche parte.
ALTER TABLE luoghi
  ADD COLUMN IF NOT EXISTS guarda VARCHAR(8) NOT NULL DEFAULT ''
    COMMENT 'punto cardinale verso cui si apre la vista: nord, sud, est, ovest',
  ADD COLUMN IF NOT EXISTS veduta VARCHAR(160) NOT NULL DEFAULT ''
    COMMENT 'cosa si vede da qui, una riga';
