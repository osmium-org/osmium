ALTER TABLE fittingmoduletargets DROP CONSTRAINT IF EXISTS fittingmoduletargets_source_fkey;

ALTER TABLE fittingmoduletargets
  ADD CONSTRAINT fittingmoduletargets_source_fkey FOREIGN KEY (fittinghash, source, sourcefittinghash)
      REFERENCES fittingremotes (fittinghash, key, remotefittinghash) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
      DEFERRABLE;
