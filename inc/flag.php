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

const FLAG_TYPE_LOADOUT = 1;
const FLAG_TYPE_COMMENT = 2;
const FLAG_TYPE_COMMENTREPLY = 3;

/* General subtypes */
const FLAG_SUBTYPE_OTHER = 0;
const FLAG_SUBTYPE_OFFENSIVE = 1;
const FLAG_SUBTYPE_SPAM = 2;

/* Loadout specific */
const FLAG_SUBTYPE_NOT_A_REAL_LOADOUT = 101;

/* Comment specific */
const FLAG_SUBTYPE_NOT_CONSTRUCTIVE = 201;

const FLAG_STATUS_NEW = 0;
const FLAG_STATUS_HELPFUL = 1;
const FLAG_STATUS_ABUSIVE = 2;

/**
 * Get an array of all flag types.
 */
function get_flag_types() {
	return array(
		FLAG_TYPE_LOADOUT => 'loadout',
		FLAG_TYPE_COMMENT => 'comment',
		FLAG_TYPE_COMMENTREPLY => 'comment reply',
		);
}

/**
 * Get an array of all flag subtypes.
 */
function get_flag_subtypes() {
	return array(
		FLAG_SUBTYPE_OTHER => 'other',
		FLAG_SUBTYPE_OFFENSIVE => 'offensive',
		FLAG_SUBTYPE_SPAM => 'spam',
		FLAG_SUBTYPE_NOT_A_REAL_LOADOUT => 'not a real loadout',
		FLAG_SUBTYPE_NOT_CONSTRUCTIVE => 'not constructive',
		);
}

/**
 * Get an array of all flag statuses.
 */
function get_flag_statuses() {
	return array(
		FLAG_STATUS_NEW => 'new',
		FLAG_STATUS_HELPFUL => 'helpful',
		FLAG_STATUS_ABUSIVE => 'abusive',
		);
}

/**
 * Get an array of flag weight changes per status.
 */
function get_flag_weight_deltas() {
	return array(
		FLAG_STATUS_NEW => 0, /* <- do not change this */
		FLAG_STATUS_HELPFUL => 10,
		FLAG_STATUS_ABUSIVE => -5,
		);
}

/**
 * Checks whether a fit can be flagged by the current user.
 */
function is_fit_flaggable($fit) {
	return \Osmium\State\is_logged_in() && \Osmium\Reputation\is_fit_public($fit);
}

/**
 * Format (if needed) the name of a moderator.
 */
function maybe_add_moderator_symbol($a, $name) {
	if(!isset($a['ismoderator']) || $a['ismoderator'] !== 't') return $name;
  
	return "<span title='Moderator' class='mod'>".MODERATOR_SYMBOL.$name."</span>";
}
