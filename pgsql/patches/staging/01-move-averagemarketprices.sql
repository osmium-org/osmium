CREATE TABLE osmium.averagemarketprices
(
  typeid integer NOT NULL,
  averageprice numeric(15,2) NOT NULL,
  CONSTRAINT averagemarketprices_pkey PRIMARY KEY (typeid),
  CONSTRAINT averagemarketprices_typeid_fkey FOREIGN KEY (typeid)
      REFERENCES eve.invtypes (typeid) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);

INSERT INTO osmium.averagemarketprices (typeid, averageprice) (
  SELECT typeid, averageprice FROM eve.averagemarketprices
);

DROP TABLE eve.averagemarketprices;
