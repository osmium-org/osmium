<?php
/* Osmium
 * Copyright (C) 2012, 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\Page\EditComment;

require __DIR__.'/../inc/root.php';

\Osmium\State\assume_logged_in('..');
$a = \Osmium\State\get_state('a');
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($_GET['type'] == 'comment') {
	$comment = \Osmium\Db\fetch_assoc(\Osmium\Db\query_params(
		'SELECT efc.rawcontent AS commentbody, accountid, loadoutid, latestrevision, efc.contentid
		FROM osmium.loadoutcommentslatestrevision AS lclr
		JOIN osmium.loadoutcommentrevisions AS lcr ON lcr.commentid = lclr.commentid
		AND lcr.revision = lclr.latestrevision
		JOIN osmium.editableformattedcontents efc ON efc.contentid = lcr.bodycontentid
		JOIN osmium.loadoutcomments lc ON lc.commentid = lclr.commentid
		WHERE lclr.commentid = $1',
		array($id)
	));
	
	if($comment === false) {
		\Osmium\fatal(404);
	}
	
	if($a['ismoderator'] !== 't' && $a['accountid'] != $comment['accountid']) {
		\Osmium\fatal(403);
	}
	
	$body = $comment['commentbody'];
	$ftype = 'Comment';
} else if($_GET['type'] == 'commentreply') {
	$reply = \Osmium\Db\fetch_assoc(\Osmium\Db\query_params(
		'SELECT efc.rawcontent AS replybody, accountid, commentid, efc.contentid
		FROM osmium.loadoutcommentreplies lcr
		JOIN osmium.editableformattedcontents efc ON efc.contentid = lcr.bodycontentid
		WHERE commentreplyid = $1',
		array($id)
	));

	if($reply === false) {
		\Osmium\fatal(404);
	}

	if($a['ismoderator'] !== 't' && $a['accountid'] != $reply['accountid']) {
		\Osmium\fatal(403);
	}

	$body = $reply['replybody'];
	$ftype = 'Comment reply';
} else {
	\Osmium\fatal(400);
}

if(isset($_POST['body'])) {
	if($_GET['type'] == 'comment') {
		$body = trim($_POST['body']);
		$filtermask = \Osmium\Chrome\CONTENT_FILTER_MARKDOWN | \Osmium\Chrome\CONTENT_FILTER_SANITIZE;
		$formatted = \Osmium\Chrome\filter_content($body, $filtermask);

		if($_POST['body'] == $comment['commentbody'] && $a['accountid'] == $comment['accountid']) {
			/* Keep the same revision, but update the formattedbody */
			\Osmium\Db\query_params(
				'UPDATE osmium.editableformattedcontents
				SET filtermask = $2, formattedcontent = $1
				WHERE contentid = $3',
				array(
					$formatted,
					$filtermask,
					$comment['contentid'],
				)
			);
		} else {
			/* Insert a new revision */
			\Osmium\Db\query('BEGIN');

			list($newcontentid) = \Osmium\Db\fetch_row(\Osmium\Db\query_params(
				'INSERT INTO osmium.editableformattedcontents (rawcontent, filtermask, formattedcontent)
				VALUES ($1, $2, $3) RETURNING contentid',
				[ $_POST['body'], $filtermask, $formatted ]
			));

			$newrevision = $comment['latestrevision'] + 1;
			\Osmium\Db\query_params(
				'INSERT INTO osmium.loadoutcommentrevisions (
				commentid, revision, updatedbyaccountid, updatedate, bodycontentid
				) VALUES ($1, $2, $3, $4, $5)',
				array(
					$id,
					$newrevision,
					$a['accountid'],
					time(),
					$newcontentid,
				)
			);

			\Osmium\Db\query('COMMIT');
		}

		\Osmium\Db\query_params(
			'UPDATE osmium.votes SET cancellableuntil = NULL
			WHERE targettype = $1 AND targetid1 = $2 AND targetid2 = $3 AND targetid3 IS NULL',
			array(
				\Osmium\Reputation\VOTE_TARGET_TYPE_COMMENT,
				$id,
				$comment['loadoutid'],
			)
		);

		$jump = '?jtc='.$id;
	} else if($_GET['type'] == 'commentreply') {
		$body = trim($_POST['body']);
		$filtermask = \Osmium\Chrome\CONTENT_FILTER_MARKDOWN | \Osmium\Chrome\CONTENT_FILTER_SANITIZE_PHRASING;
		$formatted = \Osmium\Chrome\filter_content($body, $filtermask);

		\Osmium\Db\query('BEGIN');

		\Osmium\Db\query_params(
			'UPDATE osmium.editableformattedcontents
			SET rawcontent = $1, filtermask = $2, formattedcontent = $3
			WHERE contentid = $4',
			[
				$_POST['body'],
				$filtermask,
				$formatted,
				$reply['contentid'],
			]
		);

		\Osmium\Db\query_params(
			'UPDATE osmium.loadoutcommentreplies
			SET updatedate = $1, updatedbyaccountid = $2
			WHERE commentreplyid = $3',
			array(
				time(),
				$a['accountid'],
				$id,
			)
		);

		\Osmium\Db\query('COMMIT');

		$replyid = $id;
		$id = $reply['commentid'];
		$jump = '?jtr='.$replyid;
	}

	list($loadoutid) = \Osmium\Db\fetch_row(\Osmium\Db\query_params(
		'SELECT loadoutid FROM osmium.loadoutcomments
		WHERE commentid = $1',
		array($id)
	));

	if($_GET['type'] == 'comment') {
		\Osmium\Log\add_log_entry(\Osmium\Log\LOG_TYPE_UPDATE_COMMENT, null, $id, $loadoutid);
	} else if($_GET['type'] == 'commentreply') {
		\Osmium\Log\add_log_entry(\Osmium\Log\LOG_TYPE_UPDATE_COMMENT_REPLY, null, $id, $replyid, $loadoutid);
	}

	header('Location: ../'.\Osmium\Fit\fetch_fit_uri($loadoutid).$jump);
	die();
}

$p = new \Osmium\DOM\Page();
$p->title = 'Edit '.lcfirst($ftype).' #'.$id;

$p->content->appendCreate('h1', $p->title);

$_POST['body'] = $body;
$tbody = $p->content->appendCreate('o-form', [
	'method' => 'post', 'action' => $_SERVER['REQUEST_URI'],
])->appendCreate('table')->appendCreate('tbody');

$tbody->appendCreate('tr')->append([
	[ 'th', [[ 'label', [ 'for' => 'body', $ftype.' body' ] ]] ],
	[ 'td', [[ 'o-textarea', [ 'name' => 'body', 'id' => 'body' ] ]] ]
]);

$tbody->appendCreate('tr')->append([
	[ 'th' ],
	[ 'td', [[ 'input', [ 'type' => 'submit', 'value' => 'Confirm edit' ] ]] ]
]);


$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = '..';
$p->render($ctx);
