CREATE OR REPLACE VIEW invboosters AS 
 SELECT invtypes.typeid, invtypes.typename, dta.value::integer AS boosterness
   FROM eve.invtypes
   JOIN eve.invgroups ON invtypes.groupid = invgroups.groupid
   JOIN eve.dgmtypeattribs dta ON dta.attributeid = 1087 AND dta.typeid = invtypes.typeid
  WHERE invgroups.categoryid = 20 AND invtypes.groupid = 303 AND invgroups.published = true AND invtypes.published = true;

CREATE OR REPLACE VIEW invimplants AS 
 SELECT invtypes.typeid, invtypes.typename, dta.value::integer AS implantness
   FROM eve.invtypes
   JOIN eve.invgroups ON invtypes.groupid = invgroups.groupid
   JOIN eve.dgmtypeattribs dta ON dta.attributeid = 331 AND dta.typeid = invtypes.typeid
  WHERE invgroups.categoryid = 20 AND invtypes.groupid <> 303 AND invgroups.published = true AND invtypes.published = true;

CREATE OR REPLACE VIEW typessearchdata AS 
 SELECT t.typeid, it.typename, t.category, t.subcategory, ig.groupname, 
    COALESCE(imt.metagroupid::integer, dta_mg.value::integer, 
        CASE dta_tl.value::integer
            WHEN 2 THEN 2
            WHEN 3 THEN 14
            ELSE 1
        END) AS metagroupid, 
    img.marketgroupid, img.marketgroupname
   FROM (        (        (        (        (         SELECT invships.typeid, 
                                                    'ship'::text AS category, 
                                                    NULL::text AS subcategory
                                                   FROM invships
                                        UNION 
                                                 SELECT invmodules.typeid, 
                                                    'module'::text AS category, 
                                                        CASE dte.effectid
                                                            WHEN 11 THEN 'low'::text
                                                            WHEN 12 THEN 'medium'::text
                                                            WHEN 13 THEN 'high'::text
                                                            WHEN 2663 THEN 'rig'::text
                                                            WHEN 3772 THEN 'subsystem'::text
                                                            ELSE NULL::text
                                                        END AS subcategory
                                                   FROM invmodules
                                              JOIN eve.dgmtypeeffects dte ON invmodules.typeid = dte.typeid AND (dte.effectid = ANY (ARRAY[11, 12, 13, 2663, 3772])))
                                UNION 
                                         SELECT invcharges.chargeid AS typeid, 
                                            'charge'::text AS category, 
                                            NULL::text AS subcategory
                                           FROM invcharges)
                        UNION 
                                 SELECT invdrones.typeid, 
                                    'drone'::text AS category, 
                                    NULL::text AS subcategory
                                   FROM invdrones)
                UNION 
                         SELECT invimplants.typeid, 'implant'::text AS category, 
                            invimplants.implantness::text AS subcategory
                           FROM invimplants)
        UNION 
                 SELECT invboosters.typeid, 'booster'::text AS category, 
                    invboosters.boosterness::text AS subcategory
                   FROM invboosters) t
   JOIN eve.invtypes it ON it.typeid = t.typeid
   JOIN eve.invgroups ig ON it.groupid = ig.groupid
   LEFT JOIN eve.invmarketgroups img ON img.marketgroupid = it.marketgroupid
   LEFT JOIN eve.invmetatypes imt ON it.typeid = imt.typeid
   LEFT JOIN eve.dgmtypeattribs dta_tl ON dta_tl.typeid = it.typeid AND dta_tl.attributeid = 422
   LEFT JOIN eve.dgmtypeattribs dta_mg ON dta_mg.typeid = it.typeid AND dta_mg.attributeid = 1692;
