-- 0002_orologio : le chiavi dell'«eterno 1987».
--
-- Il calendario di gioco e' ciclico dentro il 1987: scorre per tutto l'anno
-- scolastico giapponese (aprile -> marzo) e poi ricomincia da capo. Il
-- triangolo Kyosuke / Madoka / Hikaru resta irrisolto per costruzione: e' lo
-- sfondo immobile, non un traguardo.
--
-- Il rapporto 1:4 e' scelto perche' fa scorrere l'intera giornata di gioco in
-- sei ore reali: chi entra la sera non trova sempre la stessa ora, e nessuno
-- e' costretto a collegarsi di mattina per vedere la scuola.

INSERT INTO game_config (ckey, cvalue, ctype, note) VALUES
  ('clock.compression',   '4',          'int',    'Minuti di gioco per minuto reale, vita quotidiana'),
  ('clock.epoch_real',    '',           'string', 'Istante reale (ISO 8601) a cui corrisponde clock.epoch_game — vuoto: si fissa al primo battito'),
  ('clock.epoch_game',    '1987-04-06 07:00:00', 'string', 'Istante di gioco iniziale: lunedi'' 6 aprile 1987, primo giorno di scuola'),
  ('clock.anno_fisso',    '1987',       'int',    'Anno contenitore. Il calendario ci gira dentro e non ne esce mai'),
  ('clock.inizio_ciclo',  '04-06',      'string', 'Giorno in cui l''anno scolastico ricomincia (MM-GG)'),
  ('world.seed',          '19870406',   'int',    'Seme del mondo: meteo, incontri, tutto cio'' che e'' deterministico'),
  ('world.nome',          'Cento Gradini', 'string', 'Nome pubblico del gioco')
ON DUPLICATE KEY UPDATE cvalue = VALUES(cvalue), ctype = VALUES(ctype);
