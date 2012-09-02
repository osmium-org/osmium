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

function print_charge_presetsbox() {
	echo "<h2 class='has_spinner'>Charge presets<img id='presets_spinner' class='spinner' alt='' src='./static-".\Osmium\STATICVER."/icons/spinner.gif' /></h2>\n<form method='post' action='".htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES)."' class='presets'>\n<select name='preset' id='preset'></select><br />\n<select name='chargepreset' id='chargepreset'></select><br />\n<button type='button' id='create_charge_preset'>Create new</button> <button type='button' id='clone_charge_preset'>Clone current</button> <button type='button' id='rename_charge_preset'>Rename current</button> <button type='button' id='delete_charge_preset'>Delete current</button><br /><textarea placeholder='Description of this charge preset…' id='charge_preset_desc'></textarea><br /><button type='button' id='update_desc'>Update description</button></form>\n";
}

function print_charge_groups() {
	echo "<div id='chargegroupsbox'>\n<h2 class='has_spinner'>Charge groups";
	echo "<img src='./static-".\Osmium\STATICVER."/icons/spinner.gif' id='chargegroupsbox_spinner' class='spinner' alt='' /><br />\n";
	echo "<em class='help'>(Use links, drag or <kbd>Ctrl</kbd>+click to select multiple items)</em>\n</h2>\n";
  
	\Osmium\Forms\print_form_begin(null, 'prevnext');
	echo "<tr><td colspan='2'>\n<ul id='chargegroups'>\n";
	echo "</ul>\n</td></tr>\n";
	print_form_prevnext();
	\Osmium\Forms\print_form_end();
	echo "</div>\n";
}

function charges_select() {
	print_h1('select charges');
  
	ob_start();
	print_charge_presetsbox();
	print_attributes(ob_get_clean(), '');
	print_charge_groups();

	$fit = \Osmium\State\get_new_fit();
	\Osmium\Chrome\print_js_code("osmium_charges_load("
	                             .json_encode(\Osmium\AjaxCommon\get_loadable_charges($fit))
	                             .");");
	\Osmium\Chrome\print_js_snippet('new_fitting-step3');
}

function charges_select_pre() { return true; }
function charges_select_post() { return true; }
