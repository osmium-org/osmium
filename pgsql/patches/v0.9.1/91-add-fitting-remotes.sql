DROP TABLE IF EXISTS fittingmoduletargets;
DROP TABLE IF EXISTS fittingremotes;

CREATE TABLE fittingremotes
(
  fittinghash character(40) NOT NULL,
  key text NOT NULL,
  remotefittinghash character(40) NOT NULL,
  CONSTRAINT fittingremotes_pkey PRIMARY KEY (fittinghash, key),
  CONSTRAINT fittingremotes_fittinghash_fkey FOREIGN KEY (fittinghash)
      REFERENCES fittings (fittinghash) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fittingremotes_remotefittinghash_fkey FOREIGN KEY (remotefittinghash)
      REFERENCES fittings (fittinghash) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fittingremotes_fittinghash_key_remotefittinghash_uniq UNIQUE (fittinghash, key, remotefittinghash),
  CONSTRAINT fittingremotes_local_check CHECK (key <> 'local'::text OR fittinghash = remotefittinghash)
)
WITH (
  OIDS=FALSE
);


ALTER TABLE fittings
   ALTER COLUMN hullid DROP NOT NULL;

CREATE TABLE fittingmoduletargets
(
  fittinghash character(40) NOT NULL,
  source text NOT NULL,
  sourcefittinghash character(40) NOT NULL,
  presetid integer NOT NULL,
  slottype character varying(127) NOT NULL,
  index integer NOT NULL,
  target text NOT NULL,
  CONSTRAINT fittingmoduletargets_pkey PRIMARY KEY (fittinghash, source, sourcefittinghash, presetid, slottype, index),
  CONSTRAINT fittingmoduletargets_fittinghash_fkey FOREIGN KEY (fittinghash)
      REFERENCES fittings (fittinghash) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fittingmoduletargets_module_fkey FOREIGN KEY (sourcefittinghash, presetid, slottype, index)
      REFERENCES fittingmodules (fittinghash, presetid, slottype, index) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fittingmoduletargets_source_fkey FOREIGN KEY (fittinghash, source, sourcefittinghash)
      REFERENCES fittingremotes (fittinghash, key, remotefittinghash) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fittingmoduletargets_target_fkey FOREIGN KEY (fittinghash, target)
      REFERENCES fittingremotes (fittinghash, key) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
