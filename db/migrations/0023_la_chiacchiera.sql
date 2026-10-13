-- La chiacchiera non e' il fascino.
--
-- Le voci usavano DAI-SUKI (aspetto, carisma) come loquacita'. Con i numeri
-- degli abitanti rimessi al canone il 13 ottobre 2026, Madoka — DAI-SUKI 15,
-- e nel canone «solitaire», una che i segreti li tiene — sarebbe diventata la
-- piu' pettegola del quartiere, e Komatsu, da cui le voci partono davvero,
-- quasi muto. Gli abitanti hanno la loro loquacita' scritta nel seme, con la
-- fonte; per i giocatori resta NULL e vale DAI-SUKI, come prima.
ALTER TABLE personaggi
  ADD COLUMN IF NOT EXISTS chiacchiera TINYINT UNSIGNED NULL
    COMMENT 'quanto racconta in giro (1-15); NULL = come DAI-SUKI';
