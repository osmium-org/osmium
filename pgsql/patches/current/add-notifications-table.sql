CREATE TABLE notifications
(
  notificationid serial NOT NULL,
  accountid integer NOT NULL,
  creationdate integer NOT NULL,
  type integer NOT NULL,
  fromaccountid integer,
  targetid1 integer,
  targetid2 integer,
  targetid3 integer,
  CONSTRAINT notifications_pkey PRIMARY KEY (notificationid ),
  CONSTRAINT notifications_accountid_fkey FOREIGN KEY (accountid)
      REFERENCES accounts (accountid) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT notifications_fromaccountid_fkey FOREIGN KEY (fromaccountid)
      REFERENCES accounts (accountid) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);

CREATE INDEX notifications_accountid_idx
  ON notifications
  USING btree
  (accountid );

CREATE INDEX notifications_creationdate_idx
  ON notifications
  USING btree
  (creationdate );

CREATE INDEX notifications_fromaccountid_idx
  ON notifications
  USING btree
  (fromaccountid );

CREATE INDEX notifications_targetid1_idx
  ON notifications
  USING btree
  (targetid1 );

CREATE INDEX notifications_targetid2_idx
  ON notifications
  USING btree
  (targetid2 );

CREATE INDEX notifications_targetid3_idx
  ON notifications
  USING btree
  (targetid3 );

CREATE INDEX notifications_type_idx
  ON notifications
  USING btree
  (type );
