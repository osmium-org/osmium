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

namespace Osmium\Page\Convert;

require __DIR__.'/../inc/root.php';
require __DIR__.'/../inc/import-common.php';

\Osmium\Chrome\print_header('Convert loadouts', '.');

$source = \Osmium\Import\get_source('source', 'url', 'file');
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
		echo "<h1>Conversion errors</h1>\n";
		echo "<div id='import_errors'>\n<ol>\n";
		foreach($errors as $e) {
			echo "<li><code>".htmlspecialchars($e)."</code></li>\n";
		}
		echo "</ol>\n</div>\n";
	}
}

echo "<h1>Convert loadouts</h1>\n";

\Osmium\Forms\print_form_begin('./convert#result', '', 'multipart/form-data');

$formats = array();
foreach(\Osmium\Fit\get_import_formats() as $k => $f) {
	$formats[$k] = $f[0].' ('.htmlspecialchars($f[1]).')';
}
\Osmium\Forms\print_select('Input format', 'inputformat', $formats, null, null, \Osmium\Forms\FIELD_REMEMBER_VALUE);

\Osmium\Import\print_tri_choice('source', 'url', 'file');

$formats = array();
foreach(\Osmium\Fit\get_export_formats() as $k => $f) {
	$formats[$k] = $f[0];
}
\Osmium\Forms\print_select('Output format', 'outputformat', $formats, null, null, \Osmium\Forms\FIELD_REMEMBER_VALUE);

\Osmium\Forms\print_checkbox('Minify generated JSON', 'minify', null, null,
                             \Osmium\Forms\FIELD_REMEMBER_VALUE);

\Osmium\Forms\print_submit('Engage conversion');
\Osmium\Forms\print_form_end();

if($source !== false && $fits !== false && $exportfunc !== false) {
	echo "<h1 id='result'>Conversion results</h1>\n";

	echo "<form action='./convert' method='POST' id='exportresults'>\n";
	foreach($fits as &$fit) {
		echo "<p>\n";
		echo '<textarea readonly="readonly">'.htmlspecialchars($exportfunc($fit, $_POST))."</textarea>\n";
		echo "</p>\n";
	}
	echo "</form>";
}

\Osmium\Chrome\print_js_snippet('import');
\Osmium\Chrome\print_footer();
