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

namespace Osmium\DOM;

trait Formatter {

	/* Format a (large) integer with thousands separator(s), without
	 * loss of precision. */
	static function formatExactInteger($n) {
		return number_format($n);
	}

	/* Format a number with a given number of digits. */
	static function formatNDigits($n, $digits = 3) {
		return rtrim(rtrim(number_format($n, $digits), '0'), '.');
	}

	/* Format a number with a given number of /significant/ digits. */
	static function formatSDigits($n, $digits = 3) {
		if(!$n) return $n;

		$normalized = $n / ($m = pow(10, floor(log10(abs($n)))));
		return self::formatNDigits($normalized, $digits - 1) * $m;
	}



	/* Format an amount of reputation points. */
	static function formatReputation($points) {
		return $this->element('span', [
			'class' => 'reputation', 'title' => 'reputation points',
			self::formatExactInteger($points),
		]);
	}



	/* Format an attribute with a given unit. */
	function formatNumberWithUnit($number, $unitid, $unitdisplayname) {
		$rawval = $number;

		switch((int)$unitid) {

		case 0: /* No unit */
			$unitdisplayname = '';
			break;

		case 1: /* Meters */
			if($number >= 10000) {
				$number /= 1000;
				$unitdisplayname = 'km';
			} else {
				$unitdisplayname = 'm';
			}
			break;

		case 101: /* Milliseconds */
			$number /= 1000;
			$unitdisplayname = 's';
			break;

		case 104: /* Multiplier */
			$unitdisplayname = 'x';
			break;

		case 108: /* Inverse absolute percent */
			$number = (1 - $number) * 100;

		case 105: /* Percentage */
		case 121: /* Real percentage */
			$unitdisplayname = '%';
			break;

		case 111: /* Inversed modifier percent */
			$number = 2 - $number;

		case 109: /* Modifier percent */
			$number -= 1;
			$number *= 100;

		case 124: /* Modifier relative percent */
			$unitdisplayname = '%';
			$rounded = ($number >= 0 ? '+' : '').$number;
			break;

		case 127: /* Absolute percent */
			$unitdisplayname = '%';
			$number *= 100;
			break;

		case 112: /* Rotation speed */
			$rounded = self::formatSDigits($number, 4);
			break;

		case 115: /* Group ID */
			$sampletypeid = \Osmium\Fit\get_group_any_typeid($number);
			$gn = \Osmium\Fit\get_groupname($number);

			if($gn !== false) {
				$a = $this->element('a', [ 'o-rel-href' => '/db/group/'.$number, $gn ]);
				if($sampletypeid !== false) {
					$img = $this->element('o-eve-img', [ 'src' => '/Type/'.$sampletypeid.'_64.png', 'alt' => '' ]);
					return [ $img, ' ', $a ];
				}
				return $a;
			}
			$unitdisplayname = 'groupid';
			break;

		case 116: /* Type ID */
			$typename = \Osmium\Fit\get_typename($number);
			if($typename !== false) {
				return [
					$this->element('o-eve-img', [ 'src' => '/Type/'.$number.'_64.png', 'alt' => '' ]),
					$this->element('a', [ 'o-rel-href' => '/db/type/'.$number, $typename ]),
				];
			}
			$unitdisplayname = 'typeid';
			break;

		case 117: /* Size class */
			if($number == 1) return 'Small';
			if($number == 2) return 'Medium';
			if($number == 3) return 'Large';
			if($number == 4) return 'XLarge';
			break;

		case 119: /* Attribute ID */
			$dn = \Osmium\Fit\get_attributedisplayname($number);
			if($dn !== false) {
				return $this->element('a', [
					'o-rel-href' => '/db/attribute/'.$number,
					ucfirst(\Osmium\Fit\get_attributedisplayname($number)),
				]);
			}
			$unitdisplayname = 'attributeid';
			break;

		case 137: /* Boolean */
			if($number == 0) return 'False';
			if($number == 1) return 'True';
			break;

		case 139: /* Bonus */
			$rounded = (($number >= 0) ? '+' : '').$number;
			break;

		case 140: /* Level */
			return 'Level '.$number;

		case 142: /* Sex */
			if($number == 1) return 'Male';
			if($number == 2) return 'Unisex';
			if($number == 3) return 'Female';
			break;

		}

		if(!isset($rounded)) {
			$rounded = self::formatNDigits($number, 3);
		}

		return $this->element('span', [
			'title' => sprintf('%.14f', $rawval),
			(string)$rounded, ' ', $unitdisplayname
		]);
	}



	/* Format <a href=showinfo> and <url=showinfo> links. */
	function formatShowinfoLinks($text) {
		$pieces = preg_split(
			'%(<a href=showinfo:([1-9][0-9]*)(?:// ?[1-9][0-9]*)?>|</a>|<url=showinfo:([1-9][0-9]*)(?:// ?[1-9][0-9]*)?>|</url>)%',
			$text,
			-1,
			PREG_SPLIT_DELIM_CAPTURE
		);

		$stack = [ [ '', [] ] ];
		$stackc = 0;

		while($p = array_shift($pieces)) {
			$s = substr($p, 0, 4);

			if($s === '<a h' || $s === '<url') {
				$typeid = array_shift($pieces);

				++$stackc;
				$stack[] = [ 'a', [ 'o-rel-href' => '/db/type/'.$typeid ] ];
				continue;
			}

			if($p === '</a>' || $p === '</url>') {
				if($stackc < 1) {
					throw new \Exception('unexpected closing tag');
				}

				--$stackc;
				$p = array_pop($stack);
			}

			$stack[$stackc][1][] = $p;
		}

		return $stack[0][1];
	}

	/* Format a type's traits. */
	function formatTypeTraits($shiptypeid) {
		$traitsq = \Osmium\Db\query_params(
			'SELECT COALESCE(sourcetypeid, sourceother) AS source,
			bonus, t.message, u.unitid, u.displayname
			FROM eve.fsdtypebonuses ftb
			JOIN eve.tramessages t ON t.nameid = ftb.nameid
			LEFT JOIN eve.dgmunits u ON u.unitid = ftb.unitid
			WHERE ftb.typeid = $1
			ORDER BY sourcetypeid ASC, t.nameid ASC',
			array($shiptypeid)
		);

		$div = $this->element('div', [ 'class' => 'traits' ]);

		$prevsrc = null;
		while($t = \Osmium\Db\fetch_assoc($traitsq)) {
			if($t['source'] !== $prevsrc) {
				$prevsrc = $t['source'];
				$src = (int)$t['source'];

				$h3 = $div->appendCreate('h3');
				if($src >= 0) {
					$h3->append([
						[ 'a', [ 'o-rel-href' => '/db/type/'.$src, \Osmium\Fit\get_typename($src) ] ],
						' bonuses (per skill level):',
					]);
				} else if($src === -1) {
					$h3->append('Role bonuses:');
				} else if($src === -2) {
					$h3->append('Miscellaneous bonuses:');
				}

				$ul = $div->appendCreate('ul');
			}

			$li = $ul->appendCreate('li');

			if($t['bonus'] !== null) {
				$li->appendCreate('span', [
					'class' => 'bvalue',
					$this->formatNumberWithUnit($t['bonus'], $t['unitid'], $t['displayname'])
				]);
			} else {
				$li->append('·');
			}

			$li->append($this->formatShowinfoLinks($t['message']));
		}

		return $prevsrc === null ? false : $div;
	}
}
