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

const MAX_FILESIZE_PRETTY = '1 MiB';
const MAX_FILESIZE = 1048576;

require __DIR__.'/../inc/root.php';

if(!\Osmium\State\is_logged_in()) {
	\Osmium\fatal(403, 'You must be logged in to import loadouts.');
}

/* ----------------------------------------------------- */

if(isset($_FILES['eve_xml_file']) || isset($_POST['eve_xml_url']) 
   || isset($_POST['eve_xml_source'])) {

	$xml_source = null;
	$from = null;
	
	if($_FILES['eve_xml_file']['error'] != UPLOAD_ERR_NO_FILE) {
		$error = $_FILES['eve_xml_file']['error'];
		if($error == UPLOAD_ERR_INI_SIZE 
		   || $error == UPLOAD_ERR_FORM_SIZE 
		   || $_FILES['eve_xml_file']['size'] > MAX_FILESIZE) {
		
			\Osmium\Forms\add_field_error('eve_xml_file', 'The file you tried to upload is too big.');
		} else if($error != UPLOAD_ERR_OK) {
			\Osmium\Forms\add_field_error('eve_xml_file', "Internal error ($error), please report.");
		} else {
			$xml_source = fetch_xml($_FILES['eve_xml_file']['tmp_name']);
			$from = 'eve_xml_file';
		}
	} else if(!empty($_POST['eve_xml_url'])) {
		$url = $_POST['eve_xml_url'];
		if(filter_var($url, FILTER_VALIDATE_URL) === false) {
			\Osmium\Forms\add_field_error('eve_xml_url', 'Enter a correct URI or leave this field empty.');
		} else {
			$d = parse_url($url);
			if($d['scheme'] != 'http' && $d['scheme'] != 'https') {
				\Osmium\Forms\add_field_error('eve_xml_url', 'Invalid scheme. Use either <code>http://</code> or <code>https://</code> URLs.');
			} else if(filter_var($d['host'], FILTER_VALIDATE_IP) !== false
			          && !filter_var($d['host'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
				\Osmium\Forms\add_field_error('eve_xml_url', 'I see what you did there. Please don\'t.');
			} else {
				$xml_source = fetch_xml($url);
				$from = 'eve_xml_url';
			}
		}
	} else if(!empty($_POST['eve_xml_source'])) {
		$xml_source = truncate_xml($_POST['eve_xml_source']);
		$from = 'eve_xml_source';
	}
	
	if($xml_source !== null) {
		try {
			libxml_use_internal_errors(true);
			$xml = new \SimpleXMLElement($xml_source);
			$fits = array();
			$errors = array();

			if(isset($xml->fitting)) {
				foreach($xml->fitting as $fitting) {
					if($fit = \Osmium\Fit\try_parse_fit_from_eve_xml($fitting, $errors)) {
						$fits[] = $fit;
					}
				}
			} else {
				if($fit = \Osmium\Fit\try_parse_fit_from_eve_xml($xml, $errors)) {
					$fits[] = $fit;
				}
			}

			\Osmium\State\put_state('import_fits', $fits);
			\Osmium\State\put_state('import_errors', $errors);
		} catch(\Exception $e) {
			$errors = implode("</code><br />\n<code>", array_map(function($error) {
						return 'Line '.$error->line.', column '.$error->column.': '.$error->message;
					}, libxml_get_errors()));
			libxml_clear_errors();
			\Osmium\Forms\add_field_error($from, 'Could not parse the input as XML.<br /><code>'.$errors.'</code>');
		}
	}
}

/* ----------------------------------------------------- */

if(isset($_POST['finalize_import'])) {
	$fits = \Osmium\State\get_state('import_fits', array());
	$a = \Osmium\State\get_state('a');
	$imported = array();
	foreach($fits as $i => $fit) {
		if(isset($_POST['selectfit'][$i]) && $_POST['selectfit'][$i] == 'on') {
			\Osmium\Fit\commit_loadout($fit, $a['accountid'], $a['accountid']);
			$imported[] = $fit;
		}
	}

	\Osmium\State\put_state('import_fits', array());
	\Osmium\State\put_state('import_errors', array());

	if(count($imported) > 0) {
		\Osmium\Chrome\print_header('Import complete', '.');
		
		echo "<h1>Import complete!</h1>\n<p>The following loadouts were imported:</p>\n<ol>\n";
		
		foreach($imported as $fit) {
			echo "<li><a href='./loadout/".$fit['metadata']['loadoutid']."'>";
			\Osmium\Chrome\print_loadout_title($fit['metadata']['name'], $fit['metadata']['view_permission'], $a);
			echo "</a></li>\n";
		}

		echo "</ol>\n";
		\Osmium\Chrome\print_footer();
		die();
	}
}

/* ----------------------------------------------------- */

$fits = \Osmium\State\get_state('import_fits', array());
$errors = \Osmium\State\get_state('import_errors', array());
if(count($errors) > 0 || count($fits) > 0) {
	\Osmium\Chrome\print_header('Import loadouts', '.');

	if(count($errors) > 0) {
		echo "<div id='import_errors'>\n<p>Some errors happened while parsing:</p>\n<ol>\n";
		foreach($errors as $e) {
			echo "<li><code>".htmlspecialchars($e)."</code></li>\n";
		}
		echo "</ol>\n</div>\n";
	}

	if(count($fits) > 0) {
		echo "<h1>Select loadouts to import</h1>\n";
		echo "<p>".count($fits)." loadout(s) were successfully parsed. Please select which ones you want to import in the list below.<br />\n<strong>By default, the imported loadouts will be marked as private, so that you can edit them one last time to fix any problems (if any).</strong></p>\n";
		
		\Osmium\Forms\print_form_begin();
		
		foreach($fits as $i => $fit) {
			\Osmium\Forms\print_checkbox("<span class='fitname'>".htmlspecialchars($fit['metadata']['name'])."</span> (".$fit['hull']['typename'].")", 'selectfit['.$i.']', null, true);
		}

		\Osmium\Forms\print_submit('Import selected loadout(s)', 'finalize_import');
		\Osmium\Forms\print_form_end();
	} else {
		\Osmium\State\put_state('import_fits', array());
		\Osmium\State\put_state('import_errors', array());
		echo "<h1>No loadouts to import! <a href='./import'>Try again.</a></h1>\n";
	}

	\Osmium\Chrome\print_footer();
	die();
}

/* ----------------------------------------------------- */

\Osmium\Chrome\print_header('Import loadouts', '.');
echo "<p class='notice_box'><em>Note: you can import XML files up to ".MAX_FILESIZE_PRETTY." in size (or paste at most ".MAX_FILESIZE_PRETTY." of text).</em></p>\n";
echo "<ol>\n";

/* ----------------------------------------------------- */
/*
  echo "<li>\n<h1>Import from a XML file (Osmium)</h1>\n";
  echo "<p>Use this for XML files your exported from Osmium (from this site, or from other instances of Osmium). You can upload a file from your computer, or from an URL, or paste the source directly.<br />You can use at most one of the three fields below:</p>\n";

  \Osmium\Forms\print_form_begin(null, '', 'multipart/form-data');
  \Osmium\Forms\print_file('Upload XML file', 'osmium_xml_file', MAX_FILESIZE);
  \Osmium\Forms\print_generic_field('Fetch XML from URL', 'url', 'osmium_xml_url');
  \Osmium\Forms\print_textarea('XML source', 'osmium_xml_source', null, 0, 'Paste the XML source here…');
  \Osmium\Forms\print_submit('Import');
  \Osmium\Forms\print_form_end();

  echo "</li>\n";
*/
/* ----------------------------------------------------- */

echo "<li>\n<h1>Import from a XML file (EVE, Pyfa, EFT)</h1>\n";
echo "<p>Use this for XML files your exported from EVE, Pyfa or EFT.<br />You can use at most one of the three fields below:</p>\n";

\Osmium\Forms\print_form_begin(null, '', 'multipart/form-data');
\Osmium\Forms\print_file('Upload XML file', 'eve_xml_file', MAX_FILESIZE);
\Osmium\Forms\print_generic_field('Fetch XML from URL', 'url', 'eve_xml_url');
\Osmium\Forms\print_textarea('XML source', 'eve_xml_source', null, 0, 'Paste the XML source here…');
\Osmium\Forms\print_submit('Import');
\Osmium\Forms\print_form_end();

echo "</li>\n";

/* ----------------------------------------------------- */
/*
  echo "<li>\n<h1>Import ship DNA (IGB)</h1>\n";
  echo "<p>Use this from the in-game browser. Drag and drop one or more fittings (from the fitting list or the fitting window) in the textarea below.</p>\n";

  \Osmium\Forms\print_form_begin();
  \Osmium\Forms\print_textarea('Fitting links', 'dna_links', null, 0, 'Drag and drop fitting links here…');
  \Osmium\Forms\print_submit('Import');
  \Osmium\Forms\print_form_end();

  echo "</li>\n";
*/
/* ----------------------------------------------------- */

echo "</ol>\n";
\Osmium\Chrome\print_footer();

/* ----------------------------------------------------- */

function fetch_xml($uri) {
	$f = fopen($uri, 'rb');
	$xml = fread($f, MAX_FILESIZE);
	fclose($f);
	return $xml;
}

function truncate_xml($text) {
	return substr($text, 0, MAX_FILESIZE);
}
