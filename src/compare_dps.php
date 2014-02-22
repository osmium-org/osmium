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

\Osmium\Chrome\print_header('Compare loadout DPS', $relative, $relative === '..');
echo "<div id='comparedps'>\n";

echo "<h1>Compare loadout DPS</h1>\n";

echo "<h2>Graph output</h2>\n";
echo "<div id='graphcontext'></div>\n";
echo "<p id='graphpermalink'><small>Share this graph: <a></a></small></p>\n";





echo "<h2>Graph parameters</h2>\n";
\Osmium\Forms\print_form_begin(null, 'gparams');
\Osmium\Forms\print_submit('Redraw graph');

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
	$lis = array();
	foreach($graphable as $n => $d) {
		$f = "<input type='radio' name='{$dir}axis' id='{$dir}axistype_{$n}' value='{$n}' />";
		$f .= " <label for='{$dir}axistype_{$n}'>{$d[0]}</label><br />";
		$f .= "<div>\n";
		$f .= "from <input type='text' class='{$dir}min' placeholder='0' /> {$d[1]}<br />\n";
		$f .= "to <input type='text' class='{$dir}max' placeholder='auto' /> {$d[1]}\n";
		$f .= "</div>\n";

		$lis[] = "<li class='{$n}'>\n{$f}</li>\n";
	}

	\Osmium\Forms\print_generic_row(
		$dir.'axistype', strtoupper($dir).' axis',
		"<ul class='{$dir}'>\n".implode("", $lis)."</ul>\n",
		$dir.'axistype'
	);
}

$lis = array();
foreach($xgraphable as $n => $d) {
	$f = "<label for='{$n}_init'>{$d[0]}</label><br />";
	$f .= "<input type='text' class='init {$n}' placeholder='auto' /> {$d[1]}\n";

	$lis[] = "<li class='{$n}'>\n{$f}</li>\n";
}

\Osmium\Forms\print_generic_row(
	'initvalues', 'Other values',
	"<ul class='initvalues'>\n".implode("", $lis)."</ul>\n",
    'initvalues'
);

\Osmium\Forms\print_submit('Redraw graph');
\Osmium\Forms\print_form_end();





echo "<h2>Loadout sources</h2>\n";
\Osmium\Forms\print_form_begin(null, 'lsources');
\Osmium\Forms\print_submit('Update loadouts');


$opts = '';
$default = \Osmium\State\get_setting('default_skillset', 'All V');
foreach(\Osmium\Fit\get_available_skillset_names_for_account() as $ss) {
	$name = \Osmium\Chrome\escape($ss);
	if($ss === $default) {
		$opts .= "<option value='{$name}' selected='selected'>{$name}</option>\n";
	} else {
		$opts .= "<option value='{$name}'>{$name}</option>\n";
	}
}

for($i = 0; $i < MAX_LOADOUTS; ++$i) {
	\Osmium\Forms\print_generic_row(
		'source'.$i,
		"<label for='source[{$i}]'>Loadout #".($i + 1)."</label>",
		"<input type='text' class='source' name='source[$i]' id='source{$i}' placeholder='Loadout URI, DNA string or gzclf:// data' /><input type='text' class='legend' name='legend[$i]' id='legend{$i}' placeholder='Loadout title (optional)' /><select name='skillset[$i]'>{$opts}</select>"
	);
}

\Osmium\Forms\print_submit('Update loadouts');
\Osmium\Forms\print_form_end();





echo "</div>\n";
\Osmium\Chrome\print_js_snippet('graph_common');
\Osmium\Chrome\print_js_snippet('compare_dps');
\Osmium\Chrome\print_footer();
