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

namespace Osmium\EveApi;

/* Change this if you want to use an API proxy. BEWARE: whoever
 * controls the proxy will be able to impersonate any character. */
const API_ROOT = 'https://api.eveonline.com';
const API_TIMEOUT = 5000;
const API_CURL_TIMEOUT = 10000;

/**
 * Make an EVE API call. Handles caching.
 *
 * @param $name name of the call, with the leading /
 *
 * @param $params an array of POST parameters to send
 *
 * @returns a SimpleXMLElement if the call was successful (although
 * the XML contents itself may be an error), or false in case of
 * network error or unparseable XML.
 */
function fetch($name, array $params) {
	libxml_use_internal_errors(true);

	/* We sort the $params array to always have the same hash even when
	   the paramaters are not given in the same order. It makes
	   sense. */
	ksort($params);
	$key = serialize($name).serialize($params);

	$xmltext = \Osmium\State\get_cache($key, null, 'API_');
	if($xmltext !== null) {
		return new \SimpleXMLElement($xmltext);
	}

	/* Avoid concurrent accesses to the same API call */
	$sem = \Osmium\State\semaphore_acquire('API_'.$key);
	if($sem === false) return false;

	/* See if another process already cached the call while
	 * semaphore_acquire() blocked */
	$xmltext = \Osmium\State\get_cache($key, null, 'API_');
	if($xmltext !== null) {
		\Osmium\State\semaphore_release($sem);
		return new \SimpleXMLElement($xmltext);
	}

	$c = curl_init(API_ROOT.$name);
	curl_setopt($c, CURLOPT_POST, true);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($c, CURLOPT_CAINFO, \Osmium\ROOT.'/ext/ca/GeoTrustGlobalCA.pem');
	curl_setopt($c, CURLOPT_POSTFIELDS, $params);
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT_MS, API_TIMEOUT);
	curl_setopt($c, CURLOPT_TIMEOUT_MS, API_CURL_TIMEOUT);
	$raw_xml = curl_exec($c);
	curl_close($c);
  
	$xml = false;
	try {
		$xml = new \SimpleXMLElement($raw_xml);
	} catch(\Exception $e) {
		$xml = false;
	}

	if($xml === false || $raw_xml === false) {
		\Osmium\State\semaphore_release($sem);
		return false;
	}

	$expires = strtotime((string)$xml->cachedUntil);
	$ttl = $expires - time() + 1;
	\Osmium\State\put_cache($key, $raw_xml, $ttl, 'API_');
	\Osmium\State\semaphore_release($sem);
	return $xml;
}
