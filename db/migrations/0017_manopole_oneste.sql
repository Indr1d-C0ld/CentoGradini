-- Le manopole devono dire la verita': quelle che ci sono si toccano, e quelle
-- che si toccano fanno qualcosa.
--
-- Emerso da un audit: nel pannello comparivano tre leve che nessuna riga di
-- codice legge, e ne mancava una che il codice legge davvero. Una leva che non
-- e' collegata a niente e' peggio di una leva assente — chi la gira e non vede
-- effetto conclude che sia rotto il gioco, non la leva.

-- --- Quella che mancava -----------------------------------------------------
-- `Abitanti::muovi()` la legge con un ripiego a 2, quindi funzionava: ma non
-- comparendo in tabella non era regolabile, e il ritmo con cui i tredici
-- abitanti girano per il quartiere e' esattamente il genere di cosa che si
-- vuole poter cambiare guardando il mondo che gira.
INSERT INTO game_config (ckey, cvalue, ctype, note) VALUES
  ('abitanti.passo_ore', '2', 'int',
   'Ogni quante ore di gioco gli abitanti canonici cambiano tappa del loro giro')
ON DUPLICATE KEY UPDATE note = VALUES(note);

-- --- Quella nuova: gli incidenti lasciati aperti ----------------------------
-- Vedi la nota in Segreto::incidentiScaduti(): un incidente che nessuno
-- chiude non deve restare aperto per sempre.
INSERT INTO game_config (ckey, cvalue, ctype, note) VALUES
  ('segreto.incidente_scade_minuti', '60', 'int',
   'Dopo quanti minuti di gioco un incidente non coperto vale come «non ho fatto niente»')
ON DUPLICATE KEY UPDATE note = VALUES(note);

-- --- Quelle che non erano collegate a niente ---------------------------------
-- `clock.anno_fisso` e `clock.inizio_ciclo` duplicano Orologio::CICLO_INIZIO e
-- CICLO_FINE, che sono costanti PHP. E devono restare costanti: cambiarle a
-- caldo sfaserebbe ogni istante lineare gia' scritto a database — i viaggi in
-- corso, gli arrivi, le presenze, i ricordi. Non sono manopole, sono la forma
-- del mondo, e il posto giusto per leggerle e' il codice.
--
-- `world.nome` duplica `app.name` del file di configurazione, che e' quello
-- che il sito usa davvero.
DELETE FROM game_config WHERE ckey IN ('clock.anno_fisso', 'clock.inizio_ciclo', 'world.nome');
