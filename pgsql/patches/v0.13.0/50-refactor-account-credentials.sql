BEGIN;

CREATE TABLE accountcredentials
(
  accountcredentialsid serial NOT NULL,
  accountid integer NOT NULL,
  username text,
  passwordhash text,
  CONSTRAINT accountcredentials_pkey PRIMARY KEY (accountcredentialsid),
  CONSTRAINT accountcredentials_accountid_fkey FOREIGN KEY (accountid)
      REFERENCES accounts (accountid) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT accountcredentials_username_uniq UNIQUE (username),
  CONSTRAINT accountcredentials_meaningful_check CHECK (username IS NOT NULL AND passwordhash IS NOT NULL)
)
WITH (
  OIDS=FALSE
);

INSERT INTO accountcredentials (accountid, username, passwordhash)
SELECT accountid, accountname, passwordhash FROM accounts;

CREATE INDEX accountcredentials_accountid_idx
  ON accountcredentials
  USING btree
  (accountid);

ALTER TABLE accounts DROP CONSTRAINT accounts_accountname_uniq;
DROP INDEX accounts_accountname_idx;

ALTER TABLE accounts DROP COLUMN accountname;
ALTER TABLE accounts DROP COLUMN passwordhash;

COMMIT;
