<?php
/* Osmium
 * Copyright (C) 2012, 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\Page\EditCharacter;

require __DIR__.'/../inc/root.php';

\Osmium\State\assume_logged_in('..');

$a = \Osmium\State\get_state('a');
$name = $_GET['name'];

$p = new \Osmium\DOM\Page();
$ctx = new \Osmium\DOM\RenderContext();
$ctx->relative = '..';



$attribs = [ 'perception', 'willpower', 'intelligence', 'memory', 'charisma' ];
$char = \Osmium\Db\fetch_assoc(
	\Osmium\Db\query_params(
		'SELECT importedskillset, overriddenskillset,
		'.implode(', ', $attribs).',
		'.implode(', ', array_map(function($x) { return $x.'override'; }, $attribs)).'
		FROM osmium.accountcharacters
		WHERE accountid = $1 AND name = $2',
		array($a['accountid'], $name)
		));

if($char === false) {
	\Osmium\fatal(404);
}

$p->title = 'Edit character ('.$name.')';
$p->content->appendCreate('h1', $p->title);

$imported = $char['importedskillset'] !== null ? json_decode($char['importedskillset'], true) : array();
$overridden = $char['overriddenskillset'] !== null ? json_decode($char['overriddenskillset'], true) : array();



$p->content->appendCreate('h1', 'Attributes');
$form = $p->content->appendCreate('o-form', [
	'action' => $_SERVER['REQUEST_URI'],
	'method' => 'post',
]);
$tbody = $form->appendCreate('table', [ 'id' => 'e_attributes', 'class' => 'd' ])->appendCreate('tbody');

$tbody->appendCreate('tr')->append([
	[ 'th', 'Attribute' ],
	[ 'th', 'API value' ],
	[ 'th', 'Overridden value ('.\Osmium\Skills\MIN_ATTRIBUTE_VALUE.' ≤ x ≤ '.\Osmium\Skills\MAX_ATTRIBUTE_VALUE.')' ],
]);

foreach($attribs as $attr) {
	$ival = $char[$attr] === null ? $p->element('small', 'N/A ('.\Osmium\Skills\DEFAULT_ATTRIBUTE_VALUE.')')
		: $char[$attr];
	$oval = $char[$attr.'override'];

	if(isset($_POST['aoverride'][$attr]) && $_POST['aoverride'][$attr] !== '') {
		$oval = $_POST['aoverride'][$attr] = min(
			max(
				(int)$_POST['aoverride'][$attr],
				\Osmium\Skills\MIN_ATTRIBUTE_VALUE
			),
			\Osmium\Skills\MAX_ATTRIBUTE_VALUE
		);
	}

	$tr = $tbody->appendCreate('tr');
	$tr->appendCreate('td', ucfirst($attr));
	$tr->appendCreate('td')->append($oval !== null ? $p->element('del', $ival) : $ival);
	$td = $tr->appendCreate('td');
	$input = $td->appendCreate('o-input', [
		'name' => 'aoverride['.$attr.']',
		'placeholder' => '(no override)',
		'type' => 'text',
	]);
	if($oval !== null) $input->setAttribute('value', $oval);
	$td->append(' ');
	$td->appendCreate('input', [ 'type' => 'submit', 'value' => 'OK' ]);
}



$p->content->appendCreate('h1', 'Skills');
$form = $p->content->appendCreate('o-form', [
	'action' => $_SERVER['REQUEST_URI'],
	'method' => 'post',
]);

$tbody = $form->appendCreate('table', [ 'id' => 'e_skillset', 'class' => 'd' ])->appendCreate('tbody');
$lastgroup = null;

$q = \Osmium\Db\query(
	'SELECT typeid, typename, groupname
	FROM osmium.invskills
	ORDER BY groupname ASC, typename ASC'
);

/* There's a big number of rows (about 400), so it's more efficient to
 * make a deep copy of a template row then edit it in the loop */

$rowtemplate = $p->element('tr');
$rowtemplate->appendCreate('td')->appendCreate('a');
$rowtemplate->appendCreate('td');
$selecttd = $p->element('td');
$select = $selecttd->appendCreate('o-select');
$select->appendCreate('option', [ 'value' => '-2', 'No override' ]);
foreach([ 0, 1, 2, 3, 4, 5 ] as $k) {
	$select->appendCreate('option', [ 'value' => $k, $p->formatSkillLevel($k) ]);
}
$selecttd->append(' ');
$selecttd->appendCreate('input', [ 'type' => 'submit', 'value' => 'OK' ]);
$rowtemplate->append($selecttd);

$srowtemplate = [
	$p->element('tr')->append([[ 'th', [ 'class' => 'groupname', 'colspan' => '3' ] ]]),
	$p->element('tr')->append([
		[ 'th', 'Skill' ],
		[ 'th', 'API level' ],
		[ 'th', 'Overridden level' ],	
	]),
];

while($s = \Osmium\Db\fetch_assoc($q)) {
	if(isset($_POST['soverride'][$s['typeid']])) {
		$v = $_POST['soverride'][$s['typeid']];
		if($v == -2) {
			unset($overridden[$s['typeid']]);
		} else {
			$v = max(0, min(5, intval($v)));
			$overridden[$s['typeid']] = $v;
		}
	}

	if($lastgroup !== $s['groupname']) {
		$tr = $srowtemplate[0]->cloneNode(true);
		$tr->firstChild->append($s['groupname']);
		$tbody->append($tr);
		$tbody->append($srowtemplate[1]->cloneNode(true));
		$lastgroup = $s['groupname'];
	}

	$ilevel = isset($imported[$s['typeid']]) ? min(5, max(0, $imported[$s['typeid']])) : null;
	$olevel = isset($overridden[$s['typeid']]) ? min(5, max(0, $overridden[$s['typeid']])) : null;

	$tr = $rowtemplate->cloneNode(true);
	$anchor = $tr->firstChild->firstChild;
	$anchor->append($s['typename']);
	$anchor->setAttribute('o-rel-href', '/db/type/'.$s['typeid']);
	$tr->childNodes->item(1)->append(
		$olevel !== null
		? [[ 'del', $p->formatSkillLevel($ilevel) ]]
		: $p->formatSkillLevel($ilevel)
	);
	$select = $tr->childNodes->item(2)->firstChild;
	$select->setAttribute('name', 'soverride['.$s['typeid'].']');
	if($olevel !== null) $select->setAttribute('selected', $olevel);

	$tbody->append($tr);
}



$p->render($ctx);

if(isset($_POST['soverride'])) {
	\Osmium\Db\query_params(
		'UPDATE osmium.accountcharacters
		SET overriddenskillset = $1
		WHERE accountid = $2
		AND name = $3',
		array(
			json_encode($overridden),
			$a['accountid'],
			$name,
		));
}

if(isset($_POST['aoverride'])) {
	$vals = [];
	foreach($attribs as $attr) {
		$vals[] = isset($_POST['aoverride'][$attr]) && $_POST['aoverride'][$attr] !== ''
			? (int)$_POST['aoverride'][$attr] : null;
	}

	$i = 2;
	array_unshift($vals, $a['accountid'], $name);
	\Osmium\Db\query_params(
		'UPDATE osmium.accountcharacters SET
		'.implode(', ', array_map(function($a) use(&$i) { return $a.'override = $'.(++$i); }, $attribs)).'
		WHERE accountid = $1 AND NAME = $2',
		$vals
	);
}
