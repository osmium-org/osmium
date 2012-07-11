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

namespace Osmium\Page\NewFitting;

function ship_select() {
	$fit = \Osmium\State\get_state('new_fit', array());

	print_h1('select ship hull');
	\Osmium\Forms\print_form_begin();

	$q = \Osmium\Db\query_params('SELECT typeid, typename, groupname FROM osmium.invships ORDER BY groupname ASC, typename ASC', array());
	$o = array();
	while($row = \Osmium\Db\fetch_row($q)) {
		$o[$row[2]][$row[0]] = $row[1];
	}

	if(isset($fit['ship']['typeid'])) $_POST['hullid'] = $fit['ship']['typeid'];
	\Osmium\Forms\print_select('', 'hullid', $o, 16, null, 
	                           \Osmium\Forms\HAS_OPTGROUPS | \Osmium\Forms\FIELD_REMEMBER_VALUE);

	print_form_prevnext();
	\Osmium\Forms\print_form_end();
}

function ship_select_pre() { /* Unreachable code for the 1st step */ }

function ship_select_post() {
	$fit = \Osmium\State\get_state('new_fit', array());
	if($fit === array()) {
		\Osmium\Fit\create($fit);
	}
	if(!isset($_POST['hullid']) || !\Osmium\Fit\select_ship($fit, $_POST['hullid'])) {
		\Osmium\Forms\add_field_error('hullid', "Please select a ship first. (You can still change your mind later!)");
		return false;
	}

	\Osmium\State\put_state('new_fit', $fit);
	return true;
}