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

namespace Osmium\Cron;

/* Don't hog CPU time and IO to keep requests responsive. */
if(function_exists('proc_nice')) proc_nice(9);
@shell_exec('ionice -c 2 -n 6 -p '.getmypid()); /* Best effort, priority 6 (second lowest priority) */

chdir(__DIR__.'/../');
require __DIR__.'/../inc/root.php';

if(!function_exists('cli_set_process_title')) {
	/* Fallback for non PHP < 5.5 users */
	function cli_set_process_title() { }
}

function run($id, callable $cron, $staggerdelay = 1200, $semmaxtime = 1800) {
	global $argv;

	if(isset($argv[1]) && $argv[1] === 'now') {
		/* `$0 now` syntax used, do not wait */
		$staggerdelay = -1;
	}

	/* Sleep a random amount of time (between 0 and 20 minutes), to
	 * stagger server load. */
	if($staggerdelay > 0) {
		$sec = mt_rand(0, $staggerdelay);
		cli_set_process_title("osmium({$id}): sleeping for {$sec} seconds");
		sleep($sec);
	}

	if($semmaxtime > 0) {
		$start = time();
		cli_set_process_title("osmium({$id}): waiting for other instance to terminate");
		$sem = \Osmium\State\semaphore_acquire($id);
		if($sem === false) {
			fwrite(STDERR, "Could not acquire semaphore {$id}.\n");
			die(1);
		}
		if(time() - $start > $semmaxtime) {
			fwrite(
				STDERR,
				"Waited more than {$semmaxtime} seconds to acquire semaphore, aborting to avoid a backlog.\n"
			);
			die(2);
		}

		/* Last case scenario, if $cron throws a fatal error. It
		 * should be released automatically anyway, but it doesn't
		 * hurt to do it explicitely. */
		register_shutdown_function(function() use($sem) {
				@\Osmium\State\semaphore_release($sem);
			});

		cli_set_process_title("osmium({$id}): running task");
		$cron();

		\Osmium\State\semaphore_release($sem);
	}
}
