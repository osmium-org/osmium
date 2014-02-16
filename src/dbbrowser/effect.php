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

namespace Osmium\Page\DBBrowser\ViewAttribute;

require __DIR__.'/../../inc/root.php';
require \Osmium\ROOT.'/inc/dbbrowser_common.php';

const RELATIVE = '../..';

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

\Osmium\Chrome\print_header(
	$e['effectname'].' / Effect '.$e['effectid'],
	RELATIVE
);
echo "<div id='dbb'>\n";

echo "<header>\n<h2><span class='raw'>".$e['effectname']."</span>";
echo " <small>effect ".$e['effectid']."</small></h2>\n</header>\n";

echo "<ul>\n";
echo "<li>Effect category: <span class='raw'>".\Osmium\Chrome\format_effect_category($e['effectcategory'])."</span></li>\n";
echo "</ul>\n<ul>\n";

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
		echo "<li>{$label}: <small>see value of "
			.\Osmium\Chrome\format_number_with_unit($e[$k], 119, "attributeID")."</small></li>\n";
	}
}

echo "</ul>\n<ul>\n";

$ebools = [
	'isoffensive' => 'Effect is % considered offensive',
	'isassistance' => 'Effect is % considered as remote assist',
	'iswarpsafe' => 'Effect can % be used in warp',
];
if(in_array((int)$e['effectcategory'], [ 1, 2, 3, 6, 7 ], true)) {
	foreach($ebools as $k => $label) {
		$label = str_replace('%', $e[$k] === 't' ? '' : '<strong>not</strong>', $label);
		echo "<li>{$label}</li>\n";
	}
}

echo "</ul>\n<ul>\n";
echo "<li>Pre expression: <small class='raw'>".\Osmium\Chrome\escape($e['preexpression'])."</small></li>\n";
echo "<li>Post expression: <small class='raw'>".\Osmium\Chrome\escape($e['postexpression'])."</small></li>\n";
echo "</ul>\n";

$typesq = \Osmium\Db\query_params(
	'SELECT it.typeid, it.typename
	FROM eve.dgmtypeeffects dte
	JOIN eve.invtypes it ON it.typeid = dte.typeid AND it.published = true
	WHERE dte.effectid = $1
	ORDER BY it.typename ASC',
	array($e['effectid'])
);

echo "<h3>List of types which have this effect:</h3>\n";

$types = [];
while($t = \Osmium\Db\fetch_assoc($typesq)) {
	$e = "<li>";
	$e .= "<a href='".RELATIVE."/db/type/".$t['typeid']."'>"
		.\Osmium\Chrome\escape($t['typename'])."</a>";
	$e .= "</li>\n";

	$types[] = [ $t['typename'], $e ];
}

\Osmium\DBBrowser\print_typelist($types);


echo "</div>\n";
\Osmium\Chrome\print_js_snippet('dbbrowser');
\Osmium\Chrome\print_footer();
