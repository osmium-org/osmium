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

namespace Osmium\Json\ShortlistModules;

const SHORTLIST_MAXIMUM_LENGTH = 200;

require __DIR__.'/../../inc/root.php';
require __DIR__.'/../../inc/ajax-common.php';

if(isset($_GET['token']) && $_GET['token'] == \Osmium\State\get_token()) {
	$shortlist = array();
	$keys = array();

	$i = 0;
	while(isset($_GET["$i"]) && $i < SHORTLIST_MAXIMUM_LENGTH) {
		$typeid = $_GET["$i"];
		if(!isset($keys[$typeid])) {
			$keys[$typeid] = true;
			$shortlist[] = intval($typeid);
		}
		++$i;
	}

	$shortlist = array_unique($shortlist);

	\Osmium\State\put_setting('shortlist_modules', $shortlist);
	die();
}

\Osmium\Chrome\return_json(\Osmium\AjaxCommon\get_module_shortlist());
