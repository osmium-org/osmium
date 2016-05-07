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

/* XXX: limit to at most 30 reqs/s to avoid bans */

namespace Osmium\EveApi;

/* Change this if you want to use an API proxy. BEWARE: whoever
 * controls the proxy will be able to impersonate any character. */
const API_ROOT = 'https://api.eveonline.com';

/* Set a default timeout of 5 seconds for API calls. Higher values can
 * mean longer login times if the API server is busy. */
const DEFAULT_API_TIMEOUT = 5;

/* Rate limit to no more than 20 seconds over any one second
 * period. */
const MAX_API_REQUESTS_IN_ONE_SECOND = 20;

/* Maximum number of IDs that can be passed to CharacterAffiliation. */
const CHARACTER_AFFILIATION_MAX_IDS = 100;



/* User error: for example invalid credentials, invalid characterID,
 * etc. Usually means that the request should not be repeated. */
const E_USER = 0;

/* Backend error: the API server is acting funky. */
const E_BACKEND = 1;

/* Network error: timeout, cURL error, etc. */
const E_NETWORK = 2;

/* Internal error: local to Osmium, like semaphores, cache, etc. */
const E_INTERNAL = 3;



/**
 * Make an EVE API call. Handles caching.
 *
 * @param $name name of the call, without root domain, but with the
 * leading /.
 *
 * @param $params an array of POST parameters to send.
 *
 * @param $timeout how many seconds to wait for a backend reply. Leave
 * null to use the default value.
 *
 * @param $errortype filled with one of the E_* constants if an error
 * happens.
 *
 * @param $errorstr filled with an error message if an error happens.
 *
 * @returns a SimpleXMLElement if the call was successful or false on
 * error, in which case the parameters $errortype and $errorstr will
 * be filled accordingly.
 */
function fetch($name, array $params, $timeout = null, &$errortype = null, &$errorstr = null) {
	/* Sort parameters predictably to cache calls independently of
	 * parameter order. */
	ksort($params);
	$key = serialize($name).serialize($params);

	$xmltext = \Osmium\State\get_cache($key, null, 'API_');
	if($xmltext !== null) {
		$xml = new \SimpleXMLElement($xmltext);
		goto HasXML;
	}

	/* Avoid concurrent accesses to the same API call */
	$sem = \Osmium\State\semaphore_acquire_nc('API_'.$key);
	if($sem === false) {
		$errortype = E_INTERNAL;
		$errorstr = 'Could not acquire semaphore, please report!';
		return false;
	}

	/* See if another process already cached the call while
	 * semaphore_acquire_nc() blocked */
	$xmltext = \Osmium\State\get_cache($key, null, 'API_');
	if($xmltext !== null) {
		\Osmium\State\semaphore_release_nc($sem);
		$xml = new \SimpleXMLElement($xmltext);
		goto HasXML;
	}

	/* Rate limit API calls to avoid getting banned */
	$lsem = \Osmium\State\semaphore_acquire('latest_eve_api_calls');
	if($lsem === false) {
		\Osmium\State\semaphore_release_nc($sem);
		$errortype = E_INTERNAL;
		$errorstr = 'Could not acquire rate limiting semaphore, please report!';
		return false;
	}

	$latest = \Osmium\State\get_cache('latest_eve_api_calls', []);

	$time = microtime(true);
	$cutoff = $time - 1;

	while($latest !== [] && $latest[0] < $cutoff) array_shift($latest);
	if(count($latest) > MAX_API_REQUESTS_IN_ONE_SECOND) {
		$waitms = 1000.0 * (1.0 - ($time - $latest[0])) + 5.0;
		usleep($waitms);
		/* The process will sleep WHILE keeping the semaphore, so
		 * other API requests should queue up nicely too. */
	}

	$latest[] = $time;
	\Osmium\State\put_cache('latest_eve_api_calls', $latest, 2);
	\Osmium\State\semaphore_release($lsem);



	if($timeout === null) $timeout = DEFAULT_API_TIMEOUT;

	$c = \Osmium\curl_init_branded(API_ROOT.$name);
	curl_setopt($c, CURLOPT_POST, true);
	curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($c, CURLOPT_POSTFIELDS, $params);
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($c, CURLOPT_TIMEOUT, $timeout);
	curl_setopt($c, CURLOPT_ENCODING, 'gzip');

	$raw_xml = curl_exec($c);

	if($errno = curl_errno($c)) {
		\Osmium\State\semaphore_release_nc($sem);
		$errortype = E_NETWORK;
		$errorstr = 'cURL error '.$errno;
		return false;
	}
  
	try {
		$xml = new \SimpleXMLElement($raw_xml);
	} catch(\Exception $e) {
		$xml = false;
	}

	if(($http_code = curl_getinfo($c, CURLINFO_HTTP_CODE)) !== 200) {
		\Osmium\State\semaphore_release_nc($sem);
		/* XXX: CCP is stupid, don't make the sane assumption that
		 * they understand what HTTP codes mean. */
		//$errortype = ($http_code >= 400 && $http_code < 500) ? E_USER : E_BACKEND;
		$errortype = E_BACKEND;

		if($xml !== false) {
			if(isset($xml->error)) {
				goto HasXML;
			} else {
				$errorstr = 'API returned non-error XML with HTTP code '.$http_code;
			}

			return false;
		}

		switch($http_code) {

		case 403:
			$errorstr = 'API returned a 403 Forbidden. The API credentials are probably incorrect.';
			break;

		default:
			$errorstr = 'API returned HTTP code '.$http_code;
			break;

		}

		return false;
	}

	curl_close($c);

	if($raw_xml === false) {
		\Osmium\State\semaphore_release_nc($sem);
		$errortype = E_BACKEND;
		$errorstr = 'Unnumbered cURL error, please report!';
		return false;
	}
  
	if($xml === false) {
		\Osmium\State\semaphore_release_nc($sem);
		$errortype = E_BACKEND;
		$errorstr = 'API returned unparseable XML: '.$e->getMessage();
		return false;
	}

	/* This is timezone safe and clock-skew safe. */
	$expires = strtotime((string)$xml->cachedUntil);
	$curtime = strtotime((string)$xml->currentTime);

	/* Cache for at least 1 minute, in case the cachedUntil values are
	 * erroneous */
	$ttl = max($expires - $curtime + 1, 60);

	\Osmium\State\put_cache($key, $raw_xml, $ttl, 'API_');
	\Osmium\State\semaphore_release_nc($sem);

HasXML:
	if(isset($xml->error) && !empty($xml->error)) {
		$code = (int)$xml->error['code'];

		/* http://wiki.eve-id.net/APIv2_Eve_ErrorList_XML */
		$errortype = ($code >= 100 && $code < 300) ? E_USER : E_BACKEND;

		$errorstr = 'API error '.$code.': '.(string)$xml->error
			.', retry after '.gmdate('H:i:s \U\T\C', strtotime((string)$xml->cachedUntil));
		return false;
	}

	return $xml;
}
