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

namespace Osmium\ViewLoadout;

if(!$commentsallowed) return;
if(!$loggedin) return;

if(isset($_POST['commentbody'])) {
	$body = \Osmium\Chrome\trim($_POST['commentbody']);
	$formatted = \Osmium\Chrome\format_sanitize_md($body);

	if(empty($body) || empty($formatted)) {
		/* Cowardly refusing to insert a blank comment */
		return;
	}

	\Osmium\Db\query('BEGIN;');

	$r = \Osmium\Db\query_params(
		'INSERT INTO osmium.loadoutcomments (loadoutid, accountid, creationdate, revision)
		VALUES ($1, $2, $3, $4) RETURNING commentid',
		array(
			$loadoutid,
			$a['accountid'],
			$t = time(),
			$fit['metadata']['revision']
		)
	);

	if($r === false) {
		\Osmium\Db\query('ROLLBACK;'); /* XXX: warn user about this failure */
		return;
	}

	list($commentid) = \Osmium\Db\fetch_row($r);
	$r = \Osmium\Db\query_params(
		'INSERT INTO osmium.loadoutcommentrevisions
		(commentid, revision, updatedbyaccountid, updatedate, commentbody, commentformattedbody)
		VALUES ($1, $2, $3, $4, $5, $6)',
		array(
			$commentid,
			1,
			$a['accountid'],
			$t,
			$_POST['commentbody'],
			$formatted
		)
	);

	if($r === false) {
		\Osmium\Db\query('ROLLBACK;'); /* XXX: warn user about this failure */
		return;
	}

	if($a['accountid'] != $author['accountid']) {
		\Osmium\Notification\add_notification(
			\Osmium\Notification\NOTIFICATION_TYPE_LOADOUT_COMMENTED,
			$a['accountid'], $author['accountid'], $commentid, $loadoutid
		);
	}

	\Osmium\Log\add_log_entry(
		\Osmium\Log\LOG_TYPE_CREATE_COMMENT,
		null, $commentid, $loadoutid
	);

	\Osmium\Db\query('COMMIT;');

	header('Location: #c'.$commentid);
	die();
}



else if(isset($_POST['replybody']) && isset($_POST['commentid'])) {
	list($commentexists) = \Osmium\Db\fetch_row(\Osmium\Db\query_params(
		'SELECT COUNT(commentid) FROM osmium.loadoutcomments
		WHERE commentid = $1 AND loadoutid = $2 AND revision <= $3',
		array(
			intval($_POST['commentid']),
			$loadoutid,
			$fit['metadata']['revision']
		)
	));

	if(!$commentexists) {
		return;
	}

	$body = \Osmium\Chrome\trim($_POST['replybody']);
	$formatted = \Osmium\Chrome\format_sanitize_md_phrasing($body);

	if(empty($body) || empty($formatted)) {
		return;
	}

	\Osmium\Db\query('BEGIN;');

	$r = \Osmium\Db\query_params(
		'INSERT INTO osmium.loadoutcommentreplies
		(commentid, accountid, creationdate, replybody, replyformattedbody, updatedate, updatedbyaccountid)
		VALUES ($1, $2, $3, $4, $5, null, null) RETURNING commentreplyid',
		array(
			$_POST['commentid'],
			$a['accountid'],
			time(),
			$body,
			$formatted
		)
	);

	if($r === false) {
		\Osmium\Db\query('ROLLBACK;'); /* XXX: warn user about this failure */
		return;
	}

	list($crid) = \Osmium\Db\fetch_row($r);

	/* Notify the comment author and all other users who replied before */
	$ids = \Osmium\Db\query_params(
		'SELECT accountid FROM osmium.loadoutcomments
		WHERE commentid = $1
		UNION
		SELECT DISTINCT accountid FROM osmium.loadoutcommentreplies
		WHERE commentid = $1',
		array($_POST['commentid'])
	);

	while($id = \Osmium\Db\fetch_row($ids)) {
		$id = $id[0];
		if($id == $a['accountid']) continue;

		\Osmium\Notification\add_notification(
			\Osmium\Notification\NOTIFICATION_TYPE_COMMENT_REPLIED,
			$a['accountid'], $id, $crid, $_POST['commentid'], $loadoutid
		);
	}

	\Osmium\Log\add_log_entry(
		\Osmium\Log\LOG_TYPE_CREATE_COMMENT_REPLY,
		null, $crid, $_POST['commentid'], $loadoutid
	);

	\Osmium\Db\query('COMMIT;');

	header('Location: #r'.$crid);
	die();
}
