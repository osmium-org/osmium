CREATE OR REPLACE VIEW invcharges AS 
 SELECT modattribs.typeid AS moduleid, invtypes.typeid AS chargeid, invtypes.typename AS chargename
   FROM eve.dgmtypeattribs modattribs
   LEFT JOIN eve.dgmtypeattribs modchargesize ON modchargesize.attributeid = 128 AND modchargesize.typeid = modattribs.typeid
   JOIN eve.invtypes ON modattribs.value::integer = invtypes.groupid
   LEFT JOIN eve.dgmtypeattribs chargesize ON chargesize.attributeid = 128 AND chargesize.typeid = invtypes.typeid
   JOIN eve.invtypes modcapacity ON modcapacity.typeid = modattribs.typeid
  WHERE (modattribs.attributeid = ANY (ARRAY[604, 605, 606, 609, 610])) AND (chargesize.value IS NULL OR modchargesize.value IS NULL OR chargesize.value = modchargesize.value) AND modcapacity.capacity >= invtypes.volume AND invtypes.published = true;

CREATE OR REPLACE VIEW invdrones AS 
 SELECT invtypes.typeid, invtypes.typename, invtypes.volume, invtypes.groupid, invgroups.groupname
   FROM eve.invtypes
   JOIN eve.invgroups ON invtypes.groupid = invgroups.groupid
  WHERE invgroups.categoryid = 18 AND invgroups.published = true AND invtypes.published = true;

CREATE OR REPLACE VIEW invmodules AS 
 SELECT invtypes.typeid, invtypes.typename, COALESCE(invmetatypes.metagroupid::integer, metagroup.value::integer, 
        CASE techlevel.value::integer
            WHEN 2 THEN 2
            WHEN 3 THEN 14
            ELSE 1
        END) AS metagroupid, invgroups.groupid, invgroups.groupname, invtypes.marketgroupid, invmarketgroups.marketgroupname
   FROM eve.invtypes
   JOIN eve.invgroups ON invtypes.groupid = invgroups.groupid
   LEFT JOIN eve.invmarketgroups ON invtypes.marketgroupid = invmarketgroups.marketgroupid
   LEFT JOIN eve.invmetatypes ON invtypes.typeid = invmetatypes.typeid
   LEFT JOIN eve.dgmtypeattribs techlevel ON techlevel.typeid = invtypes.typeid AND techlevel.attributeid = 422
   LEFT JOIN eve.dgmtypeattribs metagroup ON metagroup.typeid = invtypes.typeid AND metagroup.attributeid = 1692
  WHERE (invgroups.categoryid = ANY (ARRAY[7, 32])) AND invgroups.published = true AND invtypes.published = true;

CREATE OR REPLACE VIEW invships AS 
 SELECT invtypes.typeid, invtypes.typename, invtypes.groupid, invgroups.groupname, invtypes.marketgroupid, invmarketgroups.marketgroupname
   FROM eve.invtypes
   JOIN eve.invgroups ON invtypes.groupid = invgroups.groupid
   LEFT JOIN eve.invmarketgroups ON invtypes.marketgroupid = invmarketgroups.marketgroupid
  WHERE invgroups.categoryid = 6 AND invgroups.published = true AND invtypes.published = true;

CREATE OR REPLACE VIEW invskills AS 
 SELECT invtypes.typeid, invtypes.typename, invtypes.groupid, invgroups.groupname
   FROM eve.invtypes
   JOIN eve.invgroups ON invtypes.groupid = invgroups.groupid
  WHERE invgroups.categoryid = 16 AND invtypes.published = true;

CREATE VIEW invmetagroups AS
    SELECT DISTINCT invmetagroups.metagroupid, invmetagroups.metagroupname FROM (invmodules LEFT JOIN eve.invmetagroups ON ((invmodules.metagroupid = invmetagroups.metagroupid)));
