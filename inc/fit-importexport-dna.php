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

const DNA_REGEX = '([1-9][0-9]*(;[0-9]*)?:)*:+';

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

	create($fit);

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

		if(\CommonLoadoutFormat\check_typeof_type($typeid, 'module')) {
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
		}
		else if(\CommonLoadoutFormat\check_typeof_type($typeid, 'drone')) {
			add_drone_auto($fit, $typeid, $qty);
		}
		else if(\CommonLoadoutFormat\check_typeof_type($typeid, 'implant')
		        || \CommonLoadoutFormat\check_typeof_type($typeid, 'booster')) {
			/* Non-"standard" DNA, support it anyway */
			if($qty !== 1) {
				$errors[] = 'Adding implant '.$typeid.' only once (quantity of '.$qty.' specified).';
			}
			add_implant($fit, $typeid);
		}
		else if(\CommonLoadoutFormat\check_typeof_type($typeid, 'ship')) {
			select_ship($fit, $typeid);
		}
	}

	$fit['metadata']['tags'] = get_recommended_tags($fit);

	return $fit;
}





/**
 * Mangle a given DNA string into a more compact, more sensical
 * variations. Potentially destructive as it will stack modules
 * together.
 *
 * @note This function will produce non-standard DNA, so use the
 * results for internal things only.
 *
 * @note This function is indempotent.
 */
function mangle_dna($dna) {
	require_once __DIR__.CLF_PATH;

	$types = array(
		'ship' => [],
		'module' => [],
		'charge' => [],
		'drone' => [],
		'implant' => [],
		'booster' => [],
	);

	$dnaparts = explode(':', rtrim($dna, ':'));

	foreach($dnaparts as $dnapart) {
		if(strpos($dnapart, ';') !== false) {
			list($typeid, $qty) = explode(';', $dnapart, 2);
		} else {
			$typeid = $dnapart;
			$qty = 1;
		}

		$typeid = (int)$typeid;
		$qty = (int)$qty;

		foreach($types as $t => &$a) {
			if(!\CommonLoadoutFormat\check_typeof_type($typeid, $t)) {
				continue;
			}

			if(!isset($a[$typeid])) $a[$typeid] = 0;
			$a[$typeid] += $qty;
			break;
		}
	}

	$return = '';
	$types['ship'] = array_slice($types['ship'], -1, 1, true);
	foreach($types['ship'] as &$qty) { $qty = 1; }

	foreach($types as &$a) {
		ksort($a);

		foreach($a as $typeid => $qty) {
			$return .= ':'.$typeid.(($qty > 1) ? ';'.$qty : '');
		}
	}

	return substr($return, 1).'::';
}

/**
 * Similar to mangle_dna(), but it will instead return a unique
 * representant for the supplied $dna among a class of representants
 * (precisely defined by having the same ship and the same modules
 * parent typeIDs).
 *
 * @note This function, like mangle_dna(), is also indempotent.
 */
function uniquify_dna($dna) {
	require_once __DIR__.CLF_PATH;

	$types = array(
		'ship' => [],
		'module' => [],
	);

	$dnaparts = explode(':', rtrim($dna, ':'));

	foreach($dnaparts as $dnapart) {
		if(strpos($dnapart, ';') !== false) {
			list($typeid, $qty) = explode(';', $dnapart, 2);
		} else {
			$typeid = $dnapart;
			$qty = 1;
		}

		$typeid = (int)get_parent_typeid($typeid);
		$qty = (int)$qty;

		foreach($types as $t => &$a) {
			if(!\CommonLoadoutFormat\check_typeof_type($typeid, $t)) {
				continue;
			}

			if(!isset($a[$typeid])) $a[$typeid] = 0;
			$a[$typeid] += $qty;
			break;
		}
	}

	$return = '';
	$types['ship'] = array_slice($types['ship'], -1, 1, true);
	foreach($types['ship'] as &$qty) { $qty = 1; }

	foreach($types as &$a) {
		ksort($a);

		foreach($a as $typeid => $qty) {
			$return .= ':'.$typeid.(($qty > 1) ? ';'.$qty : '');
		}
	}

	return substr($return, 1).'::';
}





/**
 * Export a loadout to the EFT format. Use at your own risk.
 */
function export_to_dna($fit) {
	static $slotorder = array('high', 'medium', 'low', 'rig', 'subsystem');

	if(isset($fit['ship']['typeid'])) {
		$dna = $fit['ship']['typeid'];
	} else {
		$dna = '';
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

	foreach($fit['charges'] as $type => $sub) {
		foreach($sub as $index => $c) {
			$cv = \Osmium\Dogma\get_charge_attribute($fit, $type, $index, 'volume');
			$mc = \Osmium\Dogma\get_module_attribute($fit, $type, $index, 'capacity');

			if($cv > 1e-300) {
				$qty = (int)floor($mc / $cv);
			} else {
				$qty = 1;
			}

			if(!isset($tids[$c['typeid']])) {
				$tids[$c['typeid']] = $qty;
			} else {
				$tids[$c['typeid']] += $qty;
			}	
		}
	}

	foreach($fit['implants'] as $i) {
		$tids[$i['typeid']] = 1;
	}

	$ftids = array();
	foreach($tids as $tid => $qty) {
		$ftids[] = $tid.";".$qty;
	}

	$dna .= ':'.implode(':', $ftids);

	return ltrim($dna, ':').'::';
}
