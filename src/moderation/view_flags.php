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

namespace Osmium\Page\Moderation\ViewFlags;

require __DIR__.'/../../inc/root.php';

\Osmium\State\assume_logged_in('..');
$a = \Osmium\State\get_state('a');
if($a['ismoderator'] !== 't') {
	\Osmium\fatal(404);
}

if(isset($_POST['status'])) {
	foreach($_POST['status'] as $flagid => $a) {
		foreach($a as $status => $b) {
			break 2;
		}
	}

	$flag = \Osmium\Db\fetch_assoc(\Osmium\Db\query_params(
		'SELECT status, flaggedbyaccountid FROM osmium.flags WHERE flagid = $1',
		array($flagid)
	));
	if($flag === false) {
		\Osmium\fatal(404, "Invalid flagid.");
	}

	if($flag['flaggedbyaccountid'] > 0) {
		$deltas = \Osmium\Flag\get_flag_weight_deltas();
		if(!isset($deltas[$status]) || !isset($deltas[$flag['status']])) {
			\Osmium\fatal(500, "Status not in get_flag_weight_deltas().");
		}
		$delta = $deltas[$status] - $deltas[$flag['status']];
	}

	\Osmium\Db\query('BEGIN;');
	\Osmium\Db\query_params(
		'UPDATE osmium.flags SET status = $1 WHERE flagid = $2',
		array($status, $flagid)
	);
	if($flag['flaggedbyaccountid'] > 0) {
		\Osmium\Db\query_params(
			'UPDATE osmium.accounts
			SET flagweight = GREATEST($1::integer, LEAST($2::integer, flagweight + $3::integer))
			WHERE accountid = $4',
			array(
				\Osmium\Flag\MIN_FLAG_WEIGHT,
				\Osmium\Flag\MAX_FLAG_WEIGHT,
				$delta,
				$flag['flaggedbyaccountid'],
			)
		);
	}
	\Osmium\Log\add_log_entry(\Osmium\Log\LOG_TYPE_CHANGED_FLAG_STATUS, $status, $flagid);
	\Osmium\Db\query('COMMIT;');
}

$p = new \Osmium\DOM\Page();
$p->title = 'Cast flags in reverse chronological order';

$div = $p->content->appendCreate('div', [ 'id' => 'modflags' ]);
$div->appendCreate('h2', $p->title);

$total = (int)\Osmium\Db\fetch_row(\Osmium\Db\query(
	'SELECT COUNT(flagid) FROM osmium.flags'
))[0];
$offset = \Osmium\Chrome\paginate('p', 50, $total, $result, $meta); /* XXX */
$div->append($p->fragment($result.$meta)); /* XXX */

$tablehdr = $p->createElement('tr');
$tablehdr->appendCreate('th', 'Flag ID');
$tablehdr->appendCreate('th', 'Creation date');
$tablehdr->appendCreate('th', 'Reported by');
$tablehdr->appendCreate('th', 'Type');
$tablehdr->appendCreate('th', 'Subtype');
$tablehdr->appendCreate('th', 'Status');
$tablehdr->appendCreate('th', 'Target');
$tablehdr->appendCreate('th', 'Action');

$table = $div
	->appendCreate('o-form', [ 'method' => 'post', 'action' => $_SERVER['REQUEST_URI'] ])
	->appendCreate('table', [ 'class' => 'd' ])
;

$table->appendCreate('thead')->append($tablehdr->cloneNode(true));
$table->appendCreate('tfoot')->append($tablehdr);

$tbody = $table->appendCreate('tbody');
$types = \Osmium\Flag\get_flag_types();
$subtypes = \Osmium\Flag\get_flag_subtypes();
$statuses = \Osmium\Flag\get_flag_statuses();
$flagsq = \Osmium\Db\query_params(
	'SELECT flagid, createdat, type, subtype, status, other,
	target1, target2, target3,
	accounts.accountid, nickname, apiverified, charactername, characterid, ismoderator, flagweight,
	l.visibility, l.privatetoken
	FROM osmium.flags
	LEFT JOIN osmium.accounts ON flaggedbyaccountid = accountid
	LEFT JOIN osmium.loadouts l ON (type = $2 AND target1 = l.loadoutid)
	OR (type = $3 AND target2 = l.loadoutid)
	OR (type = $4 AND target3 = l.loadoutid)
	ORDER BY createdat DESC
	LIMIT 50 OFFSET $1',
	array(
		$offset,
		\Osmium\Flag\FLAG_TYPE_LOADOUT,
		\Osmium\Flag\FLAG_TYPE_COMMENT,
		\Osmium\Flag\FLAG_TYPE_COMMENTREPLY,
	)
);

while($flag = \Osmium\Db\fetch_assoc($flagsq)) {
	$tr = $tbody->appendCreate('tr', [ 'class' => 'status'.$flag['status'] ]);

	$tr->appendCreate('td', $flag['flagid']);
	$tr->appendCreate('td', $p->formatRelativeDate($flag['createdat']));

	if($flag['accountid'] === null) {
		$tr->appendCreate('td', 'N/A');
	} else {
		$tr
			->appendCreate('td')
			->append($p->makeAccountLink($flag))
			->append([ ' (', $p->formatExactInteger($flag['flagweight']), ')' ])
			;
	}

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

	$td = $tr->appendCreate('td', [ 'class' => 'actions' ]);
	foreach($statuses as $status => $statusname) {
		if($status == $flag['status']) continue;

		$td->appendCreate('input', [
			'type' => 'submit',
			'name' => 'status['.$flag['flagid'].']['.$status.']',
			'value' => 'mark as '.$statusname,
		]);
	}
}

if($total === 0) {
	$tbody->appendCreate('tr')->appendCreate('td', [
		'colspan' => $tablehdr->childNodes->length,
		'No flags to show.',
	]);
}

$div->append($p->fragment($result)); /* XXX */

$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = '..';
$p->render($ctx);
