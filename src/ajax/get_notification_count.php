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

namespace Osmium\Ajax\GetNotificationCount;

require __DIR__.'/../../inc/root.php';

/* Set up a 1-minute cache, hopefully this will prevent overloading
 * the server if some guy has a bazillion tabs opened… */
header('Content-Type: text/plain');
header('Expires: '.date('r', time() + 60));
header('Pragma:');
header('Cache-Control: private');

echo \Osmium\Notification\get_new_notification_count();
