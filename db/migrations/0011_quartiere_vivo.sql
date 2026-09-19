-- F6 — Il quartiere vivo: voci, abitanti, club, bacheca, biglietti, eventi.
--
-- Fino a F5 il quartiere era popolato solo dai giocatori collegati, e la Folla
-- era un numero senza volto. Qui prende gente dentro: i PNG canonici che si
-- muovono per conto loro, i club che mettono le persone in relazione anche
-- quando non si trovano nello stesso posto, e soprattutto le VOCI, che sono il
-- vero collante del multigiocatore asincrono. L'esito che si vuole e' che il
-- quartiere sia pieno di gente che non hai mai incontrato ma di cui hai
-- sentito parlare.

-- --- I PNG sono personaggi, non una tabella a parte -------------------------
-- Scelta deliberata. Se i PNG fossero una tabella separata, ogni query del
-- gioco andrebbe scritta due volte — presenze, testimoni, legami, cast degli
-- episodi — e le due copie divergerebbero al primo cambiamento. Un PNG e'
-- semplicemente un personaggio senza utente: user_id NULL, png valorizzato.
-- Ne discende che i PNG possono fare da testimoni, ricevere gesti, entrare nel
-- cast di un episodio e portare in giro le voci senza una riga di codice in
-- piu'.
ALTER TABLE personaggi
  MODIFY COLUMN user_id BIGINT UNSIGNED NULL COMMENT 'NULL = personaggio non giocante',
  ADD COLUMN IF NOT EXISTS png VARCHAR(32) NULL COMMENT 'chiave del PNG canonico; NULL = giocatore' AFTER user_id,
  ADD COLUMN IF NOT EXISTS png_giro VARCHAR(255) NULL COMMENT 'giro abituale: luoghi separati da virgola',
  ADD COLUMN IF NOT EXISTS png_nota VARCHAR(255) NULL COMMENT 'come si presenta a chi lo incontra';

ALTER TABLE personaggi ADD UNIQUE KEY IF NOT EXISTS uq_pg_png (png);

-- Il quartiere non e' fatto solo di studenti: il Master dell'ABCB e' un
-- adulto e Kazuya ha otto anni. Nessuno dei due e' una classe giocabile — lo
-- decide Scuola::GIOCABILI, non l'enum — ma tutti e due esistono.
ALTER TABLE personaggi
  MODIFY COLUMN sezione ENUM('elementari','medie','superiori','adulti') NOT NULL DEFAULT 'superiori';

-- --- La forma locativa dei luoghi -------------------------------------------
-- «a Il parco» non si puo' leggere, e nessuna regola automatica indovina che
-- il liceo vuole «al Koryo», la spiaggia vuole «in spiaggia» e casa Ayukawa
-- vuole «da Ayukawa». L'italiano qui e' un dato, non un algoritmo: sono
-- diciotto valori scritti a mano, e valgono piu' di qualunque euristica.
-- Serve alle voci, che nominano i posti in mezzo a una frase.
ALTER TABLE luoghi
  ADD COLUMN IF NOT EXISTS dove VARCHAR(48) NOT NULL DEFAULT '' COMMENT 'moto a luogo: «al parco», «in spiaggia»';

-- --- Le voci ----------------------------------------------------------------
-- Una voce non e' un messaggio: e' un FATTO piu' tante VERSIONI soggettive
-- quante sono le persone che ne hanno sentito parlare. Il fatto e' immutabile
-- e resta vero; quello che si deforma passando di bocca in bocca e' la
-- versione. Per questo il testo NON e' salvato: si ricostruisce ogni volta dal
-- nucleo alla precisione di chi lo racconta. Salvare la frase significherebbe
-- fissare la deformazione una volta per tutte, e invece la stessa voce deve
-- suonare diversa a due persone diverse.
CREATE TABLE IF NOT EXISTS voci (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tipo         VARCHAR(24) NOT NULL COMMENT 'potere, gesto, confessione, litigio, insieme, partenza, episodio, affisso',
  soggetto_id  BIGINT UNSIGNED NULL COMMENT 'di chi si parla',
  soggetto2_id BIGINT UNSIGNED NULL COMMENT 'l''altra persona, se la voce ne coinvolge due',
  luogo        VARCHAR(32) NOT NULL,
  gts          BIGINT NOT NULL COMMENT 'quando e'' successo davvero',
  dettaglio    VARCHAR(64) NOT NULL DEFAULT '' COMMENT 'chiave del particolare: quale potere, quale gesto',
  seme         INT UNSIGNED NOT NULL COMMENT 'ancora del generatore: la stessa voce si deforma sempre allo stesso modo',
  KEY idx_voci_gts (gts),
  KEY idx_voci_soggetto (soggetto_id),
  CONSTRAINT fk_voci_s1 FOREIGN KEY (soggetto_id)  REFERENCES personaggi(id) ON DELETE SET NULL,
  CONSTRAINT fk_voci_s2 FOREIGN KEY (soggetto2_id) REFERENCES personaggi(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La versione di una persona. precisione 100 = c'era e ha visto tutto;
-- precisione bassa = «mi hanno detto che forse uno del secondo anno...».
-- Il tono e' la polarizzazione: a ogni passaggio la storia diventa piu'
-- benevola o piu' malevola, mai piu' neutra, perche' e' cosi' che funziona
-- una scuola.
CREATE TABLE IF NOT EXISTS voci_versioni (
  voce_id        BIGINT UNSIGNED NOT NULL,
  personaggio_id BIGINT UNSIGNED NOT NULL,
  precisione     TINYINT UNSIGNED NOT NULL,
  tono           TINYINT NOT NULL DEFAULT 0 COMMENT '-100 malevola .. +100 benevola',
  passaggi       TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'quante bocche prima di questa',
  gts            BIGINT NOT NULL COMMENT 'quando l''ha saputo',
  da_id          BIGINT UNSIGNED NULL COMMENT 'chi gliel''ha detta; NULL = l''ha vista',
  PRIMARY KEY (voce_id, personaggio_id),
  KEY idx_vv_pg (personaggio_id, gts),
  CONSTRAINT fk_vv_voce FOREIGN KEY (voce_id) REFERENCES voci(id) ON DELETE CASCADE,
  CONSTRAINT fk_vv_pg   FOREIGN KEY (personaggio_id) REFERENCES personaggi(id) ON DELETE CASCADE,
  CONSTRAINT fk_vv_da   FOREIGN KEY (da_id) REFERENCES personaggi(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- I club -----------------------------------------------------------------
-- Il secondo canale di propagazione, e l'unico che non passa dalla geografia:
-- due persone dello stesso club si parlano anche se non si incontrano mai nel
-- quartiere. Serve a far arrivare le voci a chi gioca poco.
CREATE TABLE IF NOT EXISTS club (
  ckey        VARCHAR(32) NOT NULL PRIMARY KEY,
  nome        VARCHAR(64) NOT NULL,
  nome_jp     VARCHAR(64) NOT NULL DEFAULT '',
  luogo       VARCHAR(32) NOT NULL COMMENT 'dove si ritrova',
  giorni      VARCHAR(32) NOT NULL DEFAULT '' COMMENT 'giorni della settimana, 1=lun .. 7=dom',
  abilita     VARCHAR(16) NOT NULL DEFAULT '' COMMENT 'abilita'' secondaria che il club allena',
  posti       SMALLINT UNSIGNED NOT NULL DEFAULT 40,
  confidenza  VARCHAR(16) NOT NULL DEFAULT 'ricostruita',
  descrizione TEXT NOT NULL,
  ordine      SMALLINT UNSIGNED NOT NULL DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS club_membri (
  ckey           VARCHAR(32) NOT NULL,
  personaggio_id BIGINT UNSIGNED NOT NULL,
  ruolo          ENUM('socio','capitano') NOT NULL DEFAULT 'socio',
  dal_gts        BIGINT NOT NULL,
  PRIMARY KEY (ckey, personaggio_id),
  KEY idx_cm_pg (personaggio_id),
  CONSTRAINT fk_cm_club FOREIGN KEY (ckey) REFERENCES club(ckey) ON DELETE CASCADE,
  CONSTRAINT fk_cm_pg   FOREIGN KEY (personaggio_id) REFERENCES personaggi(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- La bacheca -------------------------------------------------------------
-- Pubblica, legata a un luogo, e a scadenza. Quello che si scrive in bacheca
-- diventa una voce ad alta precisione: la carta non dimentica come le persone.
CREATE TABLE IF NOT EXISTS bacheca (
  id        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  luogo     VARCHAR(32) NOT NULL,
  autore_id BIGINT UNSIGNED NULL COMMENT 'NULL = affisso dalla scuola o dal quartiere',
  firma     VARCHAR(48) NOT NULL DEFAULT '' COMMENT 'come si e'' firmato: puo'' non essere il suo nome',
  tipo      ENUM('avviso','cerca','offre','club','anonimo') NOT NULL DEFAULT 'avviso',
  titolo    VARCHAR(80) NOT NULL,
  testo     VARCHAR(600) NOT NULL,
  gts       BIGINT NOT NULL,
  scade_gts BIGINT NOT NULL,
  KEY idx_bacheca_luogo (luogo, scade_gts),
  CONSTRAINT fk_bacheca_pg FOREIGN KEY (autore_id) REFERENCES personaggi(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- I biglietti ------------------------------------------------------------
-- L'opposto della bacheca, e il mezzo di comunicazione piu' presente
-- nell'opera: il biglietto lasciato nell'armadietto delle scarpe, passato
-- sotto il banco, infilato nella borsa. Non e' posta istantanea: si lascia in
-- un luogo e arriva quando il destinatario ci passa.
CREATE TABLE IF NOT EXISTS biglietti (
  id        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  da_id     BIGINT UNSIGNED NULL,
  a_id      BIGINT UNSIGNED NOT NULL,
  luogo     VARCHAR(32) NOT NULL COMMENT 'dove e'' stato lasciato',
  anonimo   TINYINT(1) NOT NULL DEFAULT 0,
  testo     VARCHAR(600) NOT NULL,
  gts       BIGINT NOT NULL,
  letto_gts BIGINT NULL,
  KEY idx_big_a (a_id, letto_gts),
  KEY idx_big_luogo (luogo),
  CONSTRAINT fk_big_da FOREIGN KEY (da_id) REFERENCES personaggi(id) ON DELETE SET NULL,
  CONSTRAINT fk_big_a  FOREIGN KEY (a_id)  REFERENCES personaggi(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- Gli eventi stagionali --------------------------------------------------
-- Di server, non di giocatore: capitano a tutti insieme perche' il calendario
-- e' lo stesso per tutti. Sono i momenti in cui il quartiere si raduna in un
-- posto solo, ed e' esattamente quando le voci corrono di piu'.
CREATE TABLE IF NOT EXISTS eventi (
  ekey        VARCHAR(32) NOT NULL PRIMARY KEY,
  nome        VARCHAR(64) NOT NULL,
  mese        TINYINT UNSIGNED NOT NULL,
  giorno      TINYINT UNSIGNED NOT NULL,
  durata      TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'giorni',
  luogo       VARCHAR(32) NOT NULL DEFAULT '' COMMENT 'vuoto = tutto il quartiere',
  richiamo    TINYINT UNSIGNED NOT NULL DEFAULT 50 COMMENT 'quanta gente tira, 0-100',
  chiacchiera TINYINT UNSIGNED NOT NULL DEFAULT 50 COMMENT 'quanto accelera le voci, 0-100',
  descrizione TEXT NOT NULL,
  ordine      SMALLINT UNSIGNED NOT NULL DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO game_config (ckey, cvalue, ctype, note) VALUES
  ('voci.durata_giorni',     '21',  'int', 'Dopo quanti giorni di gioco una voce si spegne'),
  ('voci.perdita_passaggio', '28',  'int', 'Percentuale di precisione persa a ogni passaggio di bocca'),
  ('voci.soglia_racconto',   '12',  'int', 'Sotto questa precisione non vale piu'' la pena raccontarla'),
  ('voci.prob_passaggio',    '35',  'int', 'Probabilita'' base che due presenti si raccontino una voce'),
  ('voci.prob_club',         '18',  'int', 'Probabilita'' che una voce passi per il club invece che di persona'),
  ('voci.per_battito',       '40',  'int', 'Quante coppie al massimo il battito fa chiacchierare'),
  ('bacheca.durata_giorni',  '10',  'int', 'Quanto resta appeso un avviso'),
  ('bacheca.max_per_pg',     '3',   'int', 'Avvisi contemporanei per personaggio'),
  ('biglietti.max_al_giorno','8',   'int', 'Biglietti scrivibili in un giorno di gioco'),
  ('club.max_per_pg',        '2',   'int', 'A quanti club ci si puo'' iscrivere')
ON DUPLICATE KEY UPDATE cvalue = VALUES(cvalue);
