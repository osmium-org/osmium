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

namespace Osmium\Ajax\UpdateChargePreset;

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

	\Osmium\Fit\remove_charge_preset($fit, $oldname);

	$slots = implode('|', \Osmium\Fit\get_slottypes());
	$charges = array();

	foreach($_GET as $k => $v) {
		if(!preg_match('%('.$slots.')([0-9]+)%', $k, $matches)) continue;
		list(, $type, $index) = $matches;
		$charges[$type][$index] = intval($v);
	}

	\Osmium\Fit\add_charges_batch($fit, $name, $charges);
} else if($_GET['action'] == 'delete') {
	$name = $_GET['name'];
	unset($fit['charges'][$name]);
}

\Osmium\State\put_state('new_fit', $fit);