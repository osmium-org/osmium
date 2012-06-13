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
	echo "<em class='help'>(Double-click to add to bay)</em>\n</h2>\n";
	echo "<form action='".$_SERVER['REQUEST_URI']."' method='get'>\n";
	echo "<input type='search' placeholder='Search by name or category...' />\n";
	echo "<input type='submit' value='Search' />\n";
	echo "</form>\n<ul id='search_results'></ul>\n</div>\n";
}

function print_dronebay() {
	echo "<div id='dronebay'>\n<h2 class='has_spinner'>Drones";
	echo "<img src='./static/icons/spinner.gif' id='dronebay_spinner' class='spinner' alt='' /><br />\n";
	echo "<em class='help'>(Double-click to remove)</em>\n</h2>\n";
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

	ob_start();
	print_drone_searchbox();
	print_attributes('', ob_get_clean());
	print_dronebay();
	\Osmium\Chrome\print_js_snippet('new_fitting_drones');
	echo "<script>osmium_load_drones(".json_encode(\Osmium\AjaxCommon\get_data_step_drone_select($fit)).");</script>\n";
}

function drones_select_pre() { return true; }
function drones_select_post() { return true; }
