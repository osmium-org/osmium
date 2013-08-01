DROP TABLE IF EXISTS fittingfleetboosters;

CREATE TABLE fittingfleetboosters
(
  fittinghash character(40) NOT NULL,
  hasfleetbooster boolean NOT NULL,
  fleetboosterfittinghash character(40),
  haswingbooster boolean NOT NULL,
  wingboosterfittinghash character(40),
  hassquadbooster boolean NOT NULL,
  squadboosterfittinghash character(40),
  CONSTRAINT fittingfleetboosters_pkey PRIMARY KEY (fittinghash),
  CONSTRAINT fittingfleetboosters_fleetboosterfittinghash_fkey FOREIGN KEY (fleetboosterfittinghash)
      REFERENCES fittings (fittinghash) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fittingfleetboosters_squadboosterfittinghash_fkey FOREIGN KEY (squadboosterfittinghash)
      REFERENCES fittings (fittinghash) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fittingfleetboosters_wingboosterfittinghash_fkey FOREIGN KEY (wingboosterfittinghash)
      REFERENCES fittings (fittinghash) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);

CREATE INDEX fittingfleetboosters_fleetboosterfittinghash_idx
  ON fittingfleetboosters
  USING btree
  (fleetboosterfittinghash COLLATE pg_catalog."default");

CREATE INDEX fittingfleetboosters_squadboosterfittinghash_idx
  ON fittingfleetboosters
  USING btree
  (squadboosterfittinghash COLLATE pg_catalog."default");

CREATE INDEX fittingfleetboosters_wingboosterfittinghash_idx
  ON fittingfleetboosters
  USING btree
  (wingboosterfittinghash COLLATE pg_catalog."default");
