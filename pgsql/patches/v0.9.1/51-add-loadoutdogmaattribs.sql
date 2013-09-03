DROP TABLE IF EXISTS loadoutdogmaattribs;

CREATE TABLE loadoutdogmaattribs
(
  loadoutid integer NOT NULL,
  dps double precision NOT NULL,
  ehp double precision NOT NULL,
  estimatedprice double precision,
  CONSTRAINT loadoutdogmaattribs_pkey PRIMARY KEY (loadoutid),
  CONSTRAINT loadoutdogmaattribs_loadoutid_fkey FOREIGN KEY (loadoutid)
      REFERENCES loadouts (loadoutid) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
