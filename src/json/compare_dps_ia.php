<?php
/* Osmium
 * Copyright (C) 2013 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\Json\CompareDPSInterestingAttributes;

require __DIR__.'/../../inc/root.php';

/* Should match the value in compare_dps.php */
const MAX_LOADOUTS = 6;

$json = [];

if(isset($_POST['source']) && is_array($_POST['source'])) {
	foreach($_POST['source'] as $k => $remote) {
		if($remote === '') continue;

		$errors = array();
		$fit = \Osmium\Fit\try_get_fit_from_remote_format($remote, $errors);
		\Osmium\Fit\use_skillset_by_name($fit, $_POST['skillset'][$k], \Osmium\State\get_state('a'));

		if(!is_array($fit)) {
			$json[$k]['errors'] = $errors;
		} else {
			if($errors !== array()) $json[$k]['errors'] = $errors;
			$json[$k]['ia'] = \Osmium\Fit\get_interesting_attributes($fit);
		}
	}
}

\Osmium\Chrome\return_json((object)$json);
