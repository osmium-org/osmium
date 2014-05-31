BEGIN;

CREATE TABLE eveapikeys
(
  owneraccountid integer NOT NULL,
  keyid integer NOT NULL,
  verificationcode text NOT NULL,
  active boolean NOT NULL,
  creationdate integer NOT NULL,
  updatedate integer,
  expirationdate bigint,
  mask bigint NOT NULL
)
WITH (
  OIDS=FALSE
);



INSERT INTO eveapikeys (owneraccountid, keyid, verificationcode, active, creationdate, mask)
SELECT accountid, keyid, verificationcode, apiverified, creationdate, 0 FROM accounts WHERE keyid > 0;

INSERT INTO eveapikeys (owneraccountid, keyid, verificationcode, active, creationdate, mask)
SELECT accountid, keyid, verificationcode, true, lastimportdate, 0 FROM accountcharacters WHERE keyid > 0;

-- Dirty hack, will be slow for big tables
DELETE FROM eveapikeys WHERE ctid NOT IN (
SELECT DISTINCT ON (owneraccountid, keyid) ctid FROM eveapikeys
);


ALTER TABLE accountcharacters DROP COLUMN verificationcode;
ALTER TABLE accounts DROP COLUMN verificationcode;



ALTER TABLE eveapikeys ADD CONSTRAINT eveapikeys_pkey PRIMARY KEY (owneraccountid, keyid);

CREATE INDEX eveapikeys_keyid_idx ON eveapikeys (keyid);

CREATE INDEX eveapikeys_active_updatedate_idx
ON eveapikeys (updatedate ASC NULLS FIRST) WHERE active = true;


ALTER TABLE eveapikeys
ADD CONSTRAINT eveapikeys_owneraccountid_fkey FOREIGN KEY (owneraccountid)
REFERENCES accounts (accountid) MATCH SIMPLE
ON UPDATE NO ACTION ON DELETE NO ACTION;

ALTER TABLE accounts
ADD CONSTRAINT accounts_keyid_fkey FOREIGN KEY (accountid, keyid)
REFERENCES eveapikeys (owneraccountid, keyid) MATCH SIMPLE
ON UPDATE NO ACTION ON DELETE NO ACTION;

ALTER TABLE accountcharacters
ADD CONSTRAINT accountcharacters_keyid_fkey FOREIGN KEY (accountid, keyid)
REFERENCES eveapikeys (owneraccountid, keyid) MATCH SIMPLE
ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMIT;
