DROP TABLE IF EXISTS fittingbeacons;

CREATE TABLE fittingbeacons
(
  fittinghash character(40) NOT NULL,
  presetid integer NOT NULL,
  typeid integer NOT NULL,
  CONSTRAINT fittingbeacons_pkey PRIMARY KEY (fittinghash, presetid, typeid),
  CONSTRAINT fittingbeacons_fittinghash_presetid_fkey FOREIGN KEY (fittinghash, presetid)
      REFERENCES fittingpresets (fittinghash, presetid) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fittingbeacons_typeid_fkey FOREIGN KEY (typeid)
      REFERENCES eve.invtypes (typeid) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
