CREATE OR REPLACE VIEW invtypevariations AS 
 SELECT t.typeid,
    t.vartypeid,
    t.vartypename,
    COALESCE(imt.metagroupid::integer, 1) AS varmgid,
    COALESCE(mg.value::integer, 0) AS varml
   FROM (        (         SELECT it.typeid,
                            imt_1.typeid AS vartypeid,
                            vit.typename AS vartypename
                           FROM eve.invtypes it
                      LEFT JOIN eve.invmetatypes p ON p.typeid = it.typeid
                 JOIN eve.invmetatypes imt_1 ON imt_1.parenttypeid = it.typeid OR imt_1.parenttypeid = p.parenttypeid
            JOIN eve.invtypes vit ON vit.typeid = imt_1.typeid
                UNION
                         SELECT it.typeid,
                            p.parenttypeid AS vartypeid,
                            vit.typename AS vartypename
                           FROM eve.invtypes it
                      JOIN eve.invmetatypes p ON p.typeid = it.typeid
                 JOIN eve.invtypes vit ON vit.typeid = p.parenttypeid)
        UNION
                 SELECT it.typeid,
                    it.typeid AS vartypeid,
                    it.typename AS vartypename
                   FROM eve.invtypes it) t
   LEFT JOIN eve.invmetatypes imt ON imt.typeid = t.vartypeid
   LEFT JOIN eve.dgmtypeattribs mg ON mg.attributeid = 633 AND mg.typeid = t.vartypeid;
