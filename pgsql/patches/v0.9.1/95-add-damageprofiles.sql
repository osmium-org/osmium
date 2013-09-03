ALTER TABLE fittings DROP COLUMN IF EXISTS damageprofileid;
DROP TABLE IF EXISTS damageprofiles;

CREATE TABLE damageprofiles
(
  damageprofileid serial NOT NULL,
  name character varying(255) NOT NULL,
  electromagnetic double precision NOT NULL,
  explosive double precision NOT NULL,
  kinetic double precision NOT NULL,
  thermal double precision NOT NULL,
  CONSTRAINT damageprofiles_pkey PRIMARY KEY (damageprofileid),
  CONSTRAINT damageprofiles_name_damages_uniq UNIQUE (name, electromagnetic, explosive, kinetic, thermal),
  CONSTRAINT damageprofile_sanity_check CHECK (electromagnetic >= 0::double precision AND explosive >= 0::double precision AND kinetic >= 0::double precision AND thermal >= 0::double precision AND (electromagnetic + explosive + kinetic + thermal) > 0::double precision)
);

ALTER TABLE fittings ADD COLUMN damageprofileid integer;
ALTER TABLE fittings
  ADD CONSTRAINT fittings_damageprofileid_fkey FOREIGN KEY (damageprofileid)
      REFERENCES damageprofiles (damageprofileid) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;
