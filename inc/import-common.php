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

namespace Osmium\Import;

const MAX_FILESIZE = 1048576;

function make_tri_choice(\Osmium\DOM\Document $p, $textarea_name, $uri_name, $file_name) {
	$tr = $p->element('tr', [
		[ 'th', [[ 'label', 'Method' ]] ],
		[ 'td', [[ 'div', [ 'id' => 'methodselect', 'Use at most one of the three methods below:' ] ]] ],
	]);

	$di = $p->element('tr', [
		[ 'th', [[ 'label', [ 'for' => $textarea_name, 'Direct input' ] ]] ],
		[ 'td', [[ 'o-textarea', [ 'id' => $textarea_name, 'name' => $textarea_name ] ]] ],
	]);

	$url = $p->element('tr', [
		[ 'th', [[ 'label', [ 'for' => $uri_name, 'Fetch a URL' ] ]] ],
		[ 'td', [[ 'o-input', [ 'type' => 'url', 'id' => $uri_name, 'name' => $uri_name ] ]] ],
	]);

	$up = $p->element('tr', [
		[ 'th', [[ 'label', [ 'for' => $file_name, 'Upload file' ] ]] ],
		[ 'td', [
			[ 'o-input', [ 'type' => 'hidden', 'name' => 'MAX_FILE_SIZE', 'value' => (string)MAX_FILESIZE ] ],
			[ 'o-input', [ 'type' => 'file', 'id' => $file_name, 'name' => $file_name ] ],
		]],
	]);

	return [ $tr, $di, $url, $up ];
}

function get_source(\Osmium\DOM\RawPage $p, $textarea_name, $uri_name, $file_name) {
	if(!empty($_POST[$textarea_name])) {
		return truncate($_POST['source']);
	}

	if(!empty($_POST[$uri_name])) {
		$url = $_POST[$uri_name];

		if(filter_var($url, FILTER_VALIDATE_URL) === false) {
			$p->formerrors[$uri_name][] = 'Enter a correct URI or leave this field empty.';
			return false;
		}

		$d = parse_url($url);
		if($d['scheme'] != 'http' && $d['scheme'] != 'https') {
			$p->formerrors[$uri_name][] = 'Invalid scheme. Must be either http or https.';
			return false;
		}

		if(filter_var($d['host'], FILTER_VALIDATE_IP) !== false
		          && !filter_var($d['host'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
			$p->formerrors[$uri_name][] = 'Please enter a public IP address or a domain name.';
			return false;
		}

		return fetch($url);
	}

	if(!empty($_FILES[$file_name]) && $_FILES[$file_name]['error'] != UPLOAD_ERR_NO_FILE) {
		$error = $_FILES[$file_name]['error'];
		if($error == UPLOAD_ERR_INI_SIZE || $error == UPLOAD_ERR_FORM_SIZE 
		   || $_FILES[$file_name]['size'] > MAX_FILESIZE) {
			$p->formerrors[$file_name][] = 'The file you tried to upload is too big.';
			return false;
		}

		if($error != UPLOAD_ERR_OK) {
			$p->formerrors[$file_name][] = 'Internal error ('.$error.'), please report.';
			return false;
		}

		return fetch($_FILES[$file_name]['tmp_name']);
	}

	return false;
}

function fetch($uri) {
	$c = \Osmium\curl_init_branded($uri);
	$remaining = MAX_FILESIZE;

	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($c, CURLOPT_READFUNCTION, function($c, $stream, $maxlen) use(&$remaining) {
		if($remaining === 0) return '';
		$data = fread($stream, min($maxlen, $remaining));
		$remaining -= strlen($data);
		return $data;
	});

	return curl_exec($c);
}

function truncate($text) {
	return substr(\Osmium\Chrome\trim($text), 0, MAX_FILESIZE);
}
