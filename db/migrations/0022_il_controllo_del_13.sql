-- Le correzioni dell'audit del 13 ottobre 2026.
--
-- 1. Gli abitanti che un copione nomina nel testo non devono finire nel suo
--    cast: il Master che lucida bicchieri dietro al bancone non puo' essere
--    anche uno dei clienti a cui chiede aiuto, e nel «mondo senza Hikaru»
--    Hikaru non puo' essere in scena. Elenco separato da virgole di `png`.
ALTER TABLE copioni
  ADD COLUMN IF NOT EXISTS esclusi VARCHAR(255) NULL
    COMMENT 'abitanti (png, separati da virgola) che il testo nomina e che non devono essere nel cast';

-- 2. Il pubblico dominio: la simulazione dell'audit ha fatto traslocare due
--    esper su tre in quattro giorni di gioco, senza che nessuno li avesse
--    scoperti. Adesso contano solo le voci col nome intero (precisione 80), e
--    la soglia passa da 8 a 10 persone.
UPDATE game_config SET cvalue = '10',
  note = 'Quante persone devono aver sentito una voce chiara, col tuo nome, sui tuoi poteri perche'' sia di pubblico dominio e si traslochi (FAQ 26)'
 WHERE ckey = 'segreto.pubblico_dominio' AND cvalue = '8';
