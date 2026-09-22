-- Il canale diretto fra la gestione e un giocatore.
--
-- Non e' un messaggio DEL QUARTIERE: e' la gestione che parla, e la
-- differenza va tenuta visibile. Un avvertimento di moderazione travestito da
-- biglietto lasciato sui gradini sarebbe una bugia raccontata proprio nel
-- momento in cui serve essere creduti — e la prima volta che un giocatore se
-- ne accorge, smette di credere anche al resto.
--
-- Percio' non passa per `biglietti` (che sono finzione, si lasciano in un
-- luogo e si trovano andandoci) ne' per `voci`: ha una tabella sua, e a
-- schermo ha una faccia sua.
--
-- Serve anche al verso opposto: il giocatore risponde nello stesso filo. Un
-- canale a senso unico obbliga chi ha un problema a scrivere un'e-mail e
-- aspettare, e quasi nessuno lo fa.
CREATE TABLE IF NOT EXISTS comunicazioni (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  -- Di chi e' il filo. Sempre il GIOCATORE, anche quando scrive la gestione:
  -- cosi' la conversazione si legge con una sola interrogazione, da tutte e
  -- due le parti.
  user_id    BIGINT UNSIGNED NOT NULL,
  -- Chi ha scritto questo messaggio. NULL vuol dire «la gestione» senza
  -- nome: se un amministratore un giorno sparisce dal database, il messaggio
  -- resta leggibile invece di sparire con lui.
  autore_id  BIGINT UNSIGNED NULL,
  da_admin   TINYINT(1) NOT NULL DEFAULT 0,
  testo      VARCHAR(2000) NOT NULL,
  letto_at   DATETIME NULL COMMENT 'quando l''altra parte l''ha visto',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_com_filo (user_id, id),
  KEY idx_com_nonletti (user_id, da_admin, letto_at),
  CONSTRAINT fk_com_user   FOREIGN KEY (user_id)   REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_com_autore FOREIGN KEY (autore_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO game_config (ckey, cvalue, ctype, note) VALUES
  ('posta.avvisa_comunicazioni', '1', 'bool',
   'Manda un''e-mail al giocatore quando la gestione gli scrive')
ON DUPLICATE KEY UPDATE cvalue = cvalue;
