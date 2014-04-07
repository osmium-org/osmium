<?php
/* Osmium
 * Copyright (C) 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\API\LoadoutAttributes;

require __DIR__.'/../../../inc/root.php';
require \Osmium\ROOT.'/inc/api_common.php';

$fit = \Osmium\API\get_fit_from_input_post_get();
\Osmium\Dogma\auto_init($fit);
$out = [];
$nout = 0;

foreach(explode('/', $_GET['attributes']) as $loc) {
	$keys = [];
	foreach(explode(',', $loc) as $loc) {
		$loc = explode(':', $loc, 2);
		if(count($loc) !== 2) {
			\Osmium\fatal(400, 'Syntax error near "'.\Osmium\Chrome\escape($loc[0]).'".');
		}

		list($k, $v) = $loc;
		switch($k) {

		case 'loc':
		case 'typeid':
		case 'slot':
		case 'index':
		case 'name':
			$keys[$k] = $v;
			break;

		case 'a':
			$keys[$k][] = $v;
			break;

		default:
			\Osmium\fatal(400, 'Unknown name "'.\Osmium\Chrome\escape($k).'".');
			break;

		}
	}

	if(!isset($keys['a']) || $keys['a'] === []) continue;

	if(!isset($keys['loc'])) {
		\Osmium\fatal(400, 'Location specifier "'.\Osmium\Chrome\escape($loc).'" has no loc.');
	}

	$dogmalocation = [];

	switch($keys['loc']) {
 
	case 'char':
		$dogmalocation[] = DOGMA_LOC_Char;
		if(!isset($keys['name'])) $keys['name'] = 'char';
		break;

	case 'ship':
		$dogmalocation[] = DOGMA_LOC_Ship;
		if(!isset($keys['name'])) $keys['name'] = 'ship';
		break;

	case 'implant':
		$dogmalocation[] = DOGMA_LOC_Implant;
		if(!isset($keys['typeid'])) \Osmium\fatal(400, 'loc:implant requires a typeid');
		if(!isset($fit['implants'][$keys['typeid']])) \Osmium\fatal(400, 'No such implant');
		$dogmalocation['implant_index'] = (int)$fit['implants'][$keys['typeid']]['dogma_index'];
		if(!isset($keys['name'])) $keys['name'] = 'implant-'.$keys['typeid'];
		break;

	case 'skill':
		$dogmalocation[] = DOGMA_LOC_Skill;
		if(!isset($keys['typeid'])) \Osmium\fatal(400, 'loc:skill requires a typeid');
		$dogmalocation['skill_typeid'] = (int)$keys['typeid'];
		if(!isset($keys['name'])) $keys['name'] = 'skill-'.$keys['typeid'];
		break;

	case 'drone':
		$dogmalocation[] = DOGMA_LOC_Drone;
		if(!isset($keys['typeid'])) \Osmium\fatal(400, 'loc:drone requires a typeid');
		$dogmalocation['drone_typeid'] = (int)$keys['typeid'];
		if(!isset($keys['name'])) $keys['name'] = 'drone-'.$keys['typeid'];
		break;

	case 'module':
		$dogmalocation[] = DOGMA_LOC_Module;
		break;

	case 'charge':
		$dogmalocation[] = DOGMA_LOC_Charge;
		break;

	default:
		\Osmium\fatal(400, 'Unknown loc value "'.\Osmium\Chrome\escape($keys['loc']).'".');
		break;

	}

	switch($keys['loc']) {

	case 'module':
	case 'charge':
		if(isset($keys['slot']) && isset($keys['index'])) {
			$dogmalocation['module_index'] = $fit['modules'][$keys['slot']][$keys['index']]['dogma_index'];
			if(!isset($keys['name'])) $keys['name'] = $keys['loc'].'-'.$keys['slot'].'-'.$keys['index'];
		} else if(isset($keys['typeid'])) {
			if(isset($keys['index'])) {
				foreach($fit['modules'] as $sub) {
					if(isset($sub[$keys['index']]) && $sub[$keys['index']]['typeid'] == $keys['typeid']) {
						$dogmalocation['module_index'] = $sub[$keys['index']]['dogma_index'];
						if(!isset($keys['name'])) $keys['name'] = $keys['loc']
							                          .'-'.$keys['typeid'].'-'.$keys['index'];
						break;
					}
				}
			} else {
				foreach($fit['modules'] as $sub) {
					foreach($sub as $m) {
						if($m['typeid'] == $keys['typeid']) {
							$dogmalocation['module_index'] = $m['dogma_index'];
							if(!isset($keys['name'])) $keys['name'] = $keys['loc'].'-'.$keys['typeid'];
							break 2;
						}
					}
				}
			}
		} else {
			\Osmium\fatal(400, 'loc:module or loc:charge require slot+index, typeid+index or typeid');
		}
		if(!isset($dogmalocation['module_index'])) {
			\Osmium\fatal(400, 'Loadout has no such module');
		}
		break;

	default:
		break;

	}

	foreach($keys['a'] as $att) {
		$ret = dogma_get_location_attribute(
			$fit['__dogma_context'], $dogmalocation, \Osmium\Dogma\get_att($att), $dogma_out
		);

		$out[$keys['name']][$att] = $ret === DOGMA_OK ? $dogma_out : null;

		if(++$nout >= 50) {
			break 2;
		}
	}
}

\Osmium\API\outputp(
	json_encode($out, isset($_GET['minify']) && $_GET['minify'] ? 0 : JSON_PRETTY_PRINT),
	'application/json'
);
