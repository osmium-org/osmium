DROP INDEX IF EXISTS fittings_evebuildnumber_idx;
ALTER TABLE fittings DROP COLUMN IF EXISTS evebuildnumber;

ALTER TABLE fittings ADD COLUMN evebuildnumber integer;

CREATE TEMPORARY TABLE temp_eveversions (
    tagname character varying(50),
    name character varying(50),
    build integer,
    reldate integer
);

-- May not be ideal for people updating year-old osmium setups, but
-- heh. Too bad for them.
COPY temp_eveversions (tagname, name, build, reldate) FROM stdin;
odyssey-10	Odyssey 1.0	548234	1370304000
retribution-12	Retribution 1.2	538542	1367798400
retribution-11	Retribution 1.1	529690	1361232000
retribution-10	Retribution 1.0	476047	1354579200
inferno-13	Inferno 1.3	433763	1350345600
inferno-12	Inferno 1.2	404131	1344384000
inferno-11	Inferno 1.1	390556	1340582400
inferno-10	Inferno 1.0	377452	1337644800
\.

UPDATE osmium.fittings SET evebuildnumber = ( SELECT build FROM temp_eveversions WHERE creationdate >= reldate ORDER BY reldate DESC LIMIT 1 );


-- Just to be safe, but this shound not happen
UPDATE osmium.fittings SET evebuildnumber = ( SELECT MAX(build) FROM temp_eveversions ) WHERE evebuildnumber IS NULL;

ALTER TABLE fittings
   ALTER COLUMN evebuildnumber SET NOT NULL;

CREATE INDEX fittings_evebuildnumber_idx
  ON fittings
  USING btree
  (evebuildnumber);
