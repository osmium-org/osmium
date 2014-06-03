BEGIN;

-- Will fail if the column already exists, and thus prevent setting
-- wrong values in passwordmode. This is intentional. This script must
-- only be run once, it can't work after that.
ALTER TABLE loadouts ADD COLUMN passwordmode integer;

-- Fill passwordmode correctly for password-protected loadouts
UPDATE loadouts SET passwordmode = CASE viewpermission WHEN 1 THEN 2 ELSE 0 END;

-- Set password-protected loadouts view permission to everyone
UPDATE loadouts SET viewpermission = 0 WHERE viewpermission = 1;

ALTER TABLE loadouts ALTER COLUMN passwordmode SET NOT NULL;

COMMIT;
