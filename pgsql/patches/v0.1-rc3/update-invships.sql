DROP VIEW invusedtypes;

CREATE OR REPLACE VIEW invships AS 
 SELECT invtypes.typeid, invtypes.typename, invtypes.groupid, invgroups.groupname, invtypes.marketgroupid, invmarketgroups.marketgroupname
   FROM eve.invtypes
   JOIN eve.invgroups ON invtypes.groupid = invgroups.groupid
   LEFT JOIN eve.invmarketgroups ON invtypes.marketgroupid = invmarketgroups.marketgroupid
  WHERE invgroups.categoryid = 6 AND invgroups.published = 1 AND invtypes.published = 1;

CREATE OR REPLACE VIEW invusedtypes AS 
        (        (        (         SELECT invships.typeid
                                   FROM invships
                        UNION 
                                 SELECT invmodules.typeid
                                   FROM invmodules)
                UNION 
                         SELECT invcharges.chargeid AS typeid
                           FROM invcharges)
        UNION 
                 SELECT invdrones.typeid
                   FROM invdrones)
UNION 
         SELECT invskills.typeid
           FROM invskills;

