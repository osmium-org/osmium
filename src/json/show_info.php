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

if(isset($_GET['loadoutid'])) {
	if(!\Osmium\AjaxCommon\get_green_fit($fit, $cachename, $loadoutid, $revision)) {
		header('HTTP/1.1 400 Bad Request', true, 400);
		\Osmium\Chrome\return_json(array());
	}
} else if(isset($_GET['new'])) {
	$fit = \Osmium\State\get_new_loadout($_GET['new']);
} else {
	header('HTTP/1.1 400 Bad Request', true, 400);
	\Osmium\Chrome\return_json(array());
}

if(!isset($_GET['type'])) {
	header('HTTP/1.1 400 Bad Request', true, 400);
	\Osmium\Chrome\return_json(array());
}

function get_attributes($typeid, $getval_callback) {
	$attributes = array();

	$aq = \Osmium\Db\query_params("SELECT attributename, dgmattribs.displayname,
	dgmattribs.unitid, dgmunits.displayname AS udisplayname, categoryid FROM eve.dgmtypeattribs
	JOIN eve.dgmattribs ON dgmtypeattribs.attributeid = dgmattribs.attributeid
	LEFT JOIN eve.dgmunits ON dgmattribs.unitid = dgmunits.unitid
	WHERE typeid = $1 AND published = true AND dgmattribs.displayname <> ''
	ORDER BY categoryid ASC, dgmattribs.attributeid ASC", array($typeid));
	while($a = \Osmium\Db\fetch_assoc($aq)) {
		$attributes[$a['attributename']] = array(
			ucfirst($a['displayname']),
			\Osmium\Chrome\format_number_with_unit(
				$getval_callback($a['attributename']),
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
	$source = array('module', $st, $idx);
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
	$source = array('charge', $st, $idx);
	$attributes = get_attributes($typeid, function($aname) use(&$fit, $st, $idx) {
			return \Osmium\Dogma\get_charge_attribute($fit, $st, $idx, $aname);
		});
} else if($_GET['type'] == 'ship') {
	$typeid = $fit['ship']['typeid'];
	$typename = $fit['ship']['typename'];
	$source = array('ship');
	$attributes = get_attributes($typeid, function($aname) use(&$fit) {
			return \Osmium\Dogma\get_ship_attribute($fit, $aname);
		});
} else if($_GET['type'] == 'drone' && isset($_GET['typeid']) && isset($fit['drones'][$_GET['typeid']])) {
	$typeid = $_GET['typeid'];
	$typename = $fit['drones'][$typeid]['typename'];
	$source = array('drone', $typeid);
	$attributes = get_attributes($typeid, function($aname) use(&$fit, $typeid) {
			return \Osmium\Dogma\get_drone_attribute($fit, $typeid, $aname);
		});
} else {
	\Osmium\Chrome\return_json(array());
}

$affectors = array();
$dogmasource = \Osmium\Dogma\get_source($fit, $source);

foreach($dogmasource as $aname => $val) {
	if(isset($attributes[$aname])) {
		$dname = $attributes[$aname][0];
	} else $dname = ucfirst(preg_replace('%(([A-Z]+)|([0-9]))%', ' $1', $aname));

	$modifiers = \Osmium\Dogma\get_modifiers($fit, $aname, $dogmasource);
	if($modifiers === array()) continue;

	$mstackable = $fit['cache']['__attributes'][$aname]['stackable'];

	foreach($modifiers as $mtype => $m) {
		foreach($m as $m2) {
			foreach($m2 as $msourceattribute) {
				$fvalue = \Osmium\Dogma\get_final_attribute_value($fit,
				                                                  $msourceattribute,
				                                                  true,
				                                                  $mdogmasource);

				if(in_array($mtype, array('preassignment', 'postassignment'))) {
					$fvalue = '= '.round($fvalue, 3);
				} else if(in_array($mtype, array('premul', 'prediv', 'postmul', 'postdiv', 'postpercent'))) {
					$func = 'Osmium\Dogma\apply_'.$mtype;
					$mul = 1;
					$func($mul, $fvalue);

					/* That's right, I am testing two floats for
					 * equality. Sue me! */
					if($mul == 1.0) continue;

					$fvalue = 'x '.round($mul, 3);
				} else if(in_array($mtype, array('modadd', 'modsub'))) {
					$func = 'Osmium\Dogma\apply_'.$mtype;
					$add = 0;
					$func($add, $fvalue);

					if($add == 0) continue;

					$sign = ($add >= 0) ? '+' : '-';
					$fvalue = $sign.' '.round(abs($add), 3);
				}

				if(!$mstackable && \Osmium\Dogma\is_modifier_penalizable($mtype, $msourceattribute)) {
					$fvalue .= ' (penalized)';
				}

				$affectors[$mdogmasource['typeid']][$aname][] =
					array($dname, $fvalue);
			}
		}
	}
}

$fresult = array(
	'header' => "<img src='http://image.eveonline.com/Type/".$typeid."_64.png' alt='' /> ".htmlspecialchars($typename),
	'attributes' => '',
	'affectedby' => '',
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

	$fresult['attributes'] .= "<tr$class><td><strong>".htmlspecialchars($dname)."</strong></td><td>".$value."</td></tr>\n";
}
$fresult['attributes'] .= "</tbody>\n</table>\n";

$typeids = array_keys($affectors);
$typenames = array();
if($typeids !== array()) {
	$q = \Osmium\Db\query('SELECT typeid, typename, groupname, categoryname
	FROM eve.invtypes
	LEFT JOIN eve.invgroups ON invgroups.groupid = invtypes.groupid
	LEFT JOIN eve.invcategories ON invcategories.categoryid = invgroups.categoryid
	WHERE typeid IN ('.implode(',', $typeids).')');
	while($row = \Osmium\Db\fetch_assoc($q)) {
		$typenames[$row['typeid']] = $row;
	}
}

uksort($affectors, function($a, $b) use($typenames) {
		$a = $typenames[$a]['categoryname'].' '.$typenames[$a]['groupname'].' '.$typenames[$a]['typename'];
		$b = $typenames[$b]['categoryname'].' '.$typenames[$b]['groupname'].' '.$typenames[$b]['typename'];
		return strcmp($a, $b);
	});

$fresult['affectedby'] .= "<ul>\n";
foreach($affectors as $typeid => $a) {
	$typename = isset($typenames[$typeid]) ? htmlspecialchars($typenames[$typeid]['typename']) : $typeid.' typeID';
	$fresult['affectedby'] .= "<li><img src='http://image.eveonline.com/Type/{$typeid}_64.png' alt='' /> $typename\n";
	$fresult['affectedby'] .= "<ul>\n";
	foreach($a as $attr) {
		foreach($attr as $val) {
			$fresult['affectedby'] .= "<li>".htmlspecialchars($val[0])." ".$val[1]."</li>\n";
		}
	}
	$fresult['affectedby'] .= "</ul>\n</li>\n";
}
$fresult['affectedby'] .= "</ul>\n";
if($affectors === array()) {
	$fresult['affectedby'] .= "<p class='placeholder'>No affectors</p>\n";
}

\Osmium\Chrome\return_json(
	array(
		'modal' => "<header id='hsi'><h2>".$fresult['header']."</h2></header>\n"
		."<ul id='showinfotabs'>\n"
		."<li><a href='#siattributes'>Attributes</a></li>\n"
		."<li><a href='#siaffectedby'>Affected by</a></li>\n"
		."</ul>\n"
		."<section id='siattributes'>\n".$fresult['attributes']."</section>\n"
		."<section id='siaffectedby'>\n".$fresult['affectedby']."</section>\n"
		)
	);
