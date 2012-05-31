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

if(!\Osmium\State\is_logged_in()) {
	\Osmium\Chrome\return_json(array());
}


if(isset($_GET['token']) && $_GET['token'] == \Osmium\State\get_token()) {
	$fit = \Osmium\State\get_state('new_fit', array());
	$drones = array();
	$i = 0;
  
	while(isset($_GET['drone'.$i])) {
		$typeid = intval($_GET['drone'.$i]);
		$count = intval($_GET['count'.$i]);

		if(!isset($drones[$typeid])) $drones[$typeid] = 0;
		$drones[$typeid] += $count;
		++$i;
	}

	$old_drones = $fit['drones'];
	\Osmium\Fit\add_drones_batch($fit, $drones);

	foreach($old_drones as $typeid => $drone) {
		\Osmium\Fit\remove_drone($fit, $typeid, $drone['count']);
	}
	\Osmium\State\put_state('new_fit', $fit);
	\Osmium\Chrome\return_json(\Osmium\AjaxCommon\get_data_step_drone_select($fit));
} else {
	\Osmium\Chrome\return_json(array());
}
