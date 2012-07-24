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

namespace Osmium\Page\ViewNotifications;

require __DIR__.'/../inc/root.php';

\Osmium\Chrome\print_header('Notifications', '.');

echo "<div id='vnotifications'>\n";
echo "<h2>Notifications</h2>\n";

echo "<table class='d'>\n<tbody>\n";

\Osmium\Notification\get_notifications(function($row, $isnew) {
		echo "<tr".($isnew ? ' class="new"' : '').">\n";
		echo "<td>".\Osmium\Chrome\format_relative_date($row['creationdate'])."</td>\n";
		echo "<td>".\Osmium\Notification\get_notification_body($row)."</td>\n";
		echo "</tr>\n";
	});

echo "</tbody>\n</table>\n";

echo "</div>\n";
\Osmium\Chrome\print_footer();

\Osmium\Notification\reset_notification_count();