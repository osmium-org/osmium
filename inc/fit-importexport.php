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
 * Try to parse a loadout in the EVE XML format.
 *
 * @param $e SimpleXMLElement to parse the loadout from, should be the
 * <loadout> element.
 *
 * @param $errors the array to store any import errors into; errors
 * will be appended at the end of the array.
 */
function try_parse_fit_from_eve_xml(\SimpleXMLElement $e, &$errors) {
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
	select_ship($fit, $row[0]);

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
	foreach($modules as $type => $m) {
		foreach($m as $i => $typename) {
			if(!isset($typename_to_id[$typename])) {
				$errors[] = 'Could not find typeID of "'.$typename.'". Skipped.';
				continue;
			}
			$realmodules[$type][$i] = $typename_to_id[$typename];
		}
	}
	foreach($recover_modules as $typename) {
		if(!isset($typename_to_id[$typename])) {
			$errors[] = 'Could not find typeID of "'.$typename.'". Skipped.';
			continue;
		}
		/* "low" does not matter here, it will be corrected in update_modules later. */
		$realmodules['low'][] = $typename_to_id[$typename];
	}

	$realdrones = array();
	foreach($drones as $drone) {
		if(!isset($typename_to_id[$drone['typename']])) {
			$errors[] = 'Could not find typeID of "'.$drone['typename'].'". Skipped.';
			continue;
		}
		
		$typeid = $typename_to_id[$drone['typename']];
		if(!isset($realdrones[$typeid])) $realdrones[$typeid]['quantityinbay'] = 0;
		$realdrones[$typeid]['quantityinbay'] += $drone['count'];
	}

	add_modules_batch($fit, $realmodules);
	add_drones_batch($fit, $realdrones);

	$fit['metadata']['name'] = $name;
	$fit['metadata']['description'] = $description;
	$fit['metadata']['tags'] = array();
	$fit['metadata']['view_permission'] = VIEW_OWNER_ONLY;
	$fit['metadata']['edit_permission'] = EDIT_OWNER_ONLY;
	$fit['metadata']['visibility'] = VISIBILITY_PUBLIC;

	return $fit;
}

/**
 * Export a fit to the common loadout format (CLF).
 *
 * @returns a string containing the JSON object.
 *
 * @warning EXPERIMENTAL, the CLF is still a draft! Use for testing
 * purposes only!
 *
 * @todo fetch TQ version
 */
function export_to_common_loadout_format($fit, $minify = false, $extraprops = true) {
	static $statenames = null;
	if($statenames === null) $statenames = get_state_names();

	$json = array('clf-version' => 1);
	if($extraprops) {
		$json['X-generatedby'] = 'Osmium-'.\Osmium\VERSION;
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

	$json['ship']['typeid'] = (int)$fit['ship']['typeid'];
	if(!$minify) {
		$json['ship']['typename'] = $fit['ship']['typename'];
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
					$jsonmodule['index'] = $index;
				}

				/* Only put state if it is not the default state */
				list($isactivable, ) = get_module_states($fit, $module['typeid']);
				$state = $module['state'] === null ? $module['old_state'] : $module['state'];
				if(($isactivable && $state != STATE_ACTIVE)
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

	$flags = $minify ? 0 : JSON_PRETTY_PRINT;
	return json_encode($json, $flags);
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
		$md .= "    BEGIN gzCLF BLOCK\n    ".wordwrap(base64_encode(gzcompress(export_to_common_loadout_format($fit, true))), 68, "\n    ", true)."\n    END gzCLF BLOCK\n\n";
	}

	return $md;
}