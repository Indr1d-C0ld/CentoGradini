-- 0004_epoca_senza_ambiguita : l'epoca del mondo diventa un istante Unix.
--
-- Prima era una data scritta in chiaro ("2026-09-19 05:27:17") senza fuso, e
-- Orologio::epocaReale() la leggeva con strtotime(), che usa il fuso di
-- DEFAULT del processo. Tre attori potevano non essere d'accordo: MariaDB
-- (che l'ha scritta col proprio fuso), il front controller e la console (che
-- impostano app.timezone), e qualunque altro processo PHP che non lo imposta.
-- Due ore di scarto fra Roma e UTC diventano OTTO ore di gioco: lo stesso
-- mondo mostrava le 16:03 a un processo e le 0:03 a un altro.
--
-- Un istante Unix non ha fuso: e' lo stesso numero ovunque. La conversione
-- usa UNIX_TIMESTAMP(), cioe' lo stesso fuso con cui il valore era stato
-- scritto, quindi il mondo NON si sposta: cambia solo come lo si scrive.

UPDATE game_config
   SET cvalue = CAST(UNIX_TIMESTAMP(cvalue) AS CHAR),
       ctype  = 'int',
       note   = 'Istante Unix a cui corrisponde clock.epoch_game. Senza fuso: non ambiguo.'
 WHERE ckey = 'clock.epoch_real'
   AND cvalue IS NOT NULL
   AND cvalue <> ''
   AND cvalue NOT REGEXP '^[0-9]+$';

UPDATE game_config
   SET cvalue = CAST(UNIX_TIMESTAMP() AS CHAR), ctype = 'int'
 WHERE ckey = 'clock.epoch_real' AND (cvalue IS NULL OR cvalue = '');
