DROP VIEW loadoutssearchdata;

DROP VIEW IF EXISTS loadoutsdescriptions;

CREATE OR REPLACE VIEW fittingdescriptions AS 
 SELECT d.fittinghash, string_agg(d.description, ', '::text) AS descriptions
   FROM (        (        (         SELECT fittings.fittinghash, 
                                    fittings.description
                                   FROM fittings
                        UNION 
                                 SELECT fittingpresets.fittinghash, 
                                    fittingpresets.description
                                   FROM fittingpresets)
                UNION 
                         SELECT fittingchargepresets.fittinghash, 
                            fittingchargepresets.description
                           FROM fittingchargepresets)
        UNION 
                 SELECT fittingdronepresets.fittinghash, 
                    fittingdronepresets.description
                   FROM fittingdronepresets) d
  GROUP BY d.fittinghash;

DROP VIEW IF EXISTS loadoutsfittedtypes;

CREATE OR REPLACE VIEW fittingfittedtypes AS 
 SELECT t.fittinghash, 
    string_agg(DISTINCT invtypes.typename::text, ', '::text) AS typelist
   FROM (        (         SELECT fittingmodules.fittinghash, 
                            fittingmodules.typeid
                           FROM fittingmodules
                UNION 
                         SELECT fittingcharges.fittinghash, 
                            fittingcharges.typeid
                           FROM fittingcharges)
        UNION 
                 SELECT fittingdrones.fittinghash, fittingdrones.typeid
                   FROM fittingdrones) t
   JOIN eve.invtypes ON t.typeid = invtypes.typeid
  GROUP BY t.fittinghash;

DROP VIEW IF EXISTS loadoutstaglist;

CREATE OR REPLACE VIEW fittingaggtags AS 
 SELECT fittingtags.fittinghash, 
    string_agg(DISTINCT fittingtags.tagname::text, ' '::text) AS taglist
   FROM fittingtags
  GROUP BY fittingtags.fittinghash;

CREATE OR REPLACE VIEW loadoutssearchdata AS 
 SELECT searchableloadouts.loadoutid, 
        CASE loadouts.viewpermission
            WHEN 4 THEN accounts.accountid
            ELSE 0
        END AS restrictedtoaccountid, 
        CASE loadouts.viewpermission
            WHEN 3 THEN 
            CASE accounts.apiverified
                WHEN true THEN accounts.corporationid
                ELSE (-1)
            END
            ELSE 0
        END AS restrictedtocorporationid, 
        CASE loadouts.viewpermission
            WHEN 2 THEN 
            CASE accounts.apiverified
                WHEN true THEN accounts.allianceid
                ELSE (-1)
            END
            ELSE 0
        END AS restrictedtoallianceid, 
    fittingaggtags.taglist AS tags, fittingfittedtypes.typelist AS modules, 
        CASE accounts.apiverified
            WHEN true THEN accounts.charactername
            ELSE accounts.nickname
        END AS author, 
    fittings.name, fittingdescriptions.descriptions AS description, 
    fittings.hullid AS shipid, invtypes.typename AS ship, fittings.creationdate, 
    loadouthistory.updatedate, ls.upvotes, ls.downvotes, ls.score
   FROM searchableloadouts
   JOIN loadoutslatestrevision ON searchableloadouts.loadoutid = loadoutslatestrevision.loadoutid
   JOIN loadouts ON loadoutslatestrevision.loadoutid = loadouts.loadoutid
   JOIN accounts ON loadouts.accountid = accounts.accountid
   JOIN loadouthistory ON loadouthistory.loadoutid = loadoutslatestrevision.loadoutid AND loadouthistory.revision = loadoutslatestrevision.latestrevision
   JOIN fittings ON fittings.fittinghash = loadouthistory.fittinghash
   JOIN loadoutscores ls ON ls.loadoutid = searchableloadouts.loadoutid
   LEFT JOIN fittingaggtags ON fittingaggtags.fittinghash = loadouthistory.fittinghash
   LEFT JOIN fittingfittedtypes ON fittingfittedtypes.fittinghash = loadouthistory.fittinghash
   LEFT JOIN fittingdescriptions ON fittingdescriptions.fittinghash = loadouthistory.fittinghash
   JOIN eve.invtypes ON invtypes.typeid = fittings.hullid;
