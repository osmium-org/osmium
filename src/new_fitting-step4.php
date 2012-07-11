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

function print_drone_searchbox() {
	echo "<div id='dronelistbox'>\n<h2 class='has_spinner'>Search drones";
	echo "<img src='./static/icons/spinner.gif' id='dronelistbox_spinner' class='spinner' alt='' /><br />\n";
	echo "<em class='help'>(Click links or double-click to add to bay)</em>\n</h2>\n";
	echo "<form action='".htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES)."' method='get'>\n";
	echo "<input type='search' placeholder='Search by name or category...' />\n";
	echo "<input type='submit' value='Search' />\n";
	echo "</form>\n<ul id='search_results'></ul>\n</div>\n";
}

function print_dronebay() {
	echo "<div id='dronebay'>\n<h2 class='has_spinner'>Drones";
	echo "<img src='./static/icons/spinner.gif' id='dronebay_spinner' class='spinner' alt='' /><br />\n";
	echo "<em class='help'>(Use links or double-click to remove)</em>\n</h2>\n";
	echo "<p id='dronecapacity'><img src='./static/icons/dronecapacity.png' alt='Drone capacity' title='Drone capacity' /><strong></strong> m<sup>3</sup></p>\n<p id='dronebandwidth'><img src='./static/icons/bandwidth.png' alt='Drone bandwidth' title='Drone bandwidth' /><strong></strong> Mbit/s</p>\n";

	foreach(array('bay', 'space') as $v) {
		echo "<div id='in$v'>\n<h4>In $v</h4>\n<ul></ul>\n</div>\n";
	}

	\Osmium\Forms\print_form_begin();
	print_form_prevnext();
	\Osmium\Forms\print_form_end();
	echo "</div>\n";
}

function drones_select() {
	print_h1('select drones');
	$fit = \Osmium\State\get_state('new_fit', array());

	$presetform = "<h2 class='has_spinner'>Drone presets<img id='presets_spinner' class='spinner' alt='' src='./static/icons/spinner.gif' /></h2>\n<form method='post' action='".htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES)."' class='presets'>\n<select name='dronepreset' id='dronepreset'></select><br />\n<button type='button' id='create_drone_preset'>Create new</button> <button type='button' id='clone_drone_preset'>Clone current</button> <button type='button' id='rename_drone_preset'>Rename current</button> <button type='button' id='delete_drone_preset'>Delete current</button><br /><textarea placeholder='Description of this drone preset…' id='drone_preset_desc'></textarea><br /><button type='button' id='update_desc'>Update description</button></form>\n";

	print_drone_searchbox();
	print_attributes($presetform, '');
	print_dronebay();
	\Osmium\Chrome\print_js_snippet('new_fitting-step4');
	\Osmium\Chrome\print_js_code("osmium_load_drones("
	                             .json_encode(\Osmium\AjaxCommon\get_data_step_drone_select($fit))
	                             .");");
}

function drones_select_pre() { return true; }
function drones_select_post() { return true; }
