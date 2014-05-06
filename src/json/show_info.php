<?php
/* Osmium
 * Copyright (C) 2012, 2013, 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

if(isset($_GET['clftoken'])) {
	$fit = \Osmium\State\get_loadout($_GET['clftoken']);
} else {
	header('HTTP/1.1 400 Bad Request', true, 400);
	\Osmium\Chrome\return_json(array());
}

if(!isset($_POST['type']) || $fit === null) {
	header('HTTP/1.1 400 Bad Request', true, 400);
	\Osmium\Chrome\return_json(array());
}

if(isset($_POST['remote'])) {
	\Osmium\Fit\set_local($fit, $_POST['remote']);
}

\Osmium\Dogma\auto_init($fit);

if($_POST['type'] == 'module' && isset($_POST['slottype']) && isset($_POST['index'])
   && isset($fit['modules'][$_POST['slottype']][$_POST['index']])) {
	$st = $_POST['slottype'];
	$idx = $_POST['index'];
	$module = $fit['modules'][$st][$idx];

	$typeid = $module['typeid'];
	$typename = $module['typename'];
	$loc = [ DOGMA_LOC_Module, 'module_index' => $module['dogma_index'] ];
	$getatt = function($aname) use(&$fit, $st, $idx) {
		return \Osmium\Dogma\get_module_attribute($fit, $st, $idx, $aname);
	};
} else if($_POST['type'] == 'charge' && isset($_POST['slottype']) && isset($_POST['index'])
   && isset($fit['charges'][$_POST['slottype']][$_POST['index']])) {
	$st = $_POST['slottype'];
	$idx = $_POST['index'];
	$charge = $fit['charges'][$st][$idx];

	$typeid = $charge['typeid'];
	$typename = $charge['typename'];
	$loc = [ DOGMA_LOC_Charge, 'module_index' => $fit['modules'][$st][$idx]['dogma_index'] ];
	$getatt = function($aname) use(&$fit, $st, $idx) {
		return \Osmium\Dogma\get_charge_attribute($fit, $st, $idx, $aname);
	};
} else if($_POST['type'] == 'ship') {
	$typeid = $fit['ship']['typeid'];
	$typename = $fit['ship']['typename'];
	$loc = DOGMA_LOC_Ship;
	$getatt = function($aname) use(&$fit) {
		return \Osmium\Dogma\get_ship_attribute($fit, $aname);
	};
} else if($_POST['type'] == 'drone' && isset($_POST['typeid']) && isset($fit['drones'][$_POST['typeid']])) {
	$typeid = $_POST['typeid'];
	$typename = $fit['drones'][$typeid]['typename'];
	$loc = [ DOGMA_LOC_Drone, 'drone_typeid' => (int)$typeid ];

	if($fit['drones'][$typeid]['quantityinspace'] == 0) {
		/* libdogma only knows about drones in space. */
		\Osmium\Fit\transfer_drone($fit, $typeid, 'bay');
	}

	$getatt = function($aname) use(&$fit, $typeid) {
		return \Osmium\Dogma\get_drone_attribute($fit, $typeid, $aname);
	};
} else if(($_POST['type'] === 'implant' || $_POST['type'] === 'booster')
          && isset($_POST['typeid'])
          && isset($fit['implants'][$_POST['typeid']])) {
	$typeid = $_POST['typeid'];
	$typename = $fit['implants'][$typeid]['typename'];
	$loc = [ DOGMA_LOC_Implant, 'implant_index' => $fit['implants'][$typeid]['dogma_index'] ];
	$getatt = function($aname) use(&$fit, $typeid) {
		return \Osmium\Dogma\get_implant_attribute($fit, $typeid, $aname);
	};
} else if($_POST['type'] === 'generic') {
	$typeid = (int)$_POST['typeid'];
	$typename = \Osmium\Fit\get_typename($typeid);
	$getatt = null;
	$affectors = false;
}

else {
	header('HTTP/1.1 400 Bad Request', true, 400);
	\Osmium\Chrome\return_json(array());
}



$p = new \Osmium\DOM\RawPage();
$suffix = number_format(microtime(true), 6, '', '');

$hdr = $p->element('header', [ 'class' => 'hsi' ]);
$h2 = $hdr->appendCreate('h2');
$h2->appendCreate('o-eve-img', [ 'src' => '/Type/'.$typeid.'_64.png', 'alt' => '' ]);
$h2->appendCreate('a', [ 'o-rel-href' => '/db/type/'.$typeid, $typename ]);

$ultabs = $p->element('ul', [ 'class' => 'showinfotabs' ]);

$p->appendChild($hdr);
$p->appendChild($ultabs);



/* —————————— Traits —————————— */

$traits = $p->formatTypeTraits($typeid);
if($traits !== false) {
	$ultabs->appendCreate('li')->appendCreate('a', [ 'href' => '#sitraits-'.$suffix, 'Traits' ]);
	$p->appendChild($p->element('section', [ 'class' => 'sitraits', 'id' => 'sitraits-'.$suffix, $traits ]));
}



/* —————————— Description —————————— */

list($desc) = \Osmium\Db\fetch_row(
	\Osmium\Db\query_params(
		'SELECT description FROM eve.invtypes WHERE typeid = $1', 
		array($typeid)
	)
);

$desc = \Osmium\Chrome\trim($desc);
if($desc !== '') {
	$desc = $p->fragment(\Osmium\Chrome\format_type_description($desc));
	$ultabs->appendCreate('li')->appendCreate('a', [ 'href' => '#sidesc-'.$suffix, 'Description' ]);
	$p->appendChild($p->element('section', [ 'class' => 'sidesc', 'id' => 'sidesc-'.$suffix, $desc ]));
}



/* —————————— Attributes —————————— */

$section = $p->element('section', [ 'class' => 'siattributes', 'id' => 'siattributes-'.$suffix ]);
$tbody = $section->appendCreate('table', [ 'class' => 'd' ])->appendCreate('tbody');

$aq = \Osmium\Db\query_params(
	'SELECT attributeid, attributename, displayname, value,
	unitid, udisplayname, categoryid, published
	FROM osmium.siattributes
	WHERE typeid = $1 AND displayname <> \'\' AND published = true
	ORDER BY categoryid ASC, attributeid ASC',
	array($typeid)
);

$previouscatid = null;
$attributenames = [];

while($a = \Osmium\Db\fetch_assoc($aq)) {
	$a['displayname'] = ucfirst($a['displayname']);
	$attributenames[$a['attributeid']] = $a['displayname'];

	$tr = $tbody->appendCreate('tr');

	if($previouscatid !== $a['categoryid']) {
		if($previouscatid !== null) {
			$tr->addClass('sep');
		}
		$previouscatid = $a['categoryid'];
	}

	if($getatt !== null) {
		$val = $getatt($a['attributeid']);
	} else {
		$val = $a['value'];
	}

	$tr->appendCreate('td', [[ 'strong', $a['displayname'] ]]);
	$tr->appendCreate('td', $p->formatNumberWithUnit($val, $a['unitid'], $a['udisplayname']));
}

if($previouscatid !== null) {
	$ultabs->appendCreate('li')->appendCreate('a', [ 'href' => '#siattributes-'.$suffix, 'Attributes' ]);
	$p->appendChild($section);
}



/* —————————— Affectors —————————— */

if(!isset($affectors)) {
	dogma_get_affectors($fit['__dogma_context'], $loc, $affectors);
}

if($affectors !== false) {
	$pertype = array();
	$peratt = array();
	$naffectors = 0;

	foreach($affectors as $affector) {
		if(!isset($attributenames[$affector['destid']])) {
			if(\Osmium\Fit\get_categoryid($affector['id']) == \Osmium\Fit\CATEGORY_Skill) {
				/* XXX: some are relevant (thermodynamics for example)
				 * but hand-filtering them is a pain */
				continue;
			}

		    $dest = \Osmium\Fit\get_attributedisplayname($affector['destid']);
		} else {
			$dest = $attributenames[$affector['destid']];
		}

		$source = \Osmium\Fit\get_typename($affector['id']);
		$fval = $affector['value'];

		switch($affector['operator']) {

		case '*':
			if(abs($affector['value'] - 1.0) < 1e-300) continue 2;
			$affector['operator'] = '×';
			break;

		case '-':
			$fval = -$fval;

		case '+':
			if(abs($affector['value']) < 1e-300) continue 2;

		}

		$fval = [ 'span', [ 'title' => sprintf('%.14f', $fval), (string)$p->formatSDigits($fval, 3) ] ];

		if($affector['flags'] > 0) {
			$flags = array();
			if($affector['flags'] & DOGMA_AFFECTOR_PENALIZED) {
				$flags[] = 'penalized';
			}
			if($affector['flags'] & DOGMA_AFFECTOR_SINGLETON) {
				$flags[] = 'singleton';
			}

			$flags = implode(', ', $flags);
			if($flags !== '') $flags = [ 'small', ' ('.$flags.')' ];
		} else {
			$flags = '';
		}

		$a = [ $affector, $dest, $source, $fval, $flags ];
		$pertype[$affector['id']][] = $a;
		$peratt[$affector['destid']][] = $a;
		++$naffectors;
	}

	uasort($pertype, function($a, $b) { return strcmp($a[0][2], $b[0][2]); });
	uasort($peratt, function($a, $b) { return strcmp($a[0][1], $b[0][1]); });

	$ulpertype = $p->element('ul');
	foreach($pertype as $a_typeid => &$a) {
		$li = $ulpertype->appendCreate('li', [
			[ 'o-eve-img', [ 'src' => '/Type/'.$a_typeid.'_64.png', 'alt' => '' ] ],
			' ',
			$a[0][2],
		]);

		$subul = $li->appendCreate('ul');
		usort($a, function($x, $y) { return strcmp($x[1], $y[1]); });
		foreach($a as $val) {
			list($aff, $dest, $source, $fval, $flags) = $val;
			$subul->appendCreate('li', [
				[ 'label', $dest ],
				' ',
				$aff['operator'],
				$fval,
				$flags,
			]);
		}
	}



	$precedencetext = $p->element('p')
		->append([[ 'em', 'The operations are ordered by precedence (lower operations get applied last).' ]]);

	$ulperatt = $p->element('ul');
	foreach($peratt as $attid => &$a) {
		$li = $ulperatt->appendCreate('li', [
			$a[0][1], ':'
		]);

		$subul = $li->appendCreate('ul');
		usort($a, function($x, $y) { return $x[0]['order'] - $y[0]['order']; });
		foreach($a as $val) {
			list($aff, $dest, $source, $fval, $flags) = $val;
			$subul->appendCreate('li', [
				[ 'label', [
					[ 'o-eve-img', [ 'src' => '/Type/'.$aff['id'].'_64.png', 'alt' => '' ] ],
					' ',
					$source,
				]],
				' ',
				$aff['operator'],
				$fval,
				$flags,
			]);
		}
	}



	if($naffectors > 0) {
		$ultabs->appendCreate('li')->appendCreate(
			'a', [ 'href' => '#siafftype-'.$suffix, 'Affectors by type ('.count($pertype).')' ]
		);
		$p->appendChild(
			$p->element('section', [ 'class' => 'siaff', 'id' => 'siafftype-'.$suffix, $ulpertype ])
		);

		$ultabs->appendCreate('li')->appendCreate(
			'a', [ 'href' => '#siaffatt-'.$suffix, 'By attribute ('.count($peratt).')' ]
		);
		$p->appendChild(
			$p->element('section', [ 'class' => 'siaff', 'id' => 'siaffatt-'.$suffix, $precedencetext, $ulperatt ])
		);
	}
}



/* —————————— Variations —————————— */

$variations = array();
$fvariations = array();
$variationsq = \Osmium\Db\query_params(
	'SELECT vartypeid AS typeid, varmgid AS metagroupid, varml AS metalevel
	FROM osmium.invtypevariations
	WHERE typeid = $1
	ORDER BY metalevel DESC, typeid ASC',
	array($typeid)
);
while($r = \Osmium\Db\fetch_assoc($variationsq)) {
	$variations[$r['metagroupid']][] = [ (int)$r['typeid'], (int)$r['metalevel'] ];
}
usort($variations, function($x, $y) {
	return $x[0][1] - $y[0][1];
});
foreach($variations as $a) {
	usort($a, function($x, $y) {
		return $x[1] - $y[1];
	});
	$fvariations = array_merge($fvariations, $a);
}

if(count($fvariations) > 1) {
	$ultabs->appendCreate('li')->appendCreate('a', [
		'href' => '#sivariations-'.$suffix, 'Variations ('.count($fvariations).')'
	]);
	$p->appendChild($p->element('section', [
		'class' => 'sivariations',
		'id' => 'sivariations-'.$suffix,
		[ 'ul', [ 'class' => 'sivariations' ] ],
	]));
} else {
	$fvariations = array();
}



$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = $_POST['relative'];
$p->finalize($ctx);

$xml = '';
foreach($p->childNodes as $e) {
	$xml .= $e->renderNode();
}

\Osmium\Chrome\return_json([
	'modal' => $xml,
	'variations' => $fvariations,
]);
