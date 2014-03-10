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

namespace Osmium\Page\Convert;

require __DIR__.'/../inc/root.php';
require __DIR__.'/../inc/import-common.php';

$p = new \Osmium\DOM\Page();
$ctx = new \Osmium\DOM\RenderContext();
$p->title = 'Convert lodaouts';
$ctx->relative = '.';



$source = \Osmium\Import\get_source($p, 'source', 'url', 'file');
if($source !== false) {
	$format = $_POST['inputformat'];
	$outfmt = $_POST['outputformat'];
	$errors = array();
	$ids = array();
	$fits = false;
	$exportfunc = false;

	$importfmts = \Osmium\Fit\get_import_formats();
	if(isset($importfmts[$format])) {
		$fits = $importfmts[$format][2]($source, $errors);
	}

	$exportfmts = \Osmium\Fit\get_export_formats();
	if(isset($exportfmts[$outfmt])) {
		$exportfunc = $exportfmts[$outfmt][2];
	}

	if(count($errors) > 0) {
		$p->content->appendCreate('h1', 'Conversion errors');
		$ol = $p->content->appendCreate('div', [ 'id' => 'import_errors' ])->appendCreate('ol');
		foreach($errors as $e) {
			$ol->appendCreate('li')->appendCreate('code', $e);
		}
	}
}



$p->content->appendCreate('h1', 'Convert loadouts');
$form = $p->content->appendCreate('o-form', [
	'method' => 'post',
	'o-rel-action' => '/convert#result',
]);

$tbody = $form->appendCreate('table')->appendCreate('tbody');

$select = $p->element('o-select', [ 'name' => 'inputformat', 'id' => 'inputformat' ]);
foreach(\Osmium\Fit\get_import_formats() as $k => $f) {
	$select->appendCreate('option', [
		'value' => $k,
		$f[0].' ('.$f[1].')',
	]);
}
$tbody->appendCreate('tr', [
	[ 'th', [[ 'label', [ 'for' => 'inputformat', 'Input format' ] ]] ],
	[ 'td', $select ],
]);

$tbody->append(\Osmium\Import\make_tri_choice($p, 'source', 'url', 'file'));

$select = $p->element('o-select', [ 'name' => 'outputformat', 'id' => 'outputformat' ]);
foreach(\Osmium\Fit\get_export_formats() as $k => $f) {
	$select->appendCreate('option', [
		'value' => $k,
		$f[0],
	]);
}
$tbody->appendCreate('tr', [
	[ 'th', [[ 'label', [ 'for' => 'outputformat', 'Output format' ] ]] ],
	[ 'td', $select ],
]);

$tbody->appendCreate('tr', [
	[ 'th' ], [ 'td', [
		[ 'o-input', [
			'type' => 'checkbox',
			'name' => 'minify',
			'id' => 'minify',
			'default' => 'checked',
		] ],
		[ 'label', [ 'for' => 'minify', 'Minify generated JSON' ] ],
	]],
]);

$tbody->appendCreate('tr', [
	[ 'th' ], [ 'td', [
		[ 'input', [ 'type' => 'submit', 'value' => 'Engage conversion' ] ]
	]],
]);



if($source !== false && $fits !== false && $exportfunc !== false) {
	$p->content->appendCreate('h1', [ 'id' => 'result', 'Conversion results' ]);

	$form = $p->content->appendCreate('form', [
		'o-relaction' => '/convert',
		'method' => 'post',
		'id' => 'exportresults',
	]);

	foreach($fits as &$fit) {
		$form->appendCreate('p')->appendCreate('textarea', [ 'readonly' => 'readonly' ])->append(
			$p->createCDATASection($exportfunc($fit, $_POST))
		);
	}
}


$p->snippets[] = 'import';
$p->render($ctx);
