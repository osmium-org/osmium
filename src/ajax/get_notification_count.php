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

namespace Osmium\Ajax\GetNotificationCount;

require __DIR__.'/../../inc/root.php';

header('Content-Type: text/plain');

$a = \Osmium\State\get_state('a', null);
if($a === null) {
	echo "0";
	die();
}

$k = 'notification_count_'.$a['accountid'];
$count = \Osmium\State\get_cache_memory($k, -1);
if($count > -1) {
	echo $count;
	die();
}

$sem = \Osmium\State\semaphore_acquire($k);
if($sem === false) {
	echo "0";
	die();
}

$count = \Osmium\State\get_cache_memory($k, -1);
if($count > -1) {
	echo $count;
	die();
}

$count = \Osmium\Notification\get_new_notification_count();
\Osmium\State\put_cache_memory($k, (int)$count, 59);
\Osmium\State\semaphore_release($sem);
echo $count;
