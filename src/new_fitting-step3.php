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
	echo "<div id='presetsbox'>\n<h2 class='has_spinner'>Presets ";
	echo "<a href='javascript:void(0);' id='new_preset'><img src='./static/icons/add.png' alt='Create a new preset' title='New preset' /></a>";
	echo "<img src='./static/icons/spinner.gif' id='presetsbox_spinner' class='spinner' alt='' /><br />\n";
	echo "<em class='help'>(Click to change active preset)</em></h2>\n";
	echo "<ul id='presets'>\n";
	echo "</ul>\n";
	echo "</div>\n";
}

function print_charge_groups() {
	echo "<div id='chargegroupsbox'>\n<h2 class='has_spinner'>Charge groups";
	echo "<img src='./static/icons/spinner.gif' id='chargegroupsbox_spinner' class='spinner' alt='' /><br />\n";
	echo "<em class='help'>(Select multiple items by dragging or using <kbd>Ctrl</kbd>)</em>\n</h2>\n";
  
	\Osmium\Forms\print_form_begin();
	echo "<tr><td colspan='2'>\n<ul id='chargegroups'>\n";

	foreach(get_charges() as $i => $charges) {
		echo "<li id='group_$i'>\n";
		print_chargegroup($i, $charges['typeids'], $charges['charges']);
		echo "</li>\n";
	}

	echo "</ul>\n</td></tr>\n";
	print_form_prevnext();
	\Osmium\Forms\print_form_end();
	echo "</div>\n";
}

function get_charges() {
	$fit = \Osmium\State\get_state('new_fit', array());
	$typeids = array();
	foreach(\Osmium\Fit\get_modules($fit) as $type => $a) {
		foreach($a as $k) {
			$typeids[$k['typeid']] = true;
		}
	}

	$groups = array();
	$typetogroups = array();
	$keystonumbers = array();
	$z = 0;

	foreach($typeids as $typeid => $val) {
		$chargeids = array();
		$req = \Osmium\Db\query_params('SELECT chargeid, chargename FROM osmium.invcharges WHERE moduleid = $1 ORDER BY chargename ASC', array($typeid));
		while($row = \Osmium\Db\fetch_row($req)) {
			$chargeids[$row[0]] = array('typeid' => $row[0], 'typename' => $row[1]);
		}

		if(count($chargeids) == 0) continue;

		$keys = array_keys($chargeids);
		sort($keys);
		$key = implode(' ', $keys);
		if(!isset($keystonumbers[$key])) {
			$keystonumbers[$key] = $z;
			$groups[$z] = array_values($chargeids);
			++$z;
		}
		$typetogroups[$typeid] = $keystonumbers[$key];
	}

	$result = array();
	foreach($typetogroups as $typeid => $i) {
		$result[$i]['typeids'][] = $typeid;
	}
	foreach($groups as $i => $group) {
		$result[$i]['charges'] = $group;
	}

	return $result;
}

function print_chargegroup($groupid, $typeids, $charges) {
	$fit = \Osmium\State\get_state('new_fit', array());
	echo "<ul class='chargegroup'>\n";
	foreach(\Osmium\Fit\get_modules($fit) as $type => $a) {
		foreach($a as $i => $module) {
			$id = $module['typeid'];
			if(!in_array($id, $typeids)) continue;

			$name = $module['typename'];
			echo "<li id='{$type}_$i'><img src='http://image.eveonline.com/Type/{$id}_32.png' alt='$name' title='$name' class='module_icon' />";
			echo "<img src='./static/icons/no_charge.png' alt='(No charge)' title='(No charge)' class='charge_icon' />\n";
			echo "<select name='charge_{$groupid}_$i' data-slottype='$type' data-index='$i'>\n";
			echo "<option value='-1'>(No charge)</option>\n";
			foreach($charges as $charge) {
				echo "<option value='".$charge['typeid']."'>".$charge['typename']."</option>\n";
			}
			echo "</select>\n";
			echo "</li>\n";
		}
	}
	echo "</ul>\n";
}

function charges_select() {
	print_h1('select charges');
  
	ob_start();
	print_charge_presetsbox();
	print_attributes('', ob_get_clean());
	print_charge_groups();

	$fit = \Osmium\State\get_state('new_fit', array());
	echo "<script>\n$(function() { $('div#computed_attributes').html(".json_encode(\Osmium\Chrome\get_formatted_loadout_attributes($fit))."); });\n"
		."var charge_presets = ".json_encode($fit['charges'], JSON_FORCE_OBJECT)
		.";\nvar selected_preset = ".json_encode($fit['selectedpreset'])
		.";\n var osmium_preset_num = ".(count($fit['charges']) + 1).";\n</script>\n";

	\Osmium\Chrome\print_js_snippet('new_fitting_charges');
}

function charges_select_pre() { return true; }
function charges_select_post() { return true; }
