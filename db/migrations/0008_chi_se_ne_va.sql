-- 0008_chi_se_ne_va : lo stato del personaggio.
--
-- Il Trasloco non e' la morte. La famiglia di Kyosuke si era gia' trasferita
-- sette volte prima che la storia cominciasse, ed e' la conseguenza vera di
-- farsi scoprire: non si muore, si sparisce dal quartiere. Il personaggio
-- resta a database — serve l'elenco di chi se n'e' andato, e serve il motivo —
-- ma non e' piu' in giro, non fa da testimone e non compare fra i presenti.

ALTER TABLE personaggi
  ADD COLUMN IF NOT EXISTS stato ENUM('attivo','trasferito') NOT NULL DEFAULT 'attivo' AFTER scheda;

CREATE INDEX IF NOT EXISTS idx_pg_stato ON personaggi (stato, luogo);
