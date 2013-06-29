CREATE VIEW contactsverification AS
    SELECT loadouts.loadoutid, contacts.contactid, contacts.standing, accounts.accountid, accounts.characterid, accounts.corporationid, accounts.allianceid FROM ((contacts LEFT JOIN accounts ON ((contacts.accountid = accounts.accountid))) LEFT JOIN loadouts ON ((loadouts.accountid = accounts.accountid))) WHERE (accounts.apiverified IS TRUE);

ALTER TABLE contactsverification
  OWNER TO osmium;
