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
    loadouthistory.updatedate, ls.upvotes, ls.downvotes, ls.score, 
    invgroups.groupname::text AS groups, fittings.evebuildnumber, 
    COALESCE(lcc.count, 0::bigint) AS comments, 
    COALESCE(lda.dps, 0::double precision) AS dps, 
    COALESCE(lda.ehp, 0::double precision) AS ehp, 
    COALESCE(lda.estimatedprice, 0::double precision) AS estimatedprice
   FROM searchableloadouts
   JOIN loadoutslatestrevision ON searchableloadouts.loadoutid = loadoutslatestrevision.loadoutid
   JOIN loadouts ON loadoutslatestrevision.loadoutid = loadouts.loadoutid
   JOIN accounts ON loadouts.accountid = accounts.accountid
   JOIN loadouthistory ON loadouthistory.loadoutid = loadoutslatestrevision.loadoutid AND loadouthistory.revision = loadoutslatestrevision.latestrevision
   JOIN fittings ON fittings.fittinghash = loadouthistory.fittinghash
   JOIN loadoutscores ls ON ls.loadoutid = searchableloadouts.loadoutid
   JOIN eve.invtypes ON invtypes.typeid = fittings.hullid
   LEFT JOIN loadoutcommentcount lcc ON lcc.loadoutid = searchableloadouts.loadoutid
   LEFT JOIN fittingaggtags ON fittingaggtags.fittinghash = loadouthistory.fittinghash
   LEFT JOIN fittingfittedtypes ON fittingfittedtypes.fittinghash = loadouthistory.fittinghash
   LEFT JOIN fittingdescriptions ON fittingdescriptions.fittinghash = loadouthistory.fittinghash
   LEFT JOIN eve.invgroups ON invgroups.groupid = invtypes.groupid
   LEFT JOIN loadoutdogmaattribs lda ON lda.loadoutid = searchableloadouts.loadoutid;
