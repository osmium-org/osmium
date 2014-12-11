<?php
/* Osmium
 * Copyright (C) 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\Page\DBBrowser\ViewEffect;

require __DIR__.'/../../inc/root.php';

$p = new \Osmium\DOM\Page();



$effectid = (int)$_GET['effectid'];
$cacheid = 'DBBrowser_Effect_'.$effectid;
$xml = \Osmium\State\get_cache($cacheid);
if($xml !== null) {
	$dbb = $p->fragment($xml);
	$p->content->append($dbb);
	goto RenderStage;
}



/* Yes, SELECT * is bad, but there's so many bloody columns in there… */
$e = \Osmium\Db\fetch_assoc(
	\Osmium\Db\query_params(
		'SELECT *
		FROM eve.dgmeffects e
		WHERE effectid = $1',
		array($_GET['effectid'])
	)
);

if($e === false) \Osmium\fatal(404);

$dbb = $p->content->appendCreate('div', [ 'id' => 'dbb' ]);

$hdr = $dbb->appendCreate('header');
$hdr->appendCreate('h2', [
	[ 'span', [ 'class' => 'raw', $e['effectname'] ] ],
	[ 'small', 'effect '.$e['effectid'] ],
]);

$ul = $dbb->appendCreate('ul');
$ul->appendCreate('li', [
	'Effect category: ',
	[ 'span', [ 'class' => 'raw', \Osmium\Chrome\format_effect_category($e['effectcategory']) ] ],
]);

$ul = $dbb->appendCreate('ul');
$eattribs = [
	'durationattributeid' => 'Effect duration',
	'dischargeattributeid' => 'Effect capacitor consumption',
	'rangeattributeid' => 'Effect optimal/maximum range',
	'falloffattributeid' => 'Effect falloff',
	'trackingspeedattributeid' => 'Effect tracking speed',
	'fittingusagechanceattributeid' => 'Chance of triggering when being fitted',
	'npcactivationchanceattributeid' => 'NPC activation chance',
	'npcusagechanceattributeid' => 'NPC usage chance',
];
foreach($eattribs as $k => $label) {
	if($e[$k] !== null) {
		$ul->appendCreate('li', [
			$label, ': governed by ',
			[ 'strong', $p->formatNumberWithUnit($e[$k], 119, '') ],
		]);
	}
}

$ul = $dbb->appendCreate('ul');
$ebools = [
	'isoffensive' => 'Effect is % considered offensive',
	'isassistance' => 'Effect is % considered as remote assist',
	'iswarpsafe' => 'Effect can % be used in warp',
];
if(in_array((int)$e['effectcategory'], [ 1, 2, 3, 6, 7 ], true)) {
	foreach($ebools as $k => $label) {
		list($x, $y) = explode('%', $label);
		$ul->appendCreate('li', [
			$x,
			$e[$k] === 't' ? '' : [ 'strong', 'not' ],
			$y,
		]);
	}
}

$ul = $dbb->appendCreate('ul');
$ul->appendCreate('li', [ 'Pre expression: ', [ 'small', [ 'class' => 'raw', $e['preexpression'] ] ] ]);
$ul->appendCreate('li', [ 'Post expression: ', [ 'small', [ 'class' => 'raw', $e['postexpression'] ] ] ]);



$typesq = \Osmium\Db\query_params(
	'SELECT it.typeid, it.typename, it.published
	FROM eve.dgmtypeeffects dte
	JOIN eve.invtypes it ON it.typeid = dte.typeid
	WHERE dte.effectid = $1
	ORDER BY it.published DESC, it.typename ASC',
	array($e['effectid'])
);

$h3 = $p->element('h3', 'List of types which have this effect:');
$ul = $p->element('ul', [ 'class' => 'typelist' ]);
$ntypes = 0;

while($t = \Osmium\Db\fetch_assoc($typesq)) {
	++$ntypes;
	$li = $ul->appendCreate('li', [ [ 'a', [
		'o-rel-href' => '/db/type/'.$t['typeid'],
		$t['typename']
	]]]);

	if($t['published'] !== 't') $li->addClass('unpublished');
}

if($ntypes > 0) {
	$dbb->append([ $h3, $ul ]);
}



\Osmium\State\put_cache($cacheid, $dbb->renderNode());

RenderStage:
$p->title = ucfirst(\Osmium\Fit\get_effectname($effectid)).' / Effect '.$effectid;
$p->snippets[] = 'dbbrowser';
$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = '../..';
$p->render($ctx);
