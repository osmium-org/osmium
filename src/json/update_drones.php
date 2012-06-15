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

namespace Osmium\Json\UpdateDrones;

require __DIR__.'/../../inc/root.php';
require __DIR__.'/../../inc/ajax_common.php';

if(!isset($_GET['token']) || $_GET['token'] != \Osmium\State\get_token()) {
	\Osmium\Chrome\return_json(array());
}

$fit = \Osmium\State\get_state('new_fit', array());
$drones = array();

foreach($_GET as $k => $v) {
	if(!preg_match('%^in(bay|space)([0-9]+)$%', $k, $matches)) continue;
	list(, $type, $typeid) = $matches;
	$typeid = intval($typeid);
		
	if(!isset($drones[$typeid]['quantityin'.$type])) {
		$drones[$typeid]['quantityin'.$type] = 0;
	}

	$drones[$typeid]['quantityin'.$type] += intval($v);
}

$old_drones = $fit['drones'];
\Osmium\Fit\add_drones_batch($fit, $drones);

foreach($old_drones as $typeid => $drone) {
	\Osmium\Fit\remove_drone($fit, $typeid, 'bay', $drone['quantityinbay']);
	\Osmium\Fit\remove_drone($fit, $typeid, 'space', $drone['quantityinspace']);
}
\Osmium\State\put_state('new_fit', $fit);
\Osmium\Chrome\return_json(\Osmium\AjaxCommon\get_data_step_drone_select($fit));
