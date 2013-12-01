<?php
/* Osmium
 * Copyright (C) 2013 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\Page\ViewPrivileges;

require __DIR__.'/../inc/root.php';

\Osmium\Chrome\print_header('Privileges', '.');

$anonymous = !\Osmium\State\is_logged_in();
$myrep = \Osmium\Reputation\get_current_reputation();
$bs = \Osmium\get_ini_setting('bootstrap_mode');

echo "<div id='vprivileges'>\n";

echo "<h2>What is reputation?</h2>\n";

echo "<p>
Reputation points are a way to roughly measure how much the community trusts you.
The more points you have, the more privileges you unlock.
</p>
<p>
Most privileges do not apply to private or hidden loadouts.
</p>\n";

echo "<h3>How do I get reputation?</h3>\n";

echo "<p>
You get reputation points when…
</p>
<ul>
<li>Someone upvotes one of your public lodaouts;</li>
<li>Someone upvotes one of your comments on a public loadout.</li>
</ul>
<p>
If you submit quality content, reputation points will come naturally. Similarly, upvote content when you believe it deserves it.
</p>\n";

if($anonymous) {
	echo "<p>\nYou need to be logged in to gain reputation points and earn privileges.</p>\n";
} else {
	echo "<p>\nYou currently have <strong class='reptotal'>".\Osmium\Chrome\format_integer($myrep)."</strong> reputation points.</p>\n";
}

echo "<h2>Available privileges</h2>\n";

if($bs) {
	echo "<p class='notice_box'><strong>The site is currently in bootstrap mode.</strong><br />Some privilege requirements may be lowered in bootstrap mode. <br />When this happens, the real requirements will be shown in parentheses.</p>\n";
}

echo "<ol>";

foreach(\Osmium\Reputation\get_privileges() as $p => $d) {
	$name = $d['name'];
	$rep_needed = $d['req'][0];
	$rep_needed_bs = $d['req'][1];
	$desc = $d['desc'];

	$needed = $bs ? $rep_needed_bs : $rep_needed;
	$progress = round(min(1, $myrep / $needed) * 100, 2);

	$opacity = ($myrep >= $needed) ? 1.0 : 0.5;

	echo "<li id='p{$p}' class='".($myrep >= $needed ? 'haveit' : 'donthaveit')."' style='opacity: {$opacity};'>\n";
	echo "<h2><a href='#p{$p}'>".\Osmium\Chrome\escape($name)."</a> <span>";

	if($myrep >= $needed) {
		echo "got it!";
		if($myrep < $rep_needed) {	
			echo " <small>(".\Osmium\Chrome\format_integer($myrep)
				." / ".\Osmium\Chrome\format_integer($rep_needed).")</small>";
		}
	} else {
		echo \Osmium\Chrome\format_integer($myrep)
			." / ".\Osmium\Chrome\format_integer($bs ? $rep_needed_bs : $rep_needed);

		if($bs && $rep_needed > $rep_needed_bs) {
			echo " <small>(".\Osmium\Chrome\format_integer($myrep)
				." / ".\Osmium\Chrome\format_integer($rep_needed).")</small>";
		}
	}
	echo "</span></h2>\n";
	echo "<div class='progress'><div class='pinner' style='width: {$progress}%;'> </div></div>\n";
	echo "<div class='desc'>\n{$desc}</div>\n";
	echo "</li>\n";
}

echo "</ol>\n";

echo "</div>\n";
\Osmium\Chrome\print_footer();