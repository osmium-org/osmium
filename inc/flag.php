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

namespace Osmium\Flag;

const DEFAULT_FLAG_WEIGHT = 100;
const MIN_FLAG_WEIGHT = 0;
const MAX_FLAG_WEIGHT = 500;
const HELPFUL_FLAG_BONUS = 10;
const ABUSIVE_FLAG_PENALTY = -5;

function is_fit_flaggable($fit) {
	return $fit['metadata']['visibility'] == \Osmium\Fit\VISIBILITY_PUBLIC
		&& $fit['metadata']['view_permission'] == \Osmium\Fit\VIEW_EVERYONE;
}

function format_moderator_name($a) {
	if(!$a['ismoderator']) return $a['charactername'];
  
	return "<span title='Moderator' class='mod'>♦".$a['charactername']."</span>";
}
