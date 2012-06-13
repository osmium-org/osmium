<?php
/* Osmium
 * Copyright (C) 2012 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\Fit;

/**
 * Get an array that contains all the significant data of a fit. Used
 * to compare fits.
 */
function get_unique($fit) {
	$unique = array();

	$unique['ship'] = (int)$fit['ship']['typeid'];

	$unique['metadata'] = array(
		'name' => $fit['metadata']['name'],
		'description' => $fit['metadata']['description'],
		'tags' => $fit['metadata']['tags'],
		);

	foreach($fit['presets'] as $presetid => $preset) {
		$uniquep = array(
			'name' => $preset['name'],
			'description' => $preset['description']
			);

		foreach($preset['modules'] as $type => $d) {
			foreach($d as $index => $module) {
				/* Use the actual order of the array, discard indexes */
				$uniquep['modules'][$type][] = array((int)$module['typeid'], (int)$module['state']);
			}
		}

		foreach($preset['chargepresets'] as $chargepreset) {
			$uniquecp = array(
				'name' => $chargepreset['name'],
				'description' => $chargepreset['description']
				);

			foreach($chargepreset['charges'] as $type => $a) {
				foreach($a as $index => $charge) {
					$uniquecp['charges'][$type][] = (int)$charge['typeid'];
				}
			}

			$uniquep['chargepresets'][] = $uniquecp;
		}

		$unique['presets'][] = $uniquep;
	}

	foreach($fit['dronepresets'] as $dronepreset) {
		$uniquedp = array(
			'name' => $dronepreset['name'],
			'description' => $dronepreset['description']
			);

		foreach($dronepreset['drones'] as $drone) {
			$uniquedp['drones'][] = array((int)$drone['typeid'],
			                              (int)$drone['quantityinbay'],
			                              (int)$drone['quantityinspace']);
		}

		$unique['dronepresets'][] = $uniquedp;
	}

	/* Ensure equality if key ordering is different */
	ksort_rec($unique);
	sort($unique['metadata']['tags']); /* tags should be ordered by value */

	return $unique;
}

function ksort_rec(array &$array) {
	ksort($array);
	foreach($array as &$v) {
		if(is_array($v)) ksort_rec($v);
	}
}

/**
 * Get the hash ("fittinghash" in the database) of a fit. If two fits
 * have the same hash, they are essentially the same, not counting for
 * example order of tags, order of module indexes etc.
 */
function get_hash($fit) {
	return sha1(serialize(get_unique($fit)));
}

/**
 * Insert a fitting in the database, if necessary. The fitting hash
 * will be updated. The function will try to avoid inserting
 * duplicates, so it is safe to call it multiple times even if no
 * changes were made.
 *
 * The process is atomic, that is, all the fit gets inserted or
 * nothing is inserted at all, so integrity is always enforced.
 */
function commit_fitting(&$fit) {
	$fittinghash = get_hash($fit);

	$fit['metadata']['hash'] = $fittinghash;
  
	list($count) = \Osmium\Db\fetch_row(\Osmium\Db\query_params('SELECT COUNT(fittinghash) FROM osmium.fittings WHERE fittinghash = $1', array($fittinghash)));
	if($count == 1) {
		/* Do nothing! */
		return;
	}

	/* Insert the new fitting */
	\Osmium\Db\query('BEGIN;');
	\Osmium\Db\query_params('INSERT INTO osmium.fittings (fittinghash, name, description, hullid, creationdate) VALUES ($1, $2, $3, $4, $5)', array($fittinghash, $fit['metadata']['name'], $fit['metadata']['description'], $fit['ship']['typeid'], time()));
  
	foreach($fit['metadata']['tags'] as $tag) {
		\Osmium\Db\query_params('INSERT INTO osmium.fittingtags (fittinghash, tagname) VALUES ($1, $2)', array($fittinghash, $tag));
	}
  
	$presetid = 0;
	foreach($fit['presets'] as $preset) {
		\Osmium\Db\query_params('INSERT INTO osmium.fittingpresets (fittinghash, presetid, name, description) VALUES ($1, $2, $3, $4)', array($fittinghash, $presetid, $preset['name'], $preset['description']));

		$module_order = array();
		foreach($preset['modules'] as $type => $data) {
			$z = 0;
			foreach($data as $index => $module) {
				$module_order[$type][$index] = $z;
				\Osmium\Db\query_params('INSERT INTO osmium.fittingmodules (fittinghash, presetid, slottype, index, typeid, state) VALUES ($1, $2, $3, $4, $5, $6)', array($fittinghash, $presetid, $type, $z, $module['typeid'], $module['state']));
				++$z;
			}
		}
  
		$cpid = 0;
		foreach($preset['chargepresets'] as $chargepreset) {
			\Osmium\Db\query_params('INSERT INTO osmium.fittingchargepresets (fittinghash, presetid, chargepresetid, name, description) VALUES ($1, $2, $3, $4, $5)', array($fittinghash, $presetid, $cpid, $chargepreset['name'], $chargepreset['description']));

			foreach($chargepreset['charges'] as $type => $d) {
				foreach($d as $index => $charge) {
					if(!isset($module_order[$type][$index])) continue;
					$z = $module_order[$type][$index];

					\Osmium\Db\query_params('INSERT INTO osmium.fittingcharges (fittinghash, presetid, chargepresetid, slottype, index, typeid) VALUES ($1, $2, $3, $4, $5, $6)', array($fittinghash, $presetid, $cpid, $type, $z, $charge['typeid']));
				}
			}

			++$cpid;
		}

		++$presetid;
	}
  
	$dpid = 0;
	foreach($fit['dronepresets'] as $dronepreset) {
		\Osmium\Db\query_params('INSERT INTO osmium.fittingdronepresets (fittinghash, dronepresetid, name, description) VALUES ($1, $2, $3, $4)', array($fittinghash, $dpid, $dronepreset['name'], $dronepreset['description']));

		foreach($dronepreset['drones'] as $drone) {
			\Osmium\Db\query_params('INSERT INTO osmium.fittingdrones (fittinghash, dronepresetid, typeid, quantityinbay, quantityinspace) VALUES ($1, $2, $3, $4, $5)', array($fittinghash, $dpid, $drone['typeid'], $drone['quantityinbay'], $drone['quantityinspace']));
		}

		++$dpid;
	}
  
	\Osmium\Db\query('COMMIT;');
}

/**
 * Insert a loadout in the database.
 *
 * This function will create a new loadout if the fit does not have a
 * loadoutid, or this will insert a new revision of the loadout if it
 * already exists.
 *
 * @param $ownerid The accountid of the owner of the loadout
 * 
 * @param $accountid The accountid of the person updating the loadout
 */
function commit_loadout(&$fit, $ownerid, $accountid) {
	commit_fitting($fit);

	$loadoutid = null;
	$password = ($fit['metadata']['view_permission'] == VIEW_PASSWORD_PROTECTED) ? $fit['metadata']['password'] : '';

	if(!isset($fit['metadata']['loadoutid'])) {
		/* Insert a new loadout */
		list($loadoutid) = \Osmium\Db\fetch_row(\Osmium\Db\query_params('INSERT INTO osmium.loadouts (accountid, viewpermission, editpermission, visibility, passwordhash) VALUES ($1, $2, $3, $4, $5) RETURNING loadoutid', array($ownerid, $fit['metadata']['view_permission'], $fit['metadata']['edit_permission'], $fit['metadata']['visibility'], $password)));

		$fit['metadata']['loadoutid'] = $loadoutid;
	} else {
		/* Update a loadout */
		$loadoutid = $fit['metadata']['loadoutid'];

		\Osmium\Db\query_params('UPDATE osmium.loadouts SET accountid = $1, viewpermission = $2, editpermission = $3, visibility = $4, passwordhash = $5 WHERE loadoutid = $6', array($ownerid, $fit['metadata']['view_permission'], $fit['metadata']['edit_permission'], $fit['metadata']['visibility'], $password, $loadoutid));
	}

	/* If necessary, insert the appropriate history entry */
	$row = \Osmium\Db\fetch_row(\Osmium\Db\query_params('SELECT fittinghash, loadoutslatestrevision.latestrevision 
  FROM osmium.loadoutslatestrevision 
  JOIN osmium.loadouthistory ON (loadoutslatestrevision.loadoutid = loadouthistory.loadoutid 
                             AND loadoutslatestrevision.latestrevision = loadouthistory.revision) 
  WHERE loadoutslatestrevision.loadoutid = $1', array($loadoutid)));
	if($row === false || $row[0] != $fit['metadata']['hash']) {
		$nextrev = ($row === false) ? 1 : ($row[1] + 1);
		\Osmium\Db\query_params('INSERT INTO osmium.loadouthistory 
    (loadoutid, revision, fittinghash, updatedbyaccountid, updatedate) 
    VALUES ($1, $2, $3, $4, $5)', array($loadoutid, $nextrev, $fit['metadata']['hash'], $accountid, time()));

		$fit['metadata']['revision'] = $nextrev;
	}

	$fit['metadata']['accountid'] = $ownerid;

	if($fit['metadata']['visibility'] == VISIBILITY_PRIVATE) {
		\Osmium\Search\unindex($loadoutid);
	} else {
		\Osmium\Search\index(
			\Osmium\Db\fetch_assoc(
				\Osmium\Search\query_select_searchdata('WHERE loadoutid = $1', 
				                                       array($loadoutid))));
	}
}

/**
 * Get a loadout from the database, given its loadoutid. Returns an
 * array that can be used with all functions using $fit.
 *
 * This is pretty expensive, so the result is cached, unless a
 * revision number is specified.
 *
 * @param $loadoutid the loadoutid of the loadout to fetch
 *
 * @param $revision Revision number to get, if null the latest
 * revision is used
 *
 * @returns a $fit-compatible variable, or false if unrecoverable
 * errors happened.
 */
function get_fit($loadoutid, $revision = null) {
	if($revision === null && ($cache = \Osmium\State\get_cache('loadout-'.$loadoutid, null)) !== null) {
		return $cache;
	}

	if($revision === null) {
		/* Use latest revision */
		$row = \Osmium\Db\fetch_row(\Osmium\Db\query_params('SELECT latestrevision FROM osmium.loadoutslatestrevision WHERE loadoutid = $1', array($loadoutid)));
		if($row === false) return false;
		$revision = $row[0];
	}

	$loadout = \Osmium\Db\fetch_assoc(\Osmium\Db\query_params('SELECT accountid, viewpermission, editpermission, visibility, passwordhash FROM osmium.loadouts WHERE loadoutid = $1', array($loadoutid)));

	if($loadout === false) return false;

	$fitting = \Osmium\Db\fetch_assoc(\Osmium\Db\query_params('SELECT fittings.fittinghash AS hash, name, description, hullid, creationdate, revision FROM osmium.loadouthistory JOIN osmium.fittings ON loadouthistory.fittinghash = fittings.fittinghash WHERE loadoutid = $1 AND revision = $2', array($loadoutid, $revision)));

	if($fitting === false) return false;

	create($fit);
	select_ship($fit, $fitting['hullid']);

	$fit['metadata']['loadoutid'] = $loadoutid;
	$fit['metadata']['hash'] = $fitting['hash'];
	$fit['metadata']['name'] = $fitting['name'];
	$fit['metadata']['description'] = $fitting['description'];
	$fit['metadata']['view_permission'] = $loadout['viewpermission'];
	$fit['metadata']['edit_permission'] = $loadout['editpermission'];
	$fit['metadata']['visibility'] = $loadout['visibility'];
	$fit['metadata']['password'] = $loadout['passwordhash'];
	$fit['metadata']['revision'] = $fitting['revision'];
	$fit['metadata']['creation_date'] = $fitting['creationdate'];
	$fit['metadata']['accountid'] = $loadout['accountid'];

	$fit['metadata']['tags'] = array();
	$tagq = \Osmium\Db\query_params('SELECT tagname FROM osmium.fittingtags WHERE fittinghash = $1', array($fit['metadata']['hash']));
	while($r = \Osmium\Db\fetch_row($tagq)) {
		$fit['metadata']['tags'][] = $r[0];
	}

	/* It can be done with only one query, but the code would be
	 * monstrously complex. The result is cached anyway, so
	 * performance isn't an absolute requirement as long as it's fast
	 * enough. */

	$firstpreset = true;
	$presetsq = \Osmium\Db\query_params('SELECT presetid, name, description FROM osmium.fittingpresets WHERE fittinghash = $1 ORDER BY presetid ASC', array($fit['metadata']['hash']));
	while($preset = \Osmium\Db\fetch_assoc($presetsq)) {
		if($firstpreset === true) {
			/* Edit the default preset instead of creating a new preset */
			$fit['modulepresetname'] = $preset['name'];
			$fit['modulepresetdesc'] = $preset['description'];

			$firstpreset = false;
		} else {
			$presetid = create_preset($fit, $preset['name'], $preset['description']);
			use_preset($fit, $presetid);
		}

		$modules = array();
		$modulesq = \Osmium\Db\query_params('SELECT slottype, typeid, state FROM osmium.fittingmodules WHERE fittinghash = $1 AND presetid = $2 ORDER BY index ASC', array($fit['metadata']['hash'], $preset['presetid']));
		while($row = \Osmium\Db\fetch_row($modulesq)) {
			$modules[$row[0]][] = array($row[1], (int)$row[2]);
		}
		add_modules_batch($fit, $modules);

		$firstchargepreset = true;
		$chargepresetsq = \Osmium\Db\query_params('SELECT chargepresetid, name, description FROM osmium.fittingchargepresets WHERE fittinghash = $1 AND presetid = $2 ORDER BY chargepresetid ASC', array($fit['metadata']['hash'], $preset['presetid']));
		while($chargepreset = \Osmium\Db\fetch_assoc($chargepresetsq)) {
			if($firstchargepreset === true) {
				$fit['chargepresetname'] = $chargepreset['name'];
				$fit['chargepresetdesc'] = $chargepreset['description'];

				$firstchargepreset = false;
			} else {
				$chargepresetid = create_charge_preset($fit, $chargepreset['name'], $chargepreset['description']);
				use_charge_preset($fit, $chargepresetid);
			}

			$charges = array();
			$chargesq = \Osmium\Db\query_params('SELECT slottype, typeid FROM osmium.fittingcharges WHERE fittinghash = $1 AND presetid = $2 AND chargepresetid = $3 ORDER BY index ASC', array($fit['metadata']['hash'], $preset['presetid'], $chargepreset['chargepresetid']));
			while($row = \Osmium\Db\fetch_row($chargesq)) {
				$charges[$row[0]][] = $row[1];
			}
			add_charges_batch($fit, $charges);
		}
	}
	
	$firstdronepreset = true;
	$dronepresetsq = \Osmium\Db\query_params('SELECT dronepresetid, name, description FROM osmium.fittingdronepresets WHERE fittinghash = $1 ORDER BY dronepresetid ASC', array($fit['metadata']['hash']));
	while($dronepreset = \Osmium\Db\fetch_assoc($dronepresetsq)) {
		if($firstdronepreset === true) {
			/* Edit the default preset instead of creating a new preset */
			$fit['dronepresetname'] = $dronepreset['name'];
			$fit['dronepresetdesc'] = $dronepreset['description'];

			$firstdronepreset = false;
		} else {
			$dronepresetid = create_drone_preset($fit, $dronepreset['name'], $dronepreset['description']);
			use_drone_preset($fit, $dronepresetid);
		}

		$drones = array();
		$dronesq = \Osmium\Db\query_params('SELECT typeid, quantityinbay, quantityinspace FROM osmium.fittingdrones WHERE fittinghash = $1 AND dronepresetid = $2', array($fit['metadata']['hash'], $dronepreset['dronepresetid']));
		while($row = \Osmium\Db\fetch_row($dronesq)) {
			$drones[$row[0]] = array('quantityinbay' => $row[1], 'quantityinspace' => $row[2]);
		}
		add_drones_batch($fit, $drones);
	}
  
	\Osmium\State\put_cache('loadout-'.$loadoutid, $fit);
	return $fit;
}
