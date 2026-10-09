-- Il quartiere risponde: i poteri fanno qualcosa, chi ha capito sceglie cosa
-- farne, le voci pesano, gli abitanti agiscono. Controllo del 9 ottobre 2026,
-- seconda parte.

-- 1. CHI HA CAPITO SCEGLIE. PROGETTO §7 disegna tre strade per chi arriva al
--    sospetto fondato — ti affronta, lo dice in giro, ti osserva in silenzio —
--    e il codice si fermava prima: chi aveva capito non aveva verbi.
--    `atteggiamento` e' la strada scelta; `affrontato_gts` dice che l'esper e'
--    stato preso da parte e non ha ancora risposto.
--
--    E «tradito»: il segreto confidato a qualcuno che poi si e' guastato con
--    te. Il progetto lo diceva («se quel legame si guasta, il rischio torna,
--    moltiplicato») e non era fatto: conta doppio per il trasloco.
ALTER TABLE sanno
  MODIFY COLUMN come ENUM('confidato','scoperto','tradito') NOT NULL,
  ADD COLUMN IF NOT EXISTS atteggiamento ENUM('','affronta','dice','osserva') NOT NULL DEFAULT ''
    COMMENT 'cosa ha deciso di fare chi ha capito da solo',
  ADD COLUMN IF NOT EXISTS affrontato_gts BIGINT NULL
    COMMENT 'quando ha preso da parte l''esper, finche'' l''esper non risponde';

-- 2. GLI STATI. Raffreddore, bende, invisibilita': cose che durano un po' e poi
--    passano. Il regolamento del 1990 le aveva quasi tutte (PRENDERE UN
--    RAFFREDDORE, ESSERE MALATI, TIRARE PER LE BENDE).
CREATE TABLE IF NOT EXISTS stati (
  personaggio_id BIGINT UNSIGNED NOT NULL,
  stato          VARCHAR(24) NOT NULL,
  dal_gts        BIGINT NOT NULL,
  fino_gts       BIGINT NOT NULL,
  PRIMARY KEY (personaggio_id, stato),
  KEY idx_stati_fino (fino_gts),
  CONSTRAINT fk_stati_pg FOREIGN KEY (personaggio_id) REFERENCES personaggi(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO game_config (ckey, cvalue, ctype, note) VALUES
  ('segreto.pubblico_dominio', '8', 'int',
   'Quante persone devono sapere, per voce abbastanza chiara, dei tuoi poteri perche'' sia di pubblico dominio e si traslochi (FAQ 26)'),
  ('segreto.calore_nota_pct', '50', 'int',
   'Di quanto per cento il calore pieno di un luogo (100) alza la probabilita'' di essere notati'),
  ('legami.tradimento_affetto', '-20', 'int',
   'Sotto questo affetto, chi conosceva il tuo segreto per confidenza diventa un pericolo'),
  ('legami.tradimento_fraint', '70', 'int',
   'Sopra questo fraintendimento, chi conosceva il tuo segreto per confidenza diventa un pericolo'),
  ('stati.raffreddore_prob', '15', 'int',
   'Probabilita'' (%) di prendersi il raffreddore arrivando all''aperto sotto la pioggia, senza un ombrello diviso di recente'),
  ('stati.raffreddore_ore', '24', 'int',
   'Quante ore di gioco dura un raffreddore'),
  ('stati.potere_sballa_pct', '40', 'int',
   'Probabilita'' (%) che un esper malato usi un potere diverso da quello che voleva'),
  ('stati.bende_ore', '48', 'int',
   'Quante ore di gioco si portano cerotti e bende'),
  ('poteri.invisibile_minuti', '20', 'int',
   'Quanti minuti di gioco dura l''invisibilita'''),
  ('abitanti.gesto_pct', '4', 'int',
   'Probabilita'' (%), a ogni battito, che un abitante in compagnia faccia un gesto verso qualcuno'),
  ('abitanti.gesto_pausa_ore', '2', 'int',
   'Ore di gioco fra un gesto e l''altro dello stesso abitante')
ON DUPLICATE KEY UPDATE note = VALUES(note);
