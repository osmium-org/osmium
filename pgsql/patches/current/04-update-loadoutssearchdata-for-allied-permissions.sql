DROP VIEW loadoutssearchdata;

CREATE OR REPLACE VIEW loadoutssearchdata AS
 SELECT DISTINCT searchableloadouts.loadoutid, 
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
        CASE loadouts.viewpermission
            WHEN 5 THEN 
            CASE accounts.apiverified
                WHEN true THEN 0
                ELSE (-1)
            END
            WHEN 6 THEN 
            CASE accounts.apiverified
                WHEN true THEN 5
                ELSE (-1)
            END
            ELSE (-1)
        END AS restrictedtostanding, fittingaggtags.taglist AS tags, fittingfittedtypes.typelist AS modules, 
        CASE accounts.apiverified
            WHEN true THEN accounts.charactername
            ELSE accounts.nickname
        END AS author, loadouts.accountid, fittings.name, fittingdescriptions.descriptions AS description, fittings.hullid AS shipid,
        invtypes.typename AS ship, fittings.creationdate, loadouthistory.updatedate, ls.upvotes, ls.downvotes, ls.score, invgroups.groupname AS groups
   FROM searchableloadouts
   JOIN loadoutslatestrevision ON searchableloadouts.loadoutid = loadoutslatestrevision.loadoutid
   JOIN loadouts ON loadoutslatestrevision.loadoutid = loadouts.loadoutid
   JOIN accounts ON loadouts.accountid = accounts.accountid
   JOIN loadouthistory ON loadouthistory.loadoutid = loadoutslatestrevision.loadoutid AND loadouthistory.revision = loadoutslatestrevision.latestrevision
   JOIN fittings ON fittings.fittinghash = loadouthistory.fittinghash
   JOIN loadoutscores ls ON ls.loadoutid = searchableloadouts.loadoutid
   JOIN eve.invtypes ON invtypes.typeid = fittings.hullid
   LEFT JOIN fittingaggtags ON fittingaggtags.fittinghash = loadouthistory.fittinghash
   LEFT JOIN fittingfittedtypes ON fittingfittedtypes.fittinghash = loadouthistory.fittinghash
   LEFT JOIN fittingdescriptions ON fittingdescriptions.fittinghash = loadouthistory.fittinghash
   LEFT JOIN eve.invgroups ON invgroups.groupid = invtypes.groupid;

ALTER TABLE loadoutssearchdata
  OWNER TO osmium;
