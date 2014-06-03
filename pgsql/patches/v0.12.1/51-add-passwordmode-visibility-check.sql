ALTER TABLE loadouts DROP CONSTRAINT IF EXISTS loadouts_passwordeveryone_implies_private_check;

ALTER TABLE loadouts
  ADD CONSTRAINT loadouts_passwordeveryone_implies_private_check CHECK (passwordmode <> 2 OR visibility = 1);
