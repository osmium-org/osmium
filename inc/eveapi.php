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

namespace Osmium\EveApi;

const API_ROOT = 'https://api.eveonline.com';
const LOCK_FILE_TIMEOUT = 10; /* Number of seconds until a lock file is considered stale */

function fetch($name, array $params) {
	libxml_use_internal_errors(true);

	/* We sort the $params array to always have the same hash even when
	   the paramaters are not given in the same order. It makes
	   sense. */
	ksort($params);
	$hash = 'API_'.hash('sha512', serialize($name).serialize($params));
	$cacheDir = \Osmium\CACHE_DIRECTORY;
	$c_file = $cacheDir.'/'.$hash;
	$lock_file = $cacheDir.'/LOCK_'.$hash;

	if(file_exists($c_file) && filemtime($c_file) >= time()) {
		$xml = new \SimpleXMLElement(file_get_contents($c_file));
		return $xml;
	}

	if(file_exists($lock_file)) {
		if(filemtime($lock_file) < time() - LOCK_FILE_TIMEOUT) {
			/* Stale lock file, ignore */
		} else {
			/* Try to return outdated cache */
			if(file_exists($c_file)) {
				try {
					$xml = new \SimpleXMLElement(file_get_contents($c_file));
					return $xml;
				} catch(\Exception $e) {
					/* Invalid XML file cached, let's try again */
					@unlink($c_file);
					return fetch($name, $params);
				}
			} else {
				/* Wait for the lock file to disappear */
				do {
					clearstatcache();
					usleep(100000);
				} while(file_exists($lock_file) && filemtime($lock_file) >= time() - LOCK_FILE_TIMEOUT);
				/* Try again */
				return fetch($name, $params);
			}
		}
	}

	touch($lock_file);

	$c = curl_init(API_ROOT.$name);
	curl_setopt($c, CURLOPT_POST, true);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($params, '', '&'));
	$raw_xml = curl_exec($c);
	curl_close($c);
  
	$xml = false;
	$ex = null;
	try {
		$xml = new \SimpleXMLElement($raw_xml);
	} catch(\Exception $e) {
		$ex = $e;
	}

	if($xml === false || $raw_xml === false) {
		unlink($lock_file);
		return false;
	}

	$f = fopen($c_file, 'wb');
	fwrite($f, $raw_xml);
	fflush($f);
	fclose($f);
	unlink($lock_file);

	touch($c_file, $expires = strtotime((string)$xml->cachedUntil), $expires);
	return $xml;
}
