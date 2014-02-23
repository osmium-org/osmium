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

namespace Osmium\Page\DBBrowser\CompareTypeAttributes;

require __DIR__.'/../../inc/root.php';

const MAX_TYPES = 50;
const MAX_ATTRIBS = 50;

$p = new \Osmium\DOM\Page();



if($_GET['groupid'] > 0) {
	$gn = \Osmium\Db\fetch_row(
		\Osmium\Db\query_params(
			'SELECT groupname FROM eve.invgroups
			WHERE groupid = $1',
			array($_GET['groupid'])
		)
	);
	if($gn === false) \Osmium\fatal(404);

	$typeids = [];
	$tq = \Osmium\Db\query_params(
		'SELECT typeid
		FROM eve.invtypes
		WHERE groupid = $1 AND published = true
		ORDER BY typename ASC
		LIMIT '.MAX_TYPES,
		[ $_GET['groupid'] ]
	);
	while($t = \Osmium\Db\fetch_row($tq)) {
		$typeids[] = $t[0];
	}

	$p->title = 'Compare group: '.$gn[0];

} else if($_GET['marketgroupid'] > 0) {
	$mgn = \Osmium\Db\fetch_row(
		\Osmium\Db\query_params(
			'SELECT mg1.marketgroupname AS mgname1, mg0.marketgroupname AS mgname0
			FROM eve.invmarketgroups mg0
			LEFT JOIN eve.invmarketgroups mg1 ON mg1.marketgroupid = mg0.parentgroupid
			WHERE mg0.marketgroupid = $1',
			array($_GET['marketgroupid'])
		)
	);
	if($mgn === false) \Osmium\fatal(404);
	$mgn = implode(', ', array_filter($mgn));
	$p->title = 'Compare market group: '.$mgn;

	$typeids = [];
	$tq = \Osmium\Db\query_params(
		'SELECT typeid
		FROM eve.invtypes
		WHERE marketgroupid = $1 AND published = true
		ORDER BY typename ASC
		LIMIT '.MAX_TYPES,
		array($_GET['marketgroupid'])
	);
	while($t = \Osmium\Db\fetch_row($tq)) {
		$typeids[] = $t[0];
	}

} else {
	$typeids = [];

	foreach(explode(',', $_GET['typeids'], MAX_TYPES) as $t) {
		if($t > 0) {
			$typeids[] = (int)$t;
		}
	}

	$p->title = 'Compare types';
}

$tlist = implode(',', $typeids);
$attributes = [];
$metaattribidx = -1;
$hasauto = false;

foreach(explode(',', $_GET['attributes'], MAX_ATTRIBS) as $attrib) {
	if($attrib === 'auto') {
		if($hasauto) continue;

		/* Fetch attribute list from siattribs */
		$attribsq = \Osmium\Db\query(
			'SELECT DISTINCT attributeid
			FROM osmium.siattributes
			WHERE typeid IN ('.$tlist.')
			AND published = true
			ORDER BY attributeid ASC
			LIMIT '.MAX_ATTRIBS
		);

		while($a = \Osmium\Db\fetch_row($attribsq)) {
			$attributes[(int)$a[0]] = true;
		}

		$hasauto = true;
		continue;
	}

	if(ctype_digit($attrib)) {
		/* Simple attribute */
		$attributes[(int)$attrib] = true;
		continue;
	}

	if(preg_match(
		'%^rpn:(?<name>[^:]+)(?<rpn>(:(a|((\+|-)?[0-9]*(\.[0-9]+)?(e(\+|-)?[0-9]+)?)|add|sub|mul|div))+)$%',
		$attrib,
		$match
	)) {
		/* RPN attribute */
		$attributes[$metaattribidx--] = explode(':', $attrib);
		continue;
	}

	\Osmium\fatal(400, "Unrecognized attribute <code>".\Osmium\Chrome\escape($attrib)."</code>");
}



$dbb = $p->content->appendCreate('div', [ 'id' => 'dbb', 'class' => 'compare' ]);
$dbb->appendCreate('header')->appendCreate('h2', $p->title);



$alist = implode(',', array_keys($attributes));
$highisgood = [];
$higq = \Osmium\Db\query(
	'SELECT attributeid, highisgood
	FROM eve.dgmattribs
	WHERE attributeid IN ('.$alist.')'
);
while($hig = \Osmium\Db\fetch_row($higq)) {
	$highisgood[$hig[0]] = ($hig[1] === 't');
}

$typeattribsq = \Osmium\Db\query(
	'SELECT typeid, attributeid, value, unitid, udisplayname
	FROM osmium.siattributes
	WHERE typeid IN ('.$tlist.')
	AND attributeid IN ('.$alist.')
	AND published = true
	ORDER BY attributeid ASC'
);

$data = [];

while($ta = \Osmium\Db\fetch_assoc($typeattribsq)) {
	$data[$ta['typeid']][$ta['attributeid']] = [
		$ta['value'],
		$p->formatNumberWithUnit($ta['value'], $ta['unitid'], $ta['udisplayname']),
	];
}

/* Prune attributes with no differences */
$attributevals = [];
foreach($data as $typeid => $sub) {
	foreach($sub as $attributeid => $val) {
		$attributevals[$attributeid][$val[0]] = true;
	}
}

foreach($attributevals as $attributeid => $vals) {
	if($attributeid < 0) continue;

	if(count($vals) < 2) {
		unset($attributes[$attributeid]);
	}
}



$dbb->appendCreate('p', [ 'class' => 'meta' ])->appendCreate('strong')->appendCreate(
	'a', [ 'o-rel-href' => '/help/db#compare', 'Need help?' ]
);

$table = $dbb->appendCreate('section', [ 'class' => 'compare' ])->appendCreate('table', [ 'class' => 'd' ]);

$table->appendCreate('colgroup');
$table->appendCreate('colgroup');
foreach($attributes as $a) $table->appendCreate('colgroup');

$tr = $table->appendCreate('thead')->appendCreate('tr');
$tr->appendCreate('td', [
	[ 'img', [ 'o-static-src' => '/favicon.png', 'alt' => '' ] ],
	[ 'br' ],
	$_SERVER['HTTP_HOST'],
]);
$tr->appendCreate('td');

foreach($attributes as $aid => $a) {
	$hig = isset($highisgood[$aid]) ? (int)$highisgood[$aid] : -1;
	$dn = ($aid >= 0) ? ucfirst(\Osmium\Fit\get_attributedisplayname($aid)) : $a[1];

	$tr->appendCreate('th', [
		'data-aid' => $aid,
		'data-hig' => $hig,
	])->appendCreate('a', [
		'title' => $dn.' ('.$aid.')',
		$dn
	]);
}

$tbody = $table->appendCreate('tbody');

function eval_rpn(array $rpn, array $attribvals) {
	$stack = [];

	array_shift($rpn); /* rpn */
	array_shift($rpn); /* name */

	while(($e = array_shift($rpn)) !== null) {
		if($e === 'a') {
			/* Substitute attributeID on top of stack by attrib value */
			$aid = array_pop($stack);

			if($aid === null) return null;

			$stack[] = isset($attribvals[$aid]) ?
				$attribvals[$aid][0] : \Osmium\Fit\get_attributedefaultvalue($aid);

		} else if(is_numeric($e)) {
			/* Number literal: put on the stack */
			$stack[] = floatval($e);
		} else if($e === 'add') {
			$a = array_pop($stack);
			$b = array_pop($stack);
			if($a === null || $b === null) return null;
			$stack[] = $a + $b;
		} else if($e === 'sub') {
			$a = array_pop($stack);
			$b = array_pop($stack);
			if($a === null || $b === null) return null;
			$stack[] = $b - $a;
		} else if($e === 'mul') {
			$a = array_pop($stack);
			$b = array_pop($stack);
			if($a === null || $b === null) return null;
			$stack[] = $a * $b;
		} else if($e === 'div') {
			$a = array_pop($stack);
			$b = array_pop($stack);
			if($b === null || !$a) return null;
			$stack[] = $b / $a;
		}
	}

	return array_pop($stack);
}



foreach($typeids as $i => $typeid) {
	$tr = $tbody->appendCreate('tr', [
		'data-idx' => $i,
		'data-tid' => $typeid,
	]);

	$tr->appendCreate('th')->appendCreate('a', \Osmium\Fit\get_typename($typeid));
	$tr->appendCreate('th')->appendCreate('a', [
		'o-rel-href' => '/db/type/'.$typeid
	])->appendCreate('o-eve-img', [
		'src' => '/Type/'.$typeid.'_64.png',
		'alt' => '',
	]);

	foreach($attributes as $attributeid => $a) {
		if($attributeid >= 0) {
			$val = isset($data[$typeid][$attributeid]) ?
				$data[$typeid][$attributeid] : [ null, "<small>N/A</small>" ];
		} else if(isset($a[0]) && $a[0] === 'rpn') {
			$rpnv = eval_rpn($a, isset($data[$typeid]) ? $data[$typeid] : array());

			if($rpnv === null) {
				$val = [ null, "<small>ERR</small>" ];
			} else {
				$val = [ $rpnv, (string)$p->formatSDigits($rpnv, 2) ];
			}
		}

		$tr->appendCreate('td', [
			'data-aid' => $attributeid,
			'data-rawval' => $val[0],
			$val[1]
		]);
	}
}



$p->head->appendCreate('link', [
	'href' => '//cdnjs.cloudflare.com/ajax/libs/jquery.perfect-scrollbar/0.4.6/perfect-scrollbar.css',
	'rel' => 'stylesheet',
	'type' => 'text/css',
]);
$p->endbody[] = $p->element('script', [
	'type' => 'application/javascript',
	'src' => '//cdnjs.cloudflare.com/ajax/libs/jquery.perfect-scrollbar/0.4.6/jquery.perfect-scrollbar-with-mousewheel.min.js',
]);
$p->snippets[] = 'dbbrowser';
$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = '../../..';
$p->render($ctx);
