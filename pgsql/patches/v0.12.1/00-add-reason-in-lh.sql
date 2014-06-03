ALTER TABLE loadouthistory DROP COLUMN IF EXISTS reason;
ALTER TABLE loadouthistory ADD COLUMN reason text;
