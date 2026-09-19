-- 0010_episodi : le storie chiuse, giocate insieme.
--
-- Un episodio e' il momento in cui la vita quotidiana si organizza in una
-- puntata: un titolo, un cast, un problema, un finale. Tre scelte di fondo:
--
--  * **Non si aprono con un pulsante.** Nascono da condizioni del mondo — chi
--    c'e', dove, che tempo fa, che giorno e', che sospetti girano. Un episodio
--    che si avvia a comando e' una missione; un episodio che ti capita addosso
--    e' una puntata.
--  * **Si giocano a turni, con una finestra reale.** Ogni scena aspetta che
--    tutti abbiano scelto, o che scada il tempo.
--  * **Chi manca non blocca nessuno.** Il suo personaggio agisce lo stesso,
--    secondo carattere: e' l'agente autonomo, la stessa idea del Primo
--    Ufficiale che prende il comando quando il comandante non c'e'.
--
-- E non si VINCONO. Alla fine si guarda cos'e' successo: il segreto ha tenuto?
-- i legami dove sono finiti? chi ha pagato il prezzo? Quello che resta e' una
-- scheda nell'album dei ricordi.

CREATE TABLE IF NOT EXISTS copioni (
  ckey        VARCHAR(32) NOT NULL PRIMARY KEY,
  titolo      VARCHAR(128) NOT NULL,
  occhiello   VARCHAR(64) NOT NULL,
  premessa    TEXT NOT NULL,
  luogo       VARCHAR(32) NULL COMMENT 'NULL = dovunque si trovi il cast',
  condizione  VARCHAR(32) NOT NULL DEFAULT 'sempre',
  min_cast    TINYINT UNSIGNED NOT NULL DEFAULT 2,
  max_cast    TINYINT UNSIGNED NOT NULL DEFAULT 5,
  finestra    SMALLINT UNSIGNED NOT NULL DEFAULT 1800 COMMENT 'secondi REALI per scena',
  scene       JSON NOT NULL,
  attivo      TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS episodi (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  ckey        VARCHAR(32) NOT NULL,
  titolo      VARCHAR(160) NOT NULL,
  luogo       VARCHAR(32) NOT NULL,
  scena       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  stato       ENUM('aperto','concluso') NOT NULL DEFAULT 'aperto',
  aperto_gts  BIGINT NOT NULL,
  scena_gts   BIGINT NOT NULL COMMENT 'quando e'' cominciata la scena corrente',
  scade_reale BIGINT NOT NULL COMMENT 'istante REALE in cui la scena scade',
  chiuso_gts  BIGINT NULL,
  esito       JSON NULL,
  KEY idx_ep_stato (stato, scade_reale),
  CONSTRAINT fk_ep_c FOREIGN KEY (ckey) REFERENCES copioni(ckey) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS episodio_cast (
  episodio_id    BIGINT UNSIGNED NOT NULL,
  personaggio_id BIGINT UNSIGNED NOT NULL,
  entrato_gts    BIGINT NOT NULL,
  PRIMARY KEY (episodio_id, personaggio_id),
  KEY idx_ec_pg (personaggio_id),
  CONSTRAINT fk_ec_e FOREIGN KEY (episodio_id) REFERENCES episodi(id) ON DELETE CASCADE,
  CONSTRAINT fk_ec_p FOREIGN KEY (personaggio_id) REFERENCES personaggi(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS episodio_scelte (
  episodio_id    BIGINT UNSIGNED NOT NULL,
  scena          TINYINT UNSIGNED NOT NULL,
  personaggio_id BIGINT UNSIGNED NOT NULL,
  opzione        VARCHAR(24) NOT NULL,
  da_solo        TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = ha scelto l''agente autonomo',
  riuscita       TINYINT(1) NULL,
  racconto       VARCHAR(255) NULL,
  PRIMARY KEY (episodio_id, scena, personaggio_id),
  CONSTRAINT fk_es_e FOREIGN KEY (episodio_id) REFERENCES episodi(id) ON DELETE CASCADE,
  CONSTRAINT fk_es_p FOREIGN KEY (personaggio_id) REFERENCES personaggi(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- L'album dei ricordi ------------------------------------------------------
-- E' la collezione, ed e' anche il diario. Sopravvive al Trasloco: le persone
-- si perdono, i posti si perdono, quello che e' successo no.
CREATE TABLE IF NOT EXISTS ricordi (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  personaggio_id BIGINT UNSIGNED NOT NULL,
  user_id        BIGINT UNSIGNED NOT NULL COMMENT 'resta al giocatore anche dopo il trasloco',
  episodio_id    BIGINT UNSIGNED NULL,
  titolo         VARCHAR(160) NOT NULL,
  testo          TEXT NOT NULL,
  gts            BIGINT NOT NULL,
  luogo          VARCHAR(32) NOT NULL,
  KEY idx_ric_pg (personaggio_id, gts),
  KEY idx_ric_user (user_id, gts),
  CONSTRAINT fk_ric_u FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO game_config (ckey, cvalue, ctype, note) VALUES
  ('episodi.pausa_ore',      '18', 'int', 'Ore di gioco minime fra due episodi per la stessa persona'),
  ('episodi.finestra',     '1800', 'int', 'Secondi reali di attesa per scena, se il copione non dice altro'),
  ('episodi.max_aperti',      '6', 'int', 'Quanti episodi possono essere aperti nel quartiere insieme')
ON DUPLICATE KEY UPDATE cvalue = VALUES(cvalue), ctype = VALUES(ctype);
