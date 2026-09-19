-- 0005_personaggio : la scheda del personaggio.
--
-- Due cose cambiano rispetto a F1.
--
-- 1. **La scuola.** `anno_scuola` diventa `sezione` + `anno`: il Kōryō Gakuen
--    ha le medie (中等部) e le superiori (高等部), e all'inizio della storia i
--    protagonisti sono al terzo anno delle MEDIE. Il modello precedente
--    conosceva solo un «liceo» di tre anni e copriva metà della scuola che
--    l'opera racconta.
--
-- 2. **L'età non si salva più.** Si salva il compleanno, e l'età si calcola:
--    in Giappone la classe dipende rigidamente dalla data di nascita, e un
--    compleanno che cade in una data vera del calendario è un dato di gioco,
--    non un numero. Vedi src/Sim/Scuola.php.
--
-- I personaggi creati in F1 restano validi: hanno `scheda = 'abbozzo'` e
-- vengono completati da Scheda::completa() alla prima occasione, senza che
-- nessun altro pezzo di codice debba sapere che sono nati a metà.

-- --- La scheda ------------------------------------------------------------
ALTER TABLE personaggi
  ADD COLUMN IF NOT EXISTS sezione ENUM('medie','superiori') NOT NULL DEFAULT 'superiori' AFTER eta,
  ADD COLUMN IF NOT EXISTS anno TINYINT UNSIGNED NOT NULL DEFAULT 2 AFTER sezione,
  ADD COLUMN IF NOT EXISTS nato_mese TINYINT UNSIGNED NOT NULL DEFAULT 4 AFTER anno,
  ADD COLUMN IF NOT EXISTS nato_giorno TINYINT UNSIGNED NOT NULL DEFAULT 2 AFTER nato_mese,
  ADD COLUMN IF NOT EXISTS anno_nascita SMALLINT UNSIGNED NOT NULL DEFAULT 1971 AFTER nato_giorno,
  ADD COLUMN IF NOT EXISTS esper TINYINT(1) NOT NULL DEFAULT 0 AFTER anno_nascita,
  ADD COLUMN IF NOT EXISTS rissa TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS testa TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS dai_suki TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS cuore TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS inglese TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS sport TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS guida TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS kakko TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS nuoto TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS musica TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS cucina TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS candore TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS pf_max TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS pf TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS pp_max TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS pp TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS compostezza_max TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS compostezza TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS aspetto VARCHAR(255) NULL;

-- --- I tratti, come tabella di ambientazione -------------------------------
CREATE TABLE IF NOT EXISTS tratti (
  tkey         VARCHAR(32) NOT NULL PRIMARY KEY,
  nome         VARCHAR(48) NOT NULL,
  sesso        ENUM('MF','M','F') NOT NULL DEFAULT 'MF',
  da           TINYINT UNSIGNED NOT NULL,
  a            TINYINT UNSIGNED NOT NULL,
  modificatori JSON NULL,
  descrizione  TEXT NOT NULL,
  obiettivo    VARCHAR(255) NOT NULL,
  KEY idx_tratti_range (da, a)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS personaggio_tratti (
  personaggio_id BIGINT UNSIGNED NOT NULL,
  tkey           VARCHAR(32) NOT NULL,
  PRIMARY KEY (personaggio_id, tkey),
  CONSTRAINT fk_pt_pg FOREIGN KEY (personaggio_id) REFERENCES personaggi(id) ON DELETE CASCADE,
  CONSTRAINT fk_pt_t  FOREIGN KEY (tkey) REFERENCES tratti(tkey) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- I poteri ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS poteri (
  pkey             VARCHAR(32) NOT NULL PRIMARY KEY,
  nome             VARCHAR(48) NOT NULL,
  nome_jp          VARCHAR(48) NOT NULL,
  costo_primario   TINYINT UNSIGNED NULL COMMENT 'NULL = non puo'' essere primario',
  costo_secondario TINYINT UNSIGNED NOT NULL,
  da               TINYINT UNSIGNED NOT NULL,
  a                TINYINT UNSIGNED NOT NULL,
  descrizione      TEXT NOT NULL,
  limiti           TEXT NOT NULL,
  KEY idx_poteri_range (da, a)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Il Controllo e' l'unica cosa che cresce in questo gioco: il NUMERO dei
-- poteri non cambia mai (lo dice il regolamento del 1990 e ha ragione — se i
-- poteri crescessero, il Segreto smetterebbe di essere un problema), ma la
-- precisione con cui li si usa si', perche' nel manga Kyosuke migliora nella
-- mira.
CREATE TABLE IF NOT EXISTS personaggio_poteri (
  personaggio_id BIGINT UNSIGNED NOT NULL,
  pkey           VARCHAR(32) NOT NULL,
  primario       TINYINT(1) NOT NULL DEFAULT 0,
  controllo      TINYINT UNSIGNED NOT NULL DEFAULT 10,
  usi            INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (personaggio_id, pkey),
  CONSTRAINT fk_pp_pg FOREIGN KEY (personaggio_id) REFERENCES personaggi(id) ON DELETE CASCADE,
  CONSTRAINT fk_pp_p  FOREIGN KEY (pkey) REFERENCES poteri(pkey) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO game_config (ckey, cvalue, ctype, note) VALUES
  ('pg.punti_abilita',        '10', 'int', 'Punti da distribuire sulle quattro abilita'''),
  ('pg.punti_abilita_umano',  '14', 'int', 'Idem, per chi non ha poteri: la compensazione'),
  ('pg.tratti',                '3', 'int', 'Quanti tratti si tirano alla creazione'),
  ('pg.tratti_umano',          '4', 'int', 'Idem, per chi non ha poteri'),
  ('pg.poteri_secondari',      '3', 'int', 'Quanti poteri secondari tira un esper'),
  ('pg.abilita_max',          '15', 'int', 'Tetto di un''abilita'': 15 e'' il grado divino')
ON DUPLICATE KEY UPDATE cvalue = VALUES(cvalue), ctype = VALUES(ctype);
