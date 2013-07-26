DROP VIEW IF EXISTS recentkillsdnagroup;
DROP TABLE IF EXISTS recentkillsdnagroup__mv;
DROP TABLE IF EXISTS recentkillsdna;

CREATE TABLE recentkillsdna
(
  killid integer NOT NULL,
  killtime integer NOT NULL,
  dna text NOT NULL,
  groupdna text NOT NULL,
  solarsystemid integer NOT NULL,
  solarsystemname character varying(255) NOT NULL,
  regionid integer NOT NULL,
  regionname character varying(255) NOT NULL,
  characterid integer NOT NULL,
  charactername character varying(255) NOT NULL,
  corporationid integer NOT NULL,
  corporationname character varying(255) NOT NULL,
  allianceid integer,
  alliancename character varying(255),
  CONSTRAINT recentkillsdna_pkey PRIMARY KEY (killid)
)
WITH (
  OIDS=FALSE
);

CREATE INDEX recentkillsdna_groupdna_idx
  ON recentkillsdna
  USING btree
  (groupdna COLLATE pg_catalog."default");

CREATE INDEX recentkillsdna_killtime_idx
  ON recentkillsdna
  USING btree
  (killtime);




CREATE TABLE recentkillsdnagroup__mv
(
  groupdna text NOT NULL,
  count integer NOT NULL,
  timespan integer NOT NULL,
  CONSTRAINT recentkillsdnagroup__mv_pkey PRIMARY KEY (groupdna)
)
WITH (
  OIDS=FALSE
);

CREATE INDEX recentkillsdnagroup__mv_count_idx
  ON recentkillsdnagroup__mv
  USING btree
  (count);

CREATE INDEX recentkillsdnagroup__mv_timespan_idx
  ON recentkillsdnagroup__mv
  USING btree
  (timespan);




CREATE OR REPLACE VIEW recentkillsdnagroup AS 
 SELECT count(DISTINCT recentkillsdna.characterid) AS count, 
    max(recentkillsdna.killtime) - min(recentkillsdna.killtime) AS timespan, 
    recentkillsdna.groupdna
   FROM recentkillsdna
  GROUP BY recentkillsdna.groupdna;
