-- Il ritorno dopo il trasloco, e le sessioni che cadono quando cambia la password.
--
-- Emerso dall'audit del 23 settembre 2026.
--
-- 1. IL RITORNO. PROGETTO §7 dice del trasloco: «perdi il quartiere, i legami
--    locali, la reputazione — tieni i poteri, il carattere e i ricordi. E' una
--    perdita vera e reversibile», e poco oltre «rientri nel quartiere come
--    trasferito di recente». Il tratto «Trasferito di recente» era nei semi fin
--    dall'inizio. Il ritorno invece non esisteva: dopo il trasloco il
--    personaggio restava un fantasma — invisibile agli altri ma ancora capace
--    di muoversi e di usare i poteri — e il giocatore non poteva crearne un
--    altro. Adesso si torna, dopo un'attesa: quella che rende la perdita vera.
INSERT INTO game_config (ckey, cvalue, ctype, note) VALUES
  ('segreto.rientro_giorni', '2', 'int',
   'Quanti giorni di gioco dopo il trasloco si puo'' tornare nel quartiere, come trasferito di recente')
ON DUPLICATE KEY UPDATE note = VALUES(note);

-- 2. LE SESSIONI. Il recupero della password era promesso dal modulo
--    d'iscrizione («serve per recuperare l'accesso») e non esisteva. Rifare la
--    password senza far cadere le sessioni aperte pero' e' meta' del lavoro: di
--    solito la si rifa' proprio perche' qualcun altro e' entrato. La
--    generazione sta sull'account; la sessione ricorda quella con cui e' nata,
--    e se non coincidono piu' la sessione non vale.
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS sessioni_gen INT UNSIGNED NOT NULL DEFAULT 0
    COMMENT 'sale quando le sessioni aperte devono cadere (password rifatta)';

INSERT INTO game_config (ckey, cvalue, ctype, note) VALUES
  ('auth.recupero_ttl_ore', '2', 'int',
   'Per quante ore vale il collegamento per rifare la password')
ON DUPLICATE KEY UPDATE note = VALUES(note);
