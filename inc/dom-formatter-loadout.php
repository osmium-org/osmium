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

		$parent->append($this->makeFormattedAttributesMasterySection($fit, $prereqs));
	}

}
