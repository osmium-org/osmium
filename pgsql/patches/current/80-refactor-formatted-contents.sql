BEGIN;

CREATE TABLE editableformattedcontents
(
  contentid serial NOT NULL,
  rawcontent text NOT NULL,
  filtermask integer NOT NULL,
  formattedcontent text,
  tempid1 integer, -- Couldn't find a better way :(
  tempid2 integer,
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



ALTER TABLE editableformattedcontents DROP COLUMN tempid1;
ALTER TABLE editableformattedcontents DROP COLUMN tempid2;
COMMIT;
