CREATE OR REPLACE VIEW typessearchdata AS 
 SELECT t.typeid,
    it.typename,
    pit.typename AS parenttypename,
    t.category,
    t.subcategory,
    ig.groupname,
    COALESCE(imt.metagroupid::integer, dta_mg.value::integer,
        CASE dta_tl.value::integer
            WHEN 2 THEN 2
            WHEN 3 THEN 14
            ELSE 1
        END) AS metagroupid,
    dta_ml.value::integer AS metalevel,
    img.marketgroupid,
    img.marketgroupname,
    t.other
   FROM ( SELECT invships.typeid,
            'ship'::text AS category,
            NULL::text AS subcategory,
            NULL::text AS other
           FROM invships
        UNION
         SELECT invmodules.typeid,
            'module'::text AS category,
                CASE dte.effectid
                    WHEN 11 THEN 'low'::text
                    WHEN 12 THEN 'high'::text
                    WHEN 13 THEN 'medium'::text
                    WHEN 2663 THEN 'rig'::text
                    WHEN 3772 THEN 'subsystem'::text
                    ELSE NULL::text
                END AS subcategory,
                CASE hardpoint.effectid
                    WHEN 40 THEN 'launcher'::text
                    WHEN 42 THEN 'turret'::text
                    ELSE NULL::text
                END AS other
           FROM invmodules
             JOIN eve.dgmtypeeffects dte ON invmodules.typeid = dte.typeid AND (dte.effectid = ANY (ARRAY[11, 12, 13, 2663, 3772]))
             LEFT JOIN eve.dgmtypeeffects hardpoint ON invmodules.typeid = hardpoint.typeid AND (hardpoint.effectid = ANY (ARRAY[40, 42]))
        UNION
         SELECT invcharges.chargeid AS typeid,
            'charge'::text AS category,
            NULL::text AS subcategory,
            NULL::text AS other
           FROM invcharges
        UNION
         SELECT invdrones.typeid,
            'drone'::text AS category,
            NULL::text AS subcategory,
            bw.value::text AS other
           FROM invdrones
             LEFT JOIN eve.dgmtypeattribs bw ON bw.attributeid = 1272 AND bw.typeid = invdrones.typeid
        UNION
         SELECT invimplants.typeid,
            'implant'::text AS category,
            invimplants.implantness::text AS subcategory,
            NULL::text AS other
           FROM invimplants
        UNION
         SELECT invboosters.typeid,
            'booster'::text AS category,
            invboosters.boosterness::text AS subcategory,
            NULL::text AS other
           FROM invboosters
        UNION
         SELECT invbeacons.typeid,
            'beacon'::text AS category,
            NULL::text AS subcategory,
            NULL::text AS other
           FROM invbeacons) t
     JOIN eve.invtypes it ON it.typeid = t.typeid
     JOIN eve.invgroups ig ON it.groupid = ig.groupid
     LEFT JOIN eve.invmarketgroups img ON img.marketgroupid = it.marketgroupid
     LEFT JOIN eve.invmetatypes imt ON it.typeid = imt.typeid
     LEFT JOIN eve.invtypes pit ON pit.typeid = imt.parenttypeid
     LEFT JOIN eve.dgmtypeattribs dta_tl ON dta_tl.typeid = it.typeid AND dta_tl.attributeid = 422
     LEFT JOIN eve.dgmtypeattribs dta_ml ON dta_ml.typeid = it.typeid AND dta_ml.attributeid = 633
     LEFT JOIN eve.dgmtypeattribs dta_mg ON dta_mg.typeid = it.typeid AND dta_mg.attributeid = 1692;
