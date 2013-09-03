CREATE OR REPLACE VIEW fittingfittedtypes AS 
 SELECT t.fittinghash, 
    (((string_agg(DISTINCT invtypes.typename::text, ', '::text) || ', '::text) || COALESCE(string_agg(DISTINCT pt.typename::text, ', '::text), ' '::text)) || ', '::text) || COALESCE(string_agg(DISTINCT invgroups.groupname::text, ', '::text), ' '::text) AS typelist
   FROM (        (        (         SELECT fittingmodules.fittinghash, 
                                    fittingmodules.typeid
                                   FROM fittingmodules
                        UNION 
                                 SELECT fittingcharges.fittinghash, 
                                    fittingcharges.typeid
                                   FROM fittingcharges)
                UNION 
                         SELECT fittingdrones.fittinghash, fittingdrones.typeid
                           FROM fittingdrones)
        UNION 
                 SELECT fittingimplants.fittinghash, fittingimplants.typeid
                   FROM fittingimplants) t
   JOIN eve.invtypes ON t.typeid = invtypes.typeid
   LEFT JOIN eve.invgroups ON invgroups.groupid = invtypes.groupid
   LEFT JOIN eve.invmetatypes imt ON imt.typeid = t.typeid
   LEFT JOIN eve.invtypes pt ON pt.typeid = imt.parenttypeid
  GROUP BY t.fittinghash;
