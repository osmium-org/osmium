<?php
/* Osmium
 * Copyright (C) 2014, 2016 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\Page\DBBrowser\ViewEffect;

require __DIR__.'/../../inc/root.php';

$p = new \Osmium\DOM\Page();



$effectid = (int)$_GET['effectid'];
$cacheid = 'DBBrowser_Effect_'.$effectid;
$xml = \Osmium\State\get_cache($cacheid);
if($xml !== null) {
	$dbb = $p->fragment($xml);
	$p->content->append($dbb);
	goto RenderStage;
}



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

$dbb = $p->content->appendCreate('div', [ 'id' => 'dbb' ]);

$hdr = $dbb->appendCreate('header');
$hdr->appendCreate('h2', $e['effectname']);

$ul = $dbb->appendCreate('ul');
$ul->appendCreate('li', [
	'Effect category: ',
	[ 'span', [ 'class' => 'raw', \Osmium\Chrome\format_effect_category($e['effectcategory']) ] ],
]);

$ul = $dbb->appendCreate('ul');
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
		$ul->appendCreate('li', [
			$label, ': governed by ',
			[ 'strong', $p->formatNumberWithUnit($e[$k], 119, '') ],
		]);
	}
}

$ul = $dbb->appendCreate('ul');
$ebools = [
	'isoffensive' => 'Effect is % considered offensive',
	'isassistance' => 'Effect is % considered as remote assist',
	'iswarpsafe' => 'Effect can % be used in warp',
];
if(in_array((int)$e['effectcategory'], [ 1, 2, 3, 6, 7 ], true)) {
	foreach($ebools as $k => $label) {
		list($x, $y) = explode('%', $label);
		$ul->appendCreate('li', [
			$x,
			$e[$k] === 't' ? '' : [ 'strong', 'not' ],
			$y,
		]);
	}
}

function parse_libdogmactl_tree(array $lines) {
	static $regexp = '%^(?<id>-?[1-9][0-9]*), (?<operand>[A-Z]+)(\((?<value>.+)\))?$%';

	if(!preg_match($regexp, array_shift($lines), $m)) {
		return false;
	}

	unset($m[0], $m[1], $m[2], $m[3], $m[4]);

	$arg = [ [], [], [] ];
	$argi = 0;
	
	foreach($lines as $l) {
		$l = substr($l, 4);
		
		if(substr($l, 0, 6) === 'arg1: ') {
			$argi = 1;
			$l = substr($l, 6);
		} else if(substr($l, 0, 6) === 'arg2: ') {
			$argi = 2;
			$l = substr($l, 6);
		}

		$arg[$argi][] = $l;
	}

	if($arg[0] !== []) {
		trigger_error('dangling non-arg stuff in libdogmactl output', E_USER_WARNING);
	}
	if($arg[1] !== []) {
		$m['arg1'] = parse_libdogmactl_tree($arg[1]);
	}
	if($arg[2] !== []) {
		$m['arg2'] = parse_libdogmactl_tree($arg[2]);
	}

	return $m;
}

function combine_filter_tree(array $tree, array &$combined) {
	if($tree['operand'] === 'COMBINE') {
		combine_filter_tree($tree['arg1'], $combined);
		combine_filter_tree($tree['arg2'], $combined);
		return;
	}

	/* Filtering dummy root */
	if(substr($tree['operand'], 0, 3) === 'DEF') return;

	$combined[] = $tree;
}

function make_formatted_expr(\Osmium\DOM\Page $p, array $exp) {

	switch($exp['operand']) {

	case 'AGGM':
	case 'AGGM':
	case 'AGIM':
	case 'AGORSM':
	case 'AGRSM':
	case 'AIM':
	case 'ALGM':
	case 'ALM':
	case 'ALRSM':
	case 'AORSM':
	case 'RGGM':
	case 'RGGM':
	case 'RGIM':
	case 'RGORSM':
	case 'RGRSM':
	case 'RIM':
	case 'RLGM':
	case 'RLM':
	case 'RLRSM':
	case 'RORSM':
		$ret = $p->element('span');

		$assoctext = $exp['operand'][0] === 'R' ? 'undo ' : '';		
		$assoctext .= make_formatted_expr($p, $exp['arg1']['arg1']);
		$dest = strpos($assoctext, '{$dest}');
		$src = strpos($assoctext, '{$src}');
		if($dest < $src) {
			list($a, $b) = explode('{$dest}', $assoctext, 2);
			list($b, $c) = explode('{$src}', $b, 2);
			$ret->append($a);
			$ret->append(make_formatted_expr($p, $exp['arg1']['arg2']));
			$ret->append($b);
			$ret->append(make_formatted_expr($p, $exp['arg2']));
			$ret->append($c);
		} else {
			list($b, $c) = explode('{$dest}', $assoctext, 2);
			list($a, $b) = explode('{$src}', $b, 2);
			$ret->append($a);
			$ret->append(make_formatted_expr($p, $exp['arg2']));
			$ret->append($b);
			$ret->append(make_formatted_expr($p, $exp['arg1']['arg2']));
			$ret->append($c);
		}
		
		return $ret;

	case 'DEFATTRIBUTE':
		return $p->element('a.raw', [
			'o-rel-href' => '/db/attribute/'.$exp['value'],
			'title' => \Osmium\Fit\get_attributedisplayname($exp['value']),
			\Osmium\Fit\get_attributename($exp['value']),
		]);

	case 'DEFGROUP':
		return $p->element('a', [
			'o-rel-href' => '/db/group/'.$exp['value'],
			\Osmium\Fit\get_groupname($exp['value']).'s', /* XXX: pluralization may fuck up */
		]);

	case 'DEFTYPEID':
		return $p->element('a', [
			'o-rel-href' => '/db/type/'.$exp['value'],
			\Osmium\Fit\get_typename($exp['value']),
		]);

	case 'DEFINT':
		return $exp['value'];
		

	case 'DEFASSOCIATION':
		switch((int)(explode(':', $exp['value'], 2)[0])) {
		case 0:
			return 'first assign {$dest} to {$src}';

		case 1:
			return 'pre multiply {$dest} by {$src}';

		case 2:
			return 'pre divide {$dest} by {$src}';

		case 3:
			return 'increase {$dest} by {$src}';

		case 4:
			return 'decrease {$dest} by {$src}';

		case 5:
			return 'post multiply {$dest} by {$src}';

		case 6:
			return 'post divide {$dest} by {$src}';

		case 7:
			return 'post multiply {$dest} by {$src} perentage points';
			
		case 8:
			return 'last assign {$dest} to {$src}';
		}
		break;

		
	case 'DEFENVIDX':
		return 'current '.explode(':', $exp['value'], 2)[1];

	case 'ATT':
		$ret = $p->element('span');
		$ret->append(make_formatted_expr($p, $exp['arg2']));
		$ret->append(' of ');
		$ret->append(make_formatted_expr($p, $exp['arg1']));
		return $ret;

	case 'LG':
		$ret = $p->element('span');
		$ret->append(make_formatted_expr($p, $exp['arg2']));
		$ret->append(' in ');
		$ret->append(make_formatted_expr($p, $exp['arg1']));
		return $ret;

	case 'LS':
		$ret = $p->element('span', 'stuff in ');
		$ret->append(make_formatted_expr($p, $exp['arg1']));
		$ret->append(' requiring ');
		$ret->append(make_formatted_expr($p, $exp['arg2']));
		return $ret;

	case 'RSA':
		$ret = $p->element('span');
		$ret->append(make_formatted_expr($p, $exp['arg1']));
		$ret->append(' of stuff requiring ');
		$ret->append(make_formatted_expr($p, $exp['arg2']));
		return $ret;

	case 'GA': /* XXX: unused, obsolete? */
		$ret = $p->element('span');
		$ret->append(make_formatted_expr($p, $exp['arg2']));
		$ret->append(' of ');
		$ret->append(make_formatted_expr($p, $exp['arg1']));
		return $ret;

	case 'IA': /* XXX: unused, obsolete? */
		return make_formatted_expr($p, $exp['arg1']);

	default:
		$ret =  [
			$exp['operand']
		];

		if(isset($exp['arg1']) || isset($exp['arg2'])) {
			$ul = ($ret[] = $p->element('ul'));
			isset($exp['arg1']) && $ul->appendCreate('li', make_formatted_expr($p, $exp['arg1']));
			isset($exp['arg2']) && $ul->appendCreate('li', make_formatted_expr($p, $exp['arg2']));
		}
		
		return $ret;
	}
	
}

function make_expression_tree(\Osmium\DOM\Page $p, $id) {
	exec(\Osmium\get_ini_setting('libdogmactl').' dump-expression-tree '.escapeshellarg($id), $lines, $ret);
	if($ret !== 0) {
		return 'N/A';
	}

	$tree = parse_libdogmactl_tree($lines);
	
	$exprs = [];
	combine_filter_tree($tree, $exprs);

	if($exprs === []) return null;
	
	$ul = $p->element('ul');

	foreach($exprs as $exp) {
		$ul->appendCreate('li', make_formatted_expr($p, $exp));
	}

	return $ul;
}

exec(\Osmium\get_ini_setting('libdogmactl').' dump-effect '.escapeshellarg($e['effectid']), $lines, $ret);
if($ret === 0) {
	$pre = make_expression_tree($p, explode(': ', $lines[2])[1]);
	$post = make_expression_tree($p, explode(': ', $lines[3])[1]);

	if($pre !== null || $post !== null) {
		$ul = $dbb->appendCreate('ul');
		$pre !== null && $ul->appendCreate('li', [ 'Pre expression: ', $pre ]);
		$post !== null && $ul->appendCreate('li', [ 'Post expression: ', $post ]);
	}
}



$typesq = \Osmium\Db\query_params(
	'SELECT it.typeid, it.typename, it.published
	FROM eve.dgmtypeeffects dte
	JOIN eve.invtypes it ON it.typeid = dte.typeid
	WHERE dte.effectid = $1
	ORDER BY it.published DESC, it.typename ASC',
	array($e['effectid'])
);

$h3 = $p->element('h3', 'List of types which have this effect:');
$ul = $p->element('ul', [ 'class' => 'typelist' ]);
$ntypes = 0;

while($t = \Osmium\Db\fetch_assoc($typesq)) {
	++$ntypes;
	$li = $ul->appendCreate('li', [ [ 'a', [
		'o-rel-href' => '/db/type/'.$t['typeid'],
		$t['typename']
	]]]);

	if($t['published'] !== 't') $li->addClass('unpublished');
}

if($ntypes > 0) {
	$dbb->append([ $h3, $ul ]);
}



\Osmium\State\put_cache($cacheid, $dbb->renderNode());

RenderStage:
$p->title = ucfirst(\Osmium\Fit\get_effectname($effectid)).' / Effect '.$effectid;
$p->snippets[] = 'dbbrowser';
$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = '../..';
$p->render($ctx);
