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

namespace Osmium\Json\CastVote;

require __DIR__.'/../../inc/root.php';

$result = array();

if(!isset($_GET['loadoutid']) || !isset($_GET['targettype']) || !isset($_GET['action'])) {
	$result['error'] = 'Invalid parameters';
	$result['success'] = false;
	\Osmium\Chrome\return_json($result);
}

$loadoutid = intval($_GET['loadoutid']);
$targettype = $_GET['targettype'];
$action = $_GET['action'];

if($loadoutid === 0) {
	$result['error'] = 'This loadout cannot be voted on.';
	$result['success'] = false;
	\Osmium\Chrome\return_json($result);
}

if(!\Osmium\State\is_fit_green($loadoutid)) {
	$result['error'] = 'Refresh page and try again';
	$result['success'] = false;
	\Osmium\Chrome\return_json($result);
}

if($action === 'rmvote') {
	$type = null;
} else if($action === 'castupvote') {
	$type = \Osmium\Reputation\VOTE_TYPE_UP;
} else if($action === 'castdownvote') {
	$type = \Osmium\Reputation\VOTE_TYPE_DOWN;
} else {
	$result['error'] = 'Unknown action type';
	$result['success'] = false;
	\Osmium\Chrome\return_json($result);
}

$fit = \Osmium\Fit\get_fit($loadoutid);
if($fit === false) {
	$result['error'] = 'get_fit() returned false, please report';
	$result['success'] = false;
	\Osmium\Chrome\return_json($result);
}
$givesreputation = \Osmium\Reputation\is_fit_public($fit);

if($targettype === 'loadout') {
	list($targetaccountid) = \Osmium\Db\fetch_row(
		\Osmium\Db\query_params(
			'SELECT accountid FROM osmium.loadouts WHERE loadoutid = $1',
			array($loadoutid)
			));

	$targettype = \Osmium\Reputation\VOTE_TARGET_TYPE_LOADOUT;
	$id1 = $loadoutid;
	$id2 = $id3 = null;
} else if($targettype === 'comment') {
	if(!isset($_GET['commentid'])) {
		$result['error'] = 'commentid not specified';
		$result['success'] = false;
		\Osmium\Chrome\return_json($result);
	}

    $targetaccountid = \Osmium\Db\fetch_row(
		\Osmium\Db\query_params(
			'SELECT accountid FROM osmium.loadoutcomments WHERE commentid = $1 AND loadoutid = $2',
			array($_GET['commentid'], $loadoutid)
			));

    if($targetaccountid === false) {
		$result['error'] = 'invalid commentid';
		$result['success'] = false;
		\Osmium\Chrome\return_json($result);
    }

    $targetaccountid = $targetaccountid[0];
	$targettype = \Osmium\Reputation\VOTE_TARGET_TYPE_COMMENT;
	$id1 = $_GET['commentid'];
	$id2 = $loadoutid;
	$id3 = null;
} else {
	$result['error'] = 'Unknown targettype';
	$result['success'] = false;
	\Osmium\Chrome\return_json($result);
}

$result['success'] = \Osmium\Reputation\cast_updown_vote(
	$type,
	$targettype,
	$targetaccountid,
	$givesreputation,
	$id1,
	$id2,
	$id3,
	$result['error']);

\Osmium\Chrome\return_json($result);
