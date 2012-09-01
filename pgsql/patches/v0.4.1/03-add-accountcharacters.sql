DROP TABLE IF EXISTS accountcharacters;

CREATE TABLE accountcharacters
(
  accountid integer NOT NULL,
  name character varying(255) NOT NULL,
  keyid integer,
  verificationcode character varying(255),
  importname name,
  importedskillset text,
  overriddenskillset text,
  lastimportdate integer,
  CONSTRAINT accountcharacters_pkey PRIMARY KEY (accountid , name ),
  CONSTRAINT accountcharacters_accountid_fkey FOREIGN KEY (accountid)
      REFERENCES accounts (accountid) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);

CREATE INDEX accountcharacters_accountid_idx
  ON accountcharacters
  USING btree
  (accountid );

