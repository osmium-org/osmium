DROP VIEW invusedtypes;

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
  WHERE (invgroups.categoryid = ANY (ARRAY[7, 32])) AND invgroups.published = 1 AND invtypes.published = 1;

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

