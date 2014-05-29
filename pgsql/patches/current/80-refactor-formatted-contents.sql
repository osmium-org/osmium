BEGIN;

CREATE TABLE editableformattedcontents
(
  contentid serial NOT NULL,
  rawcontent text NOT NULL,
  filtermask integer NOT NULL,
  formattedcontent text,
  tempid1 integer, -- Couldn't find a better way :(
  tempid2 integer,
  temphash1 character(40),
  CONSTRAINT editableformattedcontents_pkey PRIMARY KEY (contentid)
)
WITH (
  OIDS=FALSE
);



--
-- Comments
--

ALTER TABLE loadoutcommentrevisions ADD COLUMN bodycontentid integer;

INSERT INTO editableformattedcontents (rawcontent, filtermask, formattedcontent, tempid1, tempid2)
SELECT commentbody, 5, commentformattedbody, commentid, revision FROM loadoutcommentrevisions
ORDER BY commentid ASC, revision ASC;

UPDATE loadoutcommentrevisions SET bodycontentid = (
       SELECT contentid FROM editableformattedcontents efc
       WHERE efc.tempid1 = commentid AND efc.tempid2 = revision
);

ALTER TABLE loadoutcommentrevisions DROP COLUMN commentbody;
ALTER TABLE loadoutcommentrevisions DROP COLUMN commentformattedbody;
ALTER TABLE loadoutcommentrevisions ALTER COLUMN bodycontentid SET NOT NULL;

ALTER TABLE loadoutcommentrevisions
  ADD CONSTRAINT loadoutcommentrevisions_bodycontentid_fkey FOREIGN KEY (bodycontentid)
      REFERENCES editableformattedcontents (contentid) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

UPDATE editableformattedcontents SET tempid1 = NULL, tempid2 = NULL;



--
-- Comment replies
--

ALTER TABLE loadoutcommentreplies ADD COLUMN bodycontentid integer;

INSERT INTO editableformattedcontents (rawcontent, filtermask, formattedcontent, tempid1)
SELECT replybody, 9, replyformattedbody, commentreplyid FROM loadoutcommentreplies
ORDER BY commentreplyid ASC;

UPDATE loadoutcommentreplies SET bodycontentid = (
       SELECT contentid FROM editableformattedcontents efc
       WHERE efc.tempid1 = commentreplyid
);

ALTER TABLE loadoutcommentreplies DROP COLUMN replybody;
ALTER TABLE loadoutcommentreplies DROP COLUMN replyformattedbody;
ALTER TABLE loadoutcommentreplies ALTER COLUMN bodycontentid SET NOT NULL;

ALTER TABLE loadoutcommentreplies
  ADD CONSTRAINT loadoutcommentreplies_bodycontentid_fkey FOREIGN KEY (bodycontentid)
      REFERENCES editableformattedcontents (contentid) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

UPDATE editableformattedcontents SET tempid1 = NULL;



--
-- Fitting descriptions
--

DROP VIEW loadoutssearchdata;
DROP VIEW fittingdescriptions;
ALTER TABLE fittings ADD COLUMN descriptioncontentid integer;

INSERT INTO editableformattedcontents (rawcontent, filtermask, formattedcontent, temphash1)
SELECT description, 5, '', fittinghash FROM fittings WHERE description <> '';

UPDATE fittings SET descriptioncontentid = (
       SELECT contentid FROM editableformattedcontents efc
       WHERE efc.temphash1 = fittinghash
);

ALTER TABLE fittings DROP COLUMN description;

ALTER TABLE fittings
  ADD CONSTRAINT fittings_descriptioncontentid_fkey FOREIGN KEY (descriptioncontentid)
      REFERENCES editableformattedcontents (contentid) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

UPDATE editableformattedcontents SET temphash1 = NULL;



ALTER TABLE editableformattedcontents DROP COLUMN tempid1;
ALTER TABLE editableformattedcontents DROP COLUMN tempid2;
ALTER TABLE editableformattedcontents DROP COLUMN temphash1;

-- Recreate the view that needed descriptions

CREATE OR REPLACE VIEW fittingdescriptions AS 
 SELECT d.fittinghash,
    string_agg(d.description, ', '::text) AS descriptions
   FROM (        (        (         SELECT f.fittinghash,
                                    efc.rawcontent AS description
                                   FROM fittings f
				   LEFT JOIN editableformattedcontents efc ON f.descriptioncontentid = efc.contentid
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

CREATE OR REPLACE VIEW loadoutssearchdata AS 
 SELECT l.loadoutid,
        CASE l.visibility
            WHEN 1 THEN accounts.accountid
            ELSE
            CASE l.viewpermission
                WHEN 4 THEN accounts.accountid
                ELSE 0
            END
        END AS restrictedtoaccountid,
        CASE l.viewpermission
            WHEN 3 THEN
            CASE accounts.apiverified
                WHEN true THEN accounts.corporationid
                ELSE (-1)
            END
            ELSE 0
        END AS restrictedtocorporationid,
        CASE l.viewpermission
            WHEN 2 THEN
            CASE accounts.apiverified
                WHEN true THEN accounts.allianceid
                ELSE (-1)
            END
            ELSE 0
        END AS restrictedtoallianceid,
    ( SELECT fat.taglist
           FROM fittingaggtags fat
          WHERE fat.fittinghash = fittings.fittinghash) AS tags,
    ( SELECT fft.typelist
           FROM fittingfittedtypes fft
          WHERE fft.fittinghash = fittings.fittinghash) AS modules,
        CASE accounts.apiverified
            WHEN true THEN accounts.charactername
            ELSE accounts.nickname
        END AS author,
    fittings.name,
    ( SELECT fd.descriptions
           FROM fittingdescriptions fd
          WHERE fd.fittinghash = fittings.fittinghash) AS description,
    loadoutslatestrevision.latestrevision AS revision,
    fittings.hullid AS shipid,
    invtypes.typename AS ship,
    f0.creationdate,
    loadouthistory.updatedate,
    ls.upvotes,
    ls.downvotes,
    ls.score,
    invgroups.groupname::text AS groups,
    fittings.evebuildnumber,
    COALESCE(lcc.count, 0::bigint) AS comments,
    COALESCE(lda.dps, 0::double precision) AS dps,
    COALESCE(lda.ehp, 0::double precision) AS ehp,
    COALESCE(lda.estimatedprice, 0::double precision) AS estimatedprice,
    l.viewpermission
   FROM loadouts l
   JOIN loadoutslatestrevision ON l.loadoutid = loadoutslatestrevision.loadoutid
   JOIN accounts ON l.accountid = accounts.accountid
   JOIN loadouthistory ON loadouthistory.loadoutid = loadoutslatestrevision.loadoutid AND loadouthistory.revision = loadoutslatestrevision.latestrevision
   JOIN fittings ON fittings.fittinghash = loadouthistory.fittinghash
   JOIN loadouthistory l0 ON l0.loadoutid = loadoutslatestrevision.loadoutid AND l0.revision = 1
   JOIN fittings f0 ON f0.fittinghash = l0.fittinghash
   JOIN loadoutscores ls ON ls.loadoutid = l.loadoutid
   JOIN eve.invtypes ON invtypes.typeid = fittings.hullid
   LEFT JOIN loadoutcommentcount lcc ON lcc.loadoutid = l.loadoutid
   LEFT JOIN eve.invgroups ON invgroups.groupid = invtypes.groupid
   LEFT JOIN loadoutdogmaattribs lda ON lda.loadoutid = l.loadoutid;

COMMIT;
