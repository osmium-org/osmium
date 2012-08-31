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
        END AS restrictedtoallianceid, loadoutstaglist.taglist AS tags, loadoutsmodulelist.modulelist AS modules, 
        CASE accounts.apiverified
            WHEN true THEN accounts.charactername
            ELSE accounts.nickname
        END AS author, fittings.name, fittings.description, fittings.hullid AS shipid, invtypes.typename AS ship, fittings.creationdate, loadouthistory.updatedate, ls.upvotes, ls.downvotes, ls.score
   FROM searchableloadouts
   JOIN loadoutslatestrevision ON searchableloadouts.loadoutid = loadoutslatestrevision.loadoutid
   JOIN loadouts ON loadoutslatestrevision.loadoutid = loadouts.loadoutid
   JOIN accounts ON loadouts.accountid = accounts.accountid
   JOIN loadouthistory ON loadouthistory.loadoutid = loadoutslatestrevision.loadoutid AND loadouthistory.revision = loadoutslatestrevision.latestrevision
   JOIN fittings ON fittings.fittinghash = loadouthistory.fittinghash
   JOIN loadoutscores ls ON ls.loadoutid = searchableloadouts.loadoutid
   LEFT JOIN loadoutstaglist ON loadoutstaglist.loadoutid = loadoutslatestrevision.loadoutid
   LEFT JOIN loadoutsmodulelist ON loadoutsmodulelist.loadoutid = loadoutslatestrevision.loadoutid
   JOIN eve.invtypes ON invtypes.typeid = fittings.hullid;
