-- 0009_legami : le relazioni, su due assi.
--
-- In Kimagure Orange Road le relazioni non si rompono per antipatia: si
-- ingarbugliano per equivoci mai chiariti. Un solo numero — «quanto ti voglio
-- bene» — non sa raccontare niente di questa storia. Ne servono due, e il
-- secondo e' quello interessante:
--
--   AFFETTO         -100..+100   quanto A tiene a B
--   FRAINTENDIMENTO    0..100    quanto A crede di B qualcosa che non e' vero
--
-- Tre regole che discendono da qui e che il codice deve rispettare:
--
--   1. Il fraintendimento NON nasce dalle bugie, nasce dalle informazioni
--      parziali. Vedere due persone uscire insieme dal bar e' vero; il
--      significato che ci si mette e' di chi guarda.
--   2. Il fraintendimento NON decade col tempo. Decade solo con una
--      conversazione di chiarimento, che costa Cuore e che puo' fallire.
--   3. Affetto alto CON fraintendimento alto e' lo stato piu' instabile del
--      gioco, ed e' esattamente dove vive tutta la storia.
--
-- I legami sono orientati: quello che A prova per B non e' quello che B prova
-- per A, e nel triangolo e' tutto li'.

CREATE TABLE IF NOT EXISTS legami (
  da_id           BIGINT UNSIGNED NOT NULL,
  a_id            BIGINT UNSIGNED NOT NULL,
  affetto         SMALLINT NOT NULL DEFAULT 0,
  fraintendimento SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  conosciuti_gts  BIGINT NOT NULL,
  ultimo_gts      BIGINT NOT NULL,
  PRIMARY KEY (da_id, a_id),
  KEY idx_leg_a (a_id),
  CONSTRAINT fk_leg_da FOREIGN KEY (da_id) REFERENCES personaggi(id) ON DELETE CASCADE,
  CONSTRAINT fk_leg_a  FOREIGN KEY (a_id)  REFERENCES personaggi(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- I gesti, come tabella di ambientazione ---------------------------------
CREATE TABLE IF NOT EXISTS gesti (
  gkey          VARCHAR(24) NOT NULL PRIMARY KEY,
  nome          VARCHAR(48) NOT NULL,
  descrizione   TEXT NOT NULL,
  affetto       SMALLINT NOT NULL DEFAULT 0,
  costo_cuore   TINYINT UNSIGNED NOT NULL DEFAULT 0,
  richiede      VARCHAR(24) NULL COMMENT 'pioggia, pranzo, sera, neve…',
  ambiguo       TINYINT(1) NOT NULL DEFAULT 0,
  lettura_vera  VARCHAR(255) NOT NULL,
  lettura_falsa VARCHAR(255) NULL,
  ordine        SMALLINT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gesti_fatti (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  gkey        VARCHAR(24) NOT NULL,
  attore_id   BIGINT UNSIGNED NOT NULL,
  verso_id    BIGINT UNSIGNED NOT NULL,
  luogo       VARCHAR(32) NOT NULL,
  gts         BIGINT NOT NULL,
  riuscito    TINYINT(1) NOT NULL DEFAULT 1,
  visto_da    TINYINT UNSIGNED NOT NULL DEFAULT 0,
  KEY idx_gf_attore (attore_id, gts),
  KEY idx_gf_verso (verso_id, gts),
  CONSTRAINT fk_gf_a FOREIGN KEY (attore_id) REFERENCES personaggi(id) ON DELETE CASCADE,
  CONSTRAINT fk_gf_v FOREIGN KEY (verso_id)  REFERENCES personaggi(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Come un terzo ha letto un gesto che non lo riguardava. E' la tabella da cui
-- nasce la gelosia, e quindi mezzo gioco.
CREATE TABLE IF NOT EXISTS gesti_letture (
  gesto_id       BIGINT UNSIGNED NOT NULL,
  osservatore_id BIGINT UNSIGNED NOT NULL,
  giusta         TINYINT(1) NOT NULL,
  testo          VARCHAR(255) NOT NULL,
  PRIMARY KEY (gesto_id, osservatore_id),
  CONSTRAINT fk_gl_g FOREIGN KEY (gesto_id) REFERENCES gesti_fatti(id) ON DELETE CASCADE,
  CONSTRAINT fk_gl_o FOREIGN KEY (osservatore_id) REFERENCES personaggi(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- Gli oggetti che contano -------------------------------------------------
-- Uno solo per server, e non e' un potenziamento: e' un modo di dire una cosa
-- che non si riesce a dire a parole. Nel manga e' il cappello di paglia rosso,
-- e sapevano della sua esistenza quattro persone in tutto.
CREATE TABLE IF NOT EXISTS oggetti_unici (
  okey        VARCHAR(24) NOT NULL PRIMARY KEY,
  nome        VARCHAR(64) NOT NULL,
  descrizione TEXT NOT NULL,
  detentore_id BIGINT UNSIGNED NULL,
  luogo       VARCHAR(32) NULL COMMENT 'dove si trova, se non lo ha nessuno',
  gts         BIGINT NOT NULL DEFAULT 0,
  CONSTRAINT fk_ou_det FOREIGN KEY (detentore_id) REFERENCES personaggi(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS oggetti_passaggi (
  id     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  okey   VARCHAR(24) NOT NULL,
  da_id  BIGINT UNSIGNED NULL,
  a_id   BIGINT UNSIGNED NOT NULL,
  luogo  VARCHAR(32) NOT NULL,
  gts    BIGINT NOT NULL,
  nota   VARCHAR(255) NULL,
  KEY idx_op_okey (okey, gts)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO game_config (ckey, cvalue, ctype, note) VALUES
  ('legami.affetto_max',        '100', 'int', 'Tetto dell''affetto, in valore assoluto'),
  ('legami.soglia_confessione',  '45', 'int', 'Affetto minimo per potersi dichiarare'),
  ('legami.soglia_gelosia',      '35', 'int', 'Da quale affetto un gesto altrui comincia a bruciare'),
  ('legami.chiarimento_calo',    '35', 'int', 'Quanto scende il fraintendimento con un chiarimento riuscito'),
  ('legami.chiarimento_danno',   '10', 'int', 'Quanto SALE se il chiarimento va male')
ON DUPLICATE KEY UPDATE cvalue = VALUES(cvalue), ctype = VALUES(ctype);
