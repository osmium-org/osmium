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

namespace Osmium\Page\ViewFlaggingHistory;

require __DIR__.'/../inc/root.php';

if(!isset($_GET['accountid'])) {
	\Osmium\fatal(400);
}

$p = new \Osmium\DOM\Page();
$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = '..';
$p->title = 'Flagging history';

\Osmium\State\assume_logged_in($ctx->relative);
$a = \Osmium\State\get_state('a');
$accountid = intval($_GET['accountid']);

if($a['accountid'] != $accountid && $a['ismoderator'] !== 't') {
	\Osmium\fatal(403);
}

$div = $p->content->appendCreate('div#vflaghistory');
$div->appendCreate('h2', $p->title);

$ul = $div->appendCreate('ul#summary');

$summaryq = \Osmium\Db\query_params(
	'SELECT status, COUNT(flagid) AS count FROM osmium.flags
	WHERE flaggedbyaccountid = $1
	GROUP BY status
	ORDER BY status ASC',
	array($accountid)
);
$total = 0;
$statuses = \Osmium\Flag\get_flag_statuses();
while($row = \Osmium\Db\fetch_row($summaryq)) {
	$ul->appendCreate('li', $p->formatExactInteger($row[1]).' '.$statuses[$row[0]]);
	$total += $row[1];
}
$ul->appendCreate('li', $p->formatExactInteger($total).' total');

list($offset, $pmeta, $pol) = $p->makePagination($total);
$div->append($pmeta)->append($pol);

$table = $div->appendCreate('table.d');
$tablehdr = $table->appendCreate('thead')->appendCreate('tr', [
	[ 'th', 'Flag ID' ],
	[ 'th', 'Creation date' ],
	[ 'th', 'Type' ],
	[ 'th', 'Subtype' ],
	[ 'th', 'Status' ],
	[ 'th', 'Target' ],
]);
$table->appendCreate('tfoot')->append($tablehdr->cloneNode(true));
$tbody = $table->appendCreate('tbody');



$types = \Osmium\Flag\get_flag_types();
$subtypes = \Osmium\Flag\get_flag_subtypes();
$flagsq = \Osmium\Db\query_params(
	'SELECT flagid, createdat, type, subtype, status, other,
	target1, target2, target3, l.visibility, l.privatetoken
	FROM osmium.flags
	LEFT JOIN osmium.loadouts l ON (type = $3 AND target1 = l.loadoutid)
	OR (type = $4 AND target2 = l.loadoutid) OR (type = $5 AND target3 = l.loadoutid)
	WHERE flaggedbyaccountid = $1
	ORDER BY createdat DESC
	LIMIT 50 OFFSET $2',
	array(
		$accountid,
		$offset,
		\Osmium\Flag\FLAG_TYPE_LOADOUT,
		\Osmium\Flag\FLAG_TYPE_COMMENT,
		\Osmium\Flag\FLAG_TYPE_COMMENTREPLY,
	)
);

/* TODO: refactor this with code in view_flags */
while($flag = \Osmium\Db\fetch_assoc($flagsq)) {
	$tr = $tbody->appendCreate('tr.status'.$flag['status']);

	$tr->appendCreate('td', $flag['flagid']);
	$tr->appendCreate('td', $p->formatRelativeDate($flag['createdat']));
	$tr->appendCreate('td', $types[$flag['type']]);

	$span = $tr->appendCreate('td')->appendCreate('span', $subtypes[$flag['subtype']]);
	if((int)$flag['subtype'] === \Osmium\Flag\FLAG_SUBTYPE_OTHER) {
		$span->setAttribute('title', $flag['other']);
	}

	$tr->appendCreate('td', $statuses[$flag['status']]);
	$td = $tr->appendCreate('td');

	switch((int)$flag['type']) {

	case \Osmium\Flag\FLAG_TYPE_LOADOUT:
		$uri = \Osmium\Fit\get_fit_uri($flag['target1'], $flag['visibility'], $flag['privatetoken']);
		$td->appendCreate('a', [ 'o-rel-href' => '/'.$uri, '#'.$flag['target1'] ]);
		break;

	case \Osmium\Flag\FLAG_TYPE_COMMENT:
		$uri = \Osmium\Fit\get_fit_uri($flag['target2'], $flag['visibility'], $flag['privatetoken']);
		$td->appendCreate('a', [ 'o-rel-href' => '/'.$uri.'?jtc='.$flag['target1'], '#'.$flag['target1'] ]);
		break;

	case \Osmium\Flag\FLAG_TYPE_COMMENTREPLY:
		$uri = \Osmium\Fit\get_fit_uri($flag['target3'], $flag['visibility'], $flag['privatetoken']);
		$td->appendCreate('a', [ 'o-rel-href' => '/'.$uri.'?jtr='.$flag['target1'], '#'.$flag['target1'] ]);
		break;

	default:
		$td->appendCreate('small', 'N/A');
		break;

	}
}

if($total === 0) {
	$tbody->appendCreate('tr')->appendCreate('td', [
		'colspan' => $tablehdr->childNodes->length,
		'No flags to show.',
	]);
}



if($pol !== '') $div->append($pol->cloneNode(true));
$p->render($ctx);
