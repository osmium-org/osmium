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

function print_modules_searchbox() {
	echo "<div id='searchbox'>\n<h2 class='has_spinner'>Search modules";
	echo "<img src='./static/icons/spinner.gif' id='searchbox_spinner' class='spinner' alt='' /><br />\n";
	echo "<em class='help'>(Double-click to fit)</em>\n</h2>\n";
	echo "<form action='".htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES)."' method='get'>\n";
	echo "<input type='search' placeholder='Search by name or category...' autofocus='autofocus' />\n";
	echo "<input type='submit' value='Search' />\n<br />\n";
	$filters = \Osmium\State\get_state_trypersist('module_search_filter', array());
	$filters = array_combine($v = array_values($filters), $v);
	$req = \Osmium\Db\query_params('SELECT metagroupname, metagroupid FROM osmium.invmetagroups ORDER BY metagroupname ASC', array());
	echo "<p id='search_filters'>\nFilter modules: ";
	while($row = \Osmium\Db\fetch_row($req)) {
		list($name, $id) = $row;
		if(isset($filters[$id])) {
			$nods = ' style="display:none;"';
			$ds = '';
		} else {
			$ds = ' style="display:none;"';
			$nods = '';
		}

		echo "<img src='./static/icons/metagroup_$id.png' alt='Show $name modules' title='Show $name modules' class='meta_filter' id='meta_filter_$id' data-metagroupid='$id' data-toggle='meta_filter_{$id}_disabled' data-filterval='0' $nods/>";
		echo "<img src='./static/icons/metagroup_{$id}_ds.png' alt='Hide $name modules' title='Hide $name modules' class='meta_filter ds' id='meta_filter_{$id}_disabled' data-metagroupid='$id' data-toggle='meta_filter_$id' data-filterval='1' $ds/>\n";
	}

	echo "</p>\n</form>\n<ul id='search_results'></ul>\n</div>\n";

	foreach($filters as &$val) $val = "$val: 0";
	echo "<script>var search_params = {".implode(',', $filters)."};</script>\n";
}

function print_modulelist() {
	echo "<div id='loadoutbox'>\n<h2 class='has_spinner'>Loadout";
	echo "<img src='./static/icons/spinner.gif' id='loadoutbox_spinner' class='spinner' alt='' /><br />\n";
	echo "<em class='help'>(Double-click to remove)</em>\n</h2>\n";
  
	foreach(get_slot_fnames() as $type => $fname) {
		if(in_array($type, \Osmium\Fit\get_stateful_slottypes())) $class = ' stateful';
		else $class = '';

		echo "<div id='{$type}_slots' class='loadout_slot_cat$class'>\n<h3>$fname slots <strong id='{$type}_count'></strong></h3>";
		echo "<ul></ul>\n";
		echo "</div>\n";
	}
	\Osmium\Forms\print_form_begin();
	print_form_prevnext();
	\Osmium\Forms\print_form_end();
	echo "</div>\n";
}

function print_modules_shortlist($before = '', $after = '') {
	echo "<div id='shortlistbox'>$before\n<h2 class='has_spinner'>Shortlist";
	echo "<img src='./static/icons/spinner.gif' id='shortlistbox_spinner' class='spinner' alt='' /><br />\n";
	echo "<em class='help'>(Double-click to fit)</em>\n</h2>\n";
	echo "<ul id='modules_shortlist'>\n";
	echo "</ul>\n$after</div>\n";
}

function modules_select() {
	print_h1('select modules');

	ob_start();
	print_modules_searchbox();
	$search = ob_get_clean();

	$presetform = "<h2 class='has_spinner'>Presets<img id='presets_spinner' class='spinner' alt='' src='./static/icons/spinner.gif' /></h2>\n<form method='post' action='".htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES)."' class='presets'>\n<select name='preset' id='preset'></select><br />\n<button type='button' id='create_preset'>Create new</button> <button type='button' id='clone_preset'>Clone current</button> <button type='button' id='rename_preset'>Rename current</button> <button type='button' id='delete_preset'>Delete current</button><br /><textarea placeholder='Description of this preset…' id='preset_desc'></textarea><br /><button type='button' id='update_desc'>Update description</button></form>\n";

	print_attributes($presetform, '');
	print_modules_shortlist($search, '');
	print_modulelist();
	\Osmium\Chrome\print_js_snippet('new_fitting-step2');

	$fit = \Osmium\State\get_state('new_fit', array());
	echo "<script>\n$(function() {\n";
	echo "osmium_shortlist_load(".json_encode(\Osmium\AjaxCommon\get_module_shortlist()).");\n";
	echo "osmium_loadout_load(".json_encode(\Osmium\AjaxCommon\get_loadable_fit($fit)).");\n";
	echo "});\n</script>\n";
}

function modules_select_pre() { return true; }
function modules_select_post() { return true; }
