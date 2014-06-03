CREATE TABLE editableformattedcontents
(
contentid serial NOT NULL,
mutable boolean NOT NULL DEFAULT true,
rawcontent text NOT NULL,
filtermask integer NOT NULL,
formattedcontent text,
CONSTRAINT editableformattedcontents_pkey PRIMARY KEY (contentid)
)
WITH (
OIDS=FALSE
);



CREATE INDEX editableformattedcontents_mutable_idx ON editableformattedcontents (mutable);

CREATE INDEX editableformattedcontents_nonmutable_rawcontent_uniq
ON editableformattedcontents (md5(rawcontent)) WHERE mutable = false;



ALTER TABLE loadoutcommentrevisions ADD COLUMN bodycontentid integer;
ALTER TABLE loadoutcommentreplies ADD COLUMN bodycontentid integer;
ALTER TABLE fittings ADD COLUMN descriptioncontentid integer;
ALTER TABLE fittingpresets ADD COLUMN descriptioncontentid integer;
ALTER TABLE fittingchargepresets ADD COLUMN descriptioncontentid integer;
ALTER TABLE fittingdronepresets ADD COLUMN descriptioncontentid integer;



ALTER TABLE loadoutcommentrevisions
ADD CONSTRAINT loadoutcommentrevisions_bodycontentid_fkey FOREIGN KEY (bodycontentid)
REFERENCES editableformattedcontents (contentid) MATCH SIMPLE
ON UPDATE NO ACTION ON DELETE NO ACTION;

ALTER TABLE loadoutcommentreplies
ADD CONSTRAINT loadoutcommentreplies_bodycontentid_fkey FOREIGN KEY (bodycontentid)
REFERENCES editableformattedcontents (contentid) MATCH SIMPLE
ON UPDATE NO ACTION ON DELETE NO ACTION;

ALTER TABLE fittings
ADD CONSTRAINT fittings_descriptioncontentid_fkey FOREIGN KEY (descriptioncontentid)
REFERENCES editableformattedcontents (contentid) MATCH SIMPLE
ON UPDATE NO ACTION ON DELETE NO ACTION;

ALTER TABLE fittingpresets
ADD CONSTRAINT fittingpresets_descriptioncontentid_fkey FOREIGN KEY (descriptioncontentid)
REFERENCES editableformattedcontents (contentid) MATCH SIMPLE
ON UPDATE NO ACTION ON DELETE NO ACTION;

ALTER TABLE fittingchargepresets
ADD CONSTRAINT fittingchargepresets_descriptioncontentid_fkey FOREIGN KEY (descriptioncontentid)
REFERENCES editableformattedcontents (contentid) MATCH SIMPLE
ON UPDATE NO ACTION ON DELETE NO ACTION;

ALTER TABLE fittingdronepresets
ADD CONSTRAINT fittingdronepresets_descriptioncontentid_fkey FOREIGN KEY (descriptioncontentid)
REFERENCES editableformattedcontents (contentid) MATCH SIMPLE
ON UPDATE NO ACTION ON DELETE NO ACTION;
