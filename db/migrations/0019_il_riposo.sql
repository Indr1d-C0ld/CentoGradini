-- Il riposo, e le altre correzioni del controllo del 9 ottobre 2026.
--
-- 1. IL RIPOSO. Punti potere, punti ferita e compostezza scendevano e non
--    risalivano mai: nessuna riga del motore li restituiva. Dopo una settimana
--    di gioco i due personaggi in produzione erano a compostezza zero, e
--    coprire un incidente, chiarire un malinteso o dichiararsi erano diventati
--    impossibili per sempre. Il regolamento del 1990 il recupero lo aveva
--    («RECUPERARE PUNTI FERITA», «RECUPERARE PUNTI POTERE»): un punto ogni
--    tanto durante il giorno, tutto con una notte di sonno.
--
--    `ristoro_gts` e' l'istante fin dove il recupero e' gia' stato contato.
--    Il battito conta i confini d'ora e d'alba attraversati da li' a adesso:
--    un battito saltato non fa perdere niente e uno doppio non regala niente.
ALTER TABLE personaggi
  ADD COLUMN IF NOT EXISTS ristoro_gts BIGINT NULL
    COMMENT 'fin dove il recupero di PP, PF e compostezza e'' gia'' stato contato';

INSERT INTO game_config (ckey, cvalue, ctype, note) VALUES
  ('riposo.pp_ore', '1', 'int',
   'Ogni quante ore di gioco si recupera un punto potere (non nell''ora in cui se n''e'' usato uno)'),
  ('riposo.pf_ore', '2', 'int',
   'Ogni quante ore di gioco si recupera un punto ferita'),
  ('riposo.compostezza_ore', '3', 'int',
   'Ogni quante ore di gioco si recupera un punto di compostezza'),
  ('riposo.alba_ora', '6', 'int',
   'A che ora finisce la notte: chi la passa recupera tutto')
ON DUPLICATE KEY UPDATE note = VALUES(note);

-- 2. GLI EPISODI CHE SI RIPETONO. In produzione si erano aperti 67 episodi, e
--    tutti dello stesso copione: chi giocava rivedeva «chiacchiere» ogni
--    quattro ore e mezza reali. Adesso uno stesso copione non torna alla stessa
--    persona prima di questi giorni.
INSERT INTO game_config (ckey, cvalue, ctype, note) VALUES
  ('episodi.stesso_copione_giorni', '10', 'int',
   'Dopo quanti giorni di gioco lo stesso copione puo'' tornare a un giocatore che l''ha gia'' vissuto')
ON DUPLICATE KEY UPDATE note = VALUES(note);

-- 3. IL CAPPELLO DATO A UN ABITANTE restava fermo da lui per sempre: gli
--    abitanti non lo passano a nessuno. Adesso lo riportano sui gradini.
INSERT INTO game_config (ckey, cvalue, ctype, note) VALUES
  ('oggetti.png_giorni', '3', 'int',
   'Dopo quanti giorni di gioco un abitante riporta sui gradini un oggetto unico che gli e'' stato dato')
ON DUPLICATE KEY UPDATE note = VALUES(note);

-- 4. LE CASE PRIVATE. La descrizione dei Kasuga diceva «ci si entra solo se
--    invitati», e ci si entrava alle tre di notte. Adesso serve qualcuno di
--    casa, presente, a cui si stia abbastanza simpatici.
INSERT INTO game_config (ckey, cvalue, ctype, note) VALUES
  ('legami.soglia_invito', '30', 'int',
   'Quanto affetto deve avere per te chi abita in una casa privata, per farti entrare')
ON DUPLICATE KEY UPDATE note = VALUES(note);
