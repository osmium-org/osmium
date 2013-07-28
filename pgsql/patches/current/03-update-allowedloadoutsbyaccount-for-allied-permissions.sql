CREATE OR REPLACE VIEW allowedloadoutsbyaccount AS
	SELECT DISTINCT accounts.accountid, loadouts.loadoutid
	FROM loadouts
	JOIN accounts author ON author.accountid = loadouts.accountid
	JOIN contactsverification ON contactsverification.loadoutid = loadouts.loadoutid
	JOIN accounts ON loadouts.viewpermission = 0
	OR loadouts.viewpermission = 1
	OR loadouts.viewpermission = 2 AND accounts.apiverified = true AND author.apiverified = true AND accounts.allianceid = author.allianceid
	OR loadouts.viewpermission = 3 AND accounts.apiverified = true AND author.apiverified = true AND accounts.corporationid = author.corporationid
	OR accounts.accountid = author.accountid
	OR loadouts.viewpermission = 5 AND accounts.apiverified = true AND author.apiverified = true
		AND (contactsverification.accountid = accounts.accountid OR contactsverification.characterid = accounts.characterid OR contactsverification.corporationid = accounts.corporationid OR contactsverification.allianceid = accounts.allianceid OR (contactsverification.contactid = accounts.characterid OR contactsverification.contactid = accounts.corporationid OR contactsverification.contactid = accounts.allianceid) AND contactsverification.standing > 0)
	OR loadouts.viewpermission = 6 AND accounts.apiverified = true AND author.apiverified = true
		AND (contactsverification.accountid = accounts.accountid OR contactsverification.characterid = accounts.characterid OR contactsverification.corporationid = accounts.corporationid OR contactsverification.allianceid = accounts.allianceid OR (contactsverification.contactid = accounts.characterid OR contactsverification.contactid = accounts.corporationid OR contactsverification.contactid = accounts.allianceid) AND contactsverification.standing > 5);

ALTER TABLE allowedloadoutsbyaccount
	OWNER TO osmium;
