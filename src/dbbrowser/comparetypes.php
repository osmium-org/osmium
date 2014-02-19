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

const RELATIVE = '../../..';
const MAX_TYPES = 50;
const MAX_ATTRIBS = 50;

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

	$title = 'Compare group: '.$gn[0];

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
	$title = 'Compare market group: '.$mgn;

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

	$title = 'Compare types';
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



\Osmium\Chrome\print_header(
	\Osmium\Chrome\escape(strip_tags($title)), RELATIVE, false,
	"<link href='//cdnjs.cloudflare.com/ajax/libs/jquery.perfect-scrollbar/0.4.6/perfect-scrollbar.css' rel='stylesheet' type='text/css' />\n"
);
echo "<div id='dbb' class='compare'>\n";

echo "<header><h2>".\Osmium\Chrome\escape($title)."</h2></header>\n";



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
		\Osmium\Chrome\format_number_with_unit(
			$ta['value'], $ta['unitid'], $ta['udisplayname'], RELATIVE
		)
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

echo "<p class='meta'>\n";
echo "<strong><a href='".RELATIVE."/help/db#compare'>Need help?</a></strong>";
echo "</p>\n";

echo "<section class='compare'>\n<table class='d'>\n";
echo "<colgroup></colgroup>\n<colgroup></colgroup>\n";
foreach($attributes as $attributeid => $a) { echo "<colgroup></colgroup>\n"; }
echo "<thead>\n<tr>\n<td><img src='".RELATIVE."/static-".\Osmium\STATICVER."/favicon.png' alt='' /><br />".\Osmium\Chrome\escape($_SERVER['HTTP_HOST'])."</td>\n<td></td>\n";

foreach($attributes as $attributeid => $a) {
	$hig = isset($highisgood[$attributeid]) ? (int)$highisgood[$attributeid] : -1;

	if($attributeid >= 0) {
		$dn = \Osmium\Chrome\escape(ucfirst(\Osmium\Fit\get_attributedisplayname($attributeid)));
	} else if(isset($a[0]) && $a[0] === 'rpn') {
		$dn = \Osmium\Chrome\escape($a[1]);
	}

	echo "<th data-aid='{$attributeid}' data-hig='{$hig}'><a title='{$dn} ({$attributeid})'>{$dn}</a></th>\n";
}

echo "</tr>\n</thead>\n<tbody>\n";

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
	echo "<tr data-idx='{$i}' data-tid='{$typeid}'>\n";

	echo "<th><a>".\Osmium\Chrome\escape(\Osmium\Fit\get_typename($typeid))."</a></th>\n";
	echo "<th><a href='".RELATIVE."/db/type/{$typeid}'>"
		."<img src='//image.eveonline.com/Type/{$typeid}_64.png' alt='' /></a></th>\n";

	foreach($attributes as $attributeid => $a) {
		if($attributeid >= 0) {
			$val = isset($data[$typeid][$attributeid]) ?
				$data[$typeid][$attributeid] : [ null, "<small>N/A</small>" ];
		} else if(isset($a[0]) && $a[0] === 'rpn') {
			$rpnv = eval_rpn($a, isset($data[$typeid]) ? $data[$typeid] : array());

			if($rpnv === null) {
				$val = [ null, "<small>ERR</small>" ];
			} else {
				$val = [ $rpnv, \Osmium\Chrome\round_sd($rpnv, 2) ];
			}
		}

		echo "<td data-aid='{$attributeid}' data-rawval='{$val[0]}'>{$val[1]}</td>\n";
	}

	echo "</tr>\n";
}

echo "</tbody>\n</table>\n</section>\n";

echo "</div>\n";
\Osmium\Chrome\include_js("//cdnjs.cloudflare.com/ajax/libs/jquery.perfect-scrollbar/0.4.6/jquery.perfect-scrollbar-with-mousewheel.min.js");
\Osmium\Chrome\print_js_snippet('dbbrowser');
\Osmium\Chrome\print_footer();
