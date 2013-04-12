CREATE OR REPLACE VIEW invshipslots AS 
 SELECT invships.typeid, COALESCE(hs.value::integer, 0) AS highslots, 
    COALESCE(ms.value::integer, 0) AS medslots, 
    COALESCE(ls.value::integer, 0) AS lowslots, 
    COALESCE(rs.value::integer, 0) AS rigslots, 
    COALESCE(ss.value::integer, 0) AS subsystemslots
   FROM invships
   LEFT JOIN eve.dgmtypeattribs hs ON hs.typeid = invships.typeid AND hs.attributeid = 14
   LEFT JOIN eve.dgmtypeattribs ms ON ms.typeid = invships.typeid AND ms.attributeid = 13
   LEFT JOIN eve.dgmtypeattribs ls ON ls.typeid = invships.typeid AND ls.attributeid = 12
   LEFT JOIN eve.dgmtypeattribs rs ON rs.typeid = invships.typeid AND rs.attributeid = 1154
   LEFT JOIN eve.dgmtypeattribs ss ON ss.typeid = invships.typeid AND ss.attributeid = 1367;

ALTER TABLE invshipslots
  OWNER TO osmium;

CREATE OR REPLACE VIEW invmodulestates AS 
 SELECT tsd.typeid, 
    tsd.subcategory = ANY (ARRAY['low'::text, 'medium'::text, 'high'::text]) AS offlinable, 
    true AS onlinable, 
    (tsd.subcategory = ANY (ARRAY['low'::text, 'medium'::text, 'high'::text])) AND (( SELECT count(dte.effectid) AS count
           FROM eve.dgmtypeeffects dte
      JOIN eve.dgmeffects de ON de.effectid = dte.effectid AND de.effectid <> 16 AND (de.effectcategory::integer = ANY (ARRAY[1, 2, 3, 5]))
     WHERE dte.typeid = tsd.typeid
    LIMIT 1)) > 0 AS activable, 
    (tsd.subcategory = ANY (ARRAY['low'::text, 'medium'::text, 'high'::text])) AND (( SELECT count(dte.effectid) AS count
           FROM eve.dgmtypeeffects dte
      JOIN eve.dgmeffects de ON de.effectid = dte.effectid AND de.effectcategory = 5
     WHERE dte.typeid = tsd.typeid
    LIMIT 1)) > 0 AS overloadable
   FROM typessearchdata tsd
  WHERE tsd.category = 'module'::text;

ALTER TABLE invmodulestates
  OWNER TO osmium;

