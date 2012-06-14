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

namespace Osmium\Json\UpdateModules;

require __DIR__.'/../../inc/root.php';
require __DIR__.'/../../inc/ajax_common.php';

if(isset($_POST)) {
	$_GET = array_merge($_GET, $_POST);
}

if(!isset($_GET['token']) || $_GET['token'] != \Osmium\State\get_token()) {
	\Osmium\Chrome\return_json(array());
}

if(isset($_GET['type']) && $_GET['type'] == 'module') {
	$create = 'Osmium\Fit\create_preset';
	$use = 'Osmium\Fit\use_preset';
	$remove = 'Osmium\Fit\remove_preset';
	$rename = 'Osmium\Fit\rename_preset';
	$clone = 'Osmium\Fit\clone_preset';
} else if(isset($_GET['type']) && $_GET['type'] == 'charge') {
	$create = 'Osmium\Fit\create_charge_preset';
	$use = 'Osmium\Fit\use_charge_preset';
	$remove = 'Osmium\Fit\remove_charge_preset';
	$rename = 'Osmium\Fit\rename_charge_preset';
	$clone = 'Osmium\Fit\clone_charge_preset';
} else {
	\Osmium\Chrome\return_json(array());
}

$fit = \Osmium\State\get_state('new_fit', array());

if($_GET['action'] == 'create') {
	$presetid = $create($fit, $_GET['name'], '');
	if($presetid !== false) {
	    $use($fit, $presetid);
	}
} else if($_GET['action'] == 'delete') {
    $remove($fit, $fit[$_GET['type'].'presetid']);
} else if($_GET['action'] == 'rename') {
	$rename($fit, $_GET['name']);
} else if($_GET['action'] == 'updatedesc') {
	$fit[$_GET['type'].'presetdesc'] = $_GET['desc'];
} else if($_GET['action'] == 'switch') {
	$use($fit, $_GET['presetid']);
} else if($_GET['action'] == 'clone') {
	$presetid = $clone($fit, $_GET['name']);
	if($presetid !== false) {
		$use($fit, $presetid);
	}
}

\Osmium\State\put_state('new_fit', $fit);

if($_GET['type'] == 'module') {
	\Osmium\Chrome\return_json(\Osmium\AjaxCommon\get_loadable_fit($fit));
} else if($_GET['type'] == 'charge') {
	\Osmium\Chrome\return_json(\Osmium\AjaxCommon\get_loadable_charges($fit));
}