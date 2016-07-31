CREATE OR REPLACE VIEW invmetagroups AS 
 SELECT DISTINCT invmetagroups.metagroupid,
    invmetagroups.metagroupname
   FROM invmodules
   JOIN eve.invmetagroups ON invmodules.metagroupid = invmetagroups.metagroupid;
     
