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

/* KEEP THIS NAMESPACE PURE. */

const VIEW_EVERYONE = 0;
const VIEW_PASSWORD_PROTECTED = 1;
const VIEW_ALLIANCE_ONLY = 2;
const VIEW_CORPORATION_ONLY = 3;
const VIEW_OWNER_ONLY = 4;

const EDIT_OWNER_ONLY = 0;
const EDIT_OWNER_AND_FITTING_MANAGER_ONLY = 1;
const EDIT_CORPORATION_ONLY = 2;
const EDIT_ALLIANCE_ONLY = 3;

const VISIBILITY_PUBLIC = 0;
const VISIBILITY_PRIVATE = 1;

function get_slottypes() {
	return array('high', 'medium', 'low', 'rig', 'subsystem');
}

function init_fit(&$fit, $typeid) {
	$row = \Osmium\Db\fetch_row(\Osmium\Db\query_params('SELECT typename FROM osmium.invships WHERE typeid = $1', array($typeid)));

	if($row === false) return false;

	$fit['cache'] = array();

	get_attributes_and_effects(array($typeid), $fit['cache']['hull']);
	$fit['cache']['hull'] = $fit['cache']['hull'][$typeid];

	$fit['hull'] = 
		array(
			'typeid' => $typeid,
			'typename' => $row[0]
			);

	reset_process_hull_attributes($fit);
  
	if(!isset($fit['modules'])) {
		foreach(get_slottypes() as $type) {
			$fit['modules'][$type] = array();
		}
	}
  
	if(!isset($fit['charges'])) {
		$fit['charges'] = array(array('name' => 'Default'));
	}

	if(!isset($fit['drones'])) {
		$fit['drones'] = array();
	}
  
	return true;
}

function update_modules(&$fit, $typeids, $modules) {
	get_modules_attributes_and_effects($typeids, $fit['cache']['modules'], $fit['cache']['modules']);
	reset_process_hull_attributes($fit);

	$typeids[] = -1;
	$names = array();
	$r = \Osmium\Db\query_params('SELECT typeid, typename FROM osmium.invmodules WHERE typeid IN ('.implode(',', $typeids).')', array());
	while($row = \Osmium\Db\fetch_row($r)) {
		$names[$row[0]] = $row[1];
	}

	foreach(get_slottypes() as $type) {
		$fit['modules'][$type] = array();
	}

	foreach($modules as $type => $a) {
		foreach($a as $i => $typeid) {
			if(!isset($fit['cache']['modules'][$typeid]['effects'])) continue; /* Not a real typeID */
			$trueslottype = get_module_slottype($fit['cache']['modules'][$typeid]['effects']);
			if($trueslottype === false) continue; /* Not a real module */
			$fit['modules'][$trueslottype][] = ($m = array('typeid' => $typeid, 
			                                               'typename' => $names[$typeid]));
			process_module_attributes($fit, 
			                          $m, 
			                          $fit['cache']['modules'][$typeid]['attributes'], 
			                          $fit['cache']['modules'][$typeid]['effects']);      
		}
	}
}

function update_drones(&$fit, $drones) {
	$keys = array_keys($drones);
	$keys[] = -1;
  
	$rows = array();
	$out = array();
	$r = \Osmium\Db\query_params('SELECT typeid, typename, volume FROM osmium.invdrones WHERE typeid IN ('.format_in_array($keys).')', array());
	while($row = \Osmium\Db\fetch_row($r)) {
		$rows[$row[0]] = $row;
	}

	foreach($drones as $typeid => $count) {
		$out[] = 
			array(
				'typeid' => $typeid,
				'typename' => $rows[$typeid][1],
				'volume' => $rows[$typeid][2],
				'count' => $count,
				);
	}

	$fit['drones'] = $out;
}

function pop_drone(&$fit, $typeid) {
	foreach($fit['drones'] as $i => $drone) {
		if($drone['typeid'] != $typeid) continue;

		if($drone['count'] >= 2) {
			$fit['drones'][$i]['count']--;
		} else if($drone['count'] == 1) {
			unset($fit['drones'][$i]);
			$fit['drones'] = array_values($fit['drones']);
		}

		break;
	}
}

function format_in_array($arr) {
	return implode(',', array_map(function($x) { return "'$x'"; }, $arr));
}

function get_attributes_and_effects($typeids, &$out) {
	static $interesting_effects = 
		array(
			'loPower',
			'medPower',
			'hiPower',
			'rigSlot',
			'subSystem',
			'turretFitted',
			'launcherFitted',
			'eliteBonusGunshipDroneCapacity2',
			'eliteBonusHeavyGunshipDroneCapacity2',
			'shipBonusDroneCapacityGF',
			);
	static $interesting_attributes = 
		array(
			'hiSlots', 
			'medSlots', 
			'lowSlots', 
			'rigSlots', 
			'maxSubSystems',
			'launcherSlotsLeft',
			'turretSlotsLeft',
			'hiSlotModifier',
			'medSlotModifier',
			'lowSlotModifier',
			'turretHardPointModifier',
			'launcherHardPointModifier',
			'droneCapacity',
			'eliteBonusGunship2',
			'eliteBonusHeavyGunship2',
			'shipBonusGF',
			);

	foreach($typeids as $tid) {
		$out[$tid]['effects'] = array();
		$out[$tid]['attributes'] = array();
	}

	$typeids[] = -1;
	$typeidIN = implode(',', $typeids);
  
	$effectsq = \Osmium\Db\query_params('SELECT typeid, effectname, dgmeffects.effectid
  FROM eve.dgmeffects 
  JOIN eve.dgmtypeeffects 
  ON dgmeffects.effectid = dgmtypeeffects.effectid 
  WHERE typeid IN ('.$typeidIN.') 
  AND effectname IN ('.format_in_array($interesting_effects).')', array());
	while($row = \Osmium\Db\fetch_assoc($effectsq)) {
		$tid = $row['typeid'];
		unset($row['typeid']);
		$out[$tid]['effects'][$row['effectname']] = $row;
	}

	$effectsq = \Osmium\Db\query_params('SELECT typeid, attributename, dgmattributetypes.attributeid,
  COALESCE(valuefloat, valueint) AS value 
  FROM eve.dgmattributetypes 
  JOIN eve.dgmtypeattributes ON dgmattributetypes.attributeid = dgmtypeattributes.attributeid 
  WHERE typeid IN ('.$typeidIN.') 
  AND attributename IN ('.format_in_array($interesting_attributes).')', array());
	while($row = \Osmium\Db\fetch_assoc($effectsq)) {
		$tid = $row['typeid'];
		unset($row['typeid']);
		$out[$tid]['attributes'][$row['attributename']] = $row;
	}
}

function reset_process_hull_attributes(&$fit) {
	$attributes = $fit['cache']['hull']['attributes'];
	$effects = $fit['cache']['hull']['effects'];

	foreach(array_combine(get_slottypes(), 
	                      array('hiSlots', 'medSlots', 'lowSlots', 'rigSlots', 'maxSubSystems')) 
	        as $type => $attributename) {
		$fit['hull']['slotcount'][$type] = isset($attributes[$attributename]) ?
			$attributes[$attributename]['value'] : 0;
	}

	$fit['hull']['turrethardpoints'] = isset($attributes['turretSlotsLeft']) ?
		$attributes['turretSlotsLeft']['value'] : 0;

	$fit['hull']['launcherhardpoints'] = isset($attributes['launcherSlotsLeft']) ?
		$attributes['launcherSlotsLeft']['value'] : 0;

	$fit['hull']['dronecapacity'] = isset($attributes['droneCapacity']) ?
		$attributes['droneCapacity']['value'] : 0;

	if(isset($effects['eliteBonusGunshipDroneCapacity2'])) {
		$fit['hull']['dronecapacity'] += 5 * $attributes['eliteBonusGunship2']['value'];
	}
	if(isset($effects['eliteBonusHeavyGunshipDroneCapacity2'])) {
		$fit['hull']['dronecapacity'] += 5 * $attributes['eliteBonusHeavyGunship2']['value'];
	}
	if(isset($effects['shipBonusDroneCapacityGF'])) {
		$fit['hull']['dronecapacity'] += 5 * $attributes['shipBonusGF']['value'];
	}

	$fit['hull']['usedturrethardpoints'] = 0;
	$fit['hull']['usedlauncherhardpoints'] = 0;
}

function process_module_attributes(&$fit, $module, $attributes, $effects) {
	foreach(array('low' => 'lowSlotModifier', 
	              'medium' => 'medSlotModifier', 
	              'high' => 'hiSlotModifier') as $mtype => $mattribute) {
		if(isset($attributes[$mattribute])) {
			$fit['hull']['slotcount'][$mtype] += $attributes[$mattribute]['value'];
		}
	}

	if(isset($attributes['turretHardPointModifier'])) {
		$fit['hull']['turrethardpoints'] += $attributes['turretHardPointModifier'];
	}

	if(isset($attributes['launcherHardPointModifier'])) {
		$fit['hull']['launcherhardpoints'] += $attributes['launcherHardPointModifier'];
	}

	if(isset($effects['turretFitted'])) ++$fit['hull']['usedturrethardpoints'];
	if(isset($effects['launcherFitted'])) ++$fit['hull']['usedlauncherhardpoints'];

	if(isset($attributes['droneCapacity'])) {
		$fit['hull']['dronecapacity'] += $attributes['droneCapacity']['value'];
	}
}

function get_modules_attributes_and_effects($typeids, &$out, $cache) {
	$out = array();
	foreach($typeids as &$typeid) {
		if(isset($cache[$typeid])) {
			$out[$typeid] = $cache[$typeid];
			$typeid = -1;
		}
	}

	get_attributes_and_effects($typeids, $out);
}

function get_module_slottype($effects) {
	if(isset($effects['loPower'])) return 'low';
	if(isset($effects['medPower'])) return 'medium';
	if(isset($effects['hiPower'])) return 'high';
	if(isset($effects['rigSlot'])) return 'rig';
	if(isset($effects['subSystem'])) return 'subsystem';
	return false;
}

function reset(&$fit) {
	$fit = array();
}

function sanitize(&$fit) {
	/* Unset any extra charges of nonexistent modules. */
	foreach($fit['charges'] as $i => $data) {
		foreach(get_slottypes() as $name) {
			if(!isset($fit['charges'][$i][$name])) continue;

			foreach($fit['charges'][$i][$name] as $j => $charge) {
				if(!isset($fit['modules'][$name][$j])) {
					unset($fit['charges'][$i][$name][$j]);
				}
			}
		}
	}
}

/* BE VERY CAREFUL WHEN CHANGING THIS FUNCTION.
 * ALL THE FITTINGHASHS DEPEND ON ITS RESULT. */
function get_unique($fit) {
	$unique = array(
		'metadata' => array(
			'name' => $fit['metadata']['name'],
			'description' => $fit['metadata']['description'],
			'tags' => $fit['metadata']['tags'],
			),
		'hull' => array(
			'typeid' => $fit['hull']['typeid'],
			),
		);

	foreach($fit['modules'] as $type => $d) {
		foreach($d as $index => $module) {
			$unique['modules'][$type][$index] = $module['typeid'];
		}
	}

	foreach($fit['charges'] as $preset) {
		$name = $preset['name'];
		unset($preset['name']);
		foreach($preset as $type => $charges) {
			foreach($charges as $index => $charge) {
				$unique['charges'][$name][$type][$index] = $charge['typeid'];
			}
		}
	}

	foreach($fit['drones'] as $drone) {
		$count = $drone['count'];
		$typeid = $drone['typeid'];

		if($count == 0) continue;
		if(!isset($unique['drones'][$typeid])) $unique['drones'][$typeid] = 0;

		$unique['drones'][$typeid] += $count;
	}

	return $unique;
}

function ksort_rec(array &$array) {
	ksort($array);
	foreach($array as &$v) {
		if(is_array($v)) ksort_rec($v);
	}
}

function get_hash($fit) {
	$unique = get_unique($fit);

	/* Ensure equality if key ordering is different */
	ksort_rec($unique);
	sort($unique['metadata']['tags']); /* tags should be ordered by value */

	return sha1(serialize($unique));
}

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
		                        $fit['hull']['typeid'],
		                        time(),
		                        ));
  
	foreach($fit['metadata']['tags'] as $tag) {
		\Osmium\Db\query_params('INSERT INTO osmium.fittingtags (fittinghash, tagname) VALUES ($1, $2)', 
		                        array($fittinghash, $tag));
	}
  
	foreach($fit['modules'] as $type => $data) {
		foreach($data as $index => $module) {
			\Osmium\Db\query_params('INSERT INTO osmium.fittingmodules (fittinghash, slottype, index, typeid) VALUES ($1, $2, $3, $4)', 
			                        array($fittinghash, $type, $index, $module['typeid']));
		}
	}
  
	foreach($fit['charges'] as $preset) {
		$name = $preset['name'];
		unset($preset['name']);
    
		foreach($preset as $type => $d) {
			foreach($d as $index => $charge) {
				\Osmium\Db\query_params('INSERT INTO osmium.fittingcharges (fittinghash, presetname, slottype, index, typeid) VALUES ($1, $2, $3, $4, $5)', 
				                        array($fittinghash, $name, $type, $index, $charge['typeid']));
			}
		}
	}
  
	foreach($fit['drones'] as $drone) {
		\Osmium\Db\query_params('INSERT INTO osmium.fittingdrones (fittinghash, typeid, quantity) VALUES ($1, $2, $3)',
		                        array($fittinghash, $drone['typeid'], $drone['count']));
	}
  
	\Osmium\Db\query('COMMIT;');
}

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

	\Osmium\Search\index(
		\Osmium\Db\fetch_assoc(
			\Osmium\Search\query_select_searchdata('WHERE loadoutid = $1', 
			                                       array($loadoutid))));
}

function get_fit($loadoutid, $revision = null) {
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

	$fit = array();
	init_fit($fit, $fitting['hullid']);

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

	$m_typeids = array();
	$modules = array();
	$mq = \Osmium\Db\query_params('SELECT slottype, index, typeid FROM osmium.fittingmodules WHERE fittinghash = $1 ORDER BY index ASC', array($fit['metadata']['hash']));
	while($row = \Osmium\Db\fetch_row($mq)) {
		$m_typeids[$row[2]] = true;
		$modules[$row[0]][$row[1]] = $row[2];
	}

	update_modules($fit, array_keys($m_typeids), $modules);

	$cq = \Osmium\Db\query_params('SELECT presetname, slottype, index, fittingcharges.typeid, typename FROM osmium.fittingcharges JOIN eve.invtypes ON fittingcharges.typeid = invtypes.typeid WHERE fittinghash = $1 ORDER BY index ASC', array($fit['metadata']['hash']));
	$presets = array();
	$fit['charges'] = array();
	while($row = \Osmium\Db\fetch_row($cq)) {
		$presets[$row[0]][$row[1]][$row[2]]['typeid'] = $row[3];
		$presets[$row[0]][$row[1]][$row[2]]['typename'] = $row[4];
	}
	foreach($presets as $name => $preset) {
		$preset['name'] = $name;
		$fit['charges'][] = $preset;
	}
	if(count($fit['charges']) == 0) {
		$fit['charges'][] = array('name' => 'Default');
	}

	$dq = \Osmium\Db\query_params('SELECT typeid, quantity FROM osmium.fittingdrones WHERE fittinghash = $1', array($fit['metadata']['hash']));
	$drones = array();
	while($row = \Osmium\Db\fetch_row($dq)) {
		if(!isset($drones[$row[0]])) $drones[$row[0]] = 0;

		$drones[$row[0]] += $row[1];
	}
  
	update_drones($fit, $drones);
  
	return $fit;
}

function try_parse_fit_from_eve_xml(\SimpleXMLElement $e, &$errors) {
	$fit = array();

	if(!isset($e['name'])) {
		$errors[] = 'Expected a name attribute in <fitting> tag, none found. Stopping.';
		return false;
	} else {
		$name = (string)$e['name'];
	}

	if(!isset($e->description) || !isset($e->description['value'])) {
		$errors[] = 'Expected <description> tag with value attribute, none found. Using empty description.';
		$description = '';
	} else {
		$description = (string)$e->description['value'];
	}
	$description = '(Imported from EVE XML format.)'."\n\n".$description;

	if(!isset($e->shipType) || !isset($e->shipType['value'])) {
		$errors[] = 'Expected <shipType> tag with value attribute, none found. Stopping.';
		return false;
	} else {
		$shipname = (string)$e->shipType['value'];
	}

	$row = \Osmium\Db\fetch_row(
		\Osmium\Db\query_params(
			'SELECT typeid FROM osmium.invships WHERE typename = $1',
			array($shipname)));
	if($row === false) {
		$errors[] = 'Could not fetch typeID of "'.$shipname.'". Obsolete/unpublished ship? Stopping.';
		return false;
	}
	init_fit($fit, $row[0]);

	if(!isset($e->hardware)) {
		$errors[] = 'No <hardware> element found. Expected at least 1. Stopping.';
		return false;
	}

	$typenames = array();
	$drones = array();
	$modules = array();
	$recover_modules = array();

	static $modtypes = array(
		'low' => 'low',
		'med' => 'medium',
		'hi' => 'high',
		'rig' => 'rig',
		'subsystem' => 'subsystem',
		);

	foreach($e->hardware as $hardware) {
		if(!isset($hardware['type'])) {
			$errors[] = 'Tag <hardware> has no type attribute. Discarded.';
			continue;
		}
		$type = (string)$hardware['type'];
		$typenames[$type] = true;

		if(!isset($hardware['slot'])) {
			$errors[] = 'Tag <hardware> has no slot attribute. (Recoverable error.)';
			$slot = '';
		} else {
			$slot = (string)$hardware['slot'];
		}

		if($slot === "drone bay") {
			if(!isset($hardware['qty'])) $qty = 1;
			else $qty = (int)$hardware['qty'];
			if($qty <= 0) continue;

			$drones[] = array('count' => $qty, 'typename' => $type);
		} else {
			$p_slot = $slot;
			$slot = explode(' ', $slot);
			if(count($slot) != 3
			   || $slot[1] != 'slot'
			   || !in_array($slot[0], array_keys($modtypes))
			   || !is_numeric($slot[2])
			   || (int)$slot[2] < 0
			   || (int)$slot[2] > 7) {

				$errors[] = 'Could not parse slot attribute "'.$p_slot.'". (Recoverable error.)';
				$recover_modules[] = $type;
			} else {
				$slottype = $modtypes[$slot[0]];
				$index = $slot[2];
				$modules[$slottype][$index] = $type;
			}
		}
	}

	$typenames['OsmiumSentinel'] = true; /* Just in case $typenames were to be empty */
	$typename_to_id = array();
	/* That's a pretty dick move from CCP to NOT include
	 * typeIDs. Whoever had that idea should be kicked in the nuts. */
	$req = \Osmium\Db\query('SELECT typeid, typename FROM eve.invtypes WHERE typename IN ('
	                        .implode(',', array_map(function($name) {
				                        return "'".\Osmium\Db\escape_string($name)."'";
			                        }, array_keys($typenames))).')');
	while($row = \Osmium\Db\fetch_row($req)) {
		$typename_to_id[$row[1]] = $row[0];
	}

	$realmodules = array();
	$realtypeids = array();
	foreach($modules as $type => $m) {
		foreach($m as $i => $typename) {
			if(!isset($typename_to_id[$typename])) {
				$errors[] = 'Could not find typeID of "'.$typename.'". Skipped.';
				continue;
			}
			$realmodules[$type][$i] = $typename_to_id[$typename];
			$realtypeids[$typename_to_id[$typename]] = true;
		}
	}
	foreach($recover_modules as $typename) {
		if(!isset($typename_to_id[$typename])) {
			$errors[] = 'Could not find typeID of "'.$typename.'". Skipped.';
			continue;
		}
		/* "low" does not matter here, it will be corrected in update_modules later. */
		$realmodules['low'][] = $typename_to_id[$typename];
		$realtypeids[$typename_to_id[$typename]] = true;
	}

	$realdrones = array();
	foreach($drones as $drone) {
		if(!isset($typename_to_id[$drone['typename']])) {
			$errors[] = 'Could not find typeID of "'.$drone['typename'].'". Skipped.';
			continue;
		}
		
		$typeid = $typename_to_id[$drone['typename']];
		if(!isset($realdrones[$typeid])) $realdrones[$typeid] = 0;
		$realdrones[$typeid] += $drone['count'];
	}

	update_modules($fit, array_keys($realtypeids), $realmodules);
	update_drones($fit, $drones);

	$fit['metadata']['name'] = $name;
	$fit['metadata']['description'] = $description;
	$fit['metadata']['tags'] = array();
	$fit['metadata']['view_permission'] = VIEW_OWNER_ONLY;
	$fit['metadata']['edit_permission'] = EDIT_OWNER_ONLY;
	$fit['metadata']['visibility'] = VISIBILITY_PUBLIC;

	return $fit;
}
