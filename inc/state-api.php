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

const CHARACTER_SHEET_ACCESS_MASK = 8;
const CONTACT_LIST_ACCESS_MASK = 16;
const ACCOUNT_STATUS_ACCESS_MASK = 33554432;

const REQUIRED_ACCESS_MASK_WITHOUT_CONTACTS = 33554440; /* CharacterSheet & AccountStatus */
const REQUIRED_ACCESS_MASK_WITH_CONTACTS = 33554456; /* CharacterSheet & AccountStatus & Contacts */



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
			\Osmium\Db\query('BEGIN');

			\Osmium\Db\query_params(
				'UPDATE osmium.eveapikeys SET active = false
				WHERE keyid = $1 AND verificationcode = $2',
				[ $keyid, $vcode ]
			);

			\Osmium\Db\query_params(
				'UPDATE osmium.accounts a SET
				apiverified = false,
				characterid = null, charactername = null,
				corporationid = null, corporationname = null,
				allianceid = null, alliancename = null,
				isfittingmanager = false
				WHERE accountid IN (
				SELECT owneraccountid FROM osmium.eveapikeys eak
				WHERE eak.keyid = $1 AND eak.verificationcode = $2
				) AND a.keyid = $1',
				[ $keyid, $vcode ]
			); /* XXX: also reindex loadouts! */

			$kq = \Osmium\Db\query_params(
				'SELECT DISTINCT owneraccountid
				FROM osmium.eveapikeys
				WHERE keyid = $1 AND verificationcode = $2',
				[ $keyid, $vcode ]
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

	return true;
}


/**
 * Check that a given API key can be used to API-verify an account.
 *
 * @returns true, or a string containing an error message.
 */
function check_api_key_sanity($accountid, $keyid, $vcode, &$characterid = null, &$charactername = null) {
	$api = \Osmium\EveApi\fetch(
		'/account/APIKeyInfo.xml.aspx',
		[ 'keyID' => $keyid, 'vCode' => $vcode ],
		null, $etype, $estr
	);

	if($api === false) {
		return $estr;
	}

	if((string)$api->result->key['type'] !== 'Character') {
	    return 'Invalid key type. Make sure you only select one character.';
	}

	if((int)$api->result->key['accessMask'] !== REQUIRED_ACCESS_MASK_WITH_CONTACTS
	   && (int)$api->result->key['accessMask'] !== REQUIRED_ACCESS_MASK_WITHOUT_CONTACTS ) {
		return 'Incorrect access mask. Please set it to '.REQUIRED_ACCESS_MASK_WITH_CONTACTS
			.' (with ContactList) or '.REQUIRED_ACCESS_MASK_WITHOUT_CONTACTS
			.' (without ContactList), or use the link above.';
	}

	$characterid = (int)$api->result->key->rowset->row['characterID'];
	$charactername = (string)$api->result->key->rowset->row['characterName'];
	if($accountid !== null) {
		list($c) = \Osmium\Db\fetch_row(\Osmium\Db\query_params(
			'SELECT COUNT(accountid) FROM osmium.accounts
			WHERE accountid <> $1 AND (characterid = $2 OR charactername = $3)',
			array($accountid, $characterid, $charactername)
		));

		if($c > 0) {
			return [ 'Character ', [ 'strong', $charactername ], ' is already used by another account.' ];
		}
	}

	return true;
}

/**
 * Check the API key associated with the current account, and update
 * character/corp/alliance values in the database.
 *
 * @returns null on serious error, or a boolean indicating if the user
 * must revalidate his API key.
 */
function check_api_key($a, $initial = false, $timeout = null) {
	if(!isset($a['keyid']) || !isset($a['verificationcode'])
	   || $a['keyid'] === null || $a['verificationcode'] === null) return null;

	$key_id = $a['keyid'];
	$v_code = $a['verificationcode'];
	$must_renew = false;

	$info = \Osmium\EveApi\fetch(
		'/account/APIKeyInfo.xml.aspx',
		[ 'keyID' => $key_id, 'vCode' => $v_code ],
		$timeout, $etype, $estr
	);

	if($info === false) {
		if($etype === \Osmium\EveApi\E_USER) {
			$must_renew = true;
		}

		return null;
	}

	if(!$must_renew && (
		(string)$info->result->key["type"] !== 'Character'
		|| (
			(int)$info->result->key['accessMask'] !== REQUIRED_ACCESS_MASK_WITH_CONTACTS
			&& (int)$info->result->key['accessMask'] !== REQUIRED_ACCESS_MASK_WITHOUT_CONTACTS
		) || (
			!$initial && (int)$info->result->key->rowset->row['characterID'] != $a['characterid']
		)
	)) {
		$must_renew = true;
	}

	/* XXX: some of this is duplicated in register_eve_api_key(), get rid of it HERE */
	if($must_renew) {
		\Osmium\Db\query_params('UPDATE osmium.accounts SET
		characterid = null, charactername = null,
		corporationid = null, corporationname = null,
		allianceid = null, alliancename = null,
		isfittingmanager = false, apiverified = false
		WHERE accountid = $1', array($a['accountid']));

		if(!$initial && $a['apiverified'] == 't') {
			/* Notify the user his API key broke down without user intervention */
			\Osmium\Notification\add_notification(
				\Osmium\Notification\NOTIFICATION_TYPE_ACCOUNT_API_KEY_DISABLED,
				null,
				$a['accountid'],
				$key_id
			);
		}

		$a['characterid'] = null;
		$a['charactername'] = null;
		$a['corporationid'] = null;
		$a['corporationname'] = null;
		$a['allianceid'] = null;
		$a['alliancename'] = null;
		$a['apiverified'] = 'f';
	} else if(isset($a['apiverified']) && $a['apiverified'] === 't') {
		$character_id = (int)$info->result->key->rowset->row['characterID'];

		$cinfo = \Osmium\State\get_character_info($character_id, $a, $timeout);
		if($cinfo === false) {
			/* API unavailable? */
			return null;
		}

		list($character_name,
		     $corporation_id, $corporation_name,
		     $alliance_id, $alliance_name,
		     $is_fitting_manager) = $cinfo;

		if($character_id != $a['characterid']
		   || $character_name != $a['charactername']
		   || $corporation_id != $a['corporationid']
		   || $corporation_name != $a['corporationname']
		   || $alliance_id != $a['allianceid']
		   || $alliance_name != $a['alliancename']
		   || $is_fitting_manager != ($a['isfittingmanager'] === 't')) {

			\Osmium\Db\query_params('UPDATE osmium.accounts SET
			characterid = $1, charactername = $2,
			corporationid = $3, corporationname = $4,
		    allianceid = $5, alliancename = $6,
			isfittingmanager = $7
			WHERE accountid = $8', array($character_id, $character_name,
			                             $corporation_id, $corporation_name,
			                             $alliance_id, $alliance_name,
			                             $is_fitting_manager ? 't' : 'f',
			                             $a['accountid']));

			$a['characterid'] = $character_id;
			$a['charactername'] = $character_name;
			$a['corporationid'] = $corporation_id;
			$a['corporationname'] = $corporation_name;
			$a['allianceid'] = $alliance_id;
			$a['alliancename'] = $alliance_name;
			$a['isfittingmanager'] = $is_fitting_manager ? 't' : 'f';
		}

		if((int)$info->result->key['accessMask'] === REQUIRED_ACCESS_MASK_WITH_CONTACTS) {
			/* Only update the contact list if the key actually allows it */
			$ret = update_character_contactlist($a, $timeout);
			if($ret === false) return null;
		} else {
			/* Be safe, the access mask may have changed since the
			 * last update and there may be stale contacts */
			\Osmium\Db\query_params(
				'DELETE FROM osmium.contacts WHERE accountid = $1',
				array($a['accountid'])
			);
		}
	}

	\Osmium\State\put_state('a', $a);
	return $must_renew;
}

function get_character_info($character_id, $a, $timeout = null) {
	$char_info = \Osmium\EveApi\fetch(
		'/eve/CharacterInfo.xml.aspx',
		[ 'characterID' => $character_id ],
		$timeout
	);
	if($char_info === false) return false;
  
	$character_name = (string)$char_info->result->characterName;
	$corporation_id = (int)$char_info->result->corporationID;
	$corporation_name = (string)$char_info->result->corporation;
	$alliance_id = (int)$char_info->result->allianceID;
	$alliance_name = (string)$char_info->result->alliance;
  
	if($alliance_id == 0) $alliance_id = null;
	if($alliance_name == '') $alliance_name = null;

	$char_sheet = \Osmium\EveApi\fetch(
		'/char/CharacterSheet.xml.aspx',
		array(
			'characterID' => $character_id,
			'keyID' => $a['keyid'],
			'vCode' => $a['verificationcode'],
		),
		$timeout
	);

	if($char_sheet === false) {
		return false;
	}

	$is_fitting_manager = false;
	foreach(($char_sheet->result->rowset ?: array()) as $rowset) {
		if((string)$rowset['name'] != 'corporationRoles') continue;

		foreach($rowset->children() as $row) {
			$name = (string)$row['roleName'];
			if($name == 'roleFittingManager' || $name == 'roleDirector') {
				/* FIXME: roleFittingManager may be implicitly granted by other roles. */
				$is_fitting_manager = true;
				break;
			}
		}

		break;
	}
  
	return array($character_name, $corporation_id, $corporation_name, $alliance_id, $alliance_name, (int)$is_fitting_manager);
}

function update_character_contactlist($a, $timeout = null) {
	$char_contactlist = \Osmium\EveApi\fetch(
		'/char/ContactList.xml.aspx',
		array(
			'characterID' => $a['characterid'],
			'keyID' => $a['keyid'],
			'vCode' => $a['verificationcode'],
		),
		$timeout
	);

	if($char_contactlist === false) {
		return false;
	}

	\Osmium\Db\query('BEGIN');
	\Osmium\Db\query_params(
		'DELETE FROM osmium.contacts WHERE accountid = $1',
		array($a['accountid'])
	);
  
	foreach(($char_contactlist->result->rowset ?: array()) as $rowset) {
		foreach($rowset->children() as $row) {
			if((float)$row['standing'] > 0) {
				\Osmium\Db\query_params(
					'INSERT INTO osmium.contacts (accountid, contactid, standing) VALUES ($1, $2, $3)',
					array(
						$a['accountid'],
						(int)$row['contactID'],
						(float)$row['standing'],
					)
				);
			}
		}
	}

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
