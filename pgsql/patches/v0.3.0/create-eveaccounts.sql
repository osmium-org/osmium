CREATE TABLE eveaccounts
(
  eveaccountid serial NOT NULL,
  creationdate integer NOT NULL,
  CONSTRAINT eveaccounts_pkey PRIMARY KEY (eveaccountid ),
  CONSTRAINT eveaccounts_creationdate_uniq UNIQUE (creationdate )
);
