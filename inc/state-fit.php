<?php
/* Osmium
 * Copyright (C) 2012, 2013, 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\State;

/** How long to "remember" the password for protected fits */
const DEFAULT_PASSWORD_AUTHENTICATION_DURATION = 1800;

/** For a loadout being created or edited. Typically stored for a long
 * duration somewhere persistent. */
const LOADOUT_TYPE_NEW = 1;

/** For a loadout being temporarily played with. No big deal if the
 * cache expires or is lost. */
const LOADOUT_TYPE_VIEW = 2;


/**
 * Checks whether a loadout can be viewed (but maybe still needing a
 * password) by the current user.
 */
function can_view_fit($loadoutid) {
	if(is_logged_in()) {
		$a = get_state('a');
		list($count) = \Osmium\Db\fetch_row(\Osmium\Db\query_params('SELECT COUNT(loadoutid) FROM osmium.allowedloadoutsbyaccount WHERE loadoutid = $1 AND accountid = $2', array($loadoutid, $a['accountid'])));
	} else {
		list($count) = \Osmium\Db\fetch_row(\Osmium\Db\query_params('SELECT COUNT(loadoutid) FROM osmium.allowedloadoutsanonymous WHERE loadoutid = $1', array($loadoutid)));
	}

	return (boolean)$count;
}

/**
 * Checks whether a loadout can be viewed by the current user, that
 * the privatetoken is correct, and that the user has been granted
 * access if the fit is password protected.
 */
function can_access_fit($fit) {
	if($fit['metadata']['visibility'] == \Osmium\Fit\VISIBILITY_PRIVATE) {
		/* Require private token */
		if(!isset($_GET['privatetoken']) || (string)$_GET['privatetoken'] !== (string)$fit['metadata']['privatetoken']) {
			return false;
		}
	}

	if($fit['metadata']['view_permission'] == \Osmium\Fit\VIEW_PASSWORD_PROTECTED) {
		$pw = get_state('pw_fits', array());
		$a = get_state('a', array());

		/* Author of the loadout can always access his loadout */
		if(isset($a['accountid']) && $a['accountid'] == $fit['metadata']['accountid']) {
			return true;
		}

		/* Require password authorization */
		if(isset($pw[$fit['metadata']['loadoutid']]) && $pw[$fit['metadata']['loadoutid']] > time()) {
			return true;
		}

		return false;
	}

	return true;
}

/**
 * Checks whether a loadout can be edited by the current user.
 */
function can_edit_fit($loadoutid) {
	$can_edit = false;
	if(is_logged_in()) {
		$a = get_state('a');
		list($c) = \Osmium\Db\fetch_row(\Osmium\Db\query_params('SELECT COUNT(loadoutid) FROM osmium.editableloadoutsbyaccount WHERE loadoutid = $1 AND accountid = $2', array($loadoutid, $a['accountid'])));
		$can_edit = ($c == 1);
	}

	return $can_edit;
}

/**
 * Grant permission for the current user to see a password-protected
 * fit for a given duration. This does not circumvent can_view_fit(),
 * only makes can_access_fit() return true if the fit is password
 * protected.
 */
function grant_fit_access($fit, $duration = DEFAULT_PASSWORD_AUTHENTICATION_DURATION) {
	$pw = get_state('pw_fits', array());
	$pw[$fit['metadata']['loadoutid']] = time() + $duration;
	put_state('pw_fits', $pw);
}

/**
 * Mark a loadout as "green", that is, this loadout has been
 * successfully accessed by the current user recently in the current
 * session.
 */
function set_fit_green($loadoutid) {
	$green = get_state('green_fits', array());
	$green[$loadoutid] = true;
	put_state('green_fits', $green);
}

/**
 * Check if a given loadout is "green".
 *
 * @see set_fit_green()
 */
function is_fit_green($loadoutid) {
	$green = get_state('green_fits', array());
	return isset($green[$loadoutid]) && $green[$loadoutid] === true; 
}

/**
 * Get a cached loadout being altered.
 *
 * @returns $fit or null if the token is invalid/expired.
 */
function get_loadout($token) {
	$fb = get_cache_memory_fb($token, null, 'Loadout_');
	if($fb !== null) return $fb;

	$mem = get_cache_memory($token, null, 'Loadout_');
	return $mem;
}

/**
 * Update a loadout being altered.
 */
function put_loadout($token, $fit, $type = LOADOUT_TYPE_NEW) {
	if($type === LOADOUT_TYPE_NEW) {
		return put_cache_memory_fb($token, $fit, 86400, 'Loadout_');
	} else if($type === LOADOUT_TYPE_VIEW) {
		return put_cache_memory($token, $fit, 600, 'Loadout_');
	}

	return false;
}

/**
 * Get a new token to be used with get_loadout() and put_loadout(). It
 * is guaranteed to not collide with other tokens.
 */
function get_unique_loadout_token() {
	/* There is a ridiculously small chance that two identical tokens
	 * are generated at the same time by two requests and not collide
	 * with each other. Using a semaphore will enforce a consistent
	 * order and prevent such issues. */
	$sem = semaphore_acquire('loadout_token');

	do {
		$token = get_nonce();
	} while(get_loadout($token) !== null);

	semaphore_release($sem);
	return $token;
}
