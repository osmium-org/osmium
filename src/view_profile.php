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

namespace Osmium\Page\NewFitting;

require __DIR__.'/../inc/root.php';

if(!isset($_GET['accountid'])) {
	\Osmium\fatal(404, 'No accountid given.');
}

$row = \Osmium\Db\fetch_assoc(\Osmium\Db\query_params('SELECT creationdate, lastlogindate, characterid, charactername, corporationid, corporationname, allianceid, alliancename, ismoderator, flagweight FROM osmium.accounts WHERE accountid = $1', array($_GET['accountid'])));

if($row === false) {
	\Osmium\fatal(404, 'Invalid accountid.');
}

$myprofile = \Osmium\State\is_logged_in() && \Osmium\State\get_state('a', array())['accountid'] == $_GET['accountid'];

\Osmium\Chrome\print_header($row['charactername']."'s profile", '..');

echo "<div id='vprofile'>\n";
echo "<header>\n";
$sep = "<tr class='sep'><td colspan='3'>&nbsp;</td></tr>\n";

$allianceid = (($row['allianceid'] == null) ? 1 : $row['allianceid']);
$alliancename = ($allianceid === 1) ? '(no alliance)' : $row['alliancename'];
$moderator = ($row['ismoderator'] === 't') ? '<small>Moderator '.\Osmium\Flag\MODERATOR_SYMBOL.'</small>' : '';
$isthisme = $myprofile ? '<small>(this is you!)</small>' : '';
echo "<h2>".$row['charactername']." $moderator$isthisme</h2>\n<p>\n";

echo "<img src='http://image.eveonline.com/Character/".$row['characterid']."_256.jpg' alt='portrait' /><br />";
echo "<img src='http://image.eveonline.com/Corporation/".$row['corporationid']."_128.png' alt='corporation logo' title='".htmlspecialchars($row['corporationname'], ENT_QUOTES)."' />";
echo "<img src='http://image.eveonline.com/Alliance/".$allianceid."_128.png' alt='alliance logo' title='".htmlspecialchars($alliancename, ENT_QUOTES)."' /></p>\n";
echo "<table>\n<tbody>\n";

echo "<tr>\n<th rowspan='2'>character</th>\n<td>corporation</td>\n<td>".htmlspecialchars($row['corporationname'])."</td>\n</tr>\n<tr>\n<td>alliance</td>\n<td>".htmlspecialchars($alliancename)."</td>\n</tr>\n";

echo $sep;

echo "<tr>\n<th rowspan='2'>visits</th>\n<td>member for</td>\n<td>".\Osmium\Chrome\format_long_duration(time() - $row['creationdate'])."</td>\n</tr>\n<tr>\n<td>last seen</td>\n<td>".(($s = \Osmium\Chrome\format_long_duration(time() - $row['lastlogindate'], null)) === null ? 'today' : $s.' ago')."</td>\n</tr>\n";

if($myprofile) {
	echo $sep;
	echo "<tr>\n<th>private</th>\n<td>flag weight</td>\n<td>".$row['flagweight']."</td>\n</tr>\n";
}

echo "</tbody>\n</table>\n</header>\n";

echo "<section id='ploadouts' class='psection'>\n";
echo "<h2>Loadouts recently submitted <small><a href=\"../search?q=".urlencode('@author "'.$row['charactername'].'"')."\">(browse all)</a></small></h2>\n";
\Osmium\Search\print_pretty_results("..", '@author "'.$row['charactername'].'"', 'ORDER BY creationdate DESC', false, 5, 'p', $row['charactername'].' does not have submitted any loadouts.');
echo "</section>\n";

if($myprofile) {
	$a = \Osmium\State\get_state('a');
	/* TODO: paginate this */

	echo "<section id='pfavorites' class='psection'>\n<h2>My favorite loadouts</h2>\n";
	$favorites = array();
	$favq = \Osmium\Db\query_params('SELECT loadoutid FROM osmium.accountfavorites WHERE accountid = $1 ORDER BY favoritedate DESC', array($a['accountid']));
	while($r = \Osmium\Db\fetch_row($favq)) {
		$favorites[] = $r[0];
	}
	\Osmium\Search\print_loadout_list($favorites, '..', 0, 'You have no favorited loadouts.');
	echo "</section>\n";

	echo "<section id='phidden' class='psection'>\n<h2>My hidden loadouts</h2>\n";
	$hidden = array();
	$hiddenq = \Osmium\Db\query_params('SELECT loadoutid FROM osmium.loadouts WHERE accountid = $1 AND visibility = $2 ORDER BY loadoutid DESC', array($a['accountid'], \Osmium\Fit\VISIBILITY_PRIVATE));
	while($r = \Osmium\Db\fetch_row($hiddenq)) {
		$hidden[] = $r[0];
	}
	\Osmium\Search\print_loadout_list($hidden, '..', 0, 'You have no hidden loadouts.');	
	echo "</section>\n";
}

echo "</div>\n";
\Osmium\Chrome\print_footer();