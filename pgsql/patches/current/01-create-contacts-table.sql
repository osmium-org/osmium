DROP TABLE IF EXISTS contacts;

CREATE TABLE contacts
(
  accountid integer NOT NULL,
  contactid integer NOT NULL,
  standing double precision NOT NULL,
  CONSTRAINT contacts_accountid_fkey FOREIGN KEY (accountid)
      REFERENCES accounts (accountid) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT contacts_standing_check CHECK (standing >= (-10)::double precision AND standing <= 10::double precision)
)
WITH (
  OIDS=FALSE
);

CREATE INDEX contacts_accountid_idx
  ON contacts
  USING btree
  (accountid);

CREATE INDEX contacts_standing_idx
  ON contacts
  USING btree
  (standing);
