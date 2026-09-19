-- 0003_mondo : il quartiere, chi ci abita, dove si trova e cosa ci lascia.
--
-- Tre note su cosa NON c'e' qui dentro:
--
--  * niente tabella del meteo: il tempo e' una funzione pura di seme e istante
--    (src/Sim/Meteo.php), quindi non va salvato da nessuna parte;
--  * niente tabella dell'orologio: l'ora di gioco si ricava dall'ora reale
--    (src/Sim/Orologio.php), e l'unica cosa persistente e' l'epoca, che sta
--    in game_config;
--  * niente statistiche del personaggio: arrivano con F2. Qui il personaggio
--    ha solo un nome e una posizione, quel tanto che basta per camminare per
--    il quartiere. I personaggi creati adesso andranno completati
--    retroattivamente, non lasciati a meta'.

-- --- I luoghi -----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS luoghi (
  lkey        VARCHAR(32) NOT NULL PRIMARY KEY,
  nome        VARCHAR(64) NOT NULL,
  sottotitolo VARCHAR(128) NULL,
  descrizione TEXT NOT NULL,
  tipo        ENUM('strada','ritrovo','scuola','casa','natura','fuori') NOT NULL DEFAULT 'strada',
  x           SMALLINT UNSIGNED NOT NULL,
  y           SMALLINT UNSIGNED NOT NULL,
  apre        SMALLINT NULL COMMENT 'minuti dalla mezzanotte; NULL = sempre aperto',
  chiude      SMALLINT NULL,
  stagione    VARCHAR(16) NULL COMMENT 'se valorizzato, il luogo vive solo in quella stagione',
  privato     TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'ci si entra solo se invitati',
  fuori       TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'fuori dal quartiere: ci si va in treno',
  ordine      SMALLINT NOT NULL DEFAULT 0,
  KEY idx_luoghi_ordine (ordine)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- Come si passa da un luogo all'altro -----------------------------------------
-- Gli archi sono orientati e vanno seminati in entrambi i versi: cosi' si puo'
-- avere una scorciatoia che funziona solo in discesa senza casi speciali nel
-- codice. (I Cento Gradini, per dirne una.)
CREATE TABLE IF NOT EXISTS luogo_archi (
  akey    VARCHAR(70) NOT NULL PRIMARY KEY COMMENT 'da>a',
  da      VARCHAR(32) NOT NULL,
  a       VARCHAR(32) NOT NULL,
  minuti  SMALLINT UNSIGNED NOT NULL COMMENT 'minuti di gioco',
  mezzo   ENUM('piedi','bici','treno') NOT NULL DEFAULT 'piedi',
  KEY idx_archi_da (da),
  CONSTRAINT fk_archi_da FOREIGN KEY (da) REFERENCES luoghi(lkey) ON DELETE CASCADE,
  CONSTRAINT fk_archi_a  FOREIGN KEY (a)  REFERENCES luoghi(lkey) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- Chi abita il quartiere ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS personaggi (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT UNSIGNED NOT NULL,
  nome        VARCHAR(32) NOT NULL,
  cognome     VARCHAR(32) NOT NULL,
  sesso       ENUM('m','f') NOT NULL,
  eta         TINYINT UNSIGNED NOT NULL DEFAULT 16,
  anno_scuola TINYINT UNSIGNED NOT NULL DEFAULT 2 COMMENT '1, 2 o 3 del liceo',
  -- F2 riempira' abilita', tratti e poteri. Questa colonna dice a che punto
  -- e' la scheda, perche' un personaggio nato in F1 va completato dopo.
  scheda      ENUM('abbozzo','completa') NOT NULL DEFAULT 'abbozzo',
  luogo       VARCHAR(32) NOT NULL DEFAULT 'gradini',
  arrivato_gts BIGINT NOT NULL DEFAULT 0,
  -- Viaggio in corso: durante il tragitto si e' "fra due luoghi".
  verso       VARCHAR(32) NULL,
  arrivo_gts  BIGINT NULL,
  creato_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  visto_gts   BIGINT NOT NULL DEFAULT 0 COMMENT 'ultimo istante di gioco in cui il giocatore era presente',
  UNIQUE KEY uq_pg_utente (user_id),
  KEY idx_pg_luogo (luogo),
  CONSTRAINT fk_pg_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- Dove si e' stati ---------------------------------------------------------------------
-- Serve a due cose: sapere chi c'era quando (che in F3 diventera' la lista dei
-- testimoni) e ricostruire i propri spostamenti nel diario.
CREATE TABLE IF NOT EXISTS presenze (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  personaggio_id BIGINT UNSIGNED NOT NULL,
  luogo          VARCHAR(32) NOT NULL,
  dal_gts        BIGINT NOT NULL,
  al_gts         BIGINT NULL COMMENT 'NULL = ci si trova ancora',
  KEY idx_pres_luogo_tempo (luogo, dal_gts),
  KEY idx_pres_pg (personaggio_id, dal_gts),
  CONSTRAINT fk_pres_pg FOREIGN KEY (personaggio_id) REFERENCES personaggi(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- Quello che si lascia dietro ---------------------------------------------------------------
-- Il quartiere ricorda. Una traccia e' un fatto osservabile legato a un luogo e
-- a un istante; con F6 diventera' la materia prima delle voci. La forza scende
-- col tempo: dopo qualche giorno di gioco nessuno si ricorda piu' niente.
CREATE TABLE IF NOT EXISTS tracce (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  luogo          VARCHAR(32) NOT NULL,
  gts            BIGINT NOT NULL,
  personaggio_id BIGINT UNSIGNED NULL COMMENT 'NULL = traccia del mondo, non di una persona',
  tipo           VARCHAR(24) NOT NULL,
  testo          VARCHAR(255) NOT NULL,
  forza          TINYINT UNSIGNED NOT NULL DEFAULT 100,
  KEY idx_tracce_luogo (luogo, gts),
  KEY idx_tracce_pg (personaggio_id),
  CONSTRAINT fk_tracce_pg FOREIGN KEY (personaggio_id) REFERENCES personaggi(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- L'epoca del mondo --------------------------------------------------------------------------
-- L'istante reale a cui corrisponde il primo giorno di scuola. Si fissa adesso,
-- una volta sola: da qui in poi l'ora di gioco si ricava sempre per calcolo.
-- La riga esiste gia' (migrazione 0002) con valore vuoto: la si riempie solo se
-- e' ancora vuota, cosi' rilanciare le migrazioni non sposta il mondo indietro.
UPDATE game_config SET cvalue = DATE_FORMAT(NOW(), '%Y-%m-%d %H:%i:%s')
 WHERE ckey = 'clock.epoch_real' AND (cvalue IS NULL OR cvalue = '');

INSERT INTO game_config (ckey, cvalue, ctype, note) VALUES
  ('mondo.traccia_durata_ore', '72', 'int', 'Dopo quante ore di gioco una traccia si spegne del tutto'),
  ('mondo.presenza_minuti',    '45', 'int', 'Per quanti minuti di gioco si resta "visto di recente" in un luogo')
ON DUPLICATE KEY UPDATE cvalue = VALUES(cvalue), ctype = VALUES(ctype);
