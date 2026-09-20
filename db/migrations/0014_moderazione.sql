-- La moderazione: perche' un account e' stato fermato.
--
-- Lo stato c'era gia' (`users.status`: pending, active, suspended, banned) ma
-- non c'era il motivo, e uno stato senza motivo e' inutile: fra tre mesi
-- nessuno si ricorda perche' quel giocatore e' sospeso, e nel dubbio non lo
-- si riattiva. Il registro di chi ha fatto cosa sta gia' in `audit_log`;
-- qui si tiene solo la nota corrente, quella da mostrare accanto al nome.
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS nota_admin VARCHAR(255) NOT NULL DEFAULT ''
    COMMENT 'perche'' e'' in questo stato, per chi legge fra sei mesi',
  ADD COLUMN IF NOT EXISTS nota_admin_at DATETIME NULL;
