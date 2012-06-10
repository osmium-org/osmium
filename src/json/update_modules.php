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

if(!\Osmium\State\is_logged_in()) {
	\Osmium\Chrome\return_json(array());
}


if(isset($_GET['token']) && $_GET['token'] == \Osmium\State\get_token()) {
	$fit = \Osmium\State\get_state('new_fit', array());
	$modules = array();
	$order = array();

	$slots = implode('|', \Osmium\Fit\get_slottypes());
	$j = 0;
	foreach($_GET as $k => $v) {
		if(!preg_match('%^('.$slots.')([0-9]+)$%', $k, $matches)) continue;
		list(, $type, $index) = $matches;
		
		$m = isset($_GET[$k.'_state']) ? array($v, intval($_GET[$k.'_state'])) : $v;

		$modules[$type][$index] = $m;
		$order[$type][$index] = (++$j);
	}

	\Osmium\Fit\add_modules_batch($fit, $modules);
	\Osmium\Fit\sort_modules($fit, $order);

	\Osmium\State\put_state('new_fit', $fit);
	\Osmium\Chrome\return_json(\Osmium\AjaxCommon\get_loadable_fit($fit));
} else {
	\Osmium\Chrome\return_json(array());
}
