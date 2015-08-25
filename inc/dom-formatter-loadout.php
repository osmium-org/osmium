<?php
/* Osmium
 * Copyright (C) 2012, 2013, 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
 * Copyright (C) 2013 Josiah Boning <jboning@gmail.com>
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

trait LoadoutFormatter {

	/* Make an icon with the ship render and its name as an
	 * overlay. */
	function makeLoadoutShipIcon($typeid, $typename = null) {
		if($typename === null) {
			$typename = \Osmium\Fit\get_typename($typeid);
		}

		return $this->element('div', [ 'class' => 'ship-icon' ])->append([
			[ 'o-eve-img', [ 'src' => '/Render/'.$typeid.'_128.png', 'title' => $typename, 'alt' => $typename ] ],
			[ 'span', [ 'class' => 'name', $typename ] ],
		]);
	}

	/* Make a <li> with main information about the loadout. */
	function formatLoadoutGridLayout($loadoutid) {
		$cached = \Osmium\State\get_cache_memory('Loadout_Grid_'.$loadoutid, null);
		if($cached !== null) return $this->fragment($cached);

		$sem = \Osmium\State\semaphore_acquire('Loadout_Grid_'.$loadoutid);
		$cached = \Osmium\State\get_cache_memory('Loadout_Grid_'.$loadoutid, null);
		if($cached !== null) {
			\Osmium\State\semaphore_release($sem);
			return $this->fragment($cached);
		}

		/* Yes, query in a loop (usually), bad. But it's better to do
		 * this and cache the whole result per-loadout. */
		$lrow = \Osmium\Db\fetch_assoc(\Osmium\Db\query_params(
			'SELECT loadoutid, privatetoken, latestrevision, viewpermission,
			visibility, passwordmode, hullid, typename, creationdate,
			updatedate, name, evebuildnumber, nickname, apiverified,
			charactername, characterid, corporationname,
			corporationid, alliancename, allianceid, accountid,
			taglist, reputation, votes, upvotes, downvotes, comments,
			dps, ehp, estimatedprice FROM osmium.loadoutssearchresults
			lsr WHERE lsr.loadoutid = $1',
			array($loadoutid)
		));

		if($lrow === false) {
			\Osmium\State\semaphore_release($sem);
			trigger_error('loadout '.$loadoutid.' not found in loadoutssearchresults', E_USER_NOTICE);
			return '';
		}

		$uri = '/'.\Osmium\Fit\get_fit_uri($lrow['loadoutid'], $lrow['visibility'], $lrow['privatetoken']);

		$li = $this->createElement('li');
		$li->appendCreate('a', [ 'o-rel-href' => $uri ])
			->append($this->makeLoadoutShipIcon($lrow['hullid'], $lrow['typename']));

		$li->appendCreate('div', [
			'title' => 'Damage per second of this loadout',
			'class' => 'absnum dps',
		])->appendCreate('span')->append([
			[ 'strong', $lrow['dps'] === null ? 'N/A' : (string)$this->formatKMB($lrow['dps'], 2) ],
			[ 'small', 'DPS' ],
		]);
		$li->appendCreate('div', [ 
			'title' => 'Effective hitpoints of this loadout',
			'class' => 'absnum ehp',
		])->appendCreate('span')->append([
			[ 'strong', $lrow['ehp'] === null ? 'N/A' : (string)$this->formatKMB($lrow['ehp'], 2, 'k') ],
			[ 'small', 'EHP' ],
		]);
		$li->appendCreate('div', [
			'title' => 'Estimated price of this loadout',
			'class' => 'absnum esp',
		])->appendCreate('span')->append([
			[ 'strong', $lrow['estimatedprice'] === null ? 'N/A' : (string)$this->formatKMB($lrow['estimatedprice'], 2) ],
			[ 'small', 'ISK' ],
		]);

		$li->appendCreate('a', [
			'class' => 'fitname',
			'o-rel-href' => $uri,
			$lrow['name'],
		]);

		$sideicons = $this->createElement('div');

		if($lrow['viewpermission'] > 0) {
			switch((int)$lrow['viewpermission']) {

			case \Osmium\Fit\VIEW_ALLIANCE_ONLY:
				$sp = [ 2, 13, 64, 64 ];
				$aname=  ($lrow['apiverified'] === 't' && $lrow['allianceid'] > 0)
					? $lrow['alliancename'] : 'My alliance';
				$alt = '('.$aname.' only)';
				break;

			case \Osmium\Fit\VIEW_CORPORATION_ONLY:
				$sp = [ 3, 13, 64, 64 ];
				$cname=  $lrow['apiverified'] === 't' ? $lrow['corporationname'] : 'My corporation';
				$alt = '('.$cname.' only)';
				break;

			case \Osmium\Fit\VIEW_OWNER_ONLY:
				$sp = [ 1, 25, 32, 32 ];
				$alt = '(only visible by me)';
				break;

			case \Osmium\Fit\VIEW_GOOD_STANDING:
				$sp = [ 5, 28, 32, 32 ];
				$alt = '(only visible by corporation, alliance, and contacts with good standing)';
				break;

			case \Osmium\Fit\VIEW_EXCELLENT_STANDING:
				$sp = [ 4, 28, 32, 32 ];
				$alt = '(only visible by corporation, alliance, and contacts with excellent standing)';
				break;

			}

			$sideicons->appendCreate('o-sprite', [
				'x' => $sp[0], 'y' => $sp[1],
				'gridwidth' => $sp[2], 'gridheight' => $sp[3],
				'width' => 16, 'height' => 16,
				'alt' => $alt,
				'title' => $alt,
			]);
		}

		if((int)$lrow['visibility'] === \Osmium\Fit\VISIBILITY_PRIVATE) {
			$sideicons->appendCreate('o-sprite', [
				'x' => 4, 'y' => 13,
				'gridwidth' => 64, 'gridheight' => 64,
				'width' => 16, 'height' => 16,
				'alt' => '(hidden loadout)',
				'title' => '(hidden loadout)',
			]);
		}

		if((int)$lrow['passwordmode'] === \Osmium\Fit\PASSWORD_EVERYONE) {
			$sideicons->appendCreate('o-sprite', [
				'x' => 0, 'y' => 25,
				'gridwidth' => 32, 'gridheight' => 32,
				'width' => 16, 'height' => 16,
				'alt' => '(requires password)',
				'title' => '(requires password)',
			]);
		}

		if($sideicons->childNodes->length > 0) {
			$sideicons->setAttribute('class', 'sideicons');
			$li->append($sideicons);
		}

		$li->appendCreate('small', [
			$this->makeAccountLink($lrow),
			' (',
			$this->formatReputation($lrow['reputation']),
			') — ',
			date('Y-m-d', $lrow['updatedate']),
		]);
		$li->appendCreate('br');
		$li->appendCreate('small', [
			self::formatExactInteger($lrow['votes']),
			' ',
			(int)$lrow['votes'] === 1 ? 'vote' : 'votes',
			' ',
			[ 'small', [
				'(+',
				self::formatExactInteger($lrow['upvotes']),
				'|-',
				self::formatExactInteger($lrow['downvotes']),
				')'
			]],
			' — ',
			[ 'a', [
				'o-rel-href' => $uri.'#comments',
				self::formatExactInteger($lrow['comments']),
				' ',
				(int)$lrow['comments'] === 1 ? 'comment' : 'comments',
			]]
		]);

		$taglist = trim($lrow['taglist']);
		if($taglist === '') {
			$li->appendCreate('em', [ 'class' => 'notags', '(no tags)' ]);
		} else {
			$tags = explode(' ', $taglist);
			$ul = $li->appendCreate('ul', [ 'class' => 'tags' ]);
			foreach($tags as $t) {
				$ul->appendCreate('li', [[ 'a', [
					'o-rel-href' => '/search'.self::formatQueryString([ 'q' => '@tags "'.$t.'"' ]),
					$t
				]]]);
			}
		}

		\Osmium\State\put_cache_memory('Loadout_Grid_'.$loadoutid, $li->renderNode(), 600);
		\Osmium\State\semaphore_release($sem);
		return $li;
	}

	/* Show loadouts in a grid layout. Returns an <ol> tag. */
	function makeLoadoutGridLayout(array $ids) {
		$ol = $this->createElement('ol');
		$ol->setAttribute('class', 'loadout_sr');

		foreach($ids as $id) $ol->append($this->formatLoadoutGridLayout($id));

		return $ol;
	}

	/* Make a small progress bar.
	 *
	 * @param $progress the progress, in percents (between 0 and 100+$maxoverflow)
	 * @param $fill show a background for the progress bar
	 * @param $ltr if true, the progress bar fills from left to right
	 * @param $maxoverflow truncate overflows over this limit
	 */
	function makeSmallProgressBar($progress, $fill = true, $ltr = true, $maxoverflow = 10) {
		$bar = $this->createElement('span');

		if($progress > 100) $p = 'p100';
		else if($progress >= 95) $p = 'p95';
		else if($progress >= 80) $p = 'p80';
		else $p = 'p00';

		$bar->setAttribute(
			'class',
			($ltr ? 'lbar' : 'bar')
			.' '.$p
		);

		$over = min($maxoverflow, max(0, $progress - 100));
		$progress = max(0, min(100, $progress));

		$bar->setAttribute('style', 'width: '.round($progress, 3).'%;');
		$ret = [ $bar ];

		if($over > 0) {
			$over = round($over, 3).'%';
			$ret[] = ($obar = $this->createElement('span'));

			$obar->setAttribute(
				'class',
				($ltr ? 'bar' : 'lbar')
				.' '.$p
				.' over'
			);

			$obar->setAttribute(
				'style',
				'width: '.$over.'; '.($ltr ? 'right' : 'left').': -'.$over.';'
			);
		}

		if($fill) {
			$ret[] = ($fbar = $this->createElement('span'));
			$fbar->setAttribute('class', 'bar fill');
		}

		return $ret;
	}

	/* Format a resonance, in %. Includes a progress bar. */
	function formatResonance($resonance) {
		if($resonance < 0) $resonance = 0;
		if($resonance > 1) $resonance = 1;

		$percent = (1 - $resonance) * 100;

		return $this->createElement('div')
			->append($this->formatNDigits($percent, 1).'%')
			->append($this->makeSmallProgressBar($percent, false, false, 0))
			;
	}

	/* Format a quantity with a known maximum. */
	function formatUsedResource(Element $parent, $rawused, $rawtotal,
	                            $opts = \Osmium\Chrome\F_USED_SHOW_ABSOLUTE) {
		$used = $this->formatKMB($rawused).' / '.$this->formatKMB($rawtotal);
		$diff = ($rawtotal >= $rawused)
			? $this->formatKMB($rawtotal - $rawused).' left'
			: $this->formatKMB($rawused - $rawtotal).' over';

		$first = true;

		if($opts & \Osmium\Chrome\F_USED_SHOW_ABSOLUTE) {
			if($first) $first = false;
			else $parent->appendCreate('br');

			$parent->appendCreate('span', [ 'title' => $diff, $used ]);
		}

		if($opts & \Osmium\Chrome\F_USED_SHOW_DIFFERENCE) {
			if($first) $first = false;
			else $parent->appendCreate('br');

			$parent->appendCreate('span', [ 'title' => $used, $diff ]);
		}

		if($opts & (\Osmium\Chrome\F_USED_SHOW_PERCENTAGE | \Osmium\Chrome\F_USED_SHOW_PROGRESS_BAR)) {
			$percent = ($rawtotal > 0)
				? (100 * $rawused / $rawtotal)
				: ($rawused > 0 ? 100 : 0);
		}

		if($opts & \Osmium\Chrome\F_USED_SHOW_PERCENTAGE) {
			if($first) $first = false;
			else $parent->appendCreate('br');

			$parent->append(
				(($rawtotal > 0)
				 ? $this->formatSDigits($percent, 4)
				 : ($rawused > 0 ? '∞' : '0'))
				.' %'
			);
		}

		if($opts & \Osmium\Chrome\F_USED_SHOW_PROGRESS_BAR) {
			$parent->append($this->makeSmallProgressBar($percent));
		}
	}

	/* Make a topologically sorted list of skill requirements. */
	function makeSortedSkillRequirements(Element $parent, $typeid, $level,
	                                     array &$skills, array $tree, array $invtree) {
		if(isset($tree[$typeid])) {
			foreach($tree[$typeid] as $stid => $sl) {
				if(!isset($skills[$stid])) continue;
				$ml = $skills[$stid];
				unset($skills[$stid]);

				$this->makeSortedSkillRequirements($parent, $stid, $ml, $skills, $tree, $invtree);
			}
		}

		$li = $parent->appendCreate('li', \Osmium\Fit\get_typename($typeid).' '.$this->formatSkillLevel($level));

		if(isset($invtree[$typeid])) {
			$reqs = [];

			foreach($invtree[$typeid] as $tid) {
				$reqs[] = \Osmium\Fit\get_typename($tid);
			}

			if($reqs !== []) {
				$li->appendCreate('div.reqs', 'Required by '.implode(', ', $reqs));
			}
		}

	}

	/* Make a section to be used in the formatted attributes section. */
	function makeFormattedAttributesSection($id, $title, $titledata, $titleclass, $contents) {
		$section = $this->createElement('section');
		if($id) $section->setAttribute('id', $id);

		$h = $section->appendCreate('h4', $title);
		if($titleclass) $h->setAttribute('class', $titleclass);
		if($titledata) $h->appendCreate('small', $titledata);

		$section->appendCreate('div')->append($contents);
		return $section;
	}

	/* Make the Engineering section of the formatted attributes section. */
	function makeFormattedAttributesEngineeringSection(&$fit, array $capacitor) {
		$contents = [];
		$over = false;



		$slotsLeft = \Osmium\Dogma\get_ship_attribute($fit, 'turretSlotsLeft');
		$slotsTotal = \Osmium\Dogma\get_ship_attribute($fit, 'turretSlots');
		$overturrets = $slotsLeft < 0;

		$contents[] = ($p = $this->createElement('p'));
		if($overturrets) {
			$over = true;
			$p->setAttribute('class', 'overflow');
		}

		$p->appendCreate('o-sprite', [
			'alt' => 'Turret hardpoints',
			'title' => 'Turret hardpoints',
			'x' => 0, 'y' => 0,
			'width' => 32, 'height' => 32,
			'gridwidth' => 64, 'gridheight' => 64,
		]);

		$this->formatUsedResource(
			$p->appendCreate('span#turrethardpoints'),
			$slotsTotal - $slotsLeft, $slotsTotal,
			\Osmium\Chrome\F_USED_SHOW_ABSOLUTE
		);



		$slotsLeft = \Osmium\Dogma\get_ship_attribute($fit, 'launcherSlotsLeft');
		$slotsTotal = \Osmium\Dogma\get_ship_attribute($fit, 'launcherSlots');
		$overlaunchers = $slotsLeft < 0;

		$contents[] = ($p = $this->createElement('p'));
		if($overlaunchers) {
			$over = true;
			$p->setAttribute('class', 'overflow');
		}

		$p->appendCreate('o-sprite', [
			'alt' => 'Launcher hardpoints',
			'title' => 'Launcher hardpoints',
			'x' => 0, 'y' => 1,
			'width' => 32, 'height' => 32,
			'gridwidth' => 64, 'gridheight' => 64,
		]);

		$this->formatUsedResource(
			$p->appendCreate('span#launcherhardpoints'),
			$slotsTotal - $slotsLeft, $slotsTotal,
			\Osmium\Chrome\F_USED_SHOW_ABSOLUTE
		);



		$contents[] = ($p = $this->element('p#capacitor'));
		$p->setAttribute('data-capacity', $capacitor['capacity']);
		$p->setAttribute(
			'data-usage',
			$capacitor['stable'] ? ($capacitor['capacity'] * $capacitor['stable_fraction']) : 0
		);
		$p->appendCreate('o-sprite', [
			'alt' => 'Capacitor',
			'title' => 'Capacitor',
			'x' => 2, 'y' => 0,
			'width' => 32, 'height' => 32,
			'gridwidth' => 64, 'gridheight' => 64,
		]);
		$sp = $p->appendCreate('span');
		$ssp = $sp->appendCreate('span');
		if($capacitor['stable']) {
			$ssp->setAttribute('title', $caplabel = 'Stability percentage');
			$ssp->append($captext = $this->formatSDigits($capacitor['stable_fraction'] * 100, 3).'%');
		} else {
			$ssp->setAttribute('title', $caplabel = 'Estimated depletion time');
			$ssp->append($captext = $this->formatDuration($capacitor['depletion_time'] / 1000, true, 2));
		}
		$rate = $this->formatSDigits(-$capacitor['delta'] * 1000, 3).' GJ/s';
		if($capacitor['delta'] < 0) $rate = '+'.$rate;
		$sp->appendCreate('br');
		$sp->appendCreate('span', [
			'title' => 'Capacitor delta at peak recharge rate',
			$rate,
		]);



		foreach([
			[ 'cpuLoad', 'cpuOutput', 'cpu', 'CPU', 1, 0 ],
			[ 'powerLoad', 'powerOutput', 'power', 'Powergrid', 1, 1 ],
			[ 'upgradeLoad', 'upgradeCapacity', 'upgradecapacity', 'Calibration (upgrade capacity)', 1, 2 ],
		] as $rtype) {
			$used = \Osmium\Dogma\get_ship_attribute($fit, $rtype[0]);
			$total = \Osmium\Dogma\get_ship_attribute($fit, $rtype[1]);

			$contents[] = ($p = $this->createElement('p'));
			if($used > $total) {
				$over = true;
				$p->setAttribute('class', 'overflow');
			}

			$p->appendCreate('o-sprite', [
				'alt' => $rtype[3],
				'title' => $rtype[3],
				'x' => $rtype[4], 'y' => $rtype[5],
				'width' => 32, 'height' => 32,
				'gridwidth' => 64, 'gridheight' => 64,
			]);

			$this->formatUsedResource(
				$p->appendCreate('span#'.$rtype[2]),
				$used, $total,
				\Osmium\Chrome\F_USED_SHOW_DIFFERENCE
				| \Osmium\Chrome\F_USED_SHOW_PERCENTAGE
				| \Osmium\Chrome\F_USED_SHOW_PROGRESS_BAR
			);
		}



		if($contents === []) return '';
		return $this->makeFormattedAttributesSection(
			'engineering',
			'Engineering',
			$this->element('span', [ 'title' => $caplabel, $captext ]),
			$over ? 'overflow' : false,
			$contents
		);
	}

	/* Make the Offense section used in fattribs. */
	function makeFormattedAttributesOffenseSection(&$fit, array $ia, $reload) {
		$contents = [];
		$totaldps = 0;

		$sources = [
			[ 'turret', \Osmium\Fit\get_damage_from_turrets($fit, $ia, $reload), 'Turret', 0, 0, 64 ],
			[ 'missile', \Osmium\Fit\get_damage_from_missiles($fit, $ia, $reload), 'Missile', 0, 1, 64 ],
			[ 'smartbomb', \Osmium\Fit\get_damage_from_smartbombs($fit, $ia, $reload), 'Smartbomb', 0, 3, 64 ],
			[ 'drone', \Osmium\Fit\get_damage_from_drones($fit, $ia, $reload), 'Drone', 0, 2, 64 ],
		];

		/* Sort by DPS, biggest sources first */
		usort($sources, function($a, $b) { return $a[1][0] < $b[1][0] ? 1 : -1; });
		
		foreach($sources as $dtype) {
			if($dtype[1][1] <= 0) continue;
			$totaldps += $dtype[1][0];

			$contents[] = ($p = $this->createElement('p'));
			$p->appendCreate('o-sprite', [
				'alt' => $dtype[2],
				'title' => $dtype[2],
				'x' => $dtype[3], 'y' => $dtype[4],
				'width' => 32, 'height' => 32,
				'gridwidth' => $dtype[5], 'gridheight' => $dtype[5],
			]);
			$span = $p->appendCreate('span');
			$span->appendCreate('span#'.$dtype[0].'_dps', [
				'title' => $dtype[2].' DPS',
				$this->formatNDigits($dtype[1][0], 1),
			]);
			$span->appendCreate('br');
			$span->appendCreate('span#'.$dtype[0].'_alpha', [
				'title' => $dtype[2].' volley (alpha)',
				$this->formatNDigits($dtype[1][1], 1),
			]);
		}

		if($contents === []) return '';
		return $this->makeFormattedAttributesSection(
			'offense',
			'Offense',
			$this->element('span', [
				'title' => 'Total damage per second',
				$this->formatExactInteger($totaldps).' dps',
			]),
			false,
			$contents
		);
	}

	/* Make the Defense section used in fattribs. */
	function makeFormattedAttributesDefenseSection(&$fit, array $ehp, array $cap, $reload) {
		$contents = [];
		$subtitles = [];

		$layertrtpl = $this->createElement('tr');
		$layertrtpl->appendCreate('th');
		$layertrtpl->appendCreate('td.capacity');
		$layertrtpl->appendCreate('td.emresist');
		$layertrtpl->appendCreate('td.thermalresist');
		$layertrtpl->appendCreate('td.kineticresist');
		$layertrtpl->appendCreate('td.explosiveresist');

		$contents[] = ($table = $this->element('table#resists'));

		$tr = $table->appendCreate('thead')->appendCreate('tr');
		$tr->appendCreate('th')->appendCreate('abbr', [ 'title' => 'Effective hitpoints', 'EHP' ]);
		$th = $tr->appendCreate('th#ehp');
		$th->appendCreate('span', [ 'title' => 'Worst case EHP', '≥'.$this->formatKMB($ehp['ehp']['min']) ]);
		$th->appendCreate('br');
		$th->appendCreate('strong', [ 'title' => 'Average EHP (approximation)', $this->formatKMB($ehp['ehp']['avg']) ]);
		$th->appendCreate('br');
		$th->appendCreate('span', [ 'title' => 'Best case EHP', '≤'.$this->formatKMB($ehp['ehp']['max']) ]);

		foreach([
			[ 'EM', 7, 29 ],
			[ 'Thermal', 4, 29 ],
			[ 'Kinetic', 5, 29 ],
			[ 'Explosive', 6, 29 ],
		] as $dtype) {
			$tr->appendCreate('td')->appendCreate('o-sprite', [
				'alt' => ($alt = $dtype[0].' resistance'), 'title' => $alt,
				'x' => $dtype[1], 'y' => $dtype[2],
				'width' => 32, 'height' => 32,
				'gridwidth' => 32, 'gridheight' => 32,
			]);
		}

		$tbody = $table->appendCreate('tbody');
		foreach([
			[ 'shield', 'Shield', 3, 0 ],
			[ 'armor', 'Armor', 3, 1 ],
			[ 'hull', 'Structure (hull)', 3, 2 ],
		] as $layer) {
			$tr = $layertrtpl->cloneNode(true);
			$tr->setAttribute('id', $layer[0]);

			$tr->childNodes->item(0)->appendCreate('o-sprite', [
				'alt' => $layer[1], 'title' => $layer[1],
				'x' => $layer[2], 'y' => $layer[3],
				'width' => 32, 'height' => 32,
				'gridwidth' => 64, 'gridheight' => 64,
			]);

			$tr->childNodes->item(1)->append($this->formatKMB($ehp[$layer[0]]['capacity']));

			foreach([ 'em' => 2, 'thermal' => 3, 'kinetic' => 4, 'explosive' => 5 ] as $dtype => $didx) {
				$tr->childNodes->item($didx)->append($this->formatResonance($ehp[$layer[0]]['resonance'][$dtype]));
			}

			$tbody->append($tr);
		}

		$layers = [
			'hull' => [ 'Hull repairs', 'Hull EHP repaired per second', true, 4, 3 ],
			'armor' => [ 'Armor repairs', 'Armor EHP repaired per second', true, 4, 2 ],
			'shield' => [ 'Shield boost', 'Shield EHP boost per second', true, 4, 1 ],
			'shield_passive' => [ 'Passive shield recharge', 'Peak shield EHP recharged per second', false, 4, 0 ],
		];

		$bimg = $this->element('o-sprite', [
			'alt' => 'Burstable tank', 'title' => 'Burst tank',
			'x' => 2, 'y' => 2,
			'width' => 16, 'height' => 16,
			'gridwidth' => 64, 'gridheight' => 64,
		]);
		$simg = $this->element('o-sprite', [
			'alt' => 'Sustainable tank', 'title' => 'Sustainable tank',
			'x' => 2, 'y' => 1,
			'width' => 16, 'height' => 16,
			'gridwidth' => 64, 'gridheight' => 64,
		]);

		$btotal = $stotal = 0;

		$tanks = \Osmium\Fit\get_tank($fit, $ehp, $cap['delta'], $reload);
		uasort($tanks, function($a, $b) { return $a[0] < $b[0] ? 1 : -1; });

		foreach($tanks as $layername => $a) {
			list($burst, $sustain) = $a;
			if($burst <= 0) continue;

			$btotal += $burst;
			$stotal += $sustain;

			list($alt, $title, $showboth, $sx, $sy) = $layers[$layername];
			$contents[] = ($p = $this->createElement('p'));

			$p->appendCreate('o-sprite', [
				'title' => $title, 'alt' => $alt,
				'x' => $sx, 'y' => $sy,
				'width' => 32, 'height' => 32,
				'gridwidth' => 64, 'gridheight' => 64,
			]);

			if($showboth) {
				$span = $p->appendCreate('span');
				$span->append($bimg->cloneNode(true));
				$span->appendCreate('span', [
					'title' => $title.' (burst)',
					$this->formatNDigits(1000 * $burst, 1),
				]);
				$span->appendCreate('br');
				$span->append($simg->cloneNode(true));
				$span->appendCreate('span', [
					'title' => $title.' (sustainable)',
					$this->formatNDigits(1000 * $sustain, 1),
				]);
			} else {
				$p->appendCreate('span', [ 'title' => $title, $this->formatNDigits(1000 * $burst, 1) ]);
			}
		}

		$subtitles[] = $this->element('span', [
			'title' => 'Average EHP',
			$this->formatKMB($ehp['ehp']['avg'], 2).' ehp' ]
		);

		if($btotal > 0) {
			$subtitles[] = ' – ';
			$subtitles[] = $this->element('span', [
				'title' => 'Combined burst tank',
				$this->formatSDigits($btotal * 1000, 2).' dps',
			]);
		}

		if($stotal > 0) {
			$subtitles[] = ' – ';
			$subtitles[] = $this->element('span', [
				'title' => 'Combined sustainable tank',
				$this->formatSDigits($stotal * 1000, 2).' dps',
			]);
		}

		return $this->makeFormattedAttributesSection(
			'defense',
			[ 'Defense ', [ 'span.pname', $fit['damageprofile']['name'] ] ],
			$subtitles,
			false,
			$contents
		);
	}

	/* Make the Navigation section used in fattribs. */
	function makeFormattedAttributesNavigationSection(&$fit) {
		$contents = [];

		/* XXX: code smell! */
		$maxvelocity = round(min(
			\Osmium\Dogma\get_ship_attribute($fit, 'maxVelocity'),
			\Osmium\Dogma\get_ship_attribute($fit, 'speedLimit')
		));
		$agility = \Osmium\Dogma\get_ship_attribute($fit, 'agility');
		$aligntime = -log(0.25) * \Osmium\Dogma\get_ship_attribute($fit, 'mass') * $agility / 1000000;
		$warpspeed = \Osmium\Dogma\get_ship_attribute($fit, 'warpSpeedMultiplier') * \Osmium\Dogma\get_ship_attribute($fit, 'baseWarpSpeed');
		$warpstrength = -\Osmium\Dogma\get_ship_attribute($fit, 'warpScrambleStatus');
		$fpoints = 'point'.(abs($warpstrength) == 1 ? '' : 's');

		$contents[] = ($p = $this->createElement('p'));
		$p->appendCreate('o-sprite', [
			'alt' => 'Propulsion',
			'title' => 'Propulsion',
			'x' => 5, 'y' => 0,
			'width' => 32, 'height' => 32,
			'gridwidth' => 64, 'gridheight' => 64,
		]);
		$span = $p->appendCreate('span#maximum_velocity', [
			'title' => 'Maximum velocity',
			$fvelocity = $this->formatExactInteger($maxvelocity).' m/s',
		]);

		$contents[] = ($p = $this->createElement('p'));
		$p->appendCreate('o-sprite', [
			'alt' => 'Agility',
			'title' => 'Agility',
			'x' => 10, 'y' => 2,
			'width' => 32, 'height' => 32,
			'gridwidth' => 32, 'gridheight' => 32,
		]);
		$span = $p->appendCreate('span');
		$span->appendCreate('span#agility_modifier', [
			'title' => 'Agility modifier',
			$this->formatSDigits($agility, 4).' x',
		]);
		$span->appendCreate('br');
		$span->appendCreate('span#align_time', [
			'title' => 'Time to align (theoretical)',
			$this->formatExactInteger(ceil($aligntime)).' s',
			[ 'small', ' ('.$this->formatNDigits($aligntime, 1).')' ],
		]);

		$contents[] = ($p = $this->createElement('p'));
		$p->appendCreate('o-sprite', [
			'alt' => 'Warp core',
			'title' => 'Warp core',
			'x' => 5, 'y' => 2,
			'width' => 32, 'height' => 32,
			'gridwidth' => 64, 'gridheight' => 64,
		]);
		$span = $p->appendCreate('span');
		$span->appendCreate('span#warp_speed', [
			'title' => 'Maximum warp speed',
			$this->formatNDigits($warpspeed, 1).' AU/s',
		]);
		$span->appendCreate('br');
		$span->appendCreate('span#warp_core_strength', [
			'title' => 'Warp core strength (can enter warp as long as the number of points is nonnegative)',
			$this->formatExactInteger($warpstrength).' '.$fpoints,
		]);

		return $this->makeFormattedAttributesSection(
			'navigation',
			'Navigation',
			$this->element('span', [ 'title' => 'Maximum velocity', $fvelocity ]),
			false,
			$contents
		);
	}

	/* Make the Targeting section used in fattribs. */
	function makeFormattedAttributesTargetingSection(&$fit) {
		$contents = [];

		static $types = array(
			'radar' => [ 12, 0 ],
			'ladar' => [ 13, 0 ],
			'magnetometric' => [ 12, 1 ],
			'gravimetric' => [ 13, 1 ],
		);

		$scanstrength = array();
		$maxtype = null;
		foreach($types as $t => $p) {
			$scanstrength[$t] = \Osmium\Dogma\get_ship_attribute($fit, 'scan'.ucfirst($t).'Strength');
			if($maxtype === null || $scanstrength[$t] > $scanstrength[$maxtype]) {
				$maxtype = $t;
			}
		}

		$targetrange = \Osmium\Dogma\get_ship_attribute($fit, 'maxTargetRange');
		$numtargets = (int)min(\Osmium\Dogma\get_char_attribute($fit, 'maxLockedTargets'),
		                       \Osmium\Dogma\get_ship_attribute($fit, 'maxLockedTargets'));
		$ftargets = 'target'.($numtargets !== 1 ? 's' : '');
		$scanres = \Osmium\Dogma\get_ship_attribute($fit, 'scanResolution');
		$sigradius = \Osmium\Dogma\get_ship_attribute($fit, 'signatureRadius');

		$contents[] = ($p = $this->createElement('p'));
		$p->appendCreate('o-sprite', [
			'alt' => ($alt = ucfirst($maxtype).' sensor strength'),
			'title' => $alt,
			'x' => $types[$maxtype][0],
			'y' => $types[$maxtype][1],
			'width' => 32, 'height' => 32,
			'gridwidth' => 32, 'gridheight' => 32,
		]);
		$span = $p->appendCreate('span');
		$span->appendCreate('span', [ 'title' => 'Maximum locked targets', $numtargets.' '.$ftargets ]);
		$span->appendCreate('br');
		$span->appendCreate('span', [
			'title' => 'Sensor strength',
			$this->formatNDigits($scanstrength[$maxtype], 1).' points',
		]);

		$contents[] = ($p = $this->createElement('p'));
		$p->appendCreate('o-sprite', [
			'alt' => 'Targeting',
			'title' => 'Targeting',
			'x' => 6, 'y' => 1,
			'width' => 32, 'height' => 32,
			'gridwidth' => 64, 'gridheight' => 64,
		]);
		$span = $p->appendCreate('span#targeting_range');
		$span->appendCreate('span', [
			'title' => 'Targeting range',
			$this->formatKMB($targetrange, 3, '', true).'m',
		]);
		$span->appendCreate('br');
		$span->appendCreate('span#scan_resolution', [
			'title' => 'Scan resolution',
			'data-value' => $scanres,
			$this->formatExactInteger($scanres).' mm',
		]);

		$contents[] = ($p = $this->element('p#signature_radius', [
			'title' => 'Signature radius',
			'data-value' => $sigradius,
		]));
		$p->appendCreate('o-sprite', [
			'alt' => 'Signature radius',
			'title' => 'Signature radius',
			'x' => 12, 'y' => 4,
			'width' => 32, 'height' => 32,
			'gridwidth' => 32, 'gridheight' => 32,
		]);
		$p->appendCreate('span', $this->formatKMB($sigradius, 3, '', true).'m');

		return $this->makeFormattedAttributesSection(
			'targeting', 'Targeting',
			$numtargets.' '.$ftargets.', '.$this->formatKMB($targetrange, 3, '', true).'m',
			false,
			$contents
		);
	}

	/* Make the Mastery section used in the formatted attributes section. */
	function makeFormattedAttributesMasterySection(&$fit, array $prereqs_per_type) {
		$missing_per_type = array();
		\Osmium\Skills\get_missing_prerequisites($prereqs_per_type, $fit['skillset'], $missing_per_type);
		$prereqs_unique = \Osmium\Skills\merge_skill_prerequisites($prereqs_per_type);
		$missing_unique = \Osmium\Skills\merge_skill_prerequisites($missing_per_type);
		list($missingsp, $totalsp, $secs) = \Osmium\Skills\sp_totals($prereqs_unique, $fit['skillset']);

		$types_per_prereqs = [];
		foreach($missing_per_type as $typeid => $arr) {
			foreach($arr as $stid => $level) {
				$types_per_prereqs[$stid][$typeid] = true;
			}
		}

		foreach($types_per_prereqs as &$arr) {
			$arr = array_reverse(array_keys($arr));
		}

		$contents = [];

		if($missing_unique === []) {
			$contents[] = $this->element(
				'p.placeholder',
				'No missing skills (using '.$fit['skillset']['name'].').' 
			);
		} else {
			$contents[] = $this->element('h5', 'Missing skillpoints');
			$contents[] = ($ul = $this->createElement('ul'));

			$ul->appendCreate(
				'li',
				$this->formatExactInteger($missingsp).' SP missing out of '
				.$this->formatExactInteger($totalsp).' SP required'
			);

			$li = $ul->appendCreate(
				'li',
				$this->formatDuration($secs, false, 2).' of training time'
			);

			if($fit['skillset']['name'] === 'All V' || $fit['skillset']['name'] === 'All 0') {
				$li->prepend('approximately ');
			}

			$contents[] = $this->element('h5', 'Missing skills');
			$contents[] = ($ul = $this->createElement('ul'));

			while($missing_unique !== []) {
				reset($missing_unique);
				$typeid = key($missing_unique);
				$level = $missing_unique[$typeid];
				unset($missing_unique[$typeid]);

				$this->makeSortedSkillRequirements(
					$ul,
					$typeid, $level,
					$missing_unique, $prereqs_per_type, $types_per_prereqs
				);
			}
		}

		return $this->makeFormattedAttributesSection(
			'mastery', 'Mastery',
			$this->element('span', [ 'title' => 'Effective skillset', $fit['skillset']['name'] ]),
			$missingsp > 0 ? 'overflow' : false, /* XXX */
			$contents
		);
	}

	/* Make the Misc section used in the formatted attributes section. */
	function makeFormattedAttributesMiscSection(&$fit) {
		$contents = [];

		$contents[] = ($table = $this->createElement('table'));
		$tbody = $table->appendCreate('tbody');
		$missing = array();
		$prices = \Osmium\Fit\get_estimated_price($fit, $missing);
		$total = 0;
		$ptr = $tbody->appendCreate('tr');
		$ptr->appendCreate('th', 'Estimated price:');
		$pul = $ptr->appendCreate('td')->appendCreate('ul');

		foreach($prices as $section => $p) {
			if($p !== 'N/A') {
				$total += $p;
				$p = $this->formatKMB($p).' ISK';
			}

			$li = $pul->appendCreate('li');
			$li->appendCreate('span', $p);
			$li->append($section.': ');
		}

		if($total === 0 && count($missing) > 0) {
			$total = 'N/A';
		} else {
			$total = $this->formatKMB($total).' ISK';
			if(count($missing) > 0) {
				$total = '≥ '.$total;
			}
		}

		if($prices === []) {
			$pul->appendCreate('li', 'N/A');
		}

		if(count($missing) > 0) {
			$missing = implode(",\n", array_unique(array_map('Osmium\Fit\get_typename', $missing)));
			$pul->setAttribute('title', "Estimate of the following types unavailable:\n".$missing);
		}

		$yield = \Osmium\Fit\get_mining_yield($fit);
		if($yield > 0) {
			$tbody->appendCreate('tr')->appendCreate('td', [ 'colspan' => '2' ])->appendCreate('hr');
			$yield *= 3600000; /* From m³/ms to m³/h */
			$row = $tbody->appendCreate('tr');
			$row->appendCreate('th', 'Mining yield:');
			$row->appendCreate('td', $this->formatNDigits($yield, 2).' m³/h');
		}

		static $bays = null;
		if($bays === null) {
			$bays = \Osmium\State\get_cache_memory_fb('shipbays', null);
			if($bays === null) {
				$bays = [ [ 38, 'Cargo capacity' ] ];
				/* XXX: feels hacky */
				$bq = \Osmium\Db\query(
					'SELECT attributeid, displayname
					FROM eve.dgmattribs
					WHERE attributename ~ \'Capacity$\' AND displayname <> \'\' AND unitid = 9
					ORDER BY attributeid ASC'
				);
				while($brow = \Osmium\Db\fetch_row($bq)) {
					$bays[] = [ $brow[0], ucfirst(strtolower($brow[1])) ];
				}
				\Osmium\State\put_cache_memory_fb('shipbays', $bays, 86400);
			}
		}

		$tbody->appendCreate('tr')->appendCreate('td', [ 'colspan' => '2' ])->appendCreate('hr');

		foreach($bays as $bay) {
			$cargo = \Osmium\Dogma\get_ship_attribute($fit, $bay[0]);
			if($cargo > 0) {
				$row = $tbody->appendCreate('tr');
				$row->appendCreate('th', $bay[1].':');
				$row->appendCreate('td', $this->formatNDigits($cargo, 2).' m³');
			}
		}

		if(count($fit['drones']) > 0) {
			$tbody->appendCreate('tr')->appendCreate('td', [ 'colspan' => '2' ])->appendCreate('hr');
			$dcrange = \Osmium\Dogma\get_char_attribute($fit, 'droneControlDistance');
			$row = $tbody->appendCreate('tr');
			$row->appendCreate('th', 'Drone control range:');
			$row->appendCreate('td', $this->formatKMB($dcrange, 3, '', true).'m');
		}

		return $this->makeFormattedAttributesSection(
			'misc', 'Miscellaneous',
			$total,
			'',
			$contents
		);
	}

	/** @internal */
	private static function getRemoteTypes() {
		static $types = [
			'hull' => [ 'Raw hull hitpoints repaired per second', 524 ],
			'armor' => [ 'Raw armor hitpoints repaired per second', 523 ],
			'shield' => [ 'Raw shield hitpoints transferred per second', 405 ],
			'capacitor' => [ 'Giga joules transferred per second', 529 ],
			'neutralization' => [ 'Giga joules neutralized per second', 533 ],
			'leech' => [ 'Giga joules leeched per second (in the best case)', 530 ],
		];

		return $types;
	}

	/* Make the Incoming section for fattribs. */
	function makeFormattedAttributesIncomingSection(&$fit, array $ehp) {
		$contents = [];

		$incoming = \Osmium\Fit\get_incoming($fit);
		foreach($this->getRemoteTypes() as $t => $info) {
			if(!isset($incoming[$t])) continue;
			list($amount, $unit) = $incoming[$t];
			list($title, $img) = $info;
			if($amount < 1e-300) continue;

			if(isset($ehp[$t]) && $unit === 'HP') {
				$avgresonance = 0;
				foreach($fit['damageprofile']['damages'] as $type => $dmg) {
					$avgresonance += $dmg * $ehp[$t]['resonance'][$type];
				}

				$amount /= $avgresonance;
				$unit = 'EHP';
				$title = str_replace('Raw ', 'Effective ', $title);
			}

			$unit .= '/s';
			$amount *= 1000;
			$famount = $this->formatKMB($amount).' '.$unit;

			$contents[] = ($p = $this->element('p', [ 'title' => $title ]));
			$p->appendCreate('o-eve-img', [ 'src' => '/Type/'.$img.'_64.png', 'alt' => $t ]);
			$p->append($famount);
		}

		if($contents === []) return '';
		return $this->makeFormattedAttributesSection(
			'incoming', 'Incoming', '',
			false,
			$contents
		);
	}

	/* Make the Outgoing section for fattribs. */
	function makeFormattedAttributesOutgoingSection(&$fit) {
		$contents = [];

		$outgoing = \Osmium\Fit\get_outgoing($fit);
		foreach($this->getRemoteTypes() as $t => $info) {
			if(!isset($outgoing[$t])) continue;
			list($amount, $unit) = $outgoing[$t];
			list($title, $img) = $info;
			if($amount < 1e-300) continue;

			$unit .= '/s';
			$amount *= 1000;
			$famount = $this->formatKMB($amount).' '.$unit;

			$contents[] = ($p = $this->element('p', [ 'title' => $title ]));
			$p->appendCreate('o-eve-img', [ 'src' => '/Type/'.$img.'_64.png', 'alt' => $t ]);
			$p->append($famount);
		}

		if($contents === []) return '';
		return $this->makeFormattedAttributesSection(
			'outgoing', 'Outgoing', '',
			false,
			$contents
		);
	}

	/* Make the formatted attributes section for a given loadout.
	 *
	 * @param $opts An associative array, which can contain the
	 * following keys:
	 * 
	 * - flags: a mixture of FATTRIBS_* constants
	 * - cap: capacitor information to format (if unspecified, generate it on the fly)
	 * - ehp: effective hitpoints to format (if unspecified, generate it on the fly)
	 */
	function makeFormattedAttributes(Element $parent, &$fit, array $opts = []) {
		/* NB: if you change the defaults here, also change the default
		 * exported values in CLF export function. */
		$flags = isset($opts['flags']) ? $opts['flags'] : \Osmium\Chrome\FATTRIBS_USE_RELOAD_TIME_FOR_CAPACITOR;

		if(isset($opts['cap'])) {
			$cap = $opts['cap'];
		} else {
			\Osmium\Dogma\auto_init($fit);
			dogma_get_capacitor_all(
				$fit['__dogma_context'],
				$flags & \Osmium\Chrome\FATTRIBS_USE_RELOAD_TIME_FOR_CAPACITOR,
				$result
			);
			$cap = $result[dogma_get_hashcode($fit['__dogma_context'])];
		}

		$ia = isset($opts['ia']) ? $opts['ia'] : \Osmium\Fit\get_interesting_attributes($fit);

		$ehp = isset($opts['ehp']) ? $opts['ehp'] :
			\Osmium\Fit\get_ehp_and_resists($fit);

		$prereqs = isset($opts['prerequisites']) ? $opts['prerequisites'] :
			\Osmium\Fit\get_skill_prerequisites_and_missing_prerequisites($fit)[0];

		/* NB: when adding/removing/altering sections, make sure to
		 * update the ID list in put_setting.php. */

		$parent->append($this->makeFormattedAttributesEngineeringSection($fit, $cap));

		$parent->append($this->makeFormattedAttributesOffenseSection(
			$fit, $ia, $flags & \Osmium\Chrome\FATTRIBS_USE_RELOAD_TIME_FOR_DPS
		));
		$parent->append($this->makeFormattedAttributesDefenseSection(
			$fit, $ehp, $cap, $flags & \Osmium\Chrome\FATTRIBS_USE_RELOAD_TIME_FOR_TANK
		));

		$parent->append($this->makeFormattedAttributesNavigationSection($fit));
		$parent->append($this->makeFormattedAttributesTargetingSection($fit));

		$parent->append($this->makeFormattedAttributesIncomingSection($fit, $ehp));
		$parent->append($this->makeFormattedAttributesOutgoingSection($fit));

		$parent->append($this->makeFormattedAttributesMasterySection($fit, $prereqs));
		$parent->append($this->makeFormattedAttributesMiscSection($fit));
	}

}
