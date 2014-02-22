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

function make_typelist(\Osmium\DOM\Document $d, array $types) {
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
		if(($curcnt = count($entries[$current][1])) >= 5) {
			++$current;
			continue;
		}

		if(($curcnt + count($entries[$current+1][1])) >= 10) {
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

	$total = 0;
	foreach($entries as $l) $total += count($l[1]);

	$ul = $d->element('ul', [ 'class' => 'typelist' ]);

	if($c >= 3) {
		foreach($entries as $v) {
			list($l, $e) = $v;

			$li = $ul->appendCreate('li', [ 'class' => 'h' ]);
			$lihdr = $li->appendCreate('header', [ 'class' => 'letteranchor' ]);
			$liul = $li->appendCreate('ul');

			foreach(explode(' ', $l) as $letter) {
				$lihdr->appendCreate('a', [
					'href' => '#t'.$letter,
					'id' => 't'.$letter,
					$letter === '0' ? '0-9' : ($letter === '_' ? '~' : $letter)
				]);
			}

			foreach($e as $f) {
				$liul->append([ $f[1] ]);
			}
		}

		if($total >= 100) {
			$hdr = $d->element('header', [ 'class' => 'typelist' ]);
			$hdrul = $hdr->appendCreate('ul');

			foreach($entries as $v) {
				$letters = explode(' ', $v[0]);
				foreach($letters as $letter) {
					$hdrul->appendCreate('li', [[ 'a', [
						'href' => '#t'.$letter,
						$letter === '0' ? '0-9' : ($letter === '_' ? '~' : $letter)
					]]]);
				}
			}

			return [ $hdr, $ul ];
		}
	} else {
		foreach($entries as $v) {
			foreach($v[1] as $e) $ul->append([ $e[1] ]);
		}
	}

	return $ul;
}
