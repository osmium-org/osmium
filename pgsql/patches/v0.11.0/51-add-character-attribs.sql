ALTER TABLE accountcharacters DROP COLUMN IF EXISTS perception;
ALTER TABLE accountcharacters ADD COLUMN perception smallint;

ALTER TABLE accountcharacters DROP COLUMN IF EXISTS willpower;
ALTER TABLE accountcharacters ADD COLUMN willpower smallint;

ALTER TABLE accountcharacters DROP COLUMN IF EXISTS intelligence;
ALTER TABLE accountcharacters ADD COLUMN intelligence smallint;

ALTER TABLE accountcharacters DROP COLUMN IF EXISTS memory;
ALTER TABLE accountcharacters ADD COLUMN memory smallint;

ALTER TABLE accountcharacters DROP COLUMN IF EXISTS charisma;
ALTER TABLE accountcharacters ADD COLUMN charisma smallint;

ALTER TABLE accountcharacters DROP COLUMN IF EXISTS perceptionoverride;
ALTER TABLE accountcharacters ADD COLUMN perceptionoverride smallint;

ALTER TABLE accountcharacters DROP COLUMN IF EXISTS willpoweroverride;
ALTER TABLE accountcharacters ADD COLUMN willpoweroverride smallint;

ALTER TABLE accountcharacters DROP COLUMN IF EXISTS intelligenceoverride;
ALTER TABLE accountcharacters ADD COLUMN intelligenceoverride smallint;

ALTER TABLE accountcharacters DROP COLUMN IF EXISTS memoryoverride;
ALTER TABLE accountcharacters ADD COLUMN memoryoverride smallint;

ALTER TABLE accountcharacters DROP COLUMN IF EXISTS charismaoverride;
ALTER TABLE accountcharacters ADD COLUMN charismaoverride smallint;
