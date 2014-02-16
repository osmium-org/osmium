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

namespace Osmium\Page\DBBrowser\ViewType;

require __DIR__.'/../../inc/root.php';

const RELATIVE = '../..';

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
		array($_GET['typeid'])
	)
);

if($type === false) \Osmium\fatal(404);

\Osmium\Chrome\print_header(
	strip_tags($type['typename']).' / Type '.$type['typeid'],
	RELATIVE
);
echo "<div id='dbb'>\n";

echo "<header>\n<h2>".\Osmium\Chrome\escape($type['typename']);
echo " <small>";
if($type['published'] !== 't') {
	echo "<span class='unpublished'>not public</span> – ";
}
echo "type ".$type['typeid']."</small></h2>\n</header>\n";

echo "<nav>\n";

echo "<ul>\n";
echo "<li><a href='../category/".$type['categoryid']."'>".\Osmium\Chrome\escape(
	$type['categoryname']
)."</a></li>\n";
echo "<li><a href='../group/".$type['groupid']."'>".\Osmium\Chrome\escape(
	$type['groupname']
)."</a></li>\n";
echo "<li class='lst'>".\Osmium\Chrome\escape($type['typename'])."</li>\n";
echo "</ul>\n";

if($type['mgid0'] !== null) {
	echo "<ul>\n";

	for($i = 4; $i >= 0; --$i) {
		if($type['mgid'.$i] !== null) {
			echo "<li><a href='../marketgroup/".$type['mgid'.$i]."'>".\Osmium\Chrome\escape(
				$type['mgname'.$i]
			)."</a></li>\n";
		}
	}
	echo "<li class='lst'>".\Osmium\Chrome\escape($type['typename'])."</li>\n";

	echo "</ul>\n";
}

echo "</nav>\n";

echo "<div id='desc'>\n";
echo "<img src='//image.eveonline.com/Type/".$type['typeid']."_64.png' alt='' />\n";

$desc = \Osmium\Chrome\trim($type['description']);
if($desc === '') {
	echo "<p class='placeholder'>This type has no description.</p>\n";
} else {
	echo \Osmium\Chrome\format_type_description($desc);
}

echo "</div>\n";

ob_start();

$aq = \Osmium\Db\query_params(
	"SELECT attributeid, attributename, displayname, value,
	unitid, udisplayname
	FROM osmium.siattributes
	WHERE typeid = $1
	ORDER BY attributeid ASC",
	array($type['typeid'])
);

$nattribs = 0;
while($a = \Osmium\Db\fetch_assoc($aq)) {
	if($nattribs === 0) {
		echo "<section id='a'>\n<table class='d'>\n<tbody>\n";
	}
	++$nattribs;

	$hasdname = ($a['displayname'] !== '');

	echo "<tr>\n";
	echo "<td><a href='../attribute/".$a['attributeid']."'>"
		.$a['attributeid']."</a></td>\n";
	echo "<td class='raw' colspan='".($hasdname ? 1 : 2)."'><small>"
		.\Osmium\Chrome\escape($a['attributename'])."</small></td>\n";
	if($hasdname) {
		echo "<td>".\Osmium\Chrome\escape(ucfirst($a['displayname']))."</td>\n";
	}
	echo "<td>".\Osmium\Chrome\format_number_with_unit(
		$a['value'],
		$a['unitid'],
		$a['udisplayname'],
		RELATIVE
	)."</td>\n";
	echo "</tr>\n";
}

if($nattribs > 0) {
	echo "</tbody>\n</table>\n</section>\n";
}



$eq = \Osmium\Db\query_params(
	'SELECT e.effectname, e.effectid
	FROM eve.dgmtypeeffects dte
	JOIN eve.dgmeffects e ON e.effectid = dte.effectid
	WHERE dte.typeid = $1
	ORDER BY e.effectid ASC',
	array($type['typeid'])
);

$neffects = 0;
while($e = \Osmium\Db\fetch_row($eq)) {
	if($neffects === 0) {
		echo "<section id='e'>\n<table class='d'>\n<tbody>\n";
	}
	++$neffects;

	echo "<tr>\n";
	echo "<td><a href='../effect/".$e[1]."'>".$e[1]."</a></td>\n";
	echo "<td class='raw'>".\Osmium\Chrome\escape($e[0])."</td>\n";
	echo "</tr>\n";
}

if($neffects > 0) {
	echo "</tbody>\n</table>\n</section>\n";
}



$prereqs = \Osmium\Fit\get_required_skills($type['typeid']);
$nprereqs = count($prereqs);
if($nprereqs > 0) {
	echo "<section id='r'>\n";

	function format_reqs(array $prereqs, array $blacklist) {
		if($prereqs === []) return;

		echo "<ul>\n";

		foreach($prereqs as $skill => $level) {
			echo "<li>\n";
			echo "<header><img src='//image.eveonline.com/Type/{$skill}_64.png' alt='' /> "
				."<a href='".RELATIVE."/db/type/{$skill}'>"
				.\Osmium\Chrome\escape(\Osmium\Fit\get_typename($skill))
				."</a> ".\Osmium\Chrome\format_skill_level($level)
				."</header>\n";

			$newbl = $blacklist; /* Copy the array */
			$newbl[$skill] = true;
			format_reqs(\Osmium\Fit\get_required_skills($skill), $newbl);

			echo "</li>\n";
		}

		echo "</ul>\n";
	}

	format_reqs($prereqs, []);

	echo "</section>\n";
}



$sreqq = \Osmium\Db\query_params(
	'SELECT it.typeid, it.typename, l.value::integer as level
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
	WHERE it.published = true
	ORDER BY level ASC, it.typeid ASC',
	array($type['typeid'])
);

ob_start();

$nreqby = [];
while($t = \Osmium\Db\fetch_assoc($sreqq)) {
	if(!isset($nreqby[$t['level']])) {
		if($nreqby !== []) {
			echo "</ul>\n</section>\n";
		}
		echo "<section id='l".$t['level']."'>\n<ul class='t'>\n";
		$nreqby[$t['level']] = 0;
	}
	++$nreqby[$t['level']];

	echo "<li>";
	echo "<img src='//image.eveonline.com/Type/".$t['typeid']."_64.png' alt='' /> ";
	echo "<a href='".RELATIVE."/db/type/".$t['typeid']."'>"
		.\Osmium\Chrome\escape($t['typename'])."</a>";
	echo "</li>\n";
}
if($nreqby !== []) {
	echo "</ul>\n</section>\n";
}

$lists = ob_get_clean();
if($nreqby !== []) {
	echo "<section id='b'>\n";
	echo "<ul class='tabs'>\n";
	foreach($nreqby as $l => $c) {
		echo "<li><a href='#l{$l}'>".\Osmium\Chrome\format_skill_level($l)."</a></li>\n";
	}
	echo "</ul>\n";
	echo $lists;
	echo "</section>\n";
}



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

	echo "<section id='v'>\n<table class='d'>\n<tbody>\n";

	foreach($fvariations as $v) {
		echo "<tr>\n";
		echo "<td>".\Osmium\Chrome\escape($v[3])."</td>\n";
		echo "<td><img src='//image.eveonline.com/Type/{$v[0]}_64.png' alt='' /> ";
		echo "<a href='".RELATIVE."/db/type/{$v[0]}'>".\Osmium\Chrome\escape($v[1])."</a></td>\n";
		echo "<td><small>meta level {$v[2]}</small></td>\n";
		echo "</tr>\n";
	}

	echo "</tbody>\n</table>\n</section>\n";
}

$sections = ob_get_clean();

ob_start();

if($type['categoryid'] == 6) {
	/* XXX */
	//echo "<li><a href='#t'>Traits</a></li>\n";
}
if($nattribs > 0) {
	echo "<li><a href='#a'>Attributes ({$nattribs})</a></li>\n";
}
if($neffects > 0) {
	echo "<li><a href='#e'>Effects ({$neffects})</a></li>\n";
}
if($nprereqs > 0) {
	echo "<li><a href='#r'>Requirements ({$nprereqs})</a></li>\n";
}
if($nreqby !== []) {
	echo "<li><a href='#b'>Required by (".array_sum($nreqby).")</a></li>\n";
}
if($nvariations > 1) {
	echo "<li><a href='#v'>Variations ({$nvariations})</a></li>\n";
}
//echo "<li><a href='#v'>Variations</a></li>\n";

$lis = ob_get_clean();

if($lis !== '') {
	echo "<ul class='tabs'>\n{$lis}</ul>\n";
	echo $sections;
}





echo "</div>\n";
\Osmium\Chrome\print_js_snippet('tabs');
\Osmium\Chrome\print_js_snippet('dbbrowser');
\Osmium\Chrome\print_footer();
