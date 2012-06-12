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

namespace Osmium\Json\UpdateChargePreset;

require __DIR__.'/../../inc/root.php';
require __DIR__.'/../../inc/ajax_common.php';

if(!\Osmium\State\is_logged_in()) {
	die();
}

if(!isset($_GET['token']) || $_GET['token'] != \Osmium\State\get_token()) {
	die();
}

$fit = \Osmium\State\get_state('new_fit', array());

if($_GET['action'] == 'update') {
	$name = $_GET['name'];
	$oldname = isset($_GET['old_name']) ? $_GET['old_name'] : $name;

	$slots = implode('|', \Osmium\Fit\get_slottypes());
	$charges = array();

	foreach($_GET as $k => $v) {
		if(!preg_match('%('.$slots.')([0-9]+)%', $k, $matches)) continue;
		list(, $type, $index) = $matches;
		$charges[$type][$index] = true;
		\Osmium\Fit\add_charge($fit, $name, $type, $index, intval($v));
	}

	/* Not always defined (ie if we just committed a blank preset) */
	if(isset($fit['charges'][$name])) {
		foreach($fit['charges'][$name] as $type => $a) {
			foreach($a as $index => $charge) {
				if(!isset($charges[$type][$index])) {
					\Osmium\Fit\remove_charge($fit, $name, $type, $index);
				}
			}
		}
	} else {
		$fit['charges'][$name] = array();
	}

	\Osmium\Fit\use_charge_preset($fit, $name);
} else if($_GET['action'] == 'delete') {
	$name = $_GET['name'];
	\Osmium\Fit\remove_charge_preset($fit, $name);
}

\Osmium\State\put_state('new_fit', $fit);

\Osmium\Chrome\return_json(\Osmium\Chrome\get_formatted_loadout_attributes($fit));
