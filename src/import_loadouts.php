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

const DEFAULT_VP = \Osmium\Fit\VIEW_EVERYONE;
const DEFAULT_EP = \Osmium\Fit\EDIT_OWNER_ONLY;
const DEFAULT_VIS = \Osmium\Fit\VISIBILITY_PRIVATE;
const MAX_FITS = 100;

if(!\Osmium\State\is_logged_in()) {
	$_POST['editimport'] = 'on';
}

$a = \Osmium\State\get_state('a');

\Osmium\Chrome\print_header('Import loadouts', '.');

$source = \Osmium\Import\get_source('source', 'url', 'file');
if($source !== false) {
	$format = $_POST['format'];
	$errors = array();
	$ids = array();
	$fits = false;

	$importfmts = \Osmium\Fit\get_import_formats();
	if(isset($importfmts[$format])) {
		$fits = $importfmts[$format][2]($source, $errors);
	}

	if($fits !== false) {
		$fits = array_slice($fits, 0, MAX_FITS);

		foreach($fits as &$fit) {
			post_import($fit, $ids, $a, $errors);
		}
	}

	if(count($errors) > 0) {
		echo "<h1>Import errors</h1>\n";
		echo "<div id='import_errors'>\n<ol>\n";
		foreach($errors as $e) {
			echo "<li><code>".\Osmium\Chrome\escape($e)."</code></li>\n";
		}
		echo "</ol>\n</div>\n";
	}

	echo "<h1>Import results</h1>\n";
	\Osmium\Search\print_loadout_list($ids, '.', 0, 'No loadouts were imported.');
}

echo "<h1>Import loadouts</h1>\n";

\Osmium\Forms\print_form_begin(null, '', 'multipart/form-data');

$formats = array();
foreach(\Osmium\Fit\get_import_formats() as $k => $f) {
	$formats[$k] = $f[0].' ('.\Osmium\Chrome\escape($f[1]).')';
}
\Osmium\Forms\print_select('Input format', 'format', $formats, null, null, \Osmium\Forms\FIELD_REMEMBER_VALUE);

\Osmium\Import\print_tri_choice('source', 'url', 'file');

\Osmium\Forms\print_checkbox('Immediately edit the first loadout (instead of saving them all)',
                             'editimport',
                             null,
                             \Osmium\State\is_logged_in() ? null : true,
                             \Osmium\State\is_logged_in() ? 0 : \Osmium\Forms\FIELD_DISABLED);

\Osmium\Forms\print_submit('Import');
\Osmium\Forms\print_form_end();

\Osmium\Chrome\print_js_snippet('import');
\Osmium\Chrome\print_footer();

function post_import(&$fit, &$ids, $a, &$errors) {
	if($fit == false) return;

	$fit['metadata']['view_permission'] = DEFAULT_VP;
	$fit['metadata']['edit_permission'] = DEFAULT_EP;
	$fit['metadata']['visibility'] = DEFAULT_VIS;

	if(isset($_POST['editimport']) && $_POST['editimport']) {
		/* Do not commit the loadout */
		$tok = \Osmium\State\get_unique_loadout_token();
		\Osmium\State\put_loadout($tok, $fit);
		header('Location: ./new/'.$tok);
		die();
	}

	\Osmium\Fit\sanitize($fit, $errors);
	if(empty($fit['metadata']['name'])) {
		$fit['metadata']['name'] = 'Nameless imported loadout';
		$errors[] = 'Using placeholder name for nameless loadout';
	}
	$ret = \Osmium\Fit\commit_loadout($fit, $a['accountid'], $a['accountid'], $err);
	if($ret === false) {
		$errors[] = 'Error while committing loadout, please report: '.$err;
		return;
	}

	$ids[] = $fit['metadata']['loadoutid'];
}
