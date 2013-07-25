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

namespace Osmium\Page\Main;

require __DIR__.'/../inc/root.php';
require \Osmium\ROOT.'/inc/atom_common.php';

function get_popular_tags() {
	$ptags = \Osmium\State\get_cache_memory('main_popular_tags', null);
	if($ptags !== null) return $ptags;

	$sem = \Osmium\State\semaphore_acquire('main_popular_tags');
	$ptags = \Osmium\State\get_cache_memory('main_popular_tags', null);
	if($ptags !== null) {
		\Osmium\State\semaphore_release($sem);
		return $ptags;
	}

	$query = \Osmium\Db\query(
		"SELECT tagname, count
		FROM osmium.tagcount
		ORDER BY count DESC
		LIMIT 13"
	);

	$ptags = array();
	while($row = \Osmium\Db\fetch_row($query)) {
		list($name, $count) = $row;
		$ptags[$name] = $count;
	}

	\Osmium\State\put_cache_memory('main_popular_tags', $ptags, 3600);
	\Osmium\State\semaphore_release($sem);
	return $ptags;
}

function print_estimated_count($table, $single, $plural) {
	$n = (int)\Osmium\Db\fetch_row(\Osmium\Db\query_params(
		'SELECT reltuples FROM pg_class WHERE relname = $1',
		array($table)
	))[0];
	echo "<li><strong>"
		.\Osmium\Chrome\format_integer($n)
		."</strong> ".($n === 1 ? $single : $plural)."</li>\n";
}

function get_cache_memory_or_gen($key, $ttl, $genfunc, $prefix = 'Metrics_') {
	$val = \Osmium\State\get_cache_memory($key, null, $prefix);
	if($val === null) {
		$sem = \Osmium\State\semaphore_acquire($prefix.$key);
		if($sem === false) return false;

		$val = \Osmium\State\get_cache_memory($key, null, $prefix);
		if($val === null) {
			$val = $genfunc();
			\Osmium\State\put_cache_memory($key, $val, $ttl, $prefix);
		}
		\Osmium\State\semaphore_release($sem);
	}

	return $val;
}

function print_cached_count($getcount, $key, $ttl, $single, $plural) {
	$val = get_cache_memory_or_gen($key, $ttl, $getcount);
	if($val === false) return;

	echo "<li><strong>".\Osmium\Chrome\format_integer($val)
		."</strong> ".($val === 1 ? $single : $plural)."</li>\n";
}

function print_cached_string($getstring, $key, $ttl, $label) {
	$val = get_cache_memory_or_gen($key, $ttl, $getstring);
	if($val === false) return;

	echo "<li><strong>".$val."</strong> ".$label."</li>\n";
}

\Osmium\Chrome\print_header(
	'', '.', true,
	"<link href='./atom/newfits.xml' type='application/atom+xml' rel='alternate' title='New fits' />\n<link href='./atom/recentlyupdated.xml' type='application/atom+xml' rel='alternate' title='Recently updated' />\n");

echo "<h1 id='mainp'>".\Osmium\get_ini_setting('name')." — ".\Osmium\get_ini_setting('description')."</h1>\n";

echo "<div class='mainpcont quick' id='search_mini'>\n";
\Osmium\Search\print_search_form('./search');
echo "</div>\n";

$a = \Osmium\State\get_state('a', null);
$now = time();
$accountid = $a === null ? 0 : $a['accountid'];

echo "<div class='mainpcont'>\n<section class='metrics'>\n";
echo "<h2>Quick stats</h2>\n<ul>\n";

print_estimated_count('loadouts', 'loadout', 'total loadouts');
print_estimated_count('fittings', 'fitting', 'distinct fittings');
print_estimated_count('fittingmodules', 'fitted module', 'total fitted modules');

print_cached_count(function() {
	return \Osmium\State\count_cache_entries([ 'mmin' => 10 ], 'Loadout_New_')
		+ \Osmium\State\count_memory_cache_entries(array(), 'Loadout_View_');
}, "dogma_contexts", 30, "dogma context", "dogma contexts");

echo "</ul>\n<ul>\n";

print_estimated_count('accounts', 'account', 'accounts');

print_cached_count(function() {
	return (int)\Osmium\Db\fetch_row(\Osmium\Db\query(
		'SELECT count(accountid) FROM accounts WHERE apiverified = true'
	))[0];
}, "verified_accounts", 3600, "verified account", "verified accounts");

print_cached_count(function() {
	return (int)\Osmium\Db\fetch_row(\Osmium\Db\query(
		'SELECT sum(reputation) FROM accounts'
	))[0];
}, "reputation_total", 3600, "total reputation points", "total reputation points");

print_cached_count(function() {
	return max(1, \Osmium\State\count_memory_cache_entries(array(), 'Activity_'));
}, "active_users", 10, "active user", "active users");

echo "</ul>\n";
echo "</section>\n<section class='populartags'>\n";
echo "<h2>Popular tags</h2>\n<ul class='tags' id='populartags'>\n";

foreach(get_popular_tags() as $name => $count) {
	echo "<li><a href='./browse/best?q=".urlencode('@tags "'.$name.'"')
		."'>{$name}</a> ({$count})</li>\n";
}

echo "</ul>\n";
echo "<p class='b_more'><a href='./browse/best'>Browse all popular loadouts…</a></p>\n";
echo "</section>\n";





echo "<section class='newfits'>\n";
echo "<h2>New fits <small><a href='./atom/newfits.xml' type='application/atom+xml'><img src='./static-".\Osmium\STATICVER."/icons/feed.svg' alt='Atom feed' /></a></small></h2>\n";
\Osmium\Search\print_loadout_list(\Osmium\AtomCommon\get_new_fits($accountid),
                                  '.', 0, 'No loadouts yet! What are you waiting for?');
echo "<p class='b_more'><a href='./browse/new'>Browse more new loadouts…</a></p>\n";
echo "</section>\n";

echo "<section class='recentlyupdatedfits'>\n";
echo "<h2>Recently updated <small><a href='./atom/recentlyupdated.xml' type='application/atom+xml'><img src='./static-".\Osmium\STATICVER."/icons/feed.svg' alt='Atom feed' /></a></small></h2>\n";
\Osmium\Search\print_loadout_list(
	\Osmium\AtomCommon\get_recently_updated_fits($accountid),
	'.');
echo "</section>\n</div>\n";





\Osmium\Chrome\print_footer();
