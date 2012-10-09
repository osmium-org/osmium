ALTER TABLE loadouts
   ADD COLUMN privatetoken bigint NOT NULL DEFAULT (random() * 2^63)::bigint;
