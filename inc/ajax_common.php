<?php
/* Osmium
 * Copyright (C) 2012, 2013 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\AjaxCommon;

function get_green_fit(&$fit, &$cachename, &$loadoutid, &$revision) {
	$loadoutid = isset($_GET['loadoutid']) ? intval($_GET['loadoutid']) : 0;
	$revision = isset($_GET['revision']) ? intval($_GET['revision']) : 0;
	$cachename = session_id().'_view_fit_'.$loadoutid.'_'.$revision;

	if(!\Osmium\State\is_fit_green($loadoutid)) {
		return false;
	}

	$fit = \Osmium\State\get_cache($cachename, null);
	if($fit === null) {
		$fit = \Osmium\Fit\get_fit($_GET['loadoutid'], $revision);
	}

	if($fit === false) {
		/* Invalid revision queried? */
		return false;
	}

	\Osmium\Fit\use_preset($fit, $_GET['pid']);
	\Osmium\Fit\use_charge_preset($fit, $_GET['cpid']);
	\Osmium\Fit\use_drone_preset($fit, $_GET['dpid']);

	if(isset($_GET['skillset'])) {
		$a = \Osmium\State\get_state('a', null);
		\Osmium\Fit\use_skillset_by_name($fit, $_GET['skillset'], $a);
	}

	return true;
}

function get_module_shortlist() {
    return \Osmium\State\get_state_trypersist('shortlist_modules', array());
}

function get_slot_usage(&$fit) {
	$usage = array();

	foreach(\Osmium\Fit\get_attr_slottypes() as $type => $attr) {
		$usage[$type] = (int)\Osmium\Dogma\get_ship_attribute($fit, $attr, false);
	}

	return $usage;
}

/**
 * Generate formatted interesting attributes for all the modules in
 * the fit.
 *
 * @returns an array of array(slottype, index, shortformat,
 * longformat).
 */
function get_modules_interesting_attributes(&$fit) {
	$attrs = array();
	foreach(\Osmium\Fit\get_modules($fit) as $type => $a) {
		foreach($a as $index => $m) {
			$a = \Osmium\Fit\get_module_interesting_attributes($fit, $type, $index);
			$fashort = \Osmium\Chrome\format_short_range($a);

			if(empty($fashort)) continue;
			$falong = \Osmium\Chrome\format_long_range($a);

			$attrs[] = array(
				$type, $index,
				$fashort, $falong,
			);
		}
	}

	return $attrs;
}
