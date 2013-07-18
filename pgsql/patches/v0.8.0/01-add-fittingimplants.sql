CREATE TABLE IF NOT EXISTS fittingimplants
(
  fittinghash character(40) NOT NULL,
  presetid integer NOT NULL,
  typeid integer NOT NULL,
  CONSTRAINT fittingimplants_pkey PRIMARY KEY (fittinghash, presetid, typeid),
  CONSTRAINT fittingimplants_fittinghash_presetid_fkey FOREIGN KEY (fittinghash, presetid)
      REFERENCES fittingpresets (fittinghash, presetid) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fittingimplants_typeid_fkey FOREIGN KEY (typeid)
      REFERENCES eve.invtypes (typeid) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
