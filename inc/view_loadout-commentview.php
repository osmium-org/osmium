<?php
/* Osmium
 * Copyright (C) 2013, 2014, 2015 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

$section = $div->appendCreate('section#comments');
$section->appendCreate('h2', 'Comments');

$cancomment = !\Osmium\Reputation\is_fit_public($fit) || \Osmium\Reputation\has_privilege(
	\Osmium\Reputation\PRIVILEGE_COMMENT_LOADOUT
) || (isset($author['accountid']) && isset($a['accountid']) && $a['accountid'] == $author['accountid']);
$canreply = !\Osmium\Reputation\is_fit_public($fit) || \Osmium\Reputation\has_privilege(
	\Osmium\Reputation\PRIVILEGE_REPLY_TO_COMMENTS
);

if($commentcount === 0) {
	$section->appendCreate('p.placeholder', 'This loadout has zero comments.');
	goto AddComment; /* Yeah, this isn't very cool, but it avoid
	                  * putting the following code in a else block and
	                  * wasting one indentation level */
}

list($offset, $pmeta, $pol) = $p->makePagination(
	$commentcount, [
		'name' => 'pagec',
		'perpage' => $commentsperpage,
		'anchor' => '#comments',
	]);

$commentidsq = \Osmium\Db\query_params(
	'SELECT commentid FROM osmium.loadoutcomments
	WHERE loadoutid = $1 AND revision <= $2
	ORDER BY revision DESC, creationdate DESC
	LIMIT $3 OFFSET $4',
	array(
		$loadoutid,
		$revision,
	    $commentsperpage,
		$offset
	)
);

$commentids = array(-1);
while($r = \Osmium\Db\fetch_row($commentidsq)) {
	$commentids[] = $r[0];
}

/* Here we go… The big query of death™ */
$cq = \Osmium\Db\query_params(
	'SELECT
	
	lc.commentid, lc.accountid, lc.creationdate, lc.revision AS loadoutrevision,

	lcudv.votes, lcudv.upvotes, lcudv.downvotes, v.type AS votetype, lcrev.revision AS commentrevision,

	lcrev.updatedbyaccountid, lcrev.updatedate, efc.formattedcontent AS commentformattedbody,

	lcrep.commentreplyid, lcrep.creationdate AS repcreationdate,
	lcrefc.formattedcontent AS replyformattedbody, lcrep.updatedate AS repupdatedate,

	cacc.accountid, cacc.nickname, cacc.apiverified, cacc.characterid,
	cacc.charactername, cacc.ismoderator, cacc.reputation,

	racc.accountid AS raccountid, racc.nickname AS rnickname, racc.apiverified AS rapiverified,
	racc.characterid AS rcharacterid, racc.charactername AS rcharactername, racc.ismoderator AS rismoderator,


	uacc.accountid AS uaccountid, uacc.nickname AS unickname, uacc.apiverified AS uapiverified,
	uacc.characterid AS ucharacterid, uacc.charactername AS ucharactername,
	uacc.ismoderator AS uismoderator, uacc.reputation AS ureputation

	FROM osmium.loadoutcomments AS lc
	JOIN osmium.loadoutcommentupdownvotes AS lcudv ON lcudv.commentid = lc.commentid
	LEFT JOIN osmium.votes AS v ON (v.targettype = $1 AND v.type IN ($2, $3)
	AND v.fromaccountid = $4 AND v.targetid1 = lc.commentid
	AND v.targetid2 = lc.loadoutid AND v.targetid3 IS NULL)
	JOIN osmium.accounts AS cacc ON cacc.accountid = lc.accountid
	JOIN osmium.loadoutcommentslatestrevision AS lclr ON lc.commentid = lclr.commentid
	JOIN osmium.loadoutcommentrevisions AS lcrev ON lcrev.commentid = lc.commentid
	AND lcrev.revision = lclr.latestrevision
	JOIN osmium.editableformattedcontents efc ON efc.contentid = lcrev.bodycontentid
	JOIN osmium.accounts AS uacc ON uacc.accountid = lcrev.updatedbyaccountid
	LEFT JOIN osmium.loadoutcommentreplies AS lcrep ON lcrep.commentid = lc.commentid
	LEFT JOIN osmium.editableformattedcontents lcrefc ON lcrefc.contentid = lcrep.bodycontentid
	LEFT JOIN osmium.accounts AS racc ON racc.accountid = lcrep.accountid
	WHERE lc.commentid IN ('.implode(',', $commentids).')
	ORDER BY lc.revision DESC, lcrep.creationdate ASC',
	array(
		\Osmium\Reputation\VOTE_TARGET_TYPE_COMMENT,
		\Osmium\Reputation\VOTE_TYPE_UP,
		\Osmium\Reputation\VOTE_TYPE_DOWN,
		$loggedin ? $a['accountid'] : 0,
	)
);

function format_comment($row, &$replybefore = null) {
	global $isflaggable, $ismoderator, $modprefix, $loggedin, $commentsallowed, $canreply, $a, $fit, $p;

	$cmt = $p->element('div.comment#c'.$row['commentid'], [
		'data-commentid' => $row['commentid'],
	]);

	$votes = $cmt->appendCreate('div.votes', [ 'data-targettype' => 'comment' ]);

	$anch = $votes->appendCreate('a.upvote', [
		'title' => 'This comment is useful'
	]);
	$anch->appendCreate('img', [
		'o-static-src' => '/icons/vote.svg',
		'alt' => 'upvote',
	]);
	if($row['votetype'] == \Osmium\Reputation\VOTE_TYPE_UP) $anch->addClass('voted');

	$votes->appendCreate('strong', [
		'title' => $p->formatExactInteger($row['upvotes']).' upvote(s), '.$p->formatExactInteger($row['downvotes']).' downvote(s)',
		$p->formatExactInteger($row['votes']),
	]);

	$anch = $votes->appendCreate('a.downvote', [
		'title' => 'This comment is off-topic, not constructive or not useful'
	]);
	$anch->appendCreate('img', [
		'o-static-src' => '/icons/vote.svg',
		'alt' => 'downvote',
	]);
	if($row['votetype'] == \Osmium\Reputation\VOTE_TYPE_DOWN) $anch->addClass('voted');

	$cmt->appendCreate('div.body')->append($p->fragment($row['commentformattedbody'])); /* XXX */

	$hdr = $cmt->appendCreate('header');
	$author = $hdr->appendCreate('div.author');

	if($row['apiverified'] === 't' && $row['characterid'] > 0) {
		$author->appendCreate('o-eve-img.portrait', [
			'src' => '/Character/'.$row['characterid'].'_256.jpg',
			'alt' => '',
		]);
	}
	$author->appendCreate('small', 'commented by');
	$author->appendCreate('br');
	$author->append($p->makeAccountLink($row))
		->appendCreate('br');
	$author->append($p->formatReputation($row['reputation']))
		->append(' – ')
		->append($p->formatRelativeDate($row['creationdate']));

	$meta = $hdr->appendCreate('div.meta');
	$meta->appendCreate('a', [
		'href' => $p->formatQueryString([ 'jtc' => $row['commentid'] ]),
		'title' => 'permanent link to this comment',
		'#permalink',
	]);

	if($isflaggable) {
		$meta->append(' — ');
		$meta->appendCreate('a.dangerous', [
			'o-rel-href' => '/flagcomment/'.$row['commentid'],
			'title' => 'Flag comment; this comment requires moderator attention',
			'⚑report',
		]);
	}

	if($ismoderator || ($loggedin && $row['accountid'] == $a['accountid'])) {
		$tmp = ($loggedin && $row['accountid'] == $a['accountid']) ? '' : $modprefix;

		$meta->append(' — ');
		$meta->appendCreate('a', [
			'o-rel-href' => '/editcomment/'.$row['commentid'],
			$tmp, 'edit',
		]);

		$meta->append(' — ');
		$meta->appendCreate('o-state-altering-a.dangerous.confirm', [
			'o-rel-href' => '/internal/deletecomment/'.$row['commentid'],
			$tmp, 'delete',
		]);
	}

	if($loggedin && $commentsallowed && ($canreply || $commentauthorid == $a['accountid'])) {
		$meta->append(' — ');
		$meta->appendCreate('a.add_comment', '↪reply');
	}

	if($row['loadoutrevision'] < $fit['metadata']['revision']) {
		$meta->append(' — ');

		$span = $meta->appendCreate(
			'span.outdated',
			'targeted at '
		);

		$span->appendCreate('a', [
			'o-rel-href' => '/'.\Osmium\Fit\get_fit_uri(
				$fit['metadata']['loadoutid'],
				$fit['metadata']['visibility'],
				$fit['metadata']['privatetoken'],
				$row['loadoutrevision']
			),
			'revision #'.$row['loadoutrevision'],
		]);
	}


	$replybefore = $cmt->appendCreate('ul.replies#creplies'.$row['commentid'])->appendCreate('li.new');
	$form = $replybefore->appendCreate('o-form', [
		'method' => 'post', 'action' => '#creplies'.$row['commentid'],
	]);

	$form->appendCreate('o-textarea', [
		'name' => 'replybody',
		'placeholder' => 'Type your reply… (Markdown; inline formatting only)',
	]);

	$form->appendCreate('input', [
		'type' => 'hidden',
		'name' => 'commentid',
		'value' => $row['commentid'],
	]);

	$form->appendCreate('input', [
		'type' => 'submit',
		'value' => 'Submit reply',
	]);

	$form->append(' ');
	$form->appendCreate('a.cancel', 'cancel');

	return $cmt;
}

function format_comment_reply($row) {
	global $ismoderator, $loggedin, $isflaggable, $a, $modprefix, $p;

	$c = array(
		'accountid' => $row['raccountid'],
		'nickname' => $row['rnickname'],
		'apiverified' => $row['rapiverified'],
		'characterid' => $row['rcharacterid'],
		'charactername' => $row['rcharactername'],
		'ismoderator' => $row['rismoderator']
	);

	$li = $p->element('li#r'.$row['commentreplyid']);
	$li->appendCreate('div.body')->append($p->fragment($row['replyformattedbody'])); /* XXX */
	$li->append(' — ');
	$li->append($p->makeAccountLink($c));

	$li->append(' — ');
	if($row['repupdatedate'] !== null) {
		$li->appendCreate('span', [
			'title' => 'reply was edited ('.$p->formatRelativeDate($row['repupdatedate'])->textContent.')',
			'✎',
		]);
	}
	$li->append($p->formatRelativeDate($row['repcreationdate']));

	$meta = $li->appendCreate('span.meta');
	$meta->append(' — ');
	$meta->appendCreate('a', [
		'href' => $p->formatQueryString([ 'jtr' => $row['commentreplyid'] ]),
		'title' => 'permanent link to this reply',
		'#',
	]);

	if($isflaggable) {
		$meta->append(' — ');
		$meta->appendCreate('a.dangerous', [
			'o-rel-href' => '/flagcommentreply/'.$row['commentreplyid'],
			'title' => 'Flag reply; this reply requires moderator attention',
			'⚑',
		]);
	}

	if($ismoderator || ($loggedin && $row['raccountid'] == $a['accountid'])) {
		$tmp = ($loggedin && $row['raccountid'] == $a['accountid']) ? '' : $modprefix;

		$meta->append(' — ');
		$meta->appendCreate('a', [
			'o-rel-href' => '/editcommentreply/'.$row['commentreplyid'],
			$tmp, 'edit',
		]);

		$meta->append(' — ');
		$meta->appendCreate('o-state-altering-a.dangerous.confirm', [
			'o-rel-href' => '/internal/deletecommentreply/'.$row['commentreplyid'],
			$tmp, 'delete',
		]);
	}

	return $li;
}

if($pol !== '') {
	$section->append($pmeta)->append($pol);
}

$prevcid = null;
$replybefore = null;
while($row = \Osmium\Db\fetch_assoc($cq)) {
	if($row['commentid'] !== $prevcid) {
		$prevcid = $row['commentid'];

		$section->append(format_comment($row, $replybefore));
	}

	if($row['commentreplyid'] === null) continue;
	$replybefore->before(format_comment_reply($row));
}

if($pol !== '') {
	$section->append($pol->cloneNode(true));
}

AddComment:
$section->appendCreate('h2', 'Add a comment');

if($commentsallowed && $loggedin && $cancomment) {
	$tbody = $section->appendCreate('o-form', [ 'method' => 'post', 'action' => '#comments' ])
		->appendCreate('table')->appendCreate('tbody');
	$tbody->append($p->makeFormRawRow(
		[[ 'label', [ 'for' => 'commentbody', 'Comment body', [ 'br' ], [ 'small', '(Markdown)' ] ] ]],
		[[ 'o-textarea', [ 'id' => 'commentbody', 'name' => 'commentbody' ] ]]
	));
	$tbody->append($p->makeFormSubmitRow('Submit comment'));
} else if($loggedin && $commentsallowed) {
	$section->appendCreate('p.placeholder', 'You do not have the necessary privilege to comment this loadout.');
} else if(!$loggedin && $commentsallowed) {
	$section->appendCreate('p.placeholder', [
		'You have to ',
		[ 'a', [
			'o-rel-href' => '/login'.$p->formatQueryString([ 'r' => $_SERVER['REQUEST_URI'] ]),
			'sign in',
		]],
		' to comment on this loadout.',
	]);
} else if(!$commentsallowed) {
	$section->appendCreate('p.placeholder', 'This loadout cannot be commented on.');
}
