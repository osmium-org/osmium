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
$moderator = ($row['ismoderator'] === 't') ? '<small>Moderator '.\Osmium\Flag\MODERATOR_SYMBOL.'</small>' : '';
echo "<h2>".$row['charactername']." $moderator</h2>\n<p>\n";

echo "<img src='http://image.eveonline.com/Character/".$row['characterid']."_256.jpg' alt='portrait' /><br />";
echo "<img src='http://image.eveonline.com/Corporation/".$row['corporationid']."_128.png' alt='corporation logo' />";
echo "<img src='http://image.eveonline.com/Alliance/".$allianceid."_128.png' alt='alliance logo' /></p>\n";
echo "<table>\n<tbody>\n";

echo "<tr>\n<th rowspan='2'>character</th>\n<td>corporation</td>\n<td>".htmlspecialchars($row['corporationname'])."</td>\n</tr>\n<tr>\n<td>alliance</td>\n<td>".htmlspecialchars($row['alliancename'] ?: '(no alliance)')."</td>\n</tr>\n";

echo $sep;

echo "<tr>\n<th rowspan='2'>visits</th>\n<td>member for</td>\n<td>".\Osmium\Chrome\format_long_duration(time() - $row['creationdate'])."</td>\n</tr>\n<tr>\n<td>last seen</td>\n<td>".(($s = \Osmium\Chrome\format_long_duration(time() - $row['lastlogindate'], null)) === null ? 'today' : $s.' ago')."</td>\n</tr>\n";

if($myprofile) {
	echo $sep;
	echo "<tr>\n<th>private</th>\n<td>flag weight</td>\n<td>".$row['flagweight']."</td>\n</tr>\n";
}

echo "</tbody>\n</table>\n</header>\n";

echo "<section id=''";

echo "</div>\n";
\Osmium\Chrome\print_footer();