-- 0007_segreto : il ciclo centrale del gioco.
--
--   usi un potere -> chi c'era se n'e' accorto? -> lo copri o no?
--   -> se non lo copri, quel testimone si porta dietro un'ANOMALIA
--   -> tre anomalie coerenti non sono piu' un sospetto: sono una certezza
--   -> e allora o ti confidi, o traslochi.
--
-- Non c'e' una tabella «segreto»: il segreto e' la somma di queste.

ALTER TABLE poteri
  ADD COLUMN IF NOT EXISTS vistosita TINYINT UNSIGNED NOT NULL DEFAULT 5 AFTER a;

ALTER TABLE personaggi
  ADD COLUMN IF NOT EXISTS traslochi TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS ultimo_trasloco_gts BIGINT NULL;

-- --- Gli incidenti ----------------------------------------------------------
-- Un incidente e' un uso di potere che qualcuno POTEVA vedere. Esiste anche
-- quando nessuno se n'e' accorto: serve a poter dire, dopo, «e' successo».
CREATE TABLE IF NOT EXISTS incidenti (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  attore_id   BIGINT UNSIGNED NOT NULL,
  pkey        VARCHAR(32) NOT NULL,
  luogo       VARCHAR(32) NOT NULL,
  gts         BIGINT NOT NULL,
  vistosita   TINYINT UNSIGNED NOT NULL,
  folla       TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'quanta gente anonima c''era',
  notato_da   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  stato       ENUM('pulito','aperto','coperto','sfuggito') NOT NULL DEFAULT 'pulito',
  copertura   VARCHAR(16) NULL COMMENT 'scusa, diversivo, sfacciataggine, complice',
  nota        VARCHAR(255) NULL,
  KEY idx_inc_attore (attore_id, gts),
  KEY idx_inc_luogo (luogo, gts),
  CONSTRAINT fk_inc_pg FOREIGN KEY (attore_id) REFERENCES personaggi(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS incidente_testimoni (
  incidente_id   BIGINT UNSIGNED NOT NULL,
  personaggio_id BIGINT UNSIGNED NOT NULL,
  notato         TINYINT(1) NOT NULL DEFAULT 0,
  coperto        TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (incidente_id, personaggio_id),
  CONSTRAINT fk_it_inc FOREIGN KEY (incidente_id) REFERENCES incidenti(id) ON DELETE CASCADE,
  CONSTRAINT fk_it_pg  FOREIGN KEY (personaggio_id) REFERENCES personaggi(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- Le anomalie -------------------------------------------------------------
-- Quello che un testimone si porta dietro. Non dice «e' un esper»: dice «ho
-- visto una cosa che non torna». Metterle insieme e' un'azione del giocatore,
-- non un automatismo, ed e' il mestiere di chi non ha poteri.
CREATE TABLE IF NOT EXISTS anomalie (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  osservatore_id BIGINT UNSIGNED NOT NULL,
  soggetto_id   BIGINT UNSIGNED NOT NULL,
  incidente_id  BIGINT UNSIGNED NULL,
  gts           BIGINT NOT NULL,
  luogo         VARCHAR(32) NOT NULL,
  testo         VARCHAR(255) NOT NULL,
  collegata     TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'l''osservatore l''ha messa nel mucchio giusto',
  KEY idx_an_oss (osservatore_id, soggetto_id, gts),
  CONSTRAINT fk_an_oss FOREIGN KEY (osservatore_id) REFERENCES personaggi(id) ON DELETE CASCADE,
  CONSTRAINT fk_an_sog FOREIGN KEY (soggetto_id) REFERENCES personaggi(id) ON DELETE CASCADE,
  CONSTRAINT fk_an_inc FOREIGN KEY (incidente_id) REFERENCES incidenti(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- Chi sa -------------------------------------------------------------------
-- Due modi di sapere, e sono opposti: `confidato` e' stato detto, `scoperto`
-- e' stato capito. Il primo e' la mossa piu' rischiosa e piu' bella del gioco,
-- il secondo e' quella che fa traslocare.
CREATE TABLE IF NOT EXISTS sanno (
  esper_id    BIGINT UNSIGNED NOT NULL,
  chi_sa_id   BIGINT UNSIGNED NOT NULL,
  come        ENUM('confidato','scoperto') NOT NULL,
  gts         BIGINT NOT NULL,
  PRIMARY KEY (esper_id, chi_sa_id),
  KEY idx_sanno_chi (chi_sa_id),
  CONSTRAINT fk_sa_e FOREIGN KEY (esper_id) REFERENCES personaggi(id) ON DELETE CASCADE,
  CONSTRAINT fk_sa_c FOREIGN KEY (chi_sa_id) REFERENCES personaggi(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- Il calore dei luoghi --------------------------------------------------------
-- Dove si fanno cose vistose, la gente comincia a guardare. Sale in fretta e
-- scende piano, come l'attenzione vera.
CREATE TABLE IF NOT EXISTS calore (
  luogo   VARCHAR(32) NOT NULL PRIMARY KEY,
  valore  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  gts     BIGINT NOT NULL,
  CONSTRAINT fk_cal_luogo FOREIGN KEY (luogo) REFERENCES luoghi(lkey) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO game_config (ckey, cvalue, ctype, note) VALUES
  ('segreto.anomalie_per_sospetto', '3',   'int', 'Quante anomalie collegate fanno un sospetto fondato'),
  ('segreto.finestra_giorni',       '30',  'int', 'Entro quanti giorni di gioco le anomalie fanno mucchio'),
  ('segreto.sospetti_per_trasloco', '2',   'int', 'Quanti sospetti fondati distinti costringono al trasloco'),
  ('segreto.calore_decadimento_ore','48',  'int', 'In quante ore di gioco il calore di un luogo si dimezza'),
  ('segreto.calore_max',            '100', 'int', 'Tetto del calore di un luogo'),
  ('segreto.controllo_per_uso',     '2',   'int', 'Quanto sale il Controllo dopo un uso andato liscio')
ON DUPLICATE KEY UPDATE cvalue = VALUES(cvalue), ctype = VALUES(ctype);
