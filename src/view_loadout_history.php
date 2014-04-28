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

namespace Osmium\Page\ViewLoadoutHistory;

require __DIR__.'/../inc/root.php';

$loadoutid = intval($_GET['loadoutid']);

if(!\Osmium\State\can_view_fit($loadoutid)) {
	\Osmium\fatal(404);
}
$fit = \Osmium\Fit\get_fit($loadoutid);
$can_edit = \Osmium\State\can_edit_fit($loadoutid);
$loadouturi = '/'.\Osmium\Fit\get_fit_uri(
	$loadoutid, $fit['metadata']['visibility'], $fit['metadata']['privatetoken']
);

if(!\Osmium\State\can_access_fit($fit) ||
   ($fit['metadata']['visibility'] != \Osmium\Fit\VISIBILITY_PUBLIC && !\Osmium\State\is_fit_green($loadoutid))) {
	\Osmium\fatal(403, 'Please access the loadout page first (and eventually input the password), then retry.');
}

if($can_edit && isset($_GET['tok']) && $_GET['tok'] == \Osmium\State\get_token() && isset($_GET['rollback'])) {
	\Osmium\Db\query('BEGIN');

	$hash = \Osmium\Db\fetch_row(\Osmium\Db\query_params(
		'SELECT fittinghash FROM osmium.loadouthistory WHERE loadoutid = $1 AND revision = $2',
		array($loadoutid, $_GET['rollback'])
	));

	$rev = \Osmium\Db\fetch_row(\Osmium\Db\query_params(
		'SELECT MAX(revision) FROM osmium.loadouthistory WHERE loadoutid = $1',
		array($loadoutid)
	));

	if($hash !== false && $rev !== false) {
		$newrev = $rev[0] + 1;
		$hash = $hash[0];

		$accountid = \Osmium\State\get_state('a')['accountid'];
		\Osmium\Db\query_params(
			'INSERT INTO osmium.loadouthistory (
			loadoutid, revision, fittinghash, updatedbyaccountid, updatedate
			) VALUES ($1, $2, $3, $4, $5)',
			array(
				$loadoutid,
				$newrev,
				$hash,
				$accountid,
				time()
			)
		);

		\Osmium\Log\add_log_entry(\Osmium\Log\LOG_TYPE_REVERT_LOADOUT, null, $loadoutid, $_GET['rollback']);
		\Osmium\Fit\insert_fitting_delta_against_previous_revision(\Osmium\Fit\get_fit($loadoutid));
		\Osmium\Db\query('COMMIT');

		\Osmium\State\invalidate_cache('loadout-'.$loadoutid);
	} else {
		\Osmium\Db\query('ROLLBACK');
	}
}



$p = new \Osmium\DOM\Page();
$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = '..';
$p->title = 'Revision history of loadout #'.$loadoutid;
$p->index = $fit['metadata']['visibility'] == \Osmium\Fit\VISIBILITY_PUBLIC;
$p->snippets[] = 'loadout_history';

$p->content->appendCreate('h1', 'Revision history of loadout ')->appendCreate(
	'a', [ 'o-rel-href' => $loadouturi, '#'.$loadoutid ]
);

$histq = \Osmium\Db\query_params(
	'SELECT loadouthistory.fittinghash, loadouthistory.revision, loadouthistory.updatedate,
	delta, accounts.accountid, nickname, apiverified, characterid, charactername,
	corporationid, corporationname, allianceid, alliancename, ismoderator
	FROM osmium.loadouthistory
	LEFT JOIN osmium.loadouthistory AS previousrev ON loadouthistory.loadoutid = previousrev.loadoutid
	AND previousrev.revision = (loadouthistory.revision - 1)
	LEFT JOIN osmium.fittingdeltas ON fittingdeltas.fittinghash1 = previousrev.fittinghash
	AND fittingdeltas.fittinghash2 = loadouthistory.fittinghash
	JOIN osmium.accounts ON accounts.accountid = loadouthistory.updatedbyaccountid
	WHERE loadouthistory.loadoutid = $1
	ORDER BY revision DESC',
	array($loadoutid)
);

$ol = $p->content->appendCreate('ol#lhistory');
$first = true;
while($rev = \Osmium\Db\fetch_assoc($histq)) {
	$class = $first ? 'opened' : 'closed';
	$loadouturi = '/'.\Osmium\Fit\get_fit_uri(
		$loadoutid,
		$fit['metadata']['visibility'],
		$fit['metadata']['privatetoken'],
		$rev['revision']
	);

	$li = $ol->appendCreate('li.'.$class.'#revision'.$rev['revision']);
	$par = $li->appendCreate('p');

	$par
		->appendCreate('a', [ 'o-rel-href' => $loadouturi ])
		->appendCreate('strong', 'Revision #'.$rev['revision'])
		;

	$par
		->append(', by ')
		->append($p->makeAccountLink($rev))
		->append([ ' (', $p->formatRelativeDate($rev['updatedate']), ')', ])
		->append(' — ')
		->appendCreate('small.anchor')
		->appendCreate('a', [ 'href' => '#revision'.$rev['revision'], '#permalink' ])
		;

	if(!$first && $can_edit) {
		$par->append(' — ')->appendCreate('small')->appendCreate('a.dangerous', [
			'href' => $p->formatQueryString([
				'rollback' => $rev['revision'],
				'tok' => \Osmium\State\get_token(),
			]),
			'rollback to this revision',
		]);
	}

	$par->appendCreate('br');
	$par->appendCreate('code', 'fittinghash '.$rev['fittinghash']);

	if($rev['revision'] > 1 && $rev['delta']) {
		$li->appendCreate('pre', $p->fragment(abbreviate_delta($rev['delta'])));
	} else {
		$li->appendCreate('pre', 'No changes / delta not available.');
	}

	if($first) $first = false;
}

$p->render($ctx);



function abbreviate_delta($delta, $context = 5, $snipafter = 10) {
	$lines = explode("\n", $delta);
	$distances = [];

	$prevdistance = 100;
	$inslevel = 0;
	$dellevel = 0;
	foreach($lines as $i => $l) {
		if($i > 0) $prevdistance = $distances[$i - 1];

		$pos = 0;
		while(($pos = strpos($l, '<ins>', $pos)) !== false) {
			$pos += 5;
			++$inslevel;
			$distances[$i] = 0;
		}

		$pos = 0;
		while(($pos = strpos($l, '<del>', $pos)) !== false) {
			$pos += 5;
			++$dellevel;
			$distances[$i] = 0;
		}

		$pos = 0;
		while(($pos = strpos($l, '</ins>', $pos)) !== false) {
			$pos += 6;
			--$inslevel;
			$distances[$i] = 0;
		}

		$pos = 0;
		while(($pos = strpos($l, '</del>', $pos)) !== false) {
			$pos += 6;
			--$dellevel;
			$distances[$i] = 0;
		}

		if(isset($distances[$i])) {
			continue;
		}

		if($inslevel > 0 || $dellevel > 0) {
			$distances[$i] = 0;
		} else {
			$distances[$i] = $prevdistance + 1;
		}
	}

	for($i = count($lines) - 2; $i >= 0; --$i) {
		$prevdistance = $distances[$i + 1];
		$distances[$i] = min($prevdistance + 1, $distances[$i]);
	}

	$ret = '';
	$snip = '';
	$sniplines = 0;
	$imax = count($lines) - 1;

	foreach($lines as $i => $l) {
		if($i < $imax) $l .= "\n";

		if($distances[$i] <= $context) {
			if($sniplines >= $snipafter) {
				$ret .= '<span class="snip"><span><span>@@ lines '.($i - $sniplines + 1).'-'.$i.' snipped @@</span> </span>'
					.'<span>'.$snip.'</span></span>';
			} else {
				$ret .= $snip;
			}

			$snip = '';
			$sniplines = 0;
			$ret .= $l;
		} else {
			$snip .= $l;
			++$sniplines;
		}
	}

	if($sniplines >= $snipafter) {
		$ret .= '<span class="snip"><span><span>@@ lines '.($i - $sniplines + 1).'-'.$i.' snipped @@</span> </span>'
			.'<span>'.$snip.'</span></span>';
	} else {
		$ret .= $snip;
	}

	return $ret;
}