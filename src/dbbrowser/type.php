<?php
/* Osmium
 * Copyright (C) 2014, 2015 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\Page\DBBrowser\ViewType;

require __DIR__.'/../../inc/root.php';

$p = new \Osmium\DOM\Page();



$typeid = (int)$_GET['typeid'];
$cacheid = 'DBBrowser_Type_'.$typeid;
$xml = \Osmium\State\get_cache($cacheid);
if($xml !== null) {
	$dbb = $p->fragment($xml);
	$p->content->append($dbb);
	goto RenderStage; /* Boo */
}



$type = \Osmium\Db\fetch_assoc(
	\Osmium\Db\query_params(
		'SELECT typeid, typename, description, it.published,
		ig.groupid, ig.groupname, ic.categoryid, ic.categoryname,
		mg0.marketgroupid AS mgid0, mg0.marketgroupname AS mgname0,
		mg1.marketgroupid AS mgid1, mg1.marketgroupname AS mgname1,
		mg2.marketgroupid AS mgid2, mg2.marketgroupname AS mgname2,
		mg3.marketgroupid AS mgid3, mg3.marketgroupname AS mgname3,
		mg4.marketgroupid AS mgid4, mg4.marketgroupname AS mgname4
		FROM eve.invtypes it
		LEFT JOIN eve.invgroups ig ON ig.groupid = it.groupid
		LEFT JOIN eve.invcategories ic ON ic.categoryid = ig.categoryid
		LEFT JOIN eve.invmarketgroups mg0 ON mg0.marketgroupid = it.marketgroupid
		LEFT JOIN eve.invmarketgroups mg1 ON mg1.marketgroupid = mg0.parentgroupid
		LEFT JOIN eve.invmarketgroups mg2 ON mg2.marketgroupid = mg1.parentgroupid
		LEFT JOIN eve.invmarketgroups mg3 ON mg3.marketgroupid = mg2.parentgroupid
		LEFT JOIN eve.invmarketgroups mg4 ON mg4.marketgroupid = mg3.parentgroupid
		WHERE it.typeid = $1',
		array($typeid)
	)
);

if($type === false) \Osmium\fatal(404);

$dbb = $p->content->appendCreate('div', [ 'id' => 'dbb' ]);



/* —————————— Header —————————— */

$header = $dbb->appendCreate('header');
$h2 = $header->appendCreate('h2', $type['typename']);

$ap = \Osmium\Db\fetch_row(\Osmium\Db\query_params(
	'SELECT averageprice FROM eve.averagemarketprices
    WHERE typeid = $1',
	[ $type['typeid'] ]
));
if($ap !== false) {
	$h2->appendCreate('span.amp', [
		'title' => 'approximate market value',
		$p->formatNDigits($p->truncateSDigits($ap[0], 2), 2, false).' ISK'
	]);
}

if($type['published'] !== 't') {
	$h2->addClass('unpublished');
	$h2->setAttribute('title', 'This type is not public.');
}

$nav = $dbb->appendCreate('nav');
$ul = $nav->appendCreate('ul');
$ul->append([
	[ 'li', [ [ 'a', [ 'o-rel-href' => '/db/category/'.$type['categoryid'], $type['categoryname'] ] ] ] ],
	[ 'li', [ [ 'a', [ 'o-rel-href' => '/db/group/'.$type['groupid'], $type['groupname'] ] ] ] ],
	[ 'li', [ 'class' => 'lst memberof', $type['typename'] ] ],
]);

if($type['mgid0'] !== null) {
	$ul = $nav->appendCreate('ul');

	for($i = 4; $i >= 0; --$i) {
		if($type['mgid'.$i] !== null) {
			$ul->appendCreate('li', [
				[ 'a', [ 'o-rel-href' => '/db/marketgroup/'.$type['mgid'.$i], $type['mgname'.$i] ] ]
			]);
		}
	}

	$ul->appendCreate('li', [ 'class' => 'lst memberof', $type['typename'] ]);
}



/* —————————— Description —————————— */

$desc = $dbb->appendCreate('div', [ 'id' => 'desc' ]);
$desc->appendCreate('o-eve-img', [ 'src' => '/Type/'.$type['typeid'].'_64.png', 'alt' => '' ]);
$elements = $desc->appendCreate('ul#type-elements.tags');
$desctext = \Osmium\Chrome\trim($type['description']);

if($desctext !== '') {
	$fragment = $p->createDocumentFragment();
	$fragment->appendXML(\Osmium\Chrome\format_type_description($desctext));
	$desc->appendChild($fragment);
} else {
	$desc->appendCreate('p', [ 'class' => 'placeholder', 'This type has no description.' ]);
}

if((int)$type['categoryid'] === 6) {
	$ul = $desc->appendCreate('ul');
	
	$ul->appendCreate('li')->appendCreate('a', [
		'o-rel-href' => '/new/dna/'.$type['typeid'].'::',
		'Create a new '.$type['typename'].' loadout',
	]);

	$ul->appendCreate('li')->appendCreate('a', [
		'o-rel-href' => '/browse/best'.$p->formatQueryString([ 'q' => '@ship "'.$type['typename'].'"' ]),
		'Browse popular '.$type['typename'].' loadouts',
	]);
}

$q = \Osmium\Db\query_params(
	'SELECT ie.name, ie.description
    FROM eve.infotypeelements ite
    JOIN eve.infoelements ie ON ie.elementid = ite.elementid
    WHERE ite.typeid = $1
    ORDER BY ite.priority ASC',
	[ $type['typeid'] ]
);
while($row = \Osmium\Db\fetch_row($q)) {
	$elements->appendCreate('li', [
		'title' => $row[1],
		$row[0],
	]);
}

$ultabs = $dbb->appendCreate('ul', [ 'class' => 'tabs' ]);



/* —————————— Traits —————————— */

$traits = $p->formatTypeTraits($type['typeid']);
if($traits !== false) {
	$dbb->appendCreate('section', [ 'id' => 't' ])->append($traits);
}



/* —————————— Attributes —————————— */

$aq = \Osmium\Db\query_params(
	'SELECT attributeid, attributename, displayname, value,
	unitid, udisplayname
	FROM osmium.siattributes
	WHERE typeid = $1
	ORDER BY attributeid ASC',
	array($type['typeid'])
);

$nattribs = 0;
$section = $p->element('section', [ 'id' => 'a' ]);
$tbody = $section->appendCreate('table', [ 'class' => 'd' ])->appendCreate('tbody');
while($a = \Osmium\Db\fetch_assoc($aq)) {
	++$nattribs;

	$hasdname = ($a['displayname'] !== '');

	$tr = $tbody->appendCreate('tr');

	$tr->appendCreate('td', [
		[ 'a', [ 'o-rel-href' => '/db/attribute/'.$a['attributeid'], $a['attributeid'] ] ]
	]);

	$tr->appendCreate('td', [
		'class' => 'small', 'colspan' => ($hasdname ? 1 : 2),
		[ 'small', $a['attributename'] ]
	]);

	if($hasdname) {
		$tr->appendCreate('td', ucfirst($a['displayname']));
	}

	$tr->appendCreate('td', $p->formatNumberWithUnit($a['value'], $a['unitid'], $a['udisplayname']));
}

if($nattribs > 0) {
	$dbb->append($section);
}



/* —————————— Effects —————————— */

$eq = \Osmium\Db\query_params(
	'SELECT e.effectname, e.effectid, e.effectcategory
	FROM eve.dgmtypeeffects dte
	JOIN eve.dgmeffects e ON e.effectid = dte.effectid
	WHERE dte.typeid = $1
	ORDER BY e.effectid ASC',
	array($type['typeid'])
);

$neffects = 0;
$section = $p->element('section', [ 'id' => 'e' ]);
$tbody = $section->appendCreate('table', [ 'class' => 'd' ])->appendCreate('tbody');
while($e = \Osmium\Db\fetch_row($eq)) {
	++$neffects;

	$tbody->appendCreate('tr', [
		[ 'td', [ [ 'a', [ 'o-rel-href' => '/db/effect/'.$e[1], $e[1] ] ] ] ],
		[ 'td', [ 'class' => 'raw', $e[0] ] ],
		[ 'td', [ 'class' => 'raw', \Osmium\Chrome\format_effect_category($e[2]) ] ],
	]);
}

if($neffects > 0) {
	$dbb->append($section);
}



/* —————————— Prerequisites —————————— */

$prereqs = \Osmium\Fit\get_required_skills($type['typeid']);
$nprereqs = count($prereqs);
if($nprereqs > 0) {
	function make_reqs(\Osmium\DOM\Document $e, array $prereqs, array $blacklist) {
		if($prereqs === []) return '';

		$ul = $e->element('ul');

		foreach($prereqs as $skill => $level) {
			if(isset($blacklist[$skill])) continue;
			
			$li = $ul->appendCreate('li');

			$li->appendCreate('header', [
				[ 'o-eve-img', [ 'src' => '/Type/'.$skill.'_64.png', 'alt' => '' ] ],
				[ 'a', [ 'o-rel-href' => '/db/type/'.$skill, \Osmium\Fit\get_typename($skill) ] ],
				' ',
				$e->formatSkillLevel($level),
			]);

			$newbl = $blacklist; /* Copy the array */
			$newbl[$skill] = true;

			$li->append(make_reqs($e, \Osmium\Fit\get_required_skills($skill), $newbl));
		}

		return $ul;
	}

	$dbb->appendCreate('section', [ 'id' => 'r' ])->append(make_reqs($p, $prereqs, []));
}



/* —————————— Required by —————————— */

$sreqq = \Osmium\Db\query_params(
	'SELECT it.typeid, it.typename, l.value::integer as level, it.published
	FROM eve.invtypes it
	JOIN eve.dgmtypeattribs s ON s.typeid = it.typeid
	AND s.attributeid IN (182, 183, 184, 1285, 1289, 1290)
	AND s.value = $1
	JOIN eve.dgmtypeattribs l ON l.typeid = it.typeid
	AND l.attributeid = CASE s.attributeid
	WHEN 182 THEN 277
	WHEN 183 THEN 278
	WHEN 184 THEN 279
	WHEN 1285 THEN 1286
	WHEN 1289 THEN 1287
	WHEN 1290 THEN 1288
	ELSE NULL END
	ORDER BY level ASC, it.published DESC, it.typeid ASC',
	array($type['typeid'])
);

$nreqby = [];
$section = $p->element('section', [ 'id' => 'b' ]);

while($t = \Osmium\Db\fetch_assoc($sreqq)) {
	if(!isset($nreqby[$t['level']])) {
		$nreqby[$t['level']] = 0;

		$ul = $section->appendCreate('section', [ 'id' => 'l'.$t['level'] ])
			->appendCreate('ul', [ 'class' => 't' ]);
	}

	++$nreqby[$t['level']];

	$li = $ul->appendCreate('li', [
		[ 'o-eve-img', [ 'src' => '/Type/'.$t['typeid'].'_64.png', 'alt' => '' ] ],
		' ',
		[ 'a', [ 'o-rel-href' => '/db/type/'.$t['typeid'], $t['typename'] ] ],
	]);

	if($t['published'] !== 't') $li->addClass('unpublished');
}

if($nreqby !== []) {
	$dbb->append($section);

	$ul = $p->element('ul', [ 'class' => 'tabs' ]);
	foreach($nreqby as $l => $c) {
		$ul->appendCreate('li', [[ 'a', [
			'href' => '#l'.$l,
			$p->formatSkillLevel($l)
		] ]]);
	}

	$section->prepend($ul);
}



/* —————————— Variations —————————— */

$vq = \Osmium\Db\query_params(
	'SELECT vartypeid, vartypename, varmgid, varml, img.metagroupname
	FROM osmium.invtypevariations
	LEFT JOIN eve.invmetagroups img ON img.metagroupid = varmgid
	WHERE typeid = $1
	ORDER BY varml DESC, vartypeid ASC',
	array($type['typeid'])
);
$variations = [];
$fvariations = [];
$nvariations = 0;
while($v = \Osmium\Db\fetch_assoc($vq)) {
	$variations[$v['varmgid']][] = [
		(int)$v['vartypeid'],
		$v['vartypename'],
		(int)$v['varml'],
		$v['metagroupname'],
	];
	++$nvariations;
}
if($nvariations > 1) {
	usort($variations, function($x, $y) {
		return $x[0][2] - $y[0][2];
	});
	foreach($variations as $a) {
		usort($a, function($x, $y) {
			return $x[2] - $y[2];
		});
		$fvariations = array_merge($fvariations, $a);
	}

	$section = $section = $dbb->appendCreate('section', [ 'id' => 'v' ]);
	$tbody = $section->appendCreate('table', [ 'class' => 'd' ])->appendCreate('tbody');

	$vartypeids = [];
	foreach($fvariations as $v) {
		$vartypeids[] = $v[0];

		$tbody->appendCreate('tr', [
			[ 'td', $v[3] ],
			[ 'td', [
				[ 'o-eve-img', [ 'src' => '/Type/'.$v[0].'_64.png', 'alt' => '' ] ],
				' ',
				[ 'a', [ 'o-rel-href' => '/db/type/'.$v[0], $v[1] ] ],
			]],
			[ 'td', [ [ 'small', 'meta level '.$v[2] ] ] ],
		]);
	}

	$section->appendCreate('p', [ 'class' => 'compare', [ 'a', [
		'o-rel-href' => '/db/comparevariations/'.$typeid.'/auto',
		'rel' => 'nofollow',
		'Compare these types',
	]]]);
}



/* —————————— Tabs —————————— */

$ntabs = 0;

if($traits !== false) {
	$ultabs->appendCreate('li', [[ 'a', [ 'href' => '#t', 'Traits' ] ]]);
	++$ntabs;
}
if($nattribs > 0) {
	$ultabs->appendCreate('li', [[ 'a', [ 'href' => '#a', 'Attributes ('.$nattribs.')' ] ]]);
	++$ntabs;
}
if($neffects > 0) {
	$ultabs->appendCreate('li', [[ 'a', [ 'href' => '#e', 'Effects ('.$neffects.')' ] ]]);
	++$ntabs;
}
if($nprereqs > 0) {
	$ultabs->appendCreate('li', [[ 'a', [ 'href' => '#r', 'Requirements ('.$nprereqs.')' ] ]]);
	++$ntabs;
}
if($nreqby !== []) {
	$ultabs->appendCreate('li', [[ 'a', [ 'href' => '#b', 'Required by ('.array_sum($nreqby).')' ] ]]);
	++$ntabs;
}
if($nvariations > 1) {
	$ultabs->appendCreate('li', [[ 'a', [ 'href' => '#v', 'Variations ('.$nvariations.')' ] ]]);
	++$ntabs;
}

if($ntabs <= 1) {
	$ultabs->remove();
}



\Osmium\State\put_cache($cacheid, $dbb->renderNode());

RenderStage:
$p->title = \Osmium\Fit\get_typename($typeid).' / Type '.$typeid;
$p->snippets[] = 'dbbrowser';
$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = '../..';
$p->render($ctx);
