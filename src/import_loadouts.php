<?php
/* Osmium
 * Copyright (C) 2012 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\Page\ImportLoadouts;

const MAX_FILESIZE = 1048576;
const MAX_FITS = 100;

require __DIR__.'/../inc/root.php';

const DEFAULT_VP = \Osmium\Fit\VIEW_EVERYONE;
const DEFAULT_EP = \Osmium\Fit\EDIT_OWNER_ONLY;
const DEFAULT_VIS = \Osmium\Fit\VISIBILITY_PRIVATE;

if(!\Osmium\State\is_logged_in()) {
	\Osmium\fatal(403, 'You must be logged in to import loadouts.');
}

$a = \Osmium\State\get_state('a');

/* ----------------------------------------------------- */

if(!empty($_POST['source'])) {
	$source = truncate($_POST['source']);
} else if(!empty($_POST['url'])) {
	$url = $_POST['url'];
	if(filter_var($url, FILTER_VALIDATE_URL) === false) {
		\Osmium\Forms\add_field_error('url', 'Enter a correct URI or leave this field empty.');
	} else {
		$d = parse_url($url);
		if($d['scheme'] != 'http' && $d['scheme'] != 'https') {
			\Osmium\Forms\add_field_error('url', 'Invalid scheme. Use either <code>http://</code> or <code>https://</code> URLs.');
		} else if(filter_var($d['host'], FILTER_VALIDATE_IP) !== false
		          && !filter_var($d['host'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
			\Osmium\Forms\add_field_error('url', 'Please enter a public IP address or a domain name.');
		} else {
			$source = fetch($url);
		}
	}
} else if(!empty($_FILES['file']) && $_FILES['file']['error'] != UPLOAD_ERR_NO_FILE) {
	$error = $_FILES['file']['error'];
	if($error == UPLOAD_ERR_INI_SIZE || $error == UPLOAD_ERR_FORM_SIZE 
	   || $_FILES['file']['size'] > MAX_FILESIZE) {
		\Osmium\Forms\add_field_error('file', 'The file you tried to upload is too big.');
	} else if($error != UPLOAD_ERR_OK) {
		\Osmium\Forms\add_field_error('file', "Internal error ($error), please report.");
	} else {
		$source = fetch($_FILES['file']['tmp_name']);
	}
}

\Osmium\Chrome\print_header('Import loadouts', '.');

if(!empty($source)) {
	$format = $_POST['format'];
	$errors = array();
	$ids = array();

	if($format == 'autodetect') {
		$json = json_decode($source, true);
		if(json_last_error() === JSON_ERROR_NONE && is_array($json)) {
			/* Input is JSON array/object */

			if(isset($json['clf-version']) && is_int($json['clf-version'])) {
				/* Input looks like CLF */
				$format = 'clf';
			} else if(count($json) > 0) {
				$first = reset($json);
				if(isset($first['clf-version']) && is_int($first['clf-version'])) {
					/* Input looks like a CLF array */
					$format = 'clfarray';
				}
			}
		} else {
			/* Input is not JSON array/object */

			if(($start = strpos($source, 'BEGIN gzCLF BLOCK')) !== false
			   && ($end = strpos($source, 'END gzCLF BLOCK')) !== false
			   && $start < $end) {
				/* Input looks like gzCLF */
				$format = 'gzclf';
			}
		}
	}

	if($format == 'autodetect') {
		/* Autodetect failed */
		$errors[] = 'Fatal: input format could not be auto-detected.';
	} else if($format == 'clf') {
		import_clf($source, $errors, $ids, $a);
	} else if($format == 'clfarray') {
		$srcarray = json_decode($source, true);
		if(json_last_error() !== JSON_ERROR_NONE) {
			$errors[] = 'Fatal: source is not valid JSON';
		} else if(!is_array($srcarray)) {
			$errors[] = 'Fatal: source is not a JSON array';
		} else {
			$imported = 0;
			foreach($srcarray as $s) {
				import_clf(json_encode($s), $errors, $ids, $a);
				++$imported;

				if($imported >= MAX_FITS) {
					$errors[] = 'Limit of '.MAX_FITS.' loadouts reached - stopping.';
					break;
				}
			}
		}
	} else if($format == 'gzclf') {
		$source = explode('BEGIN gzCLF BLOCK', $source);

		$imported = 0;
		foreach($source as $s) {
			$s = explode('END gzCLF BLOCK', $s, 2);
			if(count($s) !== 2) continue;

			$gz = @base64_decode(html_entity_decode($s[0], ENT_XML1));
			$jsonstring = @gzuncompress($gz, MAX_FILESIZE);

			if($jsonstring === false) {
				$errors[] = 'Fatal: could not uncompress gzCLF, output data too big or input is corrupted.';
			} else {
				import_clf($jsonstring, $errors, $ids, $a);
				++$imported;

				if($imported >= MAX_FITS) {
					$errors[] = 'Limit of '.MAX_FITS.' loadouts reached - stopping.';
					break;
				}
			}
		}
	}
	else {
		$errors[] = 'Fatal: unknown format "'.$format.'"';
	}

	if(count($errors) > 0) {
		echo "<h1>Import errors</h1>\n";
		echo "<div id='import_errors'>\n<ol>\n";
		foreach($errors as $e) {
			echo "<li><code>".htmlspecialchars($e)."</code></li>\n";
		}
		echo "</ol>\n</div>\n";
	}

	echo "<h1>Import results</h1>\n";
	\Osmium\Search\print_loadout_list($ids, '.', 0, 'No loadouts were imported.');
}

echo "<h1>Import loadouts</h1>\n";

\Osmium\Forms\print_form_begin(null, '', 'multipart/form-data');
\Osmium\Forms\print_select('Input format', 'format', array(
	                           'autodetect' => '-- Try to autodetect format --',
	                           'clf' => 'CLF (single)',
	                           'clfarray' => 'CLF (array)',
	                           'gzclf' => 'gzCLF (supports multiple blocks)',
	                           //'xml' => 'EVE XML',
	                           //'dna' => 'DNA',
	                           //'eft' => 'EFT',
	                           ), null, null, \Osmium\Forms\FIELD_REMEMBER_VALUE);

\Osmium\Forms\print_generic_row('', '<label>Method</label>', '<div id="methodselect"><noscript>Use at most one of the three methods below:</noscript></div>');

\Osmium\Forms\print_textarea('Direct input', 'source', null, 0);
\Osmium\Forms\print_generic_field('Fetch a URI', 'url', 'url');
\Osmium\Forms\print_file('Upload file', 'file', MAX_FILESIZE);
\Osmium\Forms\print_submit('Import');
\Osmium\Forms\print_form_end();

\Osmium\Chrome\print_js_snippet('import');
\Osmium\Chrome\print_footer();

/* ----------------------------------------------------- */

function fetch($uri) {
	$f = fopen($uri, 'rb');
	$xml = fread($f, MAX_FILESIZE);
	fclose($f);
	return $xml;
}

function truncate($text) {
	return substr($text, 0, MAX_FILESIZE);
}

function import_clf($jsonstring, &$errors, &$ids, $a) {
	$fit = \Osmium\Fit\try_parse_fit_from_common_loadout_format($jsonstring, $errors);
	if($fit !== false) {
		$fit['metadata']['view_permission'] = DEFAULT_VP;
		$fit['metadata']['edit_permission'] = DEFAULT_EP;
		$fit['metadata']['visibility'] = DEFAULT_VIS;

		\Osmium\Fit\commit_loadout($fit, $a['accountid'], $a['accountid']);

		$ids[] = $fit['metadata']['loadoutid'];
	}
}
