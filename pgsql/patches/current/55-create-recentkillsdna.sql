DROP TABLE IF EXISTS recentkillsdna;

CREATE TABLE recentkillsdna (
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
    alliancename character varying(255)
);

ALTER TABLE ONLY recentkillsdna
    ADD CONSTRAINT recentkillsdna_pkey PRIMARY KEY (killid);

CREATE INDEX recentkillsdna_groupdna_idx ON recentkillsdna USING btree (groupdna);

CREATE INDEX recentkillsdna_killtime_idx ON recentkillsdna USING btree (killtime);
