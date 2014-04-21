<?php
/* Osmium
 * Copyright (C) 2013, 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\Page\Compare\DPSGraphs;

require __DIR__.'/../inc/root.php';

/* Should match the value in compare_dps_ia.php */
const MAX_LOADOUTS = 6;

$relative = '..';

$uriparts = explode('?', $_SERVER['REQUEST_URI'], 2)[0];
$uriparts = explode('/', $_SERVER['REQUEST_URI']);
while(array_pop($uriparts) !== 'dps') {
	$relative .= '/..';
}

$p = new \Osmium\DOM\Page();
$p->title = 'Compare loadout DPS';
$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = $relative;
$p->index = $relative === '..';

$div = $p->content->appendCreate('div#comparedps');

$div->appendCreate('h1', $p->title);
$div->appendCreate('div#graphcontext');
$div->appendCreate('p#graphpermalink')->appendCreate('small', 'Share this graph: ')->appendCreate('a');



$div->appendCreate('h2', 'Graph parameters');
$tbody = $div->appendCreate('o-form#gparams', [
	'action' => $_SERVER['REQUEST_URI'],
	'method' => 'post',
])->appendCreate('table')->appendCreate('tbody');

$tbody->appendCreate('tr', [
	[ 'th' ],
	[ 'td', [[ 'input', [ 'type' => 'submit', 'value' => 'Redraw graph' ] ]] ],
]);

$xgraphable = array(
	'td' => [ 'Target distance', 'km' ],
	'tv' => [ 'Target velocity', 'm/s' ],
	'tsr' => [ 'Target signature radius', 'm' ]
);

$ygraphable = array(
	'dps' => [ 'Damage per second', 'DPS' ],
	'tv' => [ 'Target velocity', 'm/s' ],
	'tsr' => [ 'Target signature radius', 'm' ]
);

foreach([ 'x' => $xgraphable, 'y' => $ygraphable ] as $dir => $graphable) {
	$tr = $tbody->appendCreate('tr#'.$dir.'axistype');
	$tr->appendCreate('th', strtoupper($dir).' axis');
	$ul = $tr->appendCreate('td')->appendCreate('ul.'.$dir);

	foreach($graphable as $n => $d) {
		$li = $ul->appendCreate('li.'.$n);

		$li->appendCreate('input', [
			'type' => 'radio',
			'name' => $dir.'axis',
			'id' => $dir.'axistype_'.$n,
			'value' => $n,
		]);

		$li->append(' ');
		$li->appendCreate('label', [
			'for' => $dir.'axistype_'.$n,
			$d[0],
		]);
		$li->appendCreate('br');

		$lidiv = $li->appendCreate('div');
		$lidiv->append('from ');
		$lidiv->appendCreate('input', [
			'type' => 'text',
			'class' => $dir.'min',
			'placeholder' => '0',
		]);
		$lidiv->append([ ' ', $d[1], [ 'br' ], 'to ' ]);
		$lidiv->appendCreate('input', [
			'type' => 'text',
			'class' => $dir.'max',
			'placeholder' => 'auto',
		]);
		$lidiv->append([ ' ', $d[1] ]);
	}
}

$tr = $tbody->appendCreate('tr#initvalues');
$tr->appendCreate('th', 'Other values');
$ul = $tr->appendCreate('td')->appendCreate('ul.initvalues');

foreach($xgraphable as $n => $d) {
	$li = $ul->appendCreate('li.'.$n);

	$li->appendCreate('label', [
		'for' => $n.'_init',
		$d[0],
	]);

	$li->appendCreate('br');
	$li->appendCreate('input', [
		'type' => 'text',
		'class' => 'init '.$n,
		'placeholder' => 'auto'
	]);

	$li->append([ ' ', $d[1] ]);

	$f = "<label for='{$n}_init'>{$d[0]}</label><br />";
	$f .= "<input type='text' class='init {$n}' placeholder='auto' /> {$d[1]}\n";

	$lis[] = "<li class='{$n}'>\n{$f}</li>\n";
}

$tbody->appendCreate('tr', [
	[ 'th' ],
	[ 'td', [[ 'input', [ 'type' => 'submit', 'value' => 'Redraw graph' ] ]] ],
]);



$div->appendCreate('h2', 'Loadout sources');
$tbody = $div->appendCreate('o-form#lsources', [
	'action' => $_SERVER['REQUEST_URI'],
	'method' => 'post',
])->appendCreate('table')->appendCreate('tbody');

$tbody->appendCreate('tr', [
	[ 'th' ],
	[ 'td', [[ 'input', [ 'type' => 'submit', 'value' => 'Update loadouts' ] ]] ],
]);

$select = $p->createElement('o-select');
$select->setAttribute('selected', \Osmium\State\get_setting('default_skillset', 'All V'));
foreach(\Osmium\Fit\get_available_skillset_names_for_account() as $ss) {
	$select->appendCreate('option', [ 'value' => $ss, $ss ]);
}

for($i = 0; $i < MAX_LOADOUTS; ++$i) {
	$tr = $tbody->appendCreate('tr');
	$tr->appendCreate('th')->appendCreate('label', [
		'for' => 'source'.$i,
		'Loadout #'.($i + 1)
	]);

	$td = $tr->appendCreate('td');
	$td->appendCreate('input', [
		'type' => 'text',
		'class' => 'source',
		'name' => 'source['.$i.']',
		'id' => 'source'.$i,
		'placeholder' => 'Loadout URI, DNA string or gzclf:// data',
	]);
	$td->appendCreate('input', [
		'type' => 'text',
		'class' => 'legend',
		'name' => 'legend['.$i.']',
		'id' => 'legend'.$i,
		'placeholder' => 'Loadout title (optional)',
	]);
	$tdselect = $select->cloneNode(true);
	$tdselect->setAttribute('name', 'skillset['.$i.']');
	$td->append($tdselect);
}

$tbody->appendCreate('tr', [
	[ 'th' ],
	[ 'td', [[ 'input', [ 'type' => 'submit', 'value' => 'Update loadouts' ] ]] ],
]);



$p->snippets[] = 'graph_common';
$p->snippets[] = 'compare_dps';
$p->render($ctx);
