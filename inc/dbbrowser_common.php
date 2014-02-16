<?php
/* Osmium
 * Copyright (C) 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\DBBrowser;

function print_typelist(array $types) {
	$fl = [];

	foreach($types as $t) {
		$typename = $t[0];

		if(preg_match('%(?<first>[a-zA-Z0-9])%', $typename, $m)) {
			$first = strtoupper($m['first']);
			$first = (strpos("0123456789", $first) === false) ? $first : '0';
		} else {
			$first = ' ';
		}

		$fl[$first][] = $t;
	}

	$entries = [];
	foreach($fl as $k => $v) $entries[] = [ $k, $v ];
	unset($fl);

	$c = count($entries);
	$current = 0;
	while(($current + 1) < $c) {
		if(($curcnt = count($entries[$current][1])) >= 10) {
			++$current;
			continue;
		}

		if(($curcnt + count($entries[$current+1][1])) >= 20) {
			$current += 2;
			continue;
		}

		$entries[$current] = [
			$entries[$current][0].' '.$entries[$current+1][0],
			array_merge($entries[$current][1], $entries[$current+1][1])
		];

		/* Remove entry from array and re-number keys */
		array_splice($entries, $current + 1, 1);
		--$c;
	}

	if($c >= 3) {
		echo "<header class='typelist'>\n";
		echo "<ul>\n";
		$lis = [];
		foreach($entries as $v) {
			$letters = explode(' ', $v[0]);
			foreach($letters as $letter) {
				echo "<li><a href='#t".$letter."'>"
					.($letter === '0' ? '0-9' : ($letter === '_' ? '~' : $letter))
					."</a></li>\n";
			}
		}
		echo "</ul>\n</header>\n";

		echo "<ul class='typelist'>\n";

		foreach($entries as $v) {
			list($l, $entries) = $v;

			$letters = explode(' ', $l);
			$ids = [];
			$links = [];

			foreach($letters as $letter) {
				$links[] = "<a href='#t".$letter."' id='t".$letter."'>"
					.($letter === '0' ? '0-9' : ($letter === '_' ? '~' : $letter))
					."</a>";
			}

			echo "<li class='letteranchor'>";
			echo implode(', ', $links);
			echo "</li>\n";
			foreach($entries as $e) echo $e[1];
		}

		echo "</ul>\n";
	} else {
		echo "<ul class='typelist'>\n";

		foreach($entries as $v) {
			foreach($v[1] as $e) echo $e[1];
		}

		echo "</ul>\n";
	}
}