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

function filter_id_list($s, $max) {
	return array_filter(
		array_slice(
			array_map('intval', explode(',', $s)),
			0, $max
		)
	);
}

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
} else {
	$typeids = filter_id_list($_GET['typeids'], MAX_TYPES);	
	$title = 'Compare types';
}

\Osmium\Chrome\print_header(
	\Osmium\Chrome\escape(strip_tags($title)), RELATIVE, false,
	"<link href='//cdnjs.cloudflare.com/ajax/libs/jquery.perfect-scrollbar/0.4.6/perfect-scrollbar.css' rel='stylesheet' type='text/css' />\n"
);
echo "<div id='dbb' class='compare'>\n";

echo "<header><h2>".\Osmium\Chrome\escape($title)."</h2></header>\n";

$attributeids = $_GET['attributeids'] === 'auto'
	? [] : array_flip(filter_id_list($_GET['attributeids'], MAX_ATTRIBS));

$tlist = implode(',', $typeids);

if($attributeids === []) {
	$attribsq = \Osmium\Db\query(
		'SELECT DISTINCT attributeid
		FROM osmium.siattributes
		WHERE typeid IN ('.$tlist.')
		AND published = true
		ORDER BY attributeid ASC
		LIMIT '.MAX_ATTRIBS
	);

	while($a = \Osmium\Db\fetch_row($attribsq)) {
		$attributeids[$a[0]] = true;
	}
}

$alist = implode(',', array_keys($attributeids));

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
	if(count($vals) < 2) {
		unset($attributeids[$attributeid]);
	}
}

$attributeids = array_keys($attributeids);

/*echo "<p class='filter'>\n";
echo "Filter and/or reorder: <a class='types'>types</a>, <a class='attribs'>attributes</a>";
echo "</p>\n";*/

echo "<section class='compare'>\n<table class='d'>\n";
echo "<colgroup></colgroup>\n<colgroup></colgroup>\n";
foreach($attributeids as $attributeid) { echo "<colgroup></colgroup>\n"; }
echo "<thead>\n<tr>\n<td><img src='".RELATIVE."/static-".\Osmium\STATICVER."/favicon.png' alt='' /><br />".\Osmium\Chrome\escape($_SERVER['HTTP_HOST'])."</td>\n<td></td>\n";

foreach($attributeids as $attributeid) {
	$hig = isset($highisgood[$attributeid]) ? (int)$highisgood[$attributeid] : -1;

	$dn = \Osmium\Chrome\escape(ucfirst(\Osmium\Fit\get_attributedisplayname($attributeid)));
	echo "<th data-aid='{$attributeid}' data-hig='{$hig}'><a title='{$dn} ({$attributeid})'>{$dn}</a></th>\n";
}

echo "</tr>\n</thead>\n<tbody>\n";

foreach($typeids as $i => $typeid) {
	echo "<tr data-idx='{$i}' data-tid='{$typeid}'>\n";

	echo "<th><a>".\Osmium\Chrome\escape(\Osmium\Fit\get_typename($typeid))."</a></th>\n";
	echo "<th><a href='".RELATIVE."/db/type/{$typeid}'>"
		."<img src='//image.eveonline.com/Type/{$typeid}_64.png' alt='' /></a></th>\n";

	foreach($attributeids as $attributeid) {
		$val = isset($data[$typeid][$attributeid])
			? $data[$typeid][$attributeid] : [ null, "<small>N/A</small>" ];

		echo "<td data-aid='{$attributeid}' data-rawval='{$val[0]}'>{$val[1]}</td>\n";
	}

	echo "</tr>\n";
}

echo "</tbody>\n</table>\n</section>\n";

echo "</div>\n";
\Osmium\Chrome\include_js("//cdnjs.cloudflare.com/ajax/libs/jquery.perfect-scrollbar/0.4.6/jquery.perfect-scrollbar-with-mousewheel.min.js");
\Osmium\Chrome\print_js_snippet('dbbrowser');
\Osmium\Chrome\print_footer();
