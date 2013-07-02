DROP TABLE IF EXISTS eve.dgmexpressions;
DROP TABLE IF EXISTS eve.dgmoperands;

DROP TABLE IF EXISTS eve.dgmcacheexpressions;
DROP INDEX IF EXISTS eve.dgmeffects_preexpression_idx;
ALTER TABLE eve.dgmeffects DROP COLUMN IF EXISTS preexpression;
DROP INDEX IF EXISTS eve.dgmeffects_postexpression_idx;
ALTER TABLE eve.dgmeffects DROP COLUMN IF EXISTS postexpression;
