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

namespace Osmium\Json\ViewLoadoutAlter;

require __DIR__.'/../../inc/root.php';
require __DIR__.'/../../inc/ajax_common.php';

if(!\Osmium\AjaxCommon\get_green_fit($fit, $cachename, $loadoutid, $revision)) {
	\Osmium\Chrome\return_json(array());
}

$types = implode('|', \Osmium\Fit\get_stateful_slottypes());
foreach($_GET as $k => $v) {
	if(!preg_match('%^('.$types.')([0-9]+)$%', $k, $matches)) {
		continue;
	}
	list(, $type, $index) = $matches;
	$index = intval($index);

	\Osmium\Fit\change_module_state_by_location($fit, $type, $index, (int)$v);
}

if(isset($_GET['toggletype']) && isset($_GET['toggleindex']) 
   && in_array($_GET['toggletype'], \Osmium\Fit\get_stateful_slottypes())) {
	$index = intval($_GET['toggleindex']);
	$type = $_GET['toggletype'];
	$direction = isset($_GET['toggledirection']) && $_GET['toggledirection'] === 'true';
	\Osmium\Fit\toggle_module_state($fit, $index, $fit['modules'][$type][$index]['typeid'], $direction);
}

foreach($fit['drones'] as $typeid => $d) {
	$quantityinspace = isset($_GET['droneinspace'.$typeid]) ?
		intval($_GET['droneinspace'.$typeid]) : 0;
	\Osmium\Fit\dispatch_drones($fit, $typeid, 'space', $quantityinspace);
}

if(isset($_GET['transferdrone']) && $_GET['transferdrone'] > 0) {
	$typeid = intval($_GET['transferdrone']);
	$quantity = intval($_GET['transferquantity']);
	$from = $_GET['transferfrom'];

	\Osmium\Fit\transfer_drone($fit, $typeid, $from, $quantity);
}

\Osmium\State\put_cache($cachename, $fit, 7200);

$array = 
	array(
		'drones' => array_values($fit['drones']),
		'dronebandwidth' => \Osmium\Dogma\get_ship_attribute($fit, 'droneBandwidth'),
		'usedbandwidth' => \Osmium\Fit\get_used_drone_bandwidth($fit),
		'attributes' => \Osmium\Chrome\get_formatted_loadout_attributes($fit, '..'),
		'states' => \Osmium\AjaxCommon\get_module_states($fit),
		'ranges' => \Osmium\AjaxCommon\get_module_ranges($fit),
		);

\Osmium\Chrome\return_json($array);
