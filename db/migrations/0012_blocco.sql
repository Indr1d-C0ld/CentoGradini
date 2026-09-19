-- Il potere di bloccare i poteri altrui.
--
-- Nel canone e' di Kazuya e di nessun altro: la voce giapponese dice che sa
-- «impedire agli altri di usare i propri poteri», ed e' una delle ragioni per
-- cui e' piu' forte di Kyosuke pur avendo otto anni. Lo avevamo lasciato
-- fuori dalla tabella dei poteri, ed era un buco: sta scritto fra le
-- questioni aperte di CANONE.md.
--
-- **Non si tira a sorte.** Nel seme dei poteri ha banda 0-0, che e' fuori
-- dall'intervallo del dado (1-100): nessun personaggio puo' nascere con
-- questo potere, ne' come primario ne' come secondario. E' di Kazuya, e
-- resta di Kazuya. Un potere unico nelle mani di un PNG non e' un
-- privilegio sprecato: e' il motivo per cui incontrarlo conta qualcosa.

INSERT INTO game_config (ckey, cvalue, ctype, note) VALUES
  ('blocco.prob_base', '55', 'int',
   'Probabilita'' base che chi sa bloccare spenga il potere di un altro presente'),
  ('blocco.resistenza_controllo', '35', 'int',
   'Quanto il Controllo di chi usa il potere riduce quella probabilita'', in percentuale'),
  ('blocco.costo_pp', '1', 'int',
   'Punti potere che si perdono lo stesso quando il potere viene spento')
ON DUPLICATE KEY UPDATE cvalue = VALUES(cvalue);
