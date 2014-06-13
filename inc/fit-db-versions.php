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

namespace Osmium\Fit;

/** Get an array of EVE database versions, with associated
 * metadata. Obviously the JSON file needs to be updated every time
 * the EVE database has major updates. */
function get_eve_db_versions() {
	static $vers = null;

	if($vers === null) {
		$rawvers = json_decode(file_get_contents(\Osmium\ROOT.'/ext/eve-versions.json'), true);
		$vers = [];

		foreach($rawvers as $ver) $vers[$ver['build']] = $ver;
	}

	return $vers;
}

/** Get the latest EVE database version. */
function get_latest_eve_db_version() {
	static $v = null;

	if($v === null) {
		$vers = get_eve_db_versions();
		\reset($vers);
		$v = current($vers);
	}

	return $v;
}

/** @internal */
function get_closest_version_by_key($key, $value) {
	$versions = array_values(get_eve_db_versions());
	$i = 0;
	$c = count($versions) - 1;

	if($value >= $versions[0][$key]) return $versions[0];
	if($value <= $versions[$c][$key]) return $versions[$c];

	for($i = 0; $i < $c; ++$i) {
		$v2 = $versions[$i][$key];
		$v1 = $versions[$i + 1][$key];

		if($v1 < $value && $value <= $v2) {
			return $versions[$i];
		}
	}

	trigger_error(
		"get_closest_version_by_key(): $key with value $value did not find a suitable version",
		E_USER_WARNING
	);

	return $versions[0];
}

/** Get the closest EVE db version of a given build number. */
function get_closest_version_by_build($b) {
	$versions = get_eve_db_versions();
	$b = (int)$b;

	if(isset($versions[$b])) return $versions[$b];
	return get_closest_version_by_key('build', $b);
}

/** Get the closest EVE db version of a given timestamp. */
function get_closest_version_by_time($t) {
	return get_closest_version_by_key('reldate', $t);
}

/** Return a "sane" latest build for searching. */
function get_build_cutoff() {
	$vers = array_values(get_eve_db_versions());
	$nvers = count($vers);
	$build = $vers[0]['build'];
	$dogmavercutoff = $vers[0]['dogmaver'] - 2;

	for($i = 1; $i < $nvers; ++$i) {
		if($vers[$i]['dogmaver'] < $dogmavercutoff) return $build;
		$build = $vers[$i]['build'];
	}

	return $build;
}
