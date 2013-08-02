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

/* Minify generated JSON (default is to pretty indent). */
const CLF_EXPORT_MINIFY = 1;

/* Include non-standard properties. */
const CLF_EXPORT_EXTRA_PROPERTIES = 2;

/* Include Osmium-specific non-standard properties. */
const CLF_EXPORT_INTERNAL_PROPERTIES = 4;

/* Only export the presets currently selected. */
const CLF_EXPORT_SELECTED_PRESETS_ONLY = 8;

/* Strip all metadata from the generated JSON. This includes
 * descriptions, author names, etc. Is superseeded by the other
 * options like EXPORT_INTERNAL_PROPERTIES. */
const CLF_EXPORT_STRIP_METADATA = 16;

/* The default options used when none are explicitely given. */
const CLF_EXPORT_DEFAULT_OPTS = 2;





/**
 * Try to parse a loadout from a CLF string (containing JSON-encoded
 * data). Any errors will be put in $errors.
 *
 * Returns false if there was an unrecoverable error, or a $fit.
 */
function try_parse_fit_from_common_loadout_format($jsonstring, &$errors) {
	require_once __DIR__.CLF_PATH;

	$status = \CommonLoadoutFormat\validate_clf($jsonstring, $errors);
	if($status !== \CommonLoadoutFormat\OK && $status !== \CommonLoadoutFormat\OK_WITH_WARNINGS) {
		$errors[] = 'validate_clf() found fatal errors.';
		return false;
	}

	$json = json_decode($jsonstring, true);
	$version =  $json['clf-version'];
	if(!function_exists($parse = 'Osmium\Fit\clf_parse_'.$version)) {
		$errors[] = "Fatal: unsupported CLF version.";
		return false;
	}

	return $parse($json, $errors);
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

	if(isset($json['client-version']) && is_int($json['client-version'])) {
		$fit['metadata']['evebuildnumber'] = get_closest_version_by_build($json['client-version'])['build'];
	}

	if(isset($json['X-Osmium-current-presetid'])
	   && isset($fit['presets'][$json['X-Osmium-current-presetid']])) {
		use_preset($fit, $json['X-Osmium-current-presetid']);
	} else {
		\reset($fit['presets']);
		use_preset($fit, key($fit['presets']));
	}

	if(isset($json['X-Osmium-current-chargepresetid'])
	   && isset($fit['chargepresets'][$json['X-Osmium-current-chargepresetid']])) {
		use_charge_preset($fit, $json['X-Osmium-current-chargepresetid']);
	} else {
		\reset($fit['chargepresets']);
		use_charge_preset($fit, key($fit['chargepresets']));
	}

	if(isset($json['X-Osmium-current-dronepresetid'])
	   && isset($fit['dronepresets'][$json['X-Osmium-current-dronepresetid']])) {
		use_drone_preset($fit, $json['X-Osmium-current-dronepresetid']);
	} else {
		\reset($fit['dronepresets']);
		use_drone_preset($fit, key($fit['dronepresets']));
	}

	if(isset($json['metadata']['X-Osmium-skillset'])) {
		$a = \Osmium\State\get_state('a');
		use_skillset_by_name($fit, $json['metadata']['X-Osmium-skillset'], $a);
	}

	if(isset($json['X-Osmium-fleet'])) {
		$a = \Osmium\State\get_state('a');
		foreach($json['X-Osmium-fleet'] as $k => $fl) {
			if($k !== 'fleet' && $k !== 'wing' && $k !== 'squad') continue;

			$ss = isset($fl['skillset']) ? $fl['skillset'] : 'All V';
			$fitting = isset($fl['fitting']) ? $fl['fitting'] : '';

			if($fitting) {
				$boosterfit = try_get_fit_from_remote_format($fitting, $errors);

				if($boosterfit === false) {
					create($boosterfit);
				}
			} else {
				create($boosterfit);
			}

			call_user_func_array(__NAMESPACE__.'\set_'.$k.'_booster', array(&$fit, $boosterfit));
			use_skillset_by_name($fit['fleet'][$k], $ss, $a);
			$fit['fleet'][$k]['__id'] = $fitting;
		}
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

		if(isset($preset['implants']) && is_array($preset['implants'])) {
			clf_parse_implants_1($fit, $preset['implants'], $errors);
		}

		if(isset($preset['boosters']) && is_array($preset['boosters'])) {
			clf_parse_implants_1($fit, $preset['boosters'], $errors);
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
		$m['index'] = $index;

		if(isset($m['state']) && isset($nstates[$m['state']])) {
			$state = $nstates[$m['state']];
			list($isactivable, $isoverloadable) = get_module_states($fit, $m['typeid']);
			if(!$isactivable) $state = min($state, STATE_ONLINE);
			else if(!$isoverloadable) $state = min($state, STATE_ACTIVE);
		} else {
			$state = null;
		}

		add_module($fit, $index, $m['typeid'], $state);
		$m['slottype'] = $type;
	}
}

/** @internal */
function clf_parse_implants_1(&$fit, &$implants, &$errors) {
	foreach($implants as $i) {
		if(\CommonLoadoutFormat\check_typeof_type($i['typeid'], 'implant')
		   || \CommonLoadoutFormat\check_typeof_type($i['typeid'], 'booster')) {
			add_implant($fit, $i['typeid']);

			if(isset($i['X-sideeffects']) && is_array($i['X-sideeffects'])) {
				foreach($i['X-sideeffects'] as $effectid) {
					toggle_implant_side_effect($fit, $i['typeid'], $effectid, true);
				}
			}
		}
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

	/* Use creation date to guess EVE database version, if
	 * client-version is present in the root object, this will be
	 * overwritten later */
	if(isset($metadata['creationdate'])) {
		$datetime = date_create_from_format(\DateTime::RFC2822, $metadata['creationdate']);
		if($datetime !== false && ($ts = date_timestamp_get($datetime)) > 0) {
			$fit['metadata']['evebuildnumber'] = get_closest_version_by_time($ts)['build'];
		}
	}

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
 * Try to parse a loadout from a gzCLF block.
 *
 * gzCLF is just a base64-encoded, gz-compressed CLF JSON string with
 * easily identifiable delimiters.
 */
function try_parse_fit_from_gzclf($source, &$errors) {
	$source = explode('BEGIN gzCLF BLOCK', $source, 2);
	if(count($source) === 1) {
		$errors[] = 'Did not find a gzCLF block.';
		return false;
	}

	$source = explode('END gzCLF BLOCK', $source[1], 2);
	if(count($source) === 1) {
		$errors[] = 'Did not find a gzCLF block.';
		return false;
	}

	return try_parse_fit_from_gzclf_raw($source[0]);
}

/** @internal */
function try_parse_fit_from_gzclf_raw($gzclf, &$errors) {
	$clf = @gzuncompress(base64_decode(html_entity_decode($gzclf, ENT_XML1)));
	if($clf === false) {
		$errors[] = 'Error parsing the gzCLF block.';
		return false;
	}

	return try_parse_fit_from_common_loadout_format($clf, $errors);
}





/**
 * Export a fit to the common loadout format (CLF), latest supported
 * version.
 *
 * @param $opts a bitwise OR-d list of CLF_EXPORT_* constants.
 *
 * @returns a string containing the JSON data.
 */
function export_to_common_loadout_format($fit, $opts = CLF_EXPORT_DEFAULT_OPTS) {
	return json_encode(
		export_to_common_loadout_format_1($fit, $opts),
		($opts & CLF_EXPORT_MINIFY) ? 0 : JSON_PRETTY_PRINT
	);
}

/**
 * Export a fit to the common loadout format (CLF) version 1.
 *
 * @returns the array to be serialized to JSON with json_encode().
 */
function export_to_common_loadout_format_1($fit, $opts = CLF_EXPORT_DEFAULT_OPTS) {
	static $statenames = null;
	if($statenames === null) $statenames = get_state_names();

	$minify = $opts & CLF_EXPORT_MINIFY;
	$extraprops = $opts & CLF_EXPORT_EXTRA_PROPERTIES;
	$osmiumextraprops = $opts & CLF_EXPORT_INTERNAL_PROPERTIES;
	$allpresets = ~$opts & CLF_EXPORT_SELECTED_PRESETS_ONLY;
	$hasmeta = ~$opts & CLF_EXPORT_STRIP_METADATA;

	$json = array('clf-version' => 1);

	if($hasmeta) {
		$json['client-version'] = (int)$fit['metadata']['evebuildnumber'];
	}

	if($extraprops) {
		$json['X-generatedby'] = 'Osmium-'.\Osmium\get_osmium_version();
	}

	if($hasmeta) {
		if(isset($fit['metadata']['name'])) {
			$json['metadata']['title'] = $fit['metadata']['name'];
		}

		if(isset($fit['metadata']['description'])) {
			$json['metadata']['description'] = $fit['metadata']['description'];
		}

		if(isset($fit['metadata']['creation_date'])) {
			$json['metadata']['creationdate'] = gmdate('r', $fit['metadata']['creation_date']);
		}
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

		$json['metadata']['X-Osmium-skillset'] = $fit['metadata']['skillset'];
		$json['metadata']['X-Osmium-capreloadtime'] = true;
		$json['metadata']['X-Osmium-dpsreloadtime'] = false;
		$json['metadata']['X-Osmium-tankreloadtime'] = false;

		$json['X-damage-profile'] = array("Uniform", [ .25, .25, .25, .25 ]);

		if(isset($fit['fleet'])) {
			foreach($fit['fleet'] as $k => $f) {
				$json['X-Osmium-fleet'][$k] = [
					'fitting' => $f['__id'],
					'skillset' => $f['metadata']['skillset'],
				];
			}
		}
	}

	if(isset($fit['ship']['typeid'])) {
		/* Allow exporting incomplete loadouts (for internal use),
		 * even though it is forbidden by the spec */
		$json['ship']['typeid'] = (int)$fit['ship']['typeid'];
		if(!$minify) {
			$json['ship']['typename'] = $fit['ship']['typename'];
		}
	}

	foreach($fit['presets'] as $pid => $preset) {
		if(!$allpresets && $pid != $fit['modulepresetid']) continue;

		$jsonpreset = array();

		if($hasmeta) {
			$jsonpreset['presetname'] = $preset['name'];
			if($preset['description'] != '') {
				$jsonpreset['presetdescription'] = $preset['description'];
			}
		}

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
					$jsonmodule['state'] = $statenames[$state][2];
				}

				foreach($preset['chargepresets'] as $cpid => $chargepreset) {
					if(!$allpresets && $cpid != $fit['chargepresetid']) continue;
					if(!isset($chargepreset['charges'][$type][$index])) continue;

					$charge = $chargepreset['charges'][$type][$index];
					$jsoncharge = array();

					$jsoncharge['typeid'] = (int)$charge['typeid'];
					if(!$minify) {
						$jsoncharge['typename'] = $charge['typename'];
					}
					if((int)$cpid !== 0) {
						$jsoncharge['cpid'] = (int)$cpid;
					}

					$jsonmodule['charges'][] = $jsoncharge;
				}

				$jsonpreset['modules'][] = $jsonmodule;
			}
		}

		foreach($preset['chargepresets'] as $cpid => $chargepreset) {
			if(!$allpresets && $cpid != $fit['chargepresetid']) continue;

			$jsoncp = array();

			$jsoncp['id'] = (int)$cpid;

			if($hasmeta) {
				$jsoncp['name'] = $chargepreset['name'];
				if($chargepreset['description'] != '') {
					$jsoncp['description'] = $chargepreset['description'];
				}
			} else {
				/* The field is required and must be unique */
				$jsoncp['name'] = (string)$cpid;
			}

			$jsonpreset['chargepresets'][] = $jsoncp;
		}

		foreach($preset['implants'] as $i) {
			$jsonimplant = array('typeid' => (int)$i['typeid']);
			if(!$minify) {
				$jsonimplant['typename'] = $i['typename'];
				$jsonimplant['slot'] = (int)$i['slot'];
			}

			if($extraprops & isset($i['sideeffects'])) {
				foreach($i['sideeffects'] as $effectid) {
					$jsonimplant['X-sideeffects'][] = (int)$effectid;
				}

				if(!isset($jsonimplant['X-sideeffects'])) continue;
				sort($jsonimplant['X-sideeffects']);
			}

			if(get_groupid($i['typeid']) != GROUP_Booster) {
				$jsonpreset['implants'][] = $jsonimplant;
			} else {
				$jsonpreset['boosters'][] = $jsonimplant;
			}
		}

		if(!$minify) {
			$slotsort = function($x, $y) { return $x['slot'] - $y['slot']; };
			if(isset($jsonpreset['implants'])) usort($jsonpreset['implants'], $slotsort);
			if(isset($jsonpreset['boosters'])) usort($jsonpreset['boosters'], $slotsort);
		}

		$json['presets'][] = $jsonpreset;
	}

	foreach($fit['dronepresets'] as $dpid => $dronepreset) {
		if(!$allpresets && $dpid != $fit['dronepresetid']) continue;

		$jsondp = array();

		if($hasmeta) {
			$jsondp['presetname'] = $dronepreset['name'];
			if($dronepreset['description'] != '') {
				$jsondp['presetdescription'] = $dronepreset['description'];
			}
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
 *
 * Takes the same arguments as export_to_common_loadout_format().
 */
function export_to_gzclf($fit, $opts = CLF_EXPORT_MINIFY) {
	$rawgzclf = export_to_gzclf_raw($fit, $opts);

	return "BEGIN gzCLF BLOCK\n"
		.wordwrap($rawgzclf, 72, "\n", true)
		."\nEND gzCLF BLOCK\n";
}

/** @internal */
function export_to_gzclf_raw($fit, $opts = CLF_EXPORT_MINIFY) {
	$clfstring = export_to_common_loadout_format($fit, $opts);
	return base64_encode(gzcompress($clfstring));
}





/**
 * Imports the loadout in $clfstring to $fit. This is usually faster
 * than calling try_parse_fit_from_common_loadout_format if $fit is
 * already a fitting somewhat close to the result.
 */
function synchronize_from_clf_1(&$fit, $clf, array &$errors = array()) {
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

	if(isset($clf['client-version']) && $clf['client-version'] != $fit['metadata']['evebuildnumber']) {
		$fit['metadata']['evebuildnumber'] =
			get_closest_version_by_build((int)$clf['client-version'])['build'];
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

		if(isset($meta['X-Osmium-skillset'])) {
			$a = \Osmium\State\get_state('a', null);
			use_skillset_by_name($fit, $meta['X-Osmium-skillset'], $a); /* Lazy function */
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
				synchronize_preset_from_clf_1($fit, $clf['presets'][$id], $cpid);
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

			synchronize_preset_from_clf_1($fit,
                                          $clf['presets'][$clf['X-Osmium-current-presetid']],
                                          $clf['X-Osmium-current-chargepresetid']);
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

	if(isset($clf['X-Osmium-fleet'])) {
		$a = \Osmium\State\get_state('a');
		foreach($clf['X-Osmium-fleet'] as $k => $booster) {
			if($k !== 'fleet' && $k !== 'wing' && $k !== 'squad') continue;
			$ss = isset($booster['skillset']) ? $booster['skillset'] : 'All V';
			$fitting = isset($booster['fitting']) ? $booster['fitting'] : '';

			if(isset($fit['fleet'][$k]) && $fit['fleet'][$k]['__id'] === $fitting) {
				use_skillset_by_name($fit['fleet'][$k], $ss, $a);
				continue;
			}

			if($fitting) {
				if(!isset($errors['fleet'][$k])) $errors['fleet'][$k] = array();
				$boosterfit = try_get_fit_from_remote_format($fitting, $errors['fleet'][$k]);

				if($boosterfit === false) {
					create($boosterfit);
				}
			} else {
				create($boosterfit);
			}

			call_user_func_array(__NAMESPACE__.'\set_'.$k.'_booster', array(&$fit, $boosterfit));
			use_skillset_by_name($fit['fleet'][$k], $ss, $a);
			$fit['fleet'][$k]['__id'] = $fitting;
		}
	}

	if(isset($fit['fleet'])) {
		foreach($fit['fleet'] as $k => $booster) {
			if(!isset($clf['X-Osmium-fleet'][$k])) {
				call_user_func_array(__NAMESPACE__.'\set_'.$k.'_booster', array(&$fit, NULL));
			}
		}
	}

	return true;
}

/** @internal */
function synchronize_preset_from_clf_1(&$fit, $clfp, $cpid) {
	static $clfstates = array(
		'offline' => STATE_OFFLINE,
		'online' => STATE_ONLINE,
		'active' => STATE_ACTIVE,
		'overloaded' => STATE_OVERLOADED,
		);

	$clfmods = array();
	$clfcharges = array();
	$clfimplants = array();
	$clfsideeffects = array();

	foreach(isset($clfp['modules']) ? $clfp['modules'] : array() as $m) {
		/* add_module() is lazy, it's okay to blindly call it here */
		add_module($fit, $m['index'], $m['typeid'], $clfstates[$m['state']]);

		$type = get_module_slottype($fit, $m['typeid']);

        if(isset($m['charges'])) {
            foreach($m['charges'] as $c) {
                if(!isset($c['cpid'])) {
                    $c['cpid'] = 0;
                }

                if($c['cpid'] != $cpid) {
                    continue;
                }

                add_charge($fit, $type, $m['index'], $c['typeid']);
                $clfcharges[$type][$m['index']] = $c['typeid'];
                break;
            }
        }

		$clfmods[$type][$m['index']] = $m['typeid'];
	}

	foreach(isset($clfp['implants']) ? $clfp['implants'] : array() as $i) {
		add_implant($fit, $i['typeid']);
		$clfimplants[$i['typeid']] = true;
	}

	foreach(isset($clfp['boosters']) ? $clfp['boosters'] : array() as $i) {
		add_implant($fit, $i['typeid']);
		$clfimplants[$i['typeid']] = true;

		if(isset($i['X-sideeffects']) && is_array($i['X-sideeffects'])) {
			foreach($i['X-sideeffects'] as $effectid) {
				\Osmium\Fit\toggle_implant_side_effect($fit, $i['typeid'], $effectid, true);
				$clfsideeffects[$i['typeid']][$effectid] = true;
			}
		}
	}

    foreach($fit['charges'] as $type => $charges) {
        foreach($charges as $index => $c) {
            if(isset($clfcharges[$type][$index])) continue;

            /* Also check if the module was deleted, in this case the
             * charge will be deleted automatically when the module is
             * deleted */
            if(!isset($clfmods[$type][$index])) continue;

            remove_charge($fit, $type, $index);
        }
    }

	foreach($fit['modules'] as $type => &$mods) {
		foreach($mods as $index => $m) {
			if(isset($clfmods[$type][$index])) continue;
			remove_module($fit, $index, $m['typeid']);
		}

		ksort($mods);
	}

	foreach($fit['implants'] as $typeid => $i) {
		if(isset($i['sideeffects'])) {
			foreach($i['sideeffects'] as $effectid) {
				if(isset($clfsideeffects[$typeid][$effectid])) continue;
				toggle_implant_side_effect($fit, $typeid, $effectid, false);
			}
		}

		if(isset($clfimplants[$typeid])) continue;
		remove_implant($fit, $typeid);
	}
}

/** @internal */
function synchronize_drone_preset_from_clf_1(&$fit, $clfp) {
	$clfd = array();

	foreach(array('inbay', 'inspace') as $loc) {
		if(!isset($clfp[$loc])) continue;

		foreach($clfp[$loc] as $d) {
			if(!isset($clfd[$d['typeid']])) {
				$clfd[$d['typeid']] = array(
					'inbay' => 0,
					'inspace' => 0,
				);
			}
			$clfd[$d['typeid']][$loc] += $d['quantity'];
		}
	}

	foreach($clfd as $typeid => $q) {
		if($q['inbay'] == 0 && $q['inspace'] == 0) {
			unset($clfd[$typeid]);
			continue;
		}

		if(isset($fit['drones'][$typeid])) {
			$inbay = $fit['drones'][$typeid]['quantityinbay'];
			$inspace = $fit['drones'][$typeid]['quantityinspace'];
		} else {
			$inbay = 0;
			$inspace = 0;
		}

		if($q['inbay'] > $inbay) {
			add_drone($fit, $typeid, $q['inbay'] - $inbay, 0);
		} else if($q['inbay'] < $inbay) {
			remove_drone($fit, $typeid, 'bay', $inbay - $q['inbay']);
		}

		if($q['inspace'] > $inspace) {
			add_drone($fit, $typeid, 0, $q['inspace'] - $inspace);
		} else if($q['inspace'] < $inspace) {
			remove_drone($fit, $typeid, 'space', $inspace - $q['inspace']);
		}
	}

	foreach($fit['drones'] as $typeid => $d) {
		if(!isset($clfd[$typeid])) {
			remove_drone($fit, $typeid, 'bay', $d['quantityinbay']);
			if(isset($fit['drones'][$typeid])) {
				remove_drone($fit, $typeid, 'space', $d['quantityinspace']);
			}
		}
	}
}

/** @internal */
function try_get_fit_from_remote_format($remote, array &$errors = array()) {
	/* Try and parse a DNA fit */
	if(preg_match('%(?P<dna>'.\Osmium\Fit\DNA_REGEX.')%', $remote, $match)) {
		$fit = try_parse_fit_from_shipdna($match['dna'], '', $errors);
		if($fit === false) return false;
	}

	/* Try and parse some gzCLF */
	else if(preg_match('%^gzclf://%', $remote)) {
		$fit = try_parse_fit_from_gzclf_raw(substr($remote, strlen('gzclf://')), $errors);
		if($fit === false) return false;
	}

	/* Try and get a fit from its URI */
	else if(($parts = parse_url($remote)) !== false) {
		if(!isset($parts['host']) || $parts['host'] !== $_SERVER['HTTP_HOST']) {
			$errors[] = "Only local URIs (".$_SERVER['HTTP_HOST'].") are supported.";
			return false;
		}
		if(!isset($parts['path'])) {
			$errors[] = "Couldn't make sense of the supplied URI path.";
			return false;
		}

		if(!preg_match(
			\Osmium\PUBLIC_LOADOUT_RULE,
			$parts['path'],
			$match
		) && !preg_match(
			\Osmium\PRIVATE_LOADOUT_RULE,
			$parts['path'],
			$match
		)) {
			$errors[] = "Supplied URI isn't a loadout URI.";
			return false;
		}

		if(!\Osmium\State\can_view_fit($match['loadoutid'])) {
			$errors[] = "Loadout not found.";
			return false;
		}

		$fit = \Osmium\Fit\get_fit(
			$match['loadoutid'], 
			(isset($match['revision']) && $match['revision'] > 0) ? $match['revision'] : null
		);

		if($fit === false) {
			$errors[] = "get_fit() returned false, please report.";
			return false;
		}

		if(!\Osmium\State\can_access_fit($fit)) {
			$errors[] = "Loadout exists but cannot be accessed.";
			return false;
		}

		if($fit['metadata']['visibility'] == VISIBILITY_PRIVATE && (
			!isset($match['privatetoken']) || $fit['metadata']['privatetoken'] != $match['privatetoken'])
		) {
			$errors[] = "This loadout is private, please use the full URI.";
			return false;
		}

		if(isset($match['fleet'])) {
			$t = $match['fleet'];

			if(!isset($fit['fleet'][$t]) || !isset($fit['fleet'][$t]['ship']['typeid'])
			   || !$fit['fleet'][$t]['ship']['typeid']) {
				$errors[] = "This loadout has no such {$t} booster.";
				return false;
			}

			$fit = $fit['fleet'][$t];
		} else {
			if(isset($match['preset']) && isset($fit['presets'][$match['preset']])) {
				use_preset($fit, $match['preset']);
			}

			if(isset($match['chargepreset']) && isset($fit['chargepresets'][$match['chargepreset']])) {
				use_charge_preset($fit, $match['chargepreset']);
			}

			if(isset($match['dronepreset']) && isset($fit['dronepresets'][$match['dronepreset']])) {
				use_drone_preset($fit, $match['dronepreset']);
			}
		}
	}

	else {
		$errors[] = "Unknown format.\n";
		return false;
	}

	/* Now strip everyhing that's useless in this context: other
	 * presets, and metadata. */
	$stripped = export_to_common_loadout_format(
		$fit,
		CLF_EXPORT_STRIP_METADATA | CLF_EXPORT_SELECTED_PRESETS_ONLY
	);
	$fit = try_parse_fit_from_common_loadout_format($stripped, $errors);

	if($fit === false) return false;
	\Osmium\Dogma\late_init($fit);
	return $fit;
}
