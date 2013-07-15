<?php
/* Osmium
 * Copyright (C) 2012, 2013 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\Json\ShowInfo;

require __DIR__.'/../../inc/root.php';
require __DIR__.'/../../inc/ajax_common.php';

if(isset($_GET['loadoutsource']) && $_GET['clftoken']) {
	if($_GET['loadoutsource'] === 'new') {
		$fit = \Osmium\State\get_new_loadout($_GET['clftoken']);
	} else if($_GET['loadoutsource'] === 'view') {
		$fit = \Osmium\State\get_view_loadout($_GET['clftoken']);
	}
} else {
	header('HTTP/1.1 400 Bad Request', true, 400);
	\Osmium\Chrome\return_json(array());
}

if(!isset($_GET['type']) || !$fit) {
	header('HTTP/1.1 400 Bad Request', true, 400);
	\Osmium\Chrome\return_json(array());
}

function get_attributes($typeid, $getval_callback) {
	$attributes = array();

	$aq = \Osmium\Db\query_params(
		"SELECT dgmtypeattribs.attributeid, attributename, dgmattribs.displayname, dgmattribs.unitid,
		dgmunits.displayname AS udisplayname, categoryid
		FROM eve.dgmtypeattribs
		JOIN eve.dgmattribs ON dgmtypeattribs.attributeid = dgmattribs.attributeid
		LEFT JOIN eve.dgmunits ON dgmattribs.unitid = dgmunits.unitid
		WHERE typeid = $1 AND published = true AND dgmattribs.displayname <> ''
		ORDER BY categoryid ASC, dgmattribs.attributeid ASC",
		array($typeid)
	);
	while($a = \Osmium\Db\fetch_assoc($aq)) {
		$attributes[$a['attributeid']] = array(
			ucfirst($a['displayname']),
			\Osmium\Chrome\format_number_with_unit(
				$getval_callback($a['attributeid']),
				$a['unitid'],
				$a['udisplayname']
				),
			$a['categoryid'],
			);
	}

	return $attributes;
}

if($_GET['type'] == 'module' && isset($_GET['slottype']) && isset($_GET['index'])
   && isset($fit['modules'][$_GET['slottype']][$_GET['index']])) {
	$st = $_GET['slottype'];
	$idx = $_GET['index'];
	$module = $fit['modules'][$st][$idx];

	$typeid = $module['typeid'];
	$typename = $module['typename'];
	$loc = [ DOGMA_LOC_Module, 'module_index' => $module['dogma_index'] ];
	$attributes = get_attributes($typeid, function($aname) use(&$fit, $st, $idx) {
			return \Osmium\Dogma\get_module_attribute($fit, $st, $idx, $aname);
		});
} else if($_GET['type'] == 'charge' && isset($_GET['slottype']) && isset($_GET['index'])
   && isset($fit['charges'][$_GET['slottype']][$_GET['index']])) {
	$st = $_GET['slottype'];
	$idx = $_GET['index'];
	$charge = $fit['charges'][$st][$idx];

	$typeid = $charge['typeid'];
	$typename = $charge['typename'];
	$loc = [ DOGMA_LOC_Charge, 'module_index' => $fit['modules'][$st][$idx]['dogma_index'] ];
	$attributes = get_attributes($typeid, function($aname) use(&$fit, $st, $idx) {
			return \Osmium\Dogma\get_charge_attribute($fit, $st, $idx, $aname);
		});
} else if($_GET['type'] == 'ship') {
	$typeid = $fit['ship']['typeid'];
	$typename = $fit['ship']['typename'];
	$loc = DOGMA_LOC_Ship;
	$attributes = get_attributes($typeid, function($aname) use(&$fit) {
			return \Osmium\Dogma\get_ship_attribute($fit, $aname);
		});
} else if($_GET['type'] == 'drone' && isset($_GET['typeid']) && isset($fit['drones'][$_GET['typeid']])) {
	$typeid = $_GET['typeid'];
	$typename = $fit['drones'][$typeid]['typename'];
	$loc = [ DOGMA_LOC_Drone, 'drone_typeid' => (int)$typeid ];

	if($temptransfer = ($fit['drones'][$typeid]['quantityinspace'] == 0)) {
		/* libdogma only knows about drones in space */
		\Osmium\Fit\transfer_drone($fit, $typeid, 'bay', 1);
	}

	$attributes = get_attributes($typeid, function($aname) use(&$fit, $typeid) {
			return \Osmium\Dogma\get_drone_attribute($fit, $typeid, $aname);
		});

	if($temptransfer) {
		dogma_get_affectors($fit['__dogma_context'], $loc, $affectors);
		\Osmium\Fit\transfer_drone($fit, $typeid, 'space', 1);
	}
} else if(($_GET['type'] === 'implant' || $_GET['type'] === 'booster')
          && isset($_GET['typeid'])
          && isset($fit['implants'][$_GET['typeid']])) {
	$typeid = $_GET['typeid'];
	$typename = $fit['implants'][$typeid]['typename'];
	$loc = [ DOGMA_LOC_Implant, 'implant_index' => $fit['implants'][$typeid]['dogma_index'] ];
	$attributes = get_attributes($typeid, function($aname) use(&$fit, $typeid) {
		return \Osmium\Dogma\get_implant_attribute($fit, $typeid, $aname);
	});
}

else {
	\Osmium\Chrome\return_json(array());
}

if(!isset($affectors)) {
	dogma_get_affectors($fit['__dogma_context'], $loc, $affectors);
}
$affectors_per_type = array();
$affectors_per_att = array();
$numaffectors = 0;

foreach($affectors as $affector) {
	/* Skip affectors affecting non-overridden attributes */
	if(!isset($attributes[$affector['destid']])) continue;

	$dest = $attributes[$affector['destid']][0];
	$source = \Osmium\Fit\get_typename($affector['id']);

	switch($affector['operator']) {

	case '*':
		$affector['operator'] = '×';
		if($affector['value'] == 1.0) continue;
		break;

	case '-':
		$affector['value'] = -$affector['value'];
	case '+':
		if($affector['value'] == 0.0) continue;
		break;

	}

	$fval = $affector['value'];

	if($affector['flags'] > 0) {
		$flags = array();
		if($affector['flags'] & DOGMA_AFFECTOR_PENALIZED) {
			$flags[] = 'penalized';
		}
		if($affector['flags'] & DOGMA_AFFECTOR_SINGLETON) {
			$flags[] = 'singleton';
		}

		$fval .= ' <small>('.implode(', ', $flags).')</small>';
	}

	$a = [ $affector, $dest, $source, $fval ];
	$affectors_per_type[$affector['id']][] = $a;
	$affectors_per_att[$affector['destid']][] = $a;
	++$numaffectors;
}



$fresult = array(
	'header' => "<img src='http://image.eveonline.com/Type/".$typeid."_64.png' alt='' /> "
	.htmlspecialchars($typename),

	'attributes' => '',
	'affectors_per_type' => '',
	'affectors_per_att' => '',
);



$fresult['attributes'] .= "<table class='d'>\n<tbody>\n";
$previouscatid = null;
foreach($attributes as $a) {
	list($dname, $value, $catid) = $a;
	if($previouscatid !== $catid) {
		if($previouscatid !== null) {
			$class = " class='sep'";
		} else $class = '';
		$previouscatid = $catid;
	} else $class = '';

	$fresult['attributes'] .= "<tr$class><td><strong>"
		.htmlspecialchars($dname)
		."</strong></td><td>".$value."</td></tr>\n";
}
$fresult['attributes'] .= "</tbody>\n</table>\n";



uasort($affectors_per_type, function($a, $b) { return strcmp($a[0][2], $b[0][2]); });
uasort($affectors_per_att, function($a, $b) { return strcmp($a[0][1], $b[0][1]); });

$fresult['affectors_per_type'] .= "<ul>\n";
foreach($affectors_per_type as $typeid => &$a) {
	$typename = htmlspecialchars($a[0][2]);
	$fresult['affectors_per_type'] .= "<li><img src='http://image.eveonline.com/Type/"
		.$typeid."_64.png' alt='' /> ".$typename.":\n";
	$fresult['affectors_per_type'] .= "<ul>\n";

	usort($a, function($x, $y) { return strcmp($x[1], $y[1]); });

	foreach($a as $val) {
		list($aff, $dest, $source, $fval) = $val;
		$op = $aff['operator'];

		$fresult['affectors_per_type'] .= "<li><label>".htmlspecialchars($dest)."</label> {$op}{$fval}</li>\n";
	}
	$fresult['affectors_per_type'] .= "</ul>\n</li>\n";
}
$fresult['affectors_per_type'] .= "</ul>\n";


if($numaffectors > 0) {
	$fresult['affectors_per_att'] .= "<p><em>The operations are ordered by precedence (lower operations get applied last).</em></p>\n";
}

$fresult['affectors_per_att'] .= "<ul>\n";
foreach($affectors_per_att as $attid => &$a) {
	$attname = htmlspecialchars($a[0][1]);
	$fresult['affectors_per_att'] .= "<li>".$attname.":\n";
	$fresult['affectors_per_att'] .= "<ul>\n";

	usort($a, function($x, $y) { return $x[0]['order'] - $y[0]['order']; });

	foreach($a as $val) {
		list($aff, $dest, $source, $fval) = $val;
		$op = $aff['operator'];

		$fresult['affectors_per_att'] .= "<li><label><img src='//image.eveonline.com/Type/".$aff['id']
			."_64.png' alt='' /> ".htmlspecialchars($source)."</label> {$op}{$fval}</li>\n";
	}
	$fresult['affectors_per_att'] .= "</ul>\n</li>\n";
}
$fresult['affectors_per_att'] .= "</ul>\n";



if($affectors_per_type === array()) {
	$fresult['affectors_per_type'] .= "<p class='placeholder'>No affectors</p>\n";
	$fresult['affectors_per_att'] .= "<p class='placeholder'>No affectors</p>\n";
}



\Osmium\Chrome\return_json(
	array(
		'modal' => "<header id='hsi'><h2>".$fresult['header']."</h2></header>\n"
		."<ul id='showinfotabs'>\n"
		."<li><a href='#siattributes'>Attributes</a></li>\n"
		."<li><a href='#siafftype'>Affectors by type (".count($affectors_per_type).")</a></li>\n"
		."<li><a href='#siaffatt'>Affectors by attribute (".count($affectors_per_att).")</a></li>\n"
		."</ul>\n"
		."<section id='siattributes'>\n".$fresult['attributes']."</section>\n"
		."<section id='siafftype'>\n".$fresult['affectors_per_type']."</section>\n"
		."<section id='siaffatt'>\n".$fresult['affectors_per_att']."</section>\n"
		)
	);
