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

/** Prefix used to identify moderators. */
const MODERATOR_SYMBOL = '♦';

/** The flag weight new accounts are given. */
const DEFAULT_FLAG_WEIGHT = 100;

/** The minimum flag weight that can be attained. */
const MIN_FLAG_WEIGHT = 0;

/** The maximum flag weight that can be attained. */
const MAX_FLAG_WEIGHT = 500;

/** Add this quantity to the flag weight of a user when he made a
 * helpful flag. */
const HELPFUL_FLAG_BONUS = 10;

/** Add this quantity to the flag weight of a user when he made an
 * abusive flag. */
const ABUSIVE_FLAG_PENALTY = -5;

/**
 * Checks whether a fit can be flagged by the current user.
 */
function is_fit_flaggable($fit) {
	return \Osmium\State\is_logged_in()
		&& $fit['metadata']['visibility'] == \Osmium\Fit\VISIBILITY_PUBLIC
		&& $fit['metadata']['view_permission'] == \Osmium\Fit\VIEW_EVERYONE;
}

/**
 * Format (if needed) the name of a moderator.
 */
function maybe_add_moderator_symbol($a, $name) {
	if(!isset($a['ismoderator']) || $a['ismoderator'] !== 't') return $name;
  
	return "<span title='Moderator' class='mod'>".MODERATOR_SYMBOL.$name."</span>";
}
