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

namespace Osmium\Json\UpdateCharges;

require __DIR__.'/../../inc/root.php';
require __DIR__.'/../../inc/ajax_common.php';

if(!isset($_GET['token']) || $_GET['token'] != \Osmium\State\get_token()) {
	\Osmium\Chrome\return_json(array());
}

$fit = \Osmium\State\get_new_fit();

$new_charges = array();
$current_charges = $fit['charges'];

$slots = implode('|', \Osmium\Fit\get_slottypes());
foreach($_GET as $k => $v) {
	if(!preg_match('%('.$slots.')([0-9]+)%', $k, $match)) continue;
	list(, $type, $index) = $match;

	$chargeid = intval($v);
	if($chargeid == 0) continue;

	$new_charges[$type][$index] = $chargeid;
}

/* NB: the operation order (update, then remove) actually matters here
 * somewhat, performance-wise, because of the calls to
 * maybe_remove_cache */

/* Update charges */
\Osmium\Fit\add_charges_batch($fit, $new_charges);

/* Remove stale charges */
foreach($current_charges as $type => $a) {
	foreach($a as $index => $charge) {
		if(!isset($new_charges[$type][$index])) {
			\Osmium\Fit\remove_charge($fit, $type, $index);
		}
	}
}

\Osmium\fprintr($new_charges);

\Osmium\State\put_new_fit($fit);
\Osmium\Chrome\return_json(\Osmium\AjaxCommon\get_loadable_charges($fit));
