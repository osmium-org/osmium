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

namespace Osmium\Page\CastFlag;

require __DIR__.'/../inc/root.php';

$p = new \Osmium\DOM\Page();
$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = '..';

\Osmium\State\assume_logged_in($ctx->relative);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$type = isset($_GET['type']) ? $_GET['type'] : '';
$a = \Osmium\State\get_state('a');

$options = array();
$flagtype = null;
$otherid1 = null;
$otherid2 = null;

if($type == 'loadout') {
	$flagtype = \Osmium\Flag\FLAG_TYPE_LOADOUT;
	$loadoutid = $id;

	$p->title = 'Flag loadout #'.$id;
	$p->content
		->appendCreate('h1', 'Flag loadout ')
		->appendCreate('a', [ 'o-rel-href' => ($uri = '/'.\Osmium\Fit\fetch_fit_uri($id)), '#'.$id ])
		;

	$options[\Osmium\Flag\FLAG_SUBTYPE_NOT_A_REAL_LOADOUT] = array(
		'Not a real loadout',
		'This loadout cannot be fitted on either Tranquility or Singularity, or fills no purpose.'
	);
} else if($type == 'comment' || $type == 'commentreply') {
	if($type == 'comment') {
		$flagtype = \Osmium\Flag\FLAG_TYPE_COMMENT;
		$entity = 'comment';
		$commentid = $id;
		$jump = '?jtc='.$id;

		$row = \Osmium\Db\fetch_assoc(\Osmium\Db\query_params(
			'SELECT loadoutid FROM osmium.loadoutcomments WHERE commentid = $1',
			array($id)
		));

		if($row === false) {
			\Osmium\fatal(404);
		}

		$loadoutid = $otherid1 = $row['loadoutid'];
	} else if($type == 'commentreply') {
		$flagtype = \Osmium\Flag\FLAG_TYPE_COMMENTREPLY;
		$entity = 'comment reply';
		$jump = '?jtr='.$id;

		$row = \Osmium\Db\fetch_assoc(\Osmium\Db\query_params(
			'SELECT lcr.commentid, loadoutid FROM osmium.loadoutcommentreplies AS lcr
			JOIN osmium.loadoutcomments AS lc ON lc.commentid = lcr.commentid
			WHERE commentreplyid = $1',
			array($id)
		));

		if($row === false) {
			\Osmium\fatal(404);
		}

		$commentid = $otherid1 = $row['commentid'];
		$loadoutid = $otherid2 = $row['loadoutid'];
	}

	$p->title = 'Flag '.$entity.' #'.$id;
	$p->content
		->appendCreate('h1', 'Flag '.$entity.' ')
		->appendCreate('a', [ 'o-rel-href' => ($uri = '/'.\Osmium\Fit\fetch_fit_uri($loadoutid).$jump), '#'.$id ])
		;

	$options[\Osmium\Flag\FLAG_SUBTYPE_NOT_CONSTRUCTIVE] = array(
		'Not constructive',
		'This comment is useless, or brings nothing new or interesting to the loadout.',
	);
} else {
	\Osmium\fatal(400);
}

$fit = \Osmium\Fit\get_fit($loadoutid);

if($fit === false) {
	\Osmium\fatal(500, "get_fit() returned false, please report!");
}
if(!\Osmium\Flag\is_fit_flaggable($fit)) {
	\Osmium\fatal(400);
}

$options[\Osmium\Flag\FLAG_SUBTYPE_OFFENSIVE] = array('Offensive');
$options[\Osmium\Flag\FLAG_SUBTYPE_SPAM] = array('Spam');
$options[\Osmium\Flag\FLAG_SUBTYPE_OTHER] = array(
	'Requires moderator attention',
	$p->element('o-textarea', [
		'placeholder' => 'Provide more details here.',
		'name' => 'other',
	]),
);

if(isset($_POST['flagtype']) && isset($options[$_POST['flagtype']])) {
	$flagsubtype = (int)$_POST['flagtype'];
	$other = \Osmium\Chrome\trim($_POST['other']);

	if($flagsubtype == \Osmium\Flag\FLAG_SUBTYPE_OTHER && !$other) {
		$p->formerrors['other'][] = 'Please provide a reason.';
	} else {
		$row = \Osmium\Db\fetch_row(\Osmium\Db\query_params(
			'SELECT flagid FROM osmium.flags
			WHERE flaggedbyaccountid = $1 AND createdat >= $2
			AND type = $3 AND subtype = $4 AND target1 = $5 AND status = $6',
			array(
				$a['accountid'],
				time() - 7200,
				$flagtype,
				$flagsubtype,
				$id,
				\Osmium\Flag\FLAG_STATUS_NEW,
			)
		));

		if($row !== false) {
			$p->content->appendCreate('p.error_box', 'You already flagged this fit recently.');
		} else {
			\Osmium\Db\query_params(
				'INSERT INTO osmium.flags (
				flaggedbyaccountid, createdat, type, subtype, status, other, target1, target2, target3
				) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)',
				array(
					$a['accountid'],
					time(),
					$flagtype,
					$flagsubtype,
					\Osmium\Flag\FLAG_STATUS_NEW,
					$flagsubtype == \Osmium\Flag\FLAG_SUBTYPE_OTHER ? $other : null,
					$id,
					$otherid1,
					$otherid2,
				)
			);

			header('Location: '.$ctx->relative.$uri);
			die();
		}
	}
}

$div = $p->content->appendCreate('div#castflag');

$tbody = $div
	->appendCreate('o-form', [ 'method' => 'post', 'action' => $_SERVER['REQUEST_URI'] ])
	->appendCreate('table')
	->appendCreate('tbody')
	;

foreach($options as $type => $a) {
	$tr = $tbody->appendCreate('tr');
	$th = $tr->appendCreate('th');
	$td = $tr->appendCreate('td');

	$td->appendCreate('h2')->appendCreate('label', [ 'for' => 'flagtype'.$type, $a[0] ]);
	if(isset($a[1])) {
		$td->appendCreate('p')->append($a[1]);
	}

	$th->appendCreate('o-input', [
		'type' => 'radio',
		'name' => 'flagtype',
		'id' => 'flagtype'.$type,
		'value' => $type,
	]);

	$tbody->append($p->makeFormSeparatorRow());
}

$tbody->append($p->makeFormSubmitRow('Cast flag'));

$p->snippets[] = 'castflag';
$p->render($ctx);
