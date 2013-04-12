<?php
/* Osmium
 * Copyright (C) 2012, 2013 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

const CLF_PATH = '/../lib/common-loadout-format/validators/php/lib.php';
const DNA_REGEX = '([0-9]+)(:([0-9]+)(;([0-9]+))?)*::';

/**
 * Get a list of available export formats.
 */
function get_export_formats() {
	return array(
		'clf' => array(
			'CLF', 'application/json',
			function($fit, $opts = array()) {
				$minify = isset($opts['minify']) && $opts['minify'];
				return export_to_common_loadout_format($fit, $minify);
			}),
		'md' => array(
			'Markdown+gzCLF', 'text/plain',
			function($fit, $opts = array()) {
				$embedclf = !isset($opts['embedclf']) || $opts['embedclf'];
				return export_to_markdown($fit, $embedclf);
			}),
		'evexml' => array(
			'XML+gzCLF', 'application/xml',
			function($fit, $opts = array()) {
				$embedclf = !isset($opts['embedclf']) || $opts['embedclf'];
				return export_to_eve_xml(array($fit), $embedclf);
			}),
		'eft' => array(
			'EFT', 'text/plain',
			function($fit, $opts = array()) {
				return export_to_eft($fit);
			}),
		'dna' => array(
			'DNA', 'text/plain',
			function($fit, $opts = array()) {
				return export_to_dna($fit);
			}),
		);
}

/*
 * Try to parse a loadout from a CLF string (containing JSON-encoded
 * data). Any errors will be put in $errors.
 *
 * Returns false if there was an unrecoverable error, or a $fit.
 */
function try_parse_fit_from_common_loadout_format($jsonstring, &$errors) {
	require_once __DIR__.CLF_PATH;

	$status = \CommonLoadoutFormat\validate_clf($jsonstring, $errors);
	if($status !== \CommonLoadoutFormat\OK && $status !== \CommonLoadoutFormat\OK_WITH_WARNINGS) {
		return false;
	}

	$json = json_decode($jsonstring, true);
	$version =  $json['clf-version'];
	if(!function_exists($parse = 'Osmium\Fit\clf_parse_'.$version)) {
		$errors[] = "Fatal: unsupported CLF version.";
		return false;
	}

	$fit = $parse($json, $errors);
	if($fit === false) return false;

	\reset($fit['presets']);
	use_preset($fit, key($fit['presets']));
	\reset($fit['chargepresets']);
	use_charge_preset($fit, key($fit['chargepresets']));
	\reset($fit['dronepresets']);
	use_drone_preset($fit, key($fit['dronepresets']));

	return $fit;
}

/** @internal */
function clf_parse_1(array $json, &$errors) {
	create($fit);
	select_ship($fit, $json['ship']['typeid']);

	if(isset($json['presets']) && is_array($json['presets'])) {
		clf_parse_presets_1($fit, $json['presets'], $errors);
	}

	if(isset($json['drones']) && is_array($json['drones'])) {
		clf_parse_dronepresets_1($fit, $json['drones'], $errors);
	}

	if(isset($json['metadata']) && is_array($json['metadata'])) {
		clf_parse_meta_1($fit, $json['metadata'], $errors);
	}

	return $fit;
}

/** @internal */
function clf_parse_presets_1(&$fit, &$presets, &$errors) {
	$names = array();
	$firstpreset = true;
	$i = 0;

	foreach($presets as &$preset) {
		if(isset($preset['presetname']) && !isset($names[$preset['presetname']])) {
			$name = $preset['presetname'];
		} else {
			if(isset($preset['presetname'])) $overwritename = $preset['presetname'];

			do {
				$name = 'Preset #'.(++$i);
			} while(isset($names[$name]));
		}

		$description = isset($preset['presetdescription']) ?
			$preset['presetdescription'] : '';

		$preset['presetname'] = $name;
		$preset['presetdescription'] = $description;

		if($firstpreset) {
			$firstpreset = false;
			$fit['modulepresetname'] = $name;
			$fit['modulepresedesc'] = $description;
			$id = $fit['modulepresetid'];
		} else {
			$id = create_preset($fit, $name, $description);
			use_preset($fit, $id);

			if(isset($overwritename) && $overwritename !== null) {
				remove_preset($fit, $names[$name]);
				$fit['modulepresetname'] = $overwritename;
				$overwritename = null;
			}
		}
		$names[$name] = $id;

		if(isset($preset['modules']) && is_array($preset['modules'])) {
			clf_parse_modules_1($fit, $preset['modules'], $errors);
		}

		if(isset($preset['chargepresets']) && is_array($preset['chargepresets'])) {
			$modules = isset($preset['modules']) && is_array($preset['modules']) ?
				$preset['modules'] : array();

			clf_parse_chargepresets_1($fit, $preset['chargepresets'], $modules, $errors);
		}
	}
}

/** @internal */
function clf_parse_modules_1(&$fit, &$modules, &$errors) {
	static $nstates = array(
		'offline' => STATE_OFFLINE,
		'online' => STATE_ONLINE,
		'active' => STATE_ACTIVE,
		'overloaded' => STATE_OVERLOADED,
		);
	static $states = array(
		array(STATE_OFFLINE, STATE_ONLINE, STATE_ACTIVE, STATE_OVERLOADED),
		array(STATE_OFFLINE, STATE_ONLINE, STATE_ACTIVE)
		);

	$indexes = array();

	foreach($modules as $k => &$m) {
		$type = \CommonLoadoutFormat\get_module_slottype($m['typeid']);
		if($type == 'unknown') {
			unset($modules[$k]);
		}

		if(isset($m['index']) && is_int($m['index']) && !isset($indexes[$type][$m['index']])) {
			$index = $m['index'];
		} else {
			$index = 0;
			while(isset($indexes[$type][$index])) ++$index;
		}
		$indexes[$type][$index] = true;

		add_module($fit, $index, $m['typeid']);

		list($isactivable, $isoverloadable) = get_module_states($fit, $m['typeid']);
		if(isset($m['state']) && isset($nstates[$m['state']])) {
			$state = $nstates[$m['state']];
			if(($isoverloadable && in_array($state, $states[0]))
			   || ($isactivable && in_array($state, $states[1]))) {
				change_module_state_by_typeid($fit, $index, $m['typeid'], $state);
			}
		}

		$m['slottype'] = $type;
		$m['index'] = $index;
	}
}

/** @internal */
function clf_parse_chargepresets_1(&$fit, &$cpresets, &$modules, &$errors) {
	$names = array();
	$firstpreset = true;
	$i = 0;
	$cpids = array();

	foreach($cpresets as &$cpreset) {
		if(isset($cpreset['name']) && !isset($names[$cpreset['name']])) {
			$name = $cpreset['name'];
		} else {
			if(isset($cpreset['name'])) $overwritename = $cpreset['name'];

			do {
				$name = 'Charge preset #'.(++$i);
			} while(isset($names[$name]));
		}

		$description = isset($cpreset['description']) ?
			$cpreset['description'] : '';

		$cpreset['name'] = $name;
		$cpreset['description'] = $description;
		if(!isset($cpreset['id']) || !is_int($cpreset['id'])) {
			$cpreset['id'] = 0;
		}

		if($firstpreset) {
			$firstpreset = false;
			$fit['chargepresetname'] = $name;
			$fit['chargepresetdesc'] = $description;
			$id = $fit['chargepresetid'];
		} else {
			$id = create_charge_preset($fit, $name, $description);
			use_charge_preset($fit, $id);

			if(isset($overwritename) && $overwritename !== null) {
				remove_charge_preset($fit, $names[$cpreset['name']]);
				$fit['chargepresetname'] = $overwritename;
				$overwritename = null;
			}
		}
		$cpids[$cpreset['id']] = $id;
		$names[$cpreset['name']] = $id;

		foreach($modules as &$m) {
			if(!isset($m['charges']) || !is_array($m['charges'])) continue;
			foreach($m['charges'] as &$c) {
				if(!isset($c['cpid'])) $c['cpid'] = 0;
				if($c['cpid'] != $cpreset['id']) continue;

				if(!\CommonLoadoutFormat\check_typeof_type(
					   $c['typeid'], "charge")) continue;
				if(!\CommonLoadoutFormat\check_charge_can_be_fitted_to_module(
					   $m['typeid'], $c['typeid'])) continue;

				/* The type and index are correct here, they were fixed previously */
				add_charge($fit, $m['slottype'], $m['index'], $c['typeid']);
			}
		}
	}	
}

/** @internal */
function clf_parse_dronepresets_1(&$fit, &$drones, &$errors) {
	$names = array();
	$firstpreset = true;
	$i = 0;

	foreach($drones as &$dpreset) {
		if(isset($dpreset['presetname']) && !isset($names[$dpreset['presetname']])) {
			$name = $dpreset['presetname'];
		} else {
			if(isset($dpreset['presetname'])) $overwritename = $dpreset['presetname'];

			do {
				$name = 'Drone preset #'.(++$i);
			} while(isset($names[$name]));
		}

		$description = isset($dpreset['presetdescription']) ?
			$dpreset['presetdescription'] : '';

		$dpreset['presetname'] = $name;
		$dpreset['presetdescription'] = $description;

		if($firstpreset) {
			$firstpreset = false;
			$fit['dronepresetname'] = $name;
			$fit['dronepresedesc'] = $description;
			$id = $fit['dronepresetid'];
		} else {
			$id = create_drone_preset($fit, $name, $description);
			use_drone_preset($fit, $id);

			if(isset($overwritename) && $overwritename !== null) {
				remove_drone_preset($fit, $names[$name]);
				$fit['dronepresetname'] = $overwritename;
				$overwritename = null;
			}
		}
		$names[$name] = $id;

		if(isset($dpreset['inbay']) && is_array($dpreset['inbay'])) {
			clf_parse_drones_1($fit, $dpreset['inbay'], 'bay', $errors);
		}

		if(isset($dpreset['inspace']) && is_array($dpreset['inspace'])) {
			clf_parse_drones_1($fit, $dpreset['inspace'], 'space', $errors);
		}
	}
}

/** @internal */
function clf_parse_drones_1(&$fit, &$drones, $from, &$errors) {
	foreach($drones as &$d) {
		if(!\CommonLoadoutFormat\check_typeof_type($d['typeid'], "drone")) continue;

		if($from === 'bay') {
			$qbay = $d['quantity'];
			$qspace = 0;
		} else if($from === 'space') {
			$qspace = $d['quantity'];
			$qbay = 0;
		} else continue;

		add_drone($fit, $d['typeid'], $qbay, $qspace);
	}
}

/** @internal */
function clf_parse_meta_1(&$fit, &$metadata, &$errors) {
	if(isset($metadata['title']) && is_string($metadata['description'])) {
		$fit['metadata']['name'] = $metadata['title'];
	}

	if(isset($metadata['description']) && is_string($metadata['description'])) {
		$fit['metadata']['description'] = $metadata['description'];
	}

	/* Ignore creation date */

	$fit['metadata']['tags'] = array();
	if(isset($metadata['X-tags']) && is_array($metadata['X-tags'])) {
		foreach($metadata['X-tags'] as $tag) {
			if(!is_string($tag)) continue;
			$fit['metadata']['tags'][$tag] = true;
		}

		$fit['metadata']['tags'] = array_keys($fit['metadata']['tags']);
	}
}

/**
 * Try to parse a loadout in the EVE XML format.
 *
 * @param $e SimpleXMLElement to parse the loadout from, should be the
 * <loadout> element.
 *
 * @param $errors the array to store any import errors into; errors
 * will be appended at the end of the array.
 */
function try_parse_fit_from_eve_xml(\SimpleXMLElement $e, &$errors) {
	require_once __DIR__.CLF_PATH;
	create($fit);

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

	if(!isset($e->shipType) || !isset($e->shipType['value'])) {
		$errors[] = 'Expected <shipType> tag with value attribute, none found. Stopping.';
		return false;
	} else {
		$shipname = (string)$e->shipType['value'];
	}

	if(($shipid = \CommonLoadoutFormat\get_typeid($shipname)) === false) {
		$errors[] = 'Could not fetch typeID of "'.$shipname.'". Obsolete/unpublished ship? Stopping.';
		return false;
	}

	if(!\CommonLoadoutFormat\check_typeof_type($shipid, "ship")) {
		$errors[] = 'Typeid '.$shipid.' is not the typeid of a ship.';
		return false;
	}

	select_ship($fit, $shipid);

	$indexes = array();

	if(!isset($e->hardware)) return $fit;
	foreach($e->hardware as $hardware) {
		if(!isset($hardware['type'])) {
			$errors[] = 'Tag <hardware> has no type attribute. Discarded.';
			continue;
		}

		$type = (string)$hardware['type'];
		$typeid = \CommonLoadoutFormat\get_typeid($type);

		if($typeid === false) {
			$errors[] = 'Could not get typeid of "'.$type.'". Discarded.';
			continue;
		}

		if(\CommonLoadoutFormat\check_typeof_type($typeid, "drone")) {
			if(!isset($hardware['slot']) || (string)$hardware['slot'] !== 'drone bay') {
				$errors[] = 'Nonsensical slot attribute for drone: "'.((string)$hardware['slot']).'". Discarded';
				continue;
			}

			if(!isset($hardware['qty']) || (int)$hardware['qty'] < 0) {
				$errors[] = 'Incorrect qty attribute for drone: "'.((int)$hardware['qty']).'". Discarded.';
				continue;
			}

			add_drone_auto($fit, $typeid, (int)$hardware['qty']);
		} else {
			$slottype = \CommonLoadoutFormat\get_module_slottype($typeid);
			if($slottype === 'unknown') {
				$errors[] = 'Type "'.$type.'" is neither a drone nor a module. Discarded.';
				continue;
			}

			if(isset($hardware['slot'])) {
				preg_match_all('%^(hi|med|low|rig|subsystem) slot ([0-9]+)$%', (string)$hardware['slot'], $matches);
				if(isset($matches[2]) && count($matches[2]) === 1) {
					$index = (int)$matches[2][0];
					add_module($fit, $index, $typeid);
				} else {
					$errors[] = 'Nonsensical slot attribute: "'.((string)$hardware['slot']).'". Discarded.';
					continue;
				}
			} else {
				$errors[] = 'No index found for module "'.$type.'". Discarded.';
				continue;
			}
		}
	}

	$fit['metadata']['name'] = $name;
	$fit['metadata']['description'] = $description;
	$fit['metadata']['tags'] = array();

	return $fit;
}

/**
 * Try to parse a loadout in the EFT format. Since the format is not
 * documented anywhere, use at your own risk!
 */
function try_parse_fit_from_eft_format($eftstring, &$errors) {
	require_once __DIR__.CLF_PATH;

	$lines = array_map('trim', explode("\n", $eftstring));
	if(count($lines) == 0) {
		$errors[] = 'No input - aborting.';
		return false;
	}

	$meta = array_shift($lines);
	if(!preg_match('%^\[(.+)(,(.+)?)\]$%U', $meta, $match)) {
		$errors[] = 'Nonsensical first line, expected [ShipType, LoadoutName].';
		return false;
	}

	$shipname = trim($match[1]);
	$shiptype = \CommonLoadoutFormat\get_typeid($shipname);
	if($shiptype === false) {
		$errors[] = 'Ship "'.$shipname.'" not found.';
		return false;
	}

	if(!\CommonLoadoutFormat\check_typeof_type($shiptype, 'ship')) {
		$errors[] = 'Type "'.$shipname.'" is not a ship.';
		return false;
	}

	create($fit);
	select_ship($fit, $shiptype);

	$name = isset($match[3]) ? trim($match[3]) : '';
	if(!$name) {
		$name = 'Unnamed loadout';
	}

	$fit['metadata']['name'] = $name;
	$fit['metadata']['description'] = '';
	$fit['metadata']['tags'] = array();

	$indexes = array();
	foreach($lines as $l) {
		if(!$l) continue; /* Ignore empty lines */
		if(preg_match('%^\[empty (low|med|high|rig|subsystem) slot\]$%', trim($l))) continue;
		if(strpos($l, ',') !== false) {
			list($module, $charge) = explode(',', $l, 2);
			$module = trim($module);
			$charge = trim($charge);
		} else {
			$module = $l; /* Already trimmed */
			$charge = false;
		}

		if(preg_match('%^(.+)(\s+)x([0-9]+)$%U', $module, $match)) {
			$module = $match[1];
			$qty = (int)$match[3];
			if(!$qty) continue; /* Foobar x0 ?! */
		} else {
			$qty = 1;
		}

		$moduleid = \CommonLoadoutFormat\get_typeid($module);
		if($moduleid === false) {
			$errors[] = 'Type "'.$module.'" not found.';
			continue;
		}

		if(\CommonLoadoutFormat\check_typeof_type($moduleid, 'drone')) {
			add_drone_auto($fit, $moduleid, $qty);
		} else {
			$slottype = \CommonLoadoutFormat\get_module_slottype($moduleid);
			if($slottype === 'unknown') {
				$errors[] = 'Type "'.$type.'" is neither a drone nor a module. Discarded.';
				continue;
			}

			if(!isset($indexes[$slottype])) {
				$indexes[$slottype] = 0;
			}
			$index = ($indexes[$slottype]++);

			add_module($fit, $index, $moduleid);

			if($charge !== false) {
				$chargeid = \CommonLoadoutFormat\get_typeid($charge);
				if($chargeid === false) {
					$errors[] = 'Type "'.$charge.'" not found.';
					continue;
				}

				if(!\CommonLoadoutFormat\check_typeof_type($chargeid, 'charge')) {
					$errors[] = 'Type "'.$charge.'" is not a charge.';
					continue;
				}

				if(!\CommonLoadoutFormat\check_charge_can_be_fitted_to_module($moduleid, $chargeid)) {
					$errors[] = 'Charge "'.$charge.'" cannot be fitted to module "'.$module.'".';
					continue;
				}

				add_charge($fit, $slottype, $index, $chargeid);
			}
		}
	}

	return $fit;
}

/**
 * Try to parse a fit in the ShipDNA format. Since the only
 * documentation available on this format is highly ambiguous (and
 * mostly wrong), use this at your own risk!
 */
function try_parse_fit_from_shipdna($dnastring, $name, &$errors) {
	require_once __DIR__.CLF_PATH;

	if(!preg_match('%^'.DNA_REGEX.'$%U', $dnastring)) {
		$errors[] = 'Could not make sense out of the supplied DNA string.';
		return false;
	}

	$dnaparts = explode(':', rtrim($dnastring, ':'));
	$shipid = (int)array_shift($dnaparts);

	if(!\CommonLoadoutFormat\check_typeof_type($shipid, "ship")) {
		$errors[] = 'Typeid "'.$shipid.'" is not a ship.';
		return false;
	}

	create($fit);
	select_ship($fit, $shipid);

	$fit['metadata']['name'] = $name;
	$fit['metadata']['description'] = '';
	$fit['metadata']['tags'] = array();

	$indexes = array();
	$modules = array();
	$charges = array();

	foreach($dnaparts as $d) {
		if(strpos($d, ';') !== false) {
			$d = explode(';', $d, 2);
			$typeid = (int)$d[0];
			$qty = (int)$d[1];
		} else {
			$typeid = (int)$d;
			$qty = 1;
		}

		if($qty <= 0) continue;

		if(\CommonLoadoutFormat\check_typeof_type($typeid, 'drone')) {
			add_drone_auto($fit, $typeid, $qty);
		}
		else if(\CommonLoadoutFormat\check_typeof_type($typeid, 'charge')) {
			/* The game won't generate/recognize charges, but it
			 * dosen't hurt to support them */

			for($z = 0; $z < $qty; ++$z) {
				/* Fit charge to first appropriate module */
				foreach($modules as $type => $a) {
					foreach($a as $index => $m) {
						if(isset($charges[$type][$index])) continue;
						if(!\CommonLoadoutFormat\check_charge_can_be_fitted_to_module($m, $typeid)) continue;
						
						$charges[$type][$index] = $typeid;
						add_charge($fit, $type, $index, $typeid);

						continue 3;
					}
				}

				$errors[] = 'Could not add charge "'.$typeid.'", discarded.';
				/* There's no point trying to add the same charge
				 * again, all the modules have already been tested */
				break;
			}
		} else {
			$slottype = \CommonLoadoutFormat\get_module_slottype($typeid);
			if($slottype === 'unknown') {
				$errors[] = 'Unknown typeid "'.$typeid.'". Discarded.';
				continue;
			}

			if(!isset($indexes[$slottype])) {
				$indexes[$slottype] = 0;
			}

			for($z = 0; $z < $qty; ++$z) {
				$index = $indexes[$slottype];
				++$indexes[$slottype];
				
				$modules[$slottype][$index] = $typeid;
				add_module($fit, $index, $typeid);
			}
		}
	}

	return $fit;
}

/**
 * Export a fit to the common loadout format (CLF), latest supported
 * version.
 *
 * @returns a string containing the JSON data.
 */
function export_to_common_loadout_format($fit, $minify = false, $extraprops = true, $osmiumextraprops = false) {
	return json_encode(
		export_to_common_loadout_format_1($fit, $minify, $extraprops),
		$minify ? 0 : JSON_PRETTY_PRINT
		);
}

/**
 * Export a fit to the common loadout format (CLF) version 1.
 *
 * @returns the array to be serialized to JSON with json_encode().
 *
 * @todo fetch TQ version
 */
function export_to_common_loadout_format_1($fit, $minify = false, $extraprops = true, $osmiumextraprops = false) {
	static $statenames = null;
	if($statenames === null) $statenames = get_state_names();

	$json = array('clf-version' => 1);
	if($extraprops) {
		$json['X-generatedby'] = 'Osmium-'.\Osmium\get_osmium_version();
	}

	if(isset($fit['metadata']['name'])) {
		$json['metadata']['title'] = $fit['metadata']['name'];
	}

	if(isset($fit['metadata']['description'])) {
		$json['metadata']['description'] = $fit['metadata']['description'];
	}

	if(isset($fit['metadata']['creation_date'])) {
		$json['metadata']['creationdate'] = gmdate('r', $fit['metadata']['creation_date']);
	}

	if($extraprops && isset($fit['metadata']['tags'])
	   && is_array($fit['metadata']['tags']) && count($fit['metadata']['tags']) > 0) {
		/* Always force [...] array even if tags array is associative */
		$json['metadata']['X-tags'] = array_values($fit['metadata']['tags']);
	}

	if($osmiumextraprops) {
		if(isset($fit['metadata']['hash'])) {
			$json['metadata']['X-Osmium-loadouthash'] = $fit['metadata']['hash'];
		}
		if(isset($fit['metadata']['loadoutid'])) {
			$json['metadata']['X-Osmium-loadoutid'] = (int)$fit['metadata']['loadoutid'];
		}
		if(isset($fit['metadata']['revision'])) {
			$json['metadata']['X-Osmium-revision'] = (int)$fit['metadata']['revision'];
		}

		$json['metadata']['X-Osmium-view-permission'] = (int)$fit['metadata']['view_permission'];
		$json['metadata']['X-Osmium-edit-permission'] = (int)$fit['metadata']['edit_permission'];
		$json['metadata']['X-Osmium-visibility'] = (int)$fit['metadata']['visibility'];

		if($fit['metadata']['view_permission'] == VIEW_PASSWORD_PROTECTED) {
			$json['metadata']['X-Osmium-hashed-password'] = 
            isset($fit['metadata']['password']) ? $fit['metadata']['password'] : '*';
		}

		if(isset($fit['modulepresetid'])) {
			/* Map between $fit IDs (not necessarily linear) and indices in the JSON array */

			$i = 0;
			foreach($fit['presets'] as $id => $p) {
				if($id === $fit['modulepresetid']) break;
				++$i;
			}
			$json['X-Osmium-current-presetid'] = $i;
		}
		if(isset($fit['chargepresetid'])) {
			/* Mapping is not needed here, as the ID is stored in the JSON array itself */
			$json['X-Osmium-current-chargepresetid'] = (int)$fit['chargepresetid'];
		}
		if(isset($fit['dronepresetid'])) {
			$i = 0;
			foreach($fit['dronepresets'] as $id => $p) {
				if($id === $fit['dronepresetid']) break;
				++$i;
			}
			$json['X-Osmium-current-dronepresetid'] = $i;
		}
	}

	if(isset($fit['ship']['typeid'])) {
		/* Allow exporting incomplete loadouts (for internal use), even though it is forbidden by the spec*/
		$json['ship']['typeid'] = (int)$fit['ship']['typeid'];
		if(!$minify) {
			$json['ship']['typename'] = $fit['ship']['typename'];
		}
	}

	foreach($fit['presets'] as $pid => $preset) {
		$jsonpreset = array();

		$jsonpreset['presetname'] = $preset['name'];
		if($preset['description'] != '') $jsonpreset['presetdescription'] = $preset['description'];

		foreach($preset['modules'] as $type => $a) {
			foreach($a as $index => $module) {
				$jsonmodule = array();

				$jsonmodule['typeid'] = (int)$module['typeid'];
				if(!$minify) {
					$jsonmodule['typename'] = $module['typename'];
					$jsonmodule['slottype'] = $type;
				}

				if($osmiumextraprops || !$minify) {
					$jsonmodule['index'] = $index;
				}

				/* Only put state if it is not the default state */
				list($isactivable, ) = get_module_states($fit, $module['typeid']);
				$state = $module['state'] === null ? $module['old_state'] : $module['state'];
				if($osmiumextraprops
				   || ($isactivable && $state != STATE_ACTIVE)
				   || (!$isactivable && $state != STATE_ONLINE)) {
					$jsonmodule['state'] = lcfirst($statenames[$state][0]);
				}

				foreach($preset['chargepresets'] as $cpid => $chargepreset) {
					if(!isset($chargepreset['charges'][$type][$index])) continue;

					$charge = $chargepreset['charges'][$type][$index];
					$jsoncharge = array();

					$jsoncharge['typeid'] = (int)$charge['typeid'];
					if(!$minify) {
						$jsoncharge['typename'] = $charge['typename'];
					}
					$jsoncharge['cpid'] = (int)$cpid;

					$jsonmodule['charges'][] = $jsoncharge;
				}

				$jsonpreset['modules'][] = $jsonmodule;
			}
		}

		foreach($preset['chargepresets'] as $cpid => $chargepreset) {
			$jsoncp = array();

			$jsoncp['id'] = (int)$cpid;
			$jsoncp['name'] = $chargepreset['name'];
			if($chargepreset['description'] != '') {
				$jsoncp['description'] = $chargepreset['description'];
			}

			$jsonpreset['chargepresets'][] = $jsoncp;
		}

		$json['presets'][] = $jsonpreset;
	}

	foreach($fit['dronepresets'] as $dronepreset) {
		$jsondp = array();

		$jsondp['presetname'] = $dronepreset['name'];
		if($dronepreset['description'] != '') {
			$jsondp['presetdescription'] = $dronepreset['description'];
		}

		foreach($dronepreset['drones'] as $drone) {
			$jsondrone = array();

			$jsondrone['typeid'] = (int)$drone['typeid'];
			if(!$minify) {
				$jsondrone['typename'] = $drone['typename'];
			}

			if($drone['quantityinbay'] > 0) {
				$jsondrone['quantity'] = (int)$drone['quantityinbay'];
				$jsondp['inbay'][] = $jsondrone;
			}

			if($drone['quantityinspace'] > 0) {
				$jsondrone['quantity'] = (int)$drone['quantityinspace'];
				$jsondp['inspace'][] = $jsondrone;
			}
		}

		$json['drones'][] = $jsondp;
	}

	return $json;
}

/**
 * Generate base64-encoded, gzipped CLF of a fit. Designed to be easy
 * to recognize/parse by machines, and resilient to user/program
 * stupidity (such as encoding changes, line breaks, added symbols
 * etc.).
 */
function export_to_gzclf($fit) {
	return "BEGIN gzCLF BLOCK\n"
		.wordwrap(base64_encode(gzcompress(export_to_common_loadout_format($fit, true))), 72, "\n", true)
		."\nEND gzCLF BLOCK\n";
}

/**
 * Generate a Markdown-formatted description of a loadout. This is a
 * one-way operation only, unless $embedclf is set to true.
 */
function export_to_markdown($fit, $embedclf = true) {
	static $statenames = null;
	if($statenames === null) $statenames = get_state_names();

	$md = "## ".$fit['ship']['typename']." loadout\n";

	$quote = function($text) {
		if((string)$text === "") return "";

		return "> ".str_replace("\n", "\n> ", wordwrap(trim($text), 70));
	};

	if(isset($fit['metadata']['name'])) {
		$md .= "# ".$fit['metadata']['name']."\n\n";
	} else {
		$md .= "\n";
	}

	if(isset($fit['metadata']['description'])) {
		$q = $quote($fit['metadata']['description']);
		if($q !== "") $md .= $q."\n\n";
	}

	if(isset($fit['metadata']['tags']) && count($fit['metadata']['tags']) > 0) {
		$md .= "Tags: ".implode(", ", $fit['metadata']['tags'])."\n\n";
	}

	if(count($fit['presets']) > 0) $md .= "# Presets\n\n";
	foreach($fit['presets'] as $pid => $preset) {
		$md .= "## ".$preset['name']."\n\n";

		if(isset($preset['description'])) {
			$q = $quote($preset['description']);
			if($q !== "") $md .= $q."\n\n";
		}

		/* Enforce consistent ordering of slot types, instead of just using foreach */
		foreach(get_slottypes() as $type) {
			if(!isset($preset['modules'][$type]) || count($preset['modules'][$type]) == 0) continue;

			$md .= "### ".ucfirst($type)." slots\n\n";

			ksort($preset['modules'][$type]);
			foreach($preset['modules'][$type] as $index => $module) {
				$md .= "- ".$module['typename'];

				list($isactivable, ) = get_module_states($fit, $module['typeid']);
				$state = $module['state'] === null ? $module['old_state'] : $module['state'];
				if(($isactivable && $state != STATE_ACTIVE) || (!$isactivable && $state != STATE_ONLINE)) {
					$md .= " (".lcfirst($statenames[$state][0]).")";
				}

				$md .= "\n";
			}

			$md .= "\n";
		}

		if(!isset($preset['chargepresets'])) continue;
		$hascharges = false;
		foreach($preset['chargepresets'] as $cp) {
			foreach($cp['charges'] as $type => $a) {
				foreach($a as $index => $c) {
					$hascharges = true;
					break 3;
				}
			}
		}

		if(!$hascharges) continue;

		$md .= "### Charge presets\n\n";

		ksort($preset['chargepresets']);
		foreach($preset['chargepresets'] as $cpid => $cp) {
			$md .= "#### ".$cp['name']."\n\n";

			if(isset($cp['description'])) {
				$q = $quote($cp['description']);
				if($q !== "") $md .= $q."\n\n";
			}

			foreach(get_slottypes() as $type) {
				if(!isset($cp['charges'][$type])) continue;

				foreach($cp['charges'][$type] as $index => $charge) {
					$md .= "- ".$charge['typename']."\n";
				}
			}

			$md .= "\n";
		}
	}

	$hasdrones = false;
	if(isset($fit['dronepresets'])) {
		foreach($fit['dronepresets'] as $dp) {
			foreach($dp['drones'] as $drone) {
				if($drone['quantityinbay'] > 0 || $drone['quantityinspace'] > 0) {
					$hasdrones = true;
					break 2;
				}
			}
		}
	}

	if($hasdrones) {
		$md .= "# Drone presets\n\n";

		foreach($fit['dronepresets'] as $dp) {
			$md .= "## ".$dp['name']."\n\n";
			
			if(isset($dp['description'])) {
				$q = $quote($dp['description']);
				if($q !== "") $md .= $q."\n\n";
			}

			foreach($dp['drones'] as $drone) {
				$qties = array();
				if($drone['quantityinspace'] > 0) {
					$qties[] = $drone['quantityinspace']." in space";
				}
				if($drone['quantityinbay'] > 0) {
					$qties[] = $drone['quantityinbay']." in bay";
				}

				if($qties === array()) continue;

				$md .= "- ".$drone['typename']." (".implode(', ', $qties).")\n";
			}

			$md .= "\n";
		}
	}

	if($embedclf) {
		$md .= "----------\n\n";
		$md .= "    ".str_replace("\n", "\n    ", trim(export_to_gzclf($fit)))."\n\n";
	}

	return $md;
}

/**
 * Export an array of fits to the EVE XML format (which can be later
 * imported in the client).
 */
function export_to_eve_xml(array $fits, $embedclf = true) {
	$xml = new \DOMDocument();
	$fittings = $xml->createElement('fittings');
	$xml->appendChild($fittings);

	foreach($fits as $fit) {
		$fittings->appendChild(export_to_eve_xml_single($xml, $fit, $embedclf));
	}

	$xml->formatOutput = true;
	return $xml->saveXML();
}

/** @internal */
function export_to_eve_xml_single(\DOMDocument $f, $fit, $embedclf = true) {
	static $modtypes = array(
		'low' => 'low',
		'medium' => 'med',
		'high' => 'hi',
		'rig' => 'rig',
		'subsystem' => 'subsystem',
		);

	$e = $f->createElement('fitting');

	$name = isset($fit['metadata']['name']) ? $fit['metadata']['name'] : 'Unnamed fitting';
	$description = isset($fit['metadata']['description']) ? $fit['metadata']['description'] : '';
	if($embedclf) {
		if($description) $description = rtrim($description)."\n\n";
		$description .= export_to_gzclf($fit);
	}

	$aname = $f->createAttribute('name');
	$aname->appendChild($f->createTextNode($name));
	$e->appendChild($aname);

	$edesc = $f->createElement('description');
	$avalue = $f->createAttribute('value');
	$avalue->appendChild($f->createTextNode($description));
	$edesc->appendChild($avalue);
	$e->appendChild($edesc);

	$eshiptype = $f->createElement('shipType');
	$avalue = $f->createAttribute('value');
	$avalue->appendChild($f->createTextNode($fit['ship']['typename']));
	$eshiptype->appendChild($avalue);
	$e->appendChild($eshiptype);

	foreach($fit['modules'] as $type => $a) {
		ksort($a);
		/* Ensure contiguous indexes */
		$i = 0;
		foreach($a as $module) {
			$ehardware = $f->createElement('hardware');
			$aslot = $f->createAttribute('slot');
			$aslot->appendChild($f->createTextNode($modtypes[$type].' slot '.$i));
			$atype = $f->createAttribute('type');
			$atype->appendChild($f->createTextNode($module['typename']));
			$ehardware->appendChild($aslot);
			$ehardware->appendChild($atype);
			$e->appendChild($ehardware);

			++$i;
		}
	}

	foreach($fit['drones'] as $drone) {
		$qty = 0;
		if(isset($drone['quantityinbay'])) $qty += $drone['quantityinbay'];
		if(isset($drone['quantityinspace'])) $qty += $drone['quantityinspace'];
		if($qty == 0) continue;

		$ehardware = $f->createElement('hardware');
		$aqty = $f->createAttribute('qty');
		$aqty->appendChild($f->createTextNode($qty));
		$aslot = $f->createAttribute('slot');
		$aslot->appendChild($f->createTextNode('drone bay'));
		$atype = $f->createAttribute('type');
		$atype->appendChild($f->createTextNode($drone['typename']));
		$ehardware->appendChild($aqty);
		$ehardware->appendChild($aslot);
		$ehardware->appendChild($atype);
		$e->appendChild($ehardware);
	}

	return $e;
}

/**
 * Export a loadout to the EFT format. Use at your own risk.
 */
function export_to_eft($fit) {
	static $slotorder = array('low', 'medium', 'high', 'rig', 'subsystem');
	$r = '['.$fit['ship']['typename'];

	$name = isset($fit['metadata']['name']) ? $fit['metadata']['name'] : 'unnamed';
	$r .= ', '.$name."]\n\n";

	foreach($slotorder as $type) {
		if(!isset($fit['modules'][$type])) continue;

		foreach($fit['modules'][$type] as $index => $module) {
			$r .= $module['typename'];
			if(isset($fit['charges'][$type][$index])) {
				$r .= ', '.$fit['charges'][$type][$index]['typename'];
			}

			$r .= "\n";
		}

		$r .= "\n";
	}

	foreach($fit['drones'] as $drone) {
		$qty = 0;
		if(isset($drone['quantityinbay'])) $qty += $drone['quantityinbay'];
		if(isset($drone['quantityinspace'])) $qty += $drone['quantityinspace'];
		if($qty == 0) continue;

		$r .= $drone['typename'].' x'.$qty."\n";
	}

	return $r;
}

/**
 * Export a loadout to the EFT format. Use at your own risk.
 */
function export_to_dna($fit) {
	static $slotorder = array('high', 'medium', 'low', 'rig');

	$dna = $fit['ship']['typeid'];

	if(isset($fit['modules']['subsystem'])) {
		foreach($fit['modules']['subsystem'] as $s) {
			$dna .= ':'.$s['typeid'];
		}
	}

	$tids = array();

	foreach($slotorder as $type) {
		if(isset($fit['modules'][$type])) {	
			foreach($fit['modules'][$type] as $m) {
				if(!isset($tids[$m['typeid']])) {
					$tids[$m['typeid']] = 1;
				} else {
					++$tids[$m['typeid']];
				}
			}
		}
	}

	foreach($fit['drones'] as $d) {
		if(!isset($tids[$d['typeid']])) {
			$tids[$d['typeid']] = 0;
		}

		$tids[$d['typeid']] += $d['quantityinspace'];
		$tids[$d['typeid']] += $d['quantityinbay'];
	}

	foreach($fit['charges'] as $a) {
		foreach($a as $c) {
			if(!isset($tids[$c['typeid']])) {
				$tids[$c['typeid']] = 1;
			} else {
				++$tids[$c['typeid']];
			}	
		}
	}

	$ftids = array();
	foreach($tids as $tid => $qty) {
		$ftids[] = $tid.";".$qty;
	}

	$dna .= ':'.implode(':', $ftids);

	return $dna.'::';
}

/**
 * Imports the loadout in $clfstring to $fit. This is usually faster
 * than calling try_parse_fit_from_common_loadout_format if $fit is
 * already a fitting somewhat close to the result.
 */
function synchronize_from_clf_1(&$fit, $clfstring) {
	/* TODO */

	$clf = json_decode($clfstring, true);
	if(json_last_error() !== JSON_ERROR_NONE) return false;

	if(!isset($clf['clf-version']) || (int)$clf['clf-version'] !== 1) {
		// @codeCoverageIgnoreStart
		trigger_error(__FUNCTION__.'(): expected CLF version 1, got '
		              .((int)(@$clf['clf-version'])), E_USER_WARNING);
		return false;
		// @codeCoverageIgnoreEnd
	}

	if(isset($clf['ship']['typeid'])
	   && (!isset($fit['ship']['typeid'])
	       || $clf['ship']['typeid'] != $fit['ship']['typeid'])) {
		if(!select_ship($fit, $clf['ship']['typeid'])) return false;
	}

	if(isset($clf['metadata'])) {
		$meta = $clf['metadata'];

		if(isset($meta['title'])) {
			$fit['metadata']['name'] = $meta['title'];
		}

		if(isset($meta['description'])) {
			$fit['metadata']['description'] = $meta['description'];
		}

		if(isset($meta['X-tags']) && is_array($meta['X-tags'])) {
			/* Expansive checks are done in sanitize(). */
			$fit['metadata']['tags'] = $meta['X-tags'];
		}

		if(isset($meta['X-Osmium-view-permission'])) {
			$fit['metadata']['view_permission'] = $meta['X-Osmium-view-permission'];
		}

		if(isset($meta['X-Osmium-edit-permission'])) {
			$fit['metadata']['edit_permission'] = $meta['X-Osmium-edit-permission'];
		}

		if(isset($meta['X-Osmium-visibility'])) {
			$fit['metadata']['visibility'] = $meta['X-Osmium-visibility'];
		}

		if(isset($meta['X-Osmium-clear-password'])
		   && strlen($meta['X-Osmium-clear-password']) > 0
		   && (!isset($fit['metadata']['__clear_pw'])
		       || $fit['metadata']['__clear_pw'] !== $meta['X-Osmium-clear-password']
			   )) {
			$fit['metadata']['__clear_pw'] = $meta['X-Osmium-clear-password'];
			$fit['metadata']['password'] = \Osmium\State\hash_password($meta['X-Osmium-clear-password']);
		}
	}

	if(isset($clf['presets'])) {
		$clfnames = array();
		$cpclfnames = array();
		$fitnames = array();
		$cpfitnames = array();

		foreach($clf['presets'] as $id => $p) {
			$clfnames[$p['presetname']] = $id;
			foreach($p['chargepresets'] as $cp) {
				$cpclfnames[$p['presetname']][$cp['name']] = $cp['id'];
			}
		}
		foreach($fit['presets'] as $id => $p) {
			$fitnames[$p['name']] = $id;
			foreach($p['chargepresets'] as $cpid => $cp) {
				$cpfitnames[$p['name']][$cp['name']] = $cpid;
			}
		}

		foreach($clfnames as $name => $id) {
			$pdesc = isset($clf['presets'][$id]['presetdescription']) ?
				$clf['presets'][$id]['presetdescription'] : '';

			if(!isset($fitnames[$name])) {
				$fitid = create_preset($fit, $name, $pdesc);
				$fitnames[$name] = $fitid;
				foreach($fit['presets'][$fitid]['chargepresets'] as $cpid => $cp) {
					$cpfitnames[$name][$cp['name']] = $cpid;
				}

				use_preset($fit, $fitid, false);
				synchronize_preset_from_clf_1($fit, $clf['presets'][$id]);
			} else {
				$fitid = $fitnames[$name];
				$fit['presets'][$fitid]['description'] = $pdesc;
			}

			foreach($cpclfnames[$name] as $cpname => $cpid) {
				$cpdesc = '';
				foreach($clf['presets'][$id]['chargepresets'] as $cp) {
					if($cp['id'] == $cpid) {
						if(isset($cp['description'])) {
							$cpdesc = $cp['description'];
						}
						break;
					}
				}

				if(!isset($cpfitnames[$name][$cpname])) {
					use_preset($fit, $fitid, false);
					$fitcpid = create_charge_preset($fit, $cpname, $cpdesc);
					$cpfitnames[$name][$cpname] = $fitcpid;
				} else {
					$fitcpid = $cpfitnames[$name][$cpname];
					$fit['presets'][$fitid]['chargepresets'][$fitcpid]['description'] = $cpdesc;
				}
			}
		}
		foreach($fitnames as $name => $id) {
			if(isset($clfnames[$name])) {
				foreach($cpfitnames[$name] as $cpname => $cpid) {
					if(!isset($cpclfnames[$name][$cpname])) {
						use_preset($fit, $id, false);
						remove_charge_preset($fit, $cpid);
					}
				}
			} else {
				remove_preset($fit, $id);
			}
		}

		if(isset($clf['X-Osmium-current-presetid'])) {
			$presetname = $clf['presets'][$clf['X-Osmium-current-presetid']]['presetname'];
			use_preset($fit, $fitnames[$presetname]);

			if(isset($clf['X-Osmium-current-chargepresetid'])) {
				foreach($clf['presets'][$clf['X-Osmium-current-presetid']]['chargepresets'] as $cp) {
					if($cp['id'] == $clf['X-Osmium-current-chargepresetid']) {
						use_charge_preset($fit, $cpfitnames[$presetname][$cp['name']]);
						break;
					}
				}
			}

			synchronize_preset_from_clf_1($fit, $clf['presets'][$clf['X-Osmium-current-presetid']]);
		}
	}

	if(isset($clf['drones'])) {
		$clfnames = array();
		$fitnames = array();

		foreach($clf['drones'] as $id => $p) {
			$clfnames[$p['presetname']] = $id;
		}
		foreach($fit['dronepresets'] as $id => $p) {
			$fitnames[$p['name']] = $id;
		}

		foreach($clfnames as $name => $id) {
			$pdesc = isset($clf['drones'][$id]['presetdescription']) ?
				$clf['drones'][$id]['presetdescription'] : '';

			if(!isset($fitnames[$name])) {
				$fitid = create_drone_preset($fit, $name, $pdesc);
				$fitnames[$name] = $fitid;
				use_drone_preset($fit, $fitid);
				synchronize_drone_preset_from_clf_1($fit, $clf['drones'][$id]);
			} else {
				$fitid = $fitnames[$name];
				$fit['dronepresets'][$fitid]['description'] = $pdesc;
			}
		}
		foreach($fitnames as $name => $id) {
			if(!isset($clfnames[$name])) {
				remove_drone_preset($fit, $id);
			}
		}

		if(isset($clf['X-Osmium-current-dronepresetid'])) {
			$name = $clf['drones'][$clf['X-Osmium-current-dronepresetid']]['presetname'];
			use_drone_preset($fit, $fitnames[$name]);
			synchronize_drone_preset_from_clf_1($fit, $clf['drones'][$clf['X-Osmium-current-dronepresetid']]);
		}
	}

	return true;
}

/** @internal */
function synchronize_preset_from_clf_1(&$fit, $clfp) {
	static $clfstates = array(
		'offline' => STATE_OFFLINE,
		'online' => STATE_ONLINE,
		'active' => STATE_ACTIVE,
		'overloaded' => STATE_OVERLOADED,
		);

	$clfmods = array();

	foreach(isset($clfp['modules']) ? $clfp['modules'] : array() as $m) {
		/* add_module() is lazy, it's okay to blindly call it here */
		add_module($fit, $m['index'], $m['typeid'], $clfstates[$m['state']]);

		$type = get_module_slottype($fit, $m['typeid']);
		$clfmods[$type][$m['index']] = $m['typeid'];
	}

	foreach($fit['modules'] as $type => $mods) {
		foreach($mods as $index => $m) {
			if(isset($clfmods[$type][$index])) continue;
			remove_module($fit, $index, $m['typeid']);
		}
	}
}

/** @internal */
function synchronize_drone_preset_from_clf_1(&$fit, $clfp) {

}
