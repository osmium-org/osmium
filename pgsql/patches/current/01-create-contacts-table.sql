CREATE TABLE contacts (
    accountid integer NOT NULL,
    contactid integer NOT NULL,
    standing integer NOT NULL
);

ALTER TABLE contacts
  OWNER TO osmium;
