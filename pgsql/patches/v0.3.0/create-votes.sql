CREATE TABLE votes
(
  voteid serial NOT NULL,
  fromaccountid integer NOT NULL,
  fromeveaccountid integer NOT NULL,
  fromclientid integer NOT NULL,
  accountid integer NOT NULL,
  creationdate integer NOT NULL,
  cancellableuntil integer,
  reputationgiventodest integer,
  reputationgiventosource integer,
  type integer NOT NULL,
  targettype integer NOT NULL,
  targetid1 integer,
  targetid2 integer,
  targetid3 integer,
  CONSTRAINT votes_pkey PRIMARY KEY (voteid ),
  CONSTRAINT votes_accountid_fkey FOREIGN KEY (accountid)
      REFERENCES accounts (accountid) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT votes_fromaccountid_fkey FOREIGN KEY (fromaccountid)
      REFERENCES accounts (accountid) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT votes_fromclientid_fkey FOREIGN KEY (fromclientid)
      REFERENCES clients (clientid) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT votes_fromeveaccountid_fkey FOREIGN KEY (fromeveaccountid)
      REFERENCES eveaccounts (eveaccountid) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT votes_fromaccountid_type_targettype_targetid1_targetid2_targeti UNIQUE (fromaccountid , type , targettype , targetid1 , targetid2 , targetid3 ),
  CONSTRAINT votes_notaselfvote_check CHECK (fromaccountid <> accountid),
  CONSTRAINT votes_notempty_check CHECK (targetid1 IS NOT NULL OR targetid2 IS NOT NULL OR targetid3 IS NOT NULL)
);

CREATE INDEX votes_accountid_idx
  ON votes
  USING btree
  (accountid );

CREATE INDEX votes_cancellableuntil_idx
  ON votes
  USING btree
  (cancellableuntil );

CREATE INDEX votes_creationdate_idx
  ON votes
  USING btree
  (creationdate );

CREATE INDEX votes_fromaccountid_idx
  ON votes
  USING btree
  (fromaccountid );

CREATE INDEX votes_fromclientid_idx
  ON votes
  USING btree
  (fromclientid );

CREATE INDEX votes_fromeveaccountid_idx
  ON votes
  USING btree
  (fromeveaccountid );

CREATE INDEX votes_targetid1_idx
  ON votes
  USING btree
  (targetid1 );

CREATE INDEX votes_targetid2_idx
  ON votes
  USING btree
  (targetid2 );

CREATE INDEX votes_targetid3_idx
  ON votes
  USING btree
  (targetid3 );

CREATE INDEX votes_targettype_idx
  ON votes
  USING btree
  (targettype );

CREATE INDEX votes_type_idx
  ON votes
  USING btree
  (type );

CREATE VIEW votecount AS
    SELECT count(votes.voteid) AS count, votes.type, votes.targettype, votes.targetid1, votes.targetid2, votes.targetid3 FROM votes GROUP BY votes.type, votes.targettype, votes.targetid1, votes.targetid2, votes.targetid3;

CREATE VIEW loadoutcommentupdownvotes AS
    SELECT c.commentid, (COALESCE(uv.count, (0)::bigint) - COALESCE(dv.count, (0)::bigint)) AS votes, COALESCE(uv.count, (0)::bigint) AS upvotes, COALESCE(dv.count, (0)::bigint) AS downvotes FROM ((loadoutcomments c LEFT JOIN votecount uv ON ((((((uv.type = 1) AND (uv.targettype = 2)) AND (uv.targetid1 = c.commentid)) AND (uv.targetid2 = c.loadoutid)) AND (uv.targetid3 IS NULL)))) LEFT JOIN votecount dv ON ((((((dv.type = 2) AND (dv.targettype = 2)) AND (dv.targetid1 = c.commentid)) AND (dv.targetid2 = c.loadoutid)) AND (dv.targetid3 IS NULL))));

CREATE VIEW loadoutupdownvotes AS
    SELECT l.loadoutid, (COALESCE(uv.count, (0)::bigint) - COALESCE(dv.count, (0)::bigint)) AS votes, COALESCE(uv.count, (0)::bigint) AS upvotes, COALESCE(dv.count, (0)::bigint) AS downvotes FROM ((loadouts l LEFT JOIN votecount uv ON ((((((uv.type = 1) AND (uv.targettype = 1)) AND (uv.targetid1 = l.loadoutid)) AND (uv.targetid2 IS NULL)) AND (uv.targetid3 IS NULL)))) LEFT JOIN votecount dv ON ((((((dv.type = 2) AND (dv.targettype = 1)) AND (dv.targetid1 = l.loadoutid)) AND (dv.targetid2 IS NULL)) AND (dv.targetid3 IS NULL))));
