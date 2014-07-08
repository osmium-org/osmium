<?php
/* Osmium
 * Copyright (C) 2012, 2013, 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Osmium\State;

const CHARACTER_SHEET_ACCESS_MASK = 8; /* Used to determine whether character is a fitting manager or not. */
const CONTACT_LIST_ACCESS_MASK = 16; /* For standing-based permissions. */
const ACCOUNT_STATUS_ACCESS_MASK = 33554432; /* For checking alts in votes. */



/* Unverify an account. Best wrapped up in a transaction. */
function unverify_account($accountid) {
	/* Delete stale contacts. */
	\Osmium\Db\query_params(
		'DELETE FROM osmium.contacts WHERE accountid = $1',
		[ $accountid ]
	);

	/* Mark the account as non-API verified, but keep the
	 * character ID for future password resets. */
	\Osmium\Db\query_params(
		'UPDATE osmium.accounts a SET
		apiverified = false, keyid = null,
		corporationid = null, corporationname = null,
		allianceid = null, alliancename = null,
		isfittingmanager = false
		WHERE accountid = $1',
		[ $accountid ]
	);

	/* XXX: also reindex loadouts of affected accounts! */
}

/* Mark the supplied API key as inactive. */
function disable_eve_api_key($keyid, $vcode, $unverifyaccounts = true) {
	\Osmium\Db\query('BEGIN');

	$p = [ $keyid, $vcode ];

	\Osmium\Db\query_params(
		'UPDATE osmium.eveapikeys SET active = false
		WHERE keyid = $1 AND verificationcode = $2',
	    $p
	);

	if($unverifyaccounts) {
		$aq = \Osmium\Db\query_params(
			'SELECT accountid FROM osmium.accounts a
			JOIN osmium.eveapikeys eak ON eak.keyid = a.keyid AND eak.owneraccountid = a.accountid
			WHERE a.keyid = $1 AND eak.verificationcode = $2',
			$p
		);

		while($arow = \Osmium\Db\fetch_row($aq)) {
			/* Yes, queries in a loop. Probably okay since it should
			 * always match at most one account. */
			unverify_account($arow[0]);
		}
	}

	$kq = \Osmium\Db\query_params(
		'SELECT DISTINCT owneraccountid
		FROM osmium.eveapikeys
		WHERE keyid = $1 AND verificationcode = $2',
		$p
	);
	while($krow = \Osmium\Db\fetch_row($kq)) {
		\Osmium\Notification\add_notification(
			\Osmium\Notification\NOTIFICATION_TYPE_ACCOUNT_API_KEY_DISABLED,
			null,
			$krow[0],
			$keyid
		);
	}

	\Osmium\Db\query('COMMIT');
}


/*
 * Try to associate an EVE API key with an account. Checks that the
 * API key works and updates (or inserts) it in the eveapikeys table.
 */
function register_eve_api_key($accountid, $keyid, $vcode, &$etype = null, &$estr = null) {
	$keyinfo = \Osmium\EveApi\fetch(
		'/account/APIKeyInfo.xml.aspx',
		[ 'keyID' => $keyid, 'vCode' => $vcode ],
		null, $etype, $estr
	);

	$exists = \Osmium\Db\fetch_row(\Osmium\Db\query_params(
		'SELECT COUNT(owneraccountid) FROM osmium.eveapikeys
		WHERE owneraccountid = $1 AND keyid = $2',
		[ $accountid, $keyid ]
	))[0];

	if($keyinfo === false) {
		if($exists && $etype === \Osmium\EveApi\E_USER) {
			/* XXX: don't be so radical, try at least 2-3 time with a
			 * bit of delay inbetween */

			/* Mark the key(s) as inactive since it very likely needs
			 * manual fixing by the owner */
			disable_eve_api_key($keyid, $vcode);
		}

		return false;
	}

	$expires = (string)$keyinfo->result->key['expires'];
	$expires = ($expires === '') ? null : strtotime($expires);
	$t = time();

	if($expires !== null && $expires < $t) {
		$etype = \Osmium\EveApi\E_USER;
		$estr = 'API key is already expired';
		return false;
	}

	$mask = (int)$keyinfo->result->key['accessMask'];

	if($exists) {
		\Osmium\Db\query_params(
			'UPDATE eveapikeys
			SET verificationcode = $1, active = true, updatedate = $2, expirationdate = $3, mask = $4
		    WHERE owneraccountid = $5 AND keyid = $6',
			[
				$vcode, $t,
				$expires, $mask,
				$accountid, $keyid,
			]
		);
	} else {
		\Osmium\Db\query_params(
			'INSERT INTO eveapikeys
			(owneraccountid, keyid, verificationcode, active, creationdate, updatedate, expirationdate, mask)
			VALUES ($1, $2, $3, true, $4, $5, $6, $7)',
			[
				$accountid, $keyid, $vcode,
				$t, $t,
				$expires, $mask,
			]
		);
	}

	return $keyinfo;
}

/* Try to API-verify an account with a given API key. */
function register_eve_api_key_account_auth($accountid, $keyid, $vcode, &$etype = null, &$estr = null) {
	\Osmium\Db\query('BEGIN');

	$keyinfo = register_eve_api_key($accountid, $keyid, $vcode, $etype, $estr);
	if($keyinfo === false) return false;

	$mask = (int)$keyinfo->result->key['accessMask'];
	if(!($mask & ACCOUNT_STATUS_ACCESS_MASK)) {
		$etype = \Osmium\EveApi\E_USER;
		$estr = 'Incorrect access mask. Needs at least AccountStatus.';
		return false;
	}

	$ktype = (string)$keyinfo->result->key['type'];
	if($ktype === 'Character') {
		$characterid = (int)$keyinfo->result->key->rowset->row['characterID'];
		$charactername = (string)$keyinfo->result->key->rowset->row['characterName'];
	} else if($ktype === 'Account') {
		$characterid = $charactername = null;

		/* Pick the oldest character by default… */
		foreach($keyinfo->result->key->rowset->row as $char) {
			$nc = (int)$char['characterID'];

			if($characterid === null || $nc < $characterid) {
				$characterid = $nc;
				$charactername = (string)$char['characterName'];
			}
		}

		if($characterid === null) {
			$etype = \Osmium\EveApi\E_USER;
			$estr = 'Requires at least one character to be on the account.';
			return false;
		}
	} else {
		$etype = \Osmium\EveApi\E_USER;
		$estr = 'Incorrect key type. Make sure "Type" is set to "Character".';
		return false;
	}

	$alreadyused = \Osmium\Db\fetch_row(\Osmium\Db\query_params(
		'SELECT COUNT(accountid)
		FROM osmium.accounts
		WHERE characterid = $1 AND accountid <> $2',
		[ $characterid, $accountid ]
	))[0];

	if($alreadyused) {
		$etype = \Osmium\EveApi\E_USER;
		$estr = 'Character '.$charactername.' is already used by another account. Use the password reset form if you forgot your credentials.';
		return false;
	}

	$charinfo = \Osmium\EveApi\fetch(
		'/eve/CharacterInfo.xml.aspx',
		[ 'characterID' => $characterid ],
		null, $etype, $estr
	);

	if($charinfo === false) {
		return false;
	}

	if($mask & CHARACTER_SHEET_ACCESS_MASK) {
		$charsheet = \Osmium\EveApi\fetch(
			'/char/CharacterSheet.xml.aspx',
			[
				'characterID' => $characterid,
				'keyID' => $keyid,
				'vCode' => $vcode,
			],
			null, $etype, $estr
		);

		if($charsheet === false) {
			return false;
		}
	} else {
		$charsheet = false;
	}

	if($mask & CONTACT_LIST_ACCESS_MASK) {
		$contactlist = \Osmium\EveApi\fetch(
			'/char/ContactList.xml.aspx',
			[
				'characterID' => $characterid,
				'keyID' => $keyid,
				'vCode' => $vcode,
			],
			null, $etype, $estr
		);

		if($contactlist === false) {
			return false;
		}
	} else {
		$contactlist = false;
	}

	/* There is a potential race condition here, where someone else
	 * tries to verify the same character while the transaction below
	 * is running. The unique constraint on accounts.characterid will
	 * prevent it in the very rare cases where it would happen. */

	/* API key looks good- API verify the account now */

	\Osmium\Db\query_params(
		'UPDATE osmium.accounts
		SET apiverified = true, keyid = $1,
		characterid = $2, charactername = $3,
		corporationid = $4, corporationname = $5,
		allianceid = $6, alliancename = $7,
		isfittingmanager = false
		WHERE accountid = $8',
		[
			$keyid,
			$characterid,
			$charactername,
			(int)$charinfo->result->corporationID,
			(string)$charinfo->result->corporation,
			(int)$charinfo->result->allianceID === 0 ? null : (int)$charinfo->result->allianceID,
			(string)$charinfo->result->alliance === '' ? null : (string)$charinfo->result->alliance,
			$accountid,
		]
	);

	if($charsheet !== false) {
		$isfm = false;

		foreach($charsheet->result->rowset as $rowset) {
			if((string)$rowset['name'] !== 'corporationRoles') continue;

			foreach($rowset->row as $row) {
				$name = (string)$row['roleName'];
				if($name === 'roleFittingManager' || $name === 'roleDirector') {
					/* FIXME: other roles may implicitely grand fitting manager */
					$isfm = true;
					break;
				}
			}
		}

		if($isfm) {
			\Osmium\Db\query_params(
				'UPDATE osmium.accounts SET isfittingmanager = true
				WHERE accountid = $1',
				[ $accountid ]
			);
		}
	}

	\Osmium\Db\query_params(
		'DELETE FROM osmium.contacts WHERE accountid = $1',
		[ $accountid ]
	);

	if($contactlist !== false) {
		static $insertprepared = false;

		if($insertprepared === false) {
			$insertprepared = true;
			\Osmium\Db\prepare(
				'insert_contact',
				'INSERT INTO osmium.contacts (accountid, contactid, standing) VALUES ($1, $2, $3)'
			);
		}

		foreach($contactlist->result->rowset as $rowset) {
			foreach($rowset->row as $row) {
				if((float)$row['standing'] > 0) {
					\Osmium\Db\execute('insert_contact', [
						$accountid,
						(int)$row['contactID'],
						(float)$row['standing'],
					]);
				}
			}
		}
	}

	\Osmium\Db\query('COMMIT');
	return true;
}

/* Try to API-verify an account from a CCP OAuth2 character.
 *
 * @note This function does not check that the characterid comes from
 * CCP's OAuth2 service.
 */
function register_ccp_oauth_character_account_auth($accountid, $characterid, &$etype = null, &$estr = null) {
	$charinfo = \Osmium\EveApi\fetch(
		'/eve/CharacterInfo.xml.aspx',
		[ 'characterID' => $characterid ],
		null, $etype, $estr
	);

	if($charinfo === false) {
		return false;
	}

	$alreadyused = \Osmium\Db\fetch_row(\Osmium\Db\query_params(
		'SELECT COUNT(accountid)
		FROM osmium.accounts
		WHERE characterid = $1 AND accountid <> $2',
		[ $characterid, $accountid ]
	))[0];

	if($alreadyused) {
		$etype = \Osmium\EveApi\E_USER;
		$estr = 'Character '.$charactername.' is already used by another account. Use the password reset form if you forgot your credentials.';
		return false;
	}

	\Osmium\Db\query('BEGIN');
	\Osmium\Db\query_params(
		'UPDATE osmium.accounts
		SET apiverified = true, keyid = $1,
		characterid = $2, charactername = $3,
		corporationid = $4, corporationname = $5,
		allianceid = $6, alliancename = $7,
		isfittingmanager = false
		WHERE accountid = $8',
		[
			null,
			$characterid,
			(string)$charinfo->result->characterName,
			(int)$charinfo->result->corporationID,
			(string)$charinfo->result->corporation,
			(int)$charinfo->result->allianceID === 0 ? null : (int)$charinfo->result->allianceID,
			(string)$charinfo->result->alliance === '' ? null : (string)$charinfo->result->alliance,
			$accountid,
		]
	);

	\Osmium\Db\query_params(
		'DELETE FROM osmium.contacts WHERE accountid = $1',
		[ $accountid ]
	);
	\Osmium\Db\query('COMMIT');
	return true;
}

/**
 * Check whether the current account matches the whitelist.
 */
function check_whitelist($a) {
	if($a['apiverified'] !== 't') return false;

	$ids = array_flip(\Osmium\get_ini_setting('whitelisted_ids', []));

	foreach([ 'characterid', 'corporationid', 'allianceid' ] as $t) {
		if(isset($a[$t]) && $a[$t] > 0 && isset($ids[$a[$t]])) {
			return true;
		}
	}

	foreach([ 'corp', 'char' ] as $cltype) {

		foreach(\Osmium\get_ini_setting($cltype.'_contactlist', []) as $k => $cl) {
			list($keyid, $vcode, $threshold) = explode(':', $cl, 3);
			$threshold = (float)$threshold;

			$ki = \Osmium\EveApi\fetch('/account/APIKeyInfo.xml.aspx', [
				'keyID' => $keyid,
				'vCode' => $vcode,
			]);

			if($ki === false) {
				/* XXX: be more resilient for something as critical as this */
				trigger_error($cltype.'_whitelist('.$k.'): could not fetch key info', E_USER_NOTICE);
				continue;
			}

			$charid = (int)$ki->result->key->rowset->row['characterID'];
			$mask = (int)$ki->result->key['accessMask'];

			if(!($mask & CONTACT_LIST_ACCESS_MASK)) {
				trigger_error($cltype.'_whitelist('.$k.'): no access to contact list', E_USER_NOTICE);
				continue;
			}

			$x = \Osmium\EveApi\fetch('/'.$cltype.'/ContactList.xml.aspx', [
				'keyID' => $keyid,
				'vCode' => $vcode,
				'characterID' => $charid,
			]);

			if($x === false) {
				/* XXX: be more resilient for something as critical as this */
				trigger_error($cltype.'_whitelist('.$k.'): could not fetch contact list', E_USER_NOTICE);
				continue;
			}

			foreach($x->result->rowset as $rs) {
				foreach($rs->children() as $row) {
					if((float)$row['standing'] < $threshold) continue;
					$id = $row['contactID'];

					foreach([ 'characterid', 'corporationid', 'allianceid' ] as $t) {
						if(isset($a[$t]) && $a[$t] == $id) {
							return true;
						}
					}
				}
			}
		}

	}

	return false;
}
