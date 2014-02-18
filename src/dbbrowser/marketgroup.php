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
require \Osmium\ROOT.'/inc/dbbrowser_common.php';

const RELATIVE = '../..';

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
		array($_GET['mgid'])
	)
);

if($mg === false) \Osmium\fatal(404);

\Osmium\Chrome\print_header(
	\Osmium\Chrome\escape(strip_tags($mg['mgname0'])).' / Market group '.$mg['mgid0'],
	RELATIVE
);
echo "<div id='dbb'>\n";

echo "<header>\n<h2>".\Osmium\Chrome\escape($mg['mgname0']);
echo " <small>marketgroup ".$mg['mgid0']."</small></h2>\n</header>\n";

if($mg['mgid1'] !== null) {
	echo "<nav>\n<ul>\n";

	for($i = 4; $i >= 1; --$i) {
		if($mg['mgid'.$i] !== null) {
			echo "<li><a href='../marketgroup/".$mg['mgid'.$i]."'>".\Osmium\Chrome\escape(
				$mg['mgname'.$i]
			)."</a></li>\n";
		}
	}
	echo "<li class='lst'>".\Osmium\Chrome\escape($mg['mgname0'])."</li>\n";

	echo "</ul>\n</nav>\n";
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

$hassubmg = false;
$hassubsubmg = false;
$prevparent = null;
while($submg = \Osmium\Db\fetch_assoc($submgq)) {
	if($hassubmg === false) {
		echo "<h3>Market subgroups:</h3>\n<ul class='submg'>\n";
		$hassubmg = true;
	}

	if($prevparent !== $submg['mgid0']) {
		if($hassubsubmg) echo "</ul>\n";
		if($prevparent !== null) echo "</li>\n";

		echo "<li><a href='".RELATIVE."/db/marketgroup/".$submg['mgid0']."'>"
			.\Osmium\Chrome\escape($submg['mgname0'])."</a>\n";

		$prevparent = $submg['mgid0'];
		$hassubsubmg = false;
	}

	if($submg['mgid1'] !== null) {
		if($hassubsubmg === false) {
			echo "<ul>\n";
			$hassubsubmg = true;
		}

		echo "<li><a href='".RELATIVE."/db/marketgroup/".$submg['mgid1']."'>"
			.\Osmium\Chrome\escape($submg['mgname1'])."</a></li>\n";
	}
}

if($hassubsubmg) echo "</ul>\n";
if($hassubmg) echo "</li>\n</ul>\n";



$typesq = \Osmium\Db\query_params(
	'SELECT it.typeid, it.typename
	FROM eve.invtypes it
	WHERE it.marketgroupid = $1 AND it.published = true
	ORDER BY it.typename ASC',
	array($mg['mgid0'])
);

$types = [];
while($t = \Osmium\Db\fetch_assoc($typesq)) {
	$e = "<li>";
	$e .= "<a href='".RELATIVE."/db/type/".$t['typeid']."'>"
		.\Osmium\Chrome\escape($t['typename'])."</a>";
	$e .= "</li>\n";

	$types[] = [ $t['typename'], $e ];
}

if($types !== []) {
	echo "<h3>Types in this market group:</h3>\n";
}

\Osmium\DBBrowser\print_typelist($types);

if($types !== []) {
	echo "<p class='compare'><a href='".RELATIVE."/db/comparemarketgroup/{$mg['mgid0']}/auto'>Compare types in this market group</a></p>\n";
}

echo "</div>\n";
\Osmium\Chrome\print_js_snippet('dbbrowser');
\Osmium\Chrome\print_footer();
