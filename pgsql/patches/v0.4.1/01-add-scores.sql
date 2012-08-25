CREATE OR REPLACE VIEW loadoutscores AS 
 SELECT searchableloadouts.loadoutid, COALESCE(uv.count, 0::bigint) AS upvotes, COALESCE(dv.count, 0::bigint) AS downvotes, ((COALESCE(uv.count::numeric, 0.5) + 1.9208) / (COALESCE(uv.count::numeric, 0.5) + COALESCE(dv.count, 0::bigint)::numeric) - 1.96 * sqrt(COALESCE(uv.count::numeric, 0.5) * COALESCE(dv.count, 0::bigint)::numeric / (COALESCE(uv.count::numeric, 0.5) + COALESCE(dv.count, 0::bigint)::numeric) + 0.9604) / (COALESCE(uv.count::numeric, 0.5) + COALESCE(dv.count, 0::bigint)::numeric)) / (1::numeric + 3.8416 / (COALESCE(uv.count::numeric, 0.5) + COALESCE(dv.count, 0::bigint)::numeric)) AS score
   FROM searchableloadouts
   LEFT JOIN votecount uv ON uv.type = 1 AND uv.targettype = 1 AND uv.targetid1 = searchableloadouts.loadoutid AND uv.targetid2 IS NULL AND uv.targetid3 IS NULL
   LEFT JOIN votecount dv ON dv.type = 2 AND dv.targettype = 1 AND dv.targetid1 = searchableloadouts.loadoutid AND dv.targetid2 IS NULL AND dv.targetid3 IS NULL;

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
                ELSE 0
            END
            ELSE 0
        END AS restrictedtocorporationid, 
        CASE loadouts.viewpermission
            WHEN 2 THEN 
            CASE accounts.apiverified
                WHEN true THEN accounts.allianceid
                ELSE 0
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
