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
	$unique = array(
		'metadata' => array(
			'name' => $fit['metadata']['name'],
			'description' => $fit['metadata']['description'],
			'tags' => $fit['metadata']['tags'],
			),
		'ship' => array(
			'typeid' => $fit['ship']['typeid'],
			),
		);

	foreach($fit['modules'] as $type => $d) {
		foreach($d as $index => $module) {
			$unique['modules'][$type][$index] = array($module['typeid'], $module['state']);
		}
	}

	foreach($fit['charges'] as $name => $preset) {
		foreach($preset as $type => $charges) {
			foreach($charges as $index => $charge) {
				$unique['charges'][$name][$type][$index] = $charge['typeid'];
			}
		}
	}

	foreach($fit['drones'] as $typeid => $drone) {
		$unique['drones'][$typeid] = array($drone['quantityinbay'], $drone['quantityinspace']);
	}

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
	$unique = get_unique($fit);

	/* Ensure equality if key ordering is different */
	ksort_rec($unique);
	sort($unique['metadata']['tags']); /* tags should be ordered by value */

	return sha1(serialize($unique));
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
	\Osmium\Db\query_params('INSERT INTO osmium.fittings (fittinghash, name, description, hullid, creationdate) VALUES ($1, $2, $3, $4, $5)', 
	                        array(
		                        $fittinghash,
		                        $fit['metadata']['name'],
		                        $fit['metadata']['description'],
		                        $fit['ship']['typeid'],
		                        time(),
		                        ));
  
	foreach($fit['metadata']['tags'] as $tag) {
		\Osmium\Db\query_params('INSERT INTO osmium.fittingtags (fittinghash, tagname) VALUES ($1, $2)', 
		                        array($fittinghash, $tag));
	}
  
	$module_order = array();
	foreach($fit['modules'] as $type => $data) {
		$z = 0;
		foreach($data as $index => $module) {
			$module_order[$type][$index] = $z;
			\Osmium\Db\query_params('INSERT INTO osmium.fittingmodules (fittinghash, slottype, index, typeid, state) VALUES ($1, $2, $3, $4, $5)', 
			                        array($fittinghash, $type, $z, $module['typeid'], $module['state']));
			++$z;
		}
	}
  
	foreach($fit['charges'] as $name => $preset) {
		foreach($preset as $type => $d) {
			foreach($d as $index => $charge) {
				if(!isset($module_order[$type][$index])) continue;
				$z = $module_order[$type][$index];

				\Osmium\Db\query_params('INSERT INTO osmium.fittingcharges (fittinghash, presetname, slottype, index, typeid) VALUES ($1, $2, $3, $4, $5)', 
				                        array($fittinghash, $name, $type, $z, $charge['typeid']));
			}
		}
	}
  
	foreach($fit['drones'] as $drone) {
		\Osmium\Db\query_params('INSERT INTO osmium.fittingdrones (fittinghash, typeid, quantityinbay, quantityinspace) VALUES ($1, $2, $3, $4)',
		                        array($fittinghash, $drone['typeid'], $drone['quantityinbay'], $drone['quantityinspace']));
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
 * array that can be used with all functions using $fit. Returns false
 * on errors.
 *
 * This is pretty expensive, so the result is cached, unless a
 * revision number is specified.
 *
 * @param $loadoutid the loadoutid of the loadout to fetch
 *
 * @param $revision Revision number to get, if null the latest
 * revision is used
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

	$modules = array();
	$mq = \Osmium\Db\query_params('SELECT slottype, index, typeid, state FROM osmium.fittingmodules WHERE fittinghash = $1 ORDER BY index ASC', array($fit['metadata']['hash']));
	while($row = \Osmium\Db\fetch_row($mq)) {
		$modules[$row[0]][$row[1]] = array($row[2], (int)$row[3]);
	}

	add_modules_batch($fit, $modules);

	$charges = array();
	$cq = \Osmium\Db\query_params('SELECT presetname, slottype, index, fittingcharges.typeid FROM osmium.fittingcharges JOIN eve.invtypes ON fittingcharges.typeid = invtypes.typeid WHERE fittinghash = $1 ORDER BY index ASC', array($fit['metadata']['hash']));
	while($row = \Osmium\Db\fetch_row($cq)) {
		$charges[$row[0]][$row[1]][$row[2]] = $row[3];
	}

	$firstpreset = null;
	foreach($charges as $presetname => $preset) {
		if($firstpreset === null) $firstpreset = $presetname;
		add_charges_batch($fit, $presetname, $preset);
	}
	if($firstpreset !== null) use_preset($fit, $firstpreset);
	
	$dq = \Osmium\Db\query_params('SELECT typeid, quantityinbay, quantityinspace FROM osmium.fittingdrones WHERE fittinghash = $1', array($fit['metadata']['hash']));
	$drones = array();
	while($row = \Osmium\Db\fetch_row($dq)) {
		$drones[$row[0]]['quantityinbay'] = $row[1];
		$drones[$row[0]]['quantityinspace'] = $row[2];
	}
  
	add_drones_batch($fit, $drones);
  
	\Osmium\State\put_cache('loadout-'.$loadoutid, $fit);
	return $fit;
}
