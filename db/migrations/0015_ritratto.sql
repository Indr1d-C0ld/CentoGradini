-- La fotografia del personaggio.
--
-- Sta sul PERSONAGGIO e non sull'account, come in Atlantik sta sul comandante
-- e non sull'utente: qui la fotografia e' il volto di una persona che abita il
-- quartiere, non l'icona di chi gioca. La differenza si vede quando qualcuno
-- trasloca e ricomincia (`personaggi.stato = 'trasferito'`): il ragazzo nuovo
-- e' un'altra persona, e si merita un'altra faccia.
--
-- Tre colonne e non una:
--   * `ritratto_file` e' il nome del file servito, che e' l'impronta del
--     contenuto piu' `.webp` — quindi non e' indovinabile e due fotografie
--     identiche non occupano il disco due volte;
--   * `ritratto_hash` ripete l'impronta da sola, per poterla confrontare
--     senza dover smontare il nome del file;
--   * `ritratto_at` dice da quando, e serve a moderare: una fotografia
--     comparsa un'ora prima di una segnalazione si trova subito.
--
-- Il file caricato non arriva mai al disco cosi' com'e': si vedano le note in
-- `src/Game/Ritratto.php`.
ALTER TABLE personaggi
  ADD COLUMN IF NOT EXISTS ritratto_file VARCHAR(80) NULL
    COMMENT 'impronta del contenuto + .webp; il file sta in assets/img/ritratti',
  ADD COLUMN IF NOT EXISTS ritratto_hash CHAR(64) NULL,
  ADD COLUMN IF NOT EXISTS ritratto_at   DATETIME NULL;

-- Si cerca per nome del file quando si deve decidere se cancellarlo dal disco:
-- e' l'unica interrogazione che non parte dall'id del personaggio.
ALTER TABLE personaggi ADD KEY IF NOT EXISTS idx_pg_ritratto (ritratto_file);
