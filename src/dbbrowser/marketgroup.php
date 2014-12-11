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

namespace Osmium\Page\DBBrowser\ViewCategory;

require __DIR__.'/../../inc/root.php';

$p = new \Osmium\DOM\Page();



$mgid = (int)$_GET['mgid'];
$cacheid = 'DBBrowser_Marketgroup_'.$mgid;
$xml = \Osmium\State\get_cache($cacheid);
if($xml !== null) {
	$dbb = $p->fragment($xml);
	$p->content->append($dbb);
	goto RenderStage;
}



$mg = \Osmium\Db\fetch_assoc(
	\Osmium\Db\query_params(
		'SELECT
		mg0.marketgroupid AS mgid0, mg0.marketgroupname AS mgname0,
		mg1.marketgroupid AS mgid1, mg1.marketgroupname AS mgname1,
		mg2.marketgroupid AS mgid2, mg2.marketgroupname AS mgname2,
		mg3.marketgroupid AS mgid3, mg3.marketgroupname AS mgname3,
		mg4.marketgroupid AS mgid4, mg4.marketgroupname AS mgname4
		FROM eve.invmarketgroups mg0
		LEFT JOIN eve.invmarketgroups mg1 ON mg1.marketgroupid = mg0.parentgroupid
		LEFT JOIN eve.invmarketgroups mg2 ON mg2.marketgroupid = mg1.parentgroupid
		LEFT JOIN eve.invmarketgroups mg3 ON mg3.marketgroupid = mg2.parentgroupid
		LEFT JOIN eve.invmarketgroups mg4 ON mg4.marketgroupid = mg3.parentgroupid
		WHERE mg0.marketgroupid = $1',
		array($mgid)
	)
);

if($mg === false) \Osmium\fatal(404);

$dbb = $p->content->appendCreate('div', [ 'id' => 'dbb' ]);

$header = $dbb->appendCreate('header');
$h2 = $header->appendCreate('h2', $mg['mgname0']);
$small = $h2->appendCreate('small', 'marketgroup '.$mgid);

if($mg['mgid1'] !== null) {
	$ul = $dbb->appendCreate('nav')->appendCreate('ul');

	for($i = 4; $i >= 1; --$i) {
		if($mg['mgid'.$i] !== null) {
			$ul->appendCreate('li', [
				[ 'a', [ 'o-rel-href' => '/db/marketgroup/'.$mg['mgid'.$i], $mg['mgname'.$i] ] ]
			]);
		}
	}

	$ul->appendCreate('li', [ 'class' => 'lst', $mg['mgname0'] ]);
}



$submgq = \Osmium\Db\query_params(
	'SELECT c0.marketgroupid AS mgid0, c0.marketgroupname AS mgname0,
	c1.marketgroupid AS mgid1, c1.marketgroupname AS mgname1
	FROM eve.invmarketgroups c0
	LEFT JOIN eve.invmarketgroups c1 ON c1.parentgroupid = c0.marketgroupid
	WHERE c0.parentgroupid = $1
	ORDER BY mgname0 ASC, mgname1 ASC',
	array($mg['mgid0'])
);



$h3 = $p->element('h3', 'Market subgroups:');
$ul = $p->element('ul', [ 'class' => 'submg' ]);
$prevparent = null;
$nmg = 0;
$nsubmg = 0;

while($submg = \Osmium\Db\fetch_assoc($submgq)) {
	if($prevparent !== $submg['mgid0']) {
		if($nsubmg > 0) {
			$li->append($subul);
		}

		$li = $ul->appendCreate('li', [[ 'a', [
			'o-rel-href' => '/db/marketgroup/'.$submg['mgid0'],
			$submg['mgname0'],
		]]]);

		$prevparent = $submg['mgid0'];
		$subul = $p->element('ul');
		$nsubmg = 0;
		++$nmg;
	}

	if($submg['mgid1'] !== null) {
		++$nsubmg;

		$subul->appendCreate('li', [['a', [
			'o-rel-href' => '/db/marketgroup/'.$submg['mgid1'],
			$submg['mgname1'],
		]]]);
	}
}

if($nmg > 0) {
	if($nsubmg > 0) {
		$li->append($subul);
	}
	$dbb->append([ $h3, $ul ]);
}



$typesq = \Osmium\Db\query_params(
	'SELECT it.typeid, it.typename, it.published
	FROM eve.invtypes it
	WHERE it.marketgroupid = $1
	ORDER BY it.published DESC, it.typename ASC',
	array($mg['mgid0'])
);

$h3 = $p->element('h3', 'Types in this market group:');
$ul = $p->element('ul', [ 'class' => 'typelist' ]);
$ntypes = 0;

while($t = \Osmium\Db\fetch_assoc($typesq)) {
	++$ntypes;
	$li = $ul->appendCreate('li', [
		[ 'a', [ 'o-rel-href' => '/db/type/'.$t['typeid'], $t['typename'] ] ]
	]);

	if($t['published'] !== 't') $li->addClass('unpublished');
}

if($ntypes > 0) {
	$dbb->append([ $h3, $ul ]);
	$dbb->appendCreate('p', [
		'class' => 'compare',
		[ 'a', [ 'o-rel-href' => '/db/comparemarketgroup/'.$mgid.'/auto',
		         'Compare types in this market group' ] ],
	]);
}



\Osmium\State\put_cache($cacheid, $dbb->renderNode());

RenderStage:
$p->title = \Osmium\Fit\get_marketgroupname($mgid).' / Market group '.$mgid;
$p->snippets[] = 'dbbrowser';
$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = '../..';
$p->render($ctx);
