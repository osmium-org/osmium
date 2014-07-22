BEGIN;

ALTER TABLE accountcredentials ADD COLUMN ccpoauthcharacterid integer;
ALTER TABLE accountcredentials ADD COLUMN ccpoauthownerhash text;

ALTER TABLE accountcredentials
  ADD CONSTRAINT accountcredentials_ccpoauthcharacterid_uniq UNIQUE(ccpoauthcharacterid);

ALTER TABLE accountcredentials DROP CONSTRAINT accountcredentials_meaningful_check;

ALTER TABLE accountcredentials
  ADD CONSTRAINT accountcredentials_meaningful_check CHECK (
(username IS NOT NULL AND passwordhash IS NOT NULL AND ccpoauthcharacterid IS NULL AND ccpoauthownerhash IS NULL)
OR
(username IS NULL AND passwordhash IS NULL AND ccpoauthcharacterid IS NOT NULL AND ccpoauthownerhash IS NOT NULL)
);

COMMIT;
