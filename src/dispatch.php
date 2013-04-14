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

namespace Osmium\Dispatch;

/* Dispatch rules, of the form <RegEx> => <Path>;
 *
 * <Path> will be included if <RegEx> matches the current request URI
 * (normalized according to the relative root). If <RegEx> has named
 * groups (like (?P<foo>bar)), they will be added to $_GET.
 *
 * If <Path> is an array, it must be of the form array(path, postget)
 * and postget will be merged to the $_GET array (postget has
 * precedence).
 *
 * For best performance, try to put the rules of the most frequent
 * pages first, and try to keep the general number of rules low (worst
 * case being 404 errors, where all the rules have to be checked).
 */
$osmium_dispatch_rules = array(
	'%^/$%D' => '/src/main.php',
	'%^/loadout/(?<loadoutid>[1-9][0-9]*)$%D' => '/src/view_fit.php',
	'%^/new(/(?<token>0|[1-9][0-9]*))?$%D' => '/src/new_loadout.php',
	'%^/browse/(?<type>best|new)$%D' => '/src/browse.php',
	'%^/search$%D' => '/src/search.php',
	'%^/profile/(?<accountid>[1-9][0-9]*)$%D' => '/src/view_profile.php',
	'%^/loadout/private/(?<loadoutid>[1-9][0-9]*)/(?<privatetoken>0|[1-9][0-9]*)$%D' => '/src/view_fit.php',
	'%^/export/(.+)-(?<type>clf|md|evexml|eft|dna)-(?<loadoutid>[1-9][0-9]*)-(?<revision>[1-9][0-9]*)\.(json|md|xml|txt)$%D' => '/src/export_fit.php',

    '%^/new/(?<import>dna)/(?<dna>[0-9:;]+)$%D' => '/src/new_loadout.php',
	'%^/old_new$%D' => '/src/new_fitting.php',

	'%^/atom/newfits\.xml$%D' => ['/src/atom/recentfits.php', ['type' => 'newfits']],
	'%^/atom/recentlyupdated\.xml$%D' => ['/src/atom/recentfits.php', ['type' => 'recentlyupdated']],

	'%^/api/$%D' => ['/src/mdstatic.php', ['relative' => '..', 'title' => 'Osmium API', 'f' => 'api.md']],
	'%^/api/json/query_loadouts\.json$%D' => '/src/api/json/query_loadouts.php',

	'%^/register$%D' => '/src/register.php',
	'%^/logout$%D' => '/src/logout.php',
	'%^/settings$%D' => '/src/settings.php',
	'%^/editskillset/(?<name>.+)$%D' => '/src/edit_skillset.php',
	'%^/reset_password$%D' => '/src/reset_password.php',
	'%^/notifications%D' => '/src/view_notifications.php',

	'%^/editcomment/(?<id>[1-9][0-9]*)$%D' => ['/src/edit_comment.php', ['type' => 'comment']],
	'%^/editcommentreply/(?<id>[1-9][0-9]*)$%D' => ['/src/edit_comment.php', ['type' => 'commentreply']],
	'%^/deletecomment/(?<id>[1-9][0-9]*)$%D' => ['/src/delete_comment.php', ['type' => 'comment']],
	'%^/deletecommentreply/(?<id>[1-9][0-9]*)$%D' => ['/src/delete_comment.php', ['type' => 'commentreply']],

	'%^/edit/(?<loadoutid>[1-9][0-9]*)$%D' => '/src/edit_fit.php',
	'%^/delete/(?<loadoutid>[1-9][0-9]*)$%D' => '/src/delete_fit.php',
	'%^/favorite/(?<loadoutid>[1-9][0-9]*)$%D' => '/src/toggle_favorite.php',
	'%^/import$%D' => '/src/import_loadouts.php',

	'%^/loadouthistory/(?<loadoutid>[1-9][0-9]*)$%D' => '/src/view_loadout_history.php',
	'%^/flagginghistory/(?<accountid>[1-9][0-9]*)$%D' => '/src/view_flagging_history.php',

	'%^/flag/(?<id>[1-9][0-9]*)$%D' => ['/src/cast_flag.php', ['type' => 'loadout']],
	'%^/flagcomment/(?<id>[1-9][0-9]*)$%D' => ['/src/cast_flag.php', ['type' => 'comment']],
	'%^/flagcommentreply/(?<id>[1-9][0-9]*)$%D' => ['/src/cast_flag.php', ['type' => 'commentreply']],

	'%^/moderation/$%D' => '/src/moderation/main.php',
	'%^/moderation/flags$%D' => '/src/moderation/view_flags.php',
);

require __DIR__.'/../inc/dispatchroot.php';

$relativeroot = \Osmium\get_ini_setting('relative_path');
$request = '/'.substr(explode('?', $_SERVER['REQUEST_URI'], 2)[0], strlen($relativeroot));

foreach($osmium_dispatch_rules as $rule => $target) {
	if(!preg_match($rule, $request, $matches)) continue;

	foreach($matches as $k => $v) {
		if(is_int($k)) continue;
		$_GET[$k] = $v;
	}

	if(is_array($target)) {
		$_GET = $target[1] + $_GET;
		$target = $target[0];
	}

	require \Osmium\ROOT.$target;
	die();
}

\Osmium\Fatal(404, "NOT FOUND (DISPATCHER)");
