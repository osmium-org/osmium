<?php
/* Osmium
 * Copyright (C) 2012, 2013, 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\Search;

const SPHINXQL_PORT = 24492;

function get_orderby_list() {
	return [
		"" => "relevance",
		"creationdate" => "creation date",
		"attname" => "name",
		"attship" => "ship",
		"attshipgroup" => "ship group",
		"attauthor" => "author",
		"atttags" => "tags",
		"score" => "score (votes)",
		"comments" => "number of comments",
		"dps" => "damage per second",
		"ehp" => "effective hitpoints",
		"estimatedprice" => "estimated price",
	];
}

function get_operator_list() {
	return [
		'gt' => [ '>=', 'or newer' ],
		'eq' => [ '=', 'exactly' ],
		'lt' => [ '<=', 'or older' ],
	];
}

function get_order_list() {
	return [
		'desc' => 'in descending order',
		'asc' => 'in ascending order',
	];
}




function get_link() {
	static $link = null;
	if($link === null) {
		$link = mysqli_connect('127.0.0.1', 'root', '', '', SPHINXQL_PORT);
		if(!$link) {
			\Osmium\fatal(500, 'Could not connect to Sphinx.');
		}
	}

	return $link;
}

function query($q) {
	return mysqli_query(get_link(), $q);
}

function escape($string) {
	/* Taken from the GPL PHP API of Sphinx */
	$from = array ('\\', '(',')','|','-','!','@','~',"'",'&', '/', '^', '$', '=');
	$to   = array ('\\\\', '\(','\)','\|','\-','\!','\@','\~','\\\'', '\&', '\/', '\^', '\$', '\=');
	return str_replace ($from, $to, $string);
}

function fetch_assoc($result) {
	return mysqli_fetch_assoc($result);
}

function fetch_row($result) {
	return mysqli_fetch_row($result);
}

function get_meta() {
	/* TODO: since this is only used to get total matches, use LIKE
	 * syntax when 2.1.x is widespread enough. */
	$q = query('SHOW META;');
	$meta = array();

	while($r = fetch_row($q)) {
		$meta[$r[0]] = $r[1];
	}

	return $meta;
}

function get_total_matches($search_query, $more_cond = '', &$hasmore = null) {
	$q = query(get_search_query($search_query).' '.$more_cond);
	
	if($q === false) return 0;

	$meta = get_meta();
	$hasmore = $meta['total_found'] > $meta['total'];
	return $meta['total'];
}




function query_select_searchdata($cond, array $params = array()) {
	return \Osmium\Db\query_params(
		'SELECT loadoutid, restrictedtoaccountid, restrictedtocorporationid,
		restrictedtoallianceid, viewpermission,
		tags, modules, author, name, description,
		revision, shipid, upvotes, downvotes, score, ship, groups, creationdate,
		updatedate, evebuildnumber, comments, dps, ehp, estimatedprice
		FROM osmium.loadoutssearchdata '.$cond,
		$params
	);
}

function parse_search_query_attr_filters(&$search_query, array $intattrs = array(), array $floatattrs = array()) {
	$and = [];

	$search_query = preg_replace_callback(
		$regex = '%(\s|^)@('
		.'(?<intattr>'.implode('|', $intattrs).')'
		.'|(?<floatattr>'.implode('|', $floatattrs).')'
		.')\s+(?<operator>(>=|<=|<>|!=|=|>|<)?)'
		.'\s*(?<value>[+-]?[0-9]*(\.[0-9]+)?([eE][+-]?[0-9]+)?)'
		.'(?<modifier>[kmb]?)%',
		function($m) use(&$and) {
			$value = (float)$m['value'];
			if($m['modifier'] === 'k') $value *= 1e3;
			else if($m['modifier'] === 'm') $value *= 1e6;
			else if($m['modifier'] === 'b') $value *= 1e9;

			if(!$m['operator']) $m['operator'] = '=';

			if($m['intattr']) {
				$and[] = $m['intattr'].' '.$m['operator'].' '.(int)$value;
			} else if($m['floatattr']) {
				$and[] = $m['floatattr'].' '.$m['operator'].' '.sprintf("%.14f", $value);
			}

			return '';
		},
		$search_query
	);

	$fand = '';
	foreach($and as $a) $fand .= ' AND '.$a;
	return $fand;
}

function get_search_query($search_query) {
	$accountids = array(0);
	$corporationids = array(0);
	$allianceids = array(0);
	$characterids = array(0);

	if(\Osmium\State\is_logged_in()) {
		$a = \Osmium\State\get_state('a');
		$accountids[] = intval($a['accountid']);

		if($a['apiverified'] === 't') {
			$corporationids[] = intval($a['corporationid']);
			$characterids[] = intval($a['characterid']);
			if($a['allianceid'] > 0) $allianceids[] = intval($a['allianceid']);
		}
	}

	$fand = parse_search_query_attr_filters(
		$search_query,
		[
			'loadoutid', 'restrictedtoaccountid',
			'restrictedtocorporationid', 'restrictedtoallianceid',
			'revision', 'shipid', 'upvotes', 'downvotes',
			'creationdate', 'updatedate',
			'build', 'comments',
		],
		[
			'score', 'dps', 'ehp', 'estimatedprice',
		]
	);

	$ac_ids = implode(', ', $accountids);
	$ch_ids = implode(', ', $characterids);
	$co_ids = implode(', ', $corporationids);
	$al_ids = implode(', ', $allianceids);

	return "SELECT id FROM osmium_loadouts
	WHERE MATCH('".escape($search_query)."')
	AND restrictedtoaccountid IN ({$ac_ids})
	AND restrictedtocorporationid IN ({$co_ids})
	AND restrictedtoallianceid IN ({$al_ids})
	AND goodstandingids IN ({$ac_ids}, {$ch_ids}, {$co_ids}, {$al_ids})
	AND excellentstandingids IN ({$ac_ids}, {$ch_ids}, {$co_ids}, {$al_ids})
	".$fand;
}

function get_type_search_query($q, $mgfilters = array(), $limit = 50) {
	$mgfilters[] = -1;

	$w = parse_search_query_attr_filters(
		$q, [ 'ml', 'mg' ], []
	);

	return 'SELECT id
	FROM osmium_types
	WHERE mg NOT IN ('.implode(',', $mgfilters).')
	AND MATCH(\''.\Osmium\Search\escape($q).'\') '.$w.'
	LIMIT '.(int)$limit.'
	OPTION field_weights=(
	typename=1000,synonyms=1000,parenttypename=100,parentsynonyms=100,
	groupname=100,marketgroupname=100
	), ranker=sph04';
}

function get_skillids() {
	static $skillids = null;
	if($skillids === null) {
		$skillids = \Osmium\State\get_cache_memory('skillids', null);
		if($skillids !== null) return $skillids;

		$sq = \Osmium\Db\query('SELECT skilltypeid FROM osmium.requirableskills ORDER BY skilltypeid ASC');
		while($row = \Osmium\Db\fetch_row($sq)) $skillids[] = (int)$row[0];
		\Osmium\State\put_cache_memory('skillids', $skillids);
	}

	return $skillids;
}

function unindex($loadoutid) {
	query('DELETE FROM osmium_loadouts WHERE id = '.$loadoutid);
}

function index($loadout) {
	unindex($loadout['loadoutid']);
	$goodstandings = array();
	$excellentstandings = array();

	if($loadout['viewpermission'] == \Osmium\Fit\VIEW_GOOD_STANDING
	|| $loadout['viewpermission'] == \Osmium\Fit\VIEW_EXCELLENT_STANDING) {
		if($loadout['viewpermission'] == \Osmium\Fit\VIEW_GOOD_STANDING) {
			/* Good standings */
			$cutoff = 0;
			$dest =& $goodstandings;
			$excellentstandings[] = 0;
		} else if($loadout['viewpermission'] == \Osmium\Fit\VIEW_EXCELLENT_STANDING) {
			/* Excellent standings */
			$cutoff = 5;
			$dest =& $excellentstandings;
			$goodstandings[] = 0;
		}

		$author = \Osmium\Db\fetch_assoc(\Osmium\Db\query_params(
			'SELECT l.accountid, a.apiverified, a.characterid, a.corporationid, a.allianceid
			FROM osmium.loadouts l
			JOIN osmium.accounts a ON a.accountid = l.accountid
			WHERE loadoutid = $1',
			array($loadout['loadoutid'])
		));
	
		$q = \Osmium\Db\query_params(
			'SELECT contactid
			FROM osmium.contacts
			WHERE accountid = $1 AND standing > $2',
			array($author['accountid'], $cutoff)
		);

		while($row = \Osmium\Db\fetch_row($q)) {
			$dest[] = $row[0];
		}

		/* As a safety for non API verified accounts, always add the
		 * author account ID to the list of authorized contacts. It's
		 * probably safe to assume that the low range of account IDs
		 * will not overlap with character, corporation or alliance
		 * IDs. */
		$dest[] = $author['accountid'];

		if($author['apiverified'] === 't') {
			if($author['corporationid'] > 0) {
				/* Add the author's corporation to the authorized contacts */
				$dest[] = $author['corporationid'];
			}
			if($author['allianceid'] > 0) {
				/* Add the author's alliance to the authorized contacts */
				$dest[] = $author['allianceid'];
			}
		}
	} else {
		$goodstandings[] = 0;
		$excellentstandings[] = 0;
	}

	$tags = array_map(function($t) { return "'".escape($t)."'"; }, explode(' ', $loadout['tags']));
	sort($tags);
	$tags = implode(' ', $tags);

	$skillids = get_skillids();
	static $skillcolumnnames = null;
	if($skillcolumnnames === null) {
		$skillcolumnnames = implode(', ', array_map(function($n) { return 'rl'.$n; }, $skillids));
	}

	/* XXX */
	$fit = \Osmium\Fit\get_fit($loadout['loadoutid']);
	$prereqs_per_type = \Osmium\Fit\get_skill_prerequisites_for_loadout($fit);
	$minskills = \Osmium\Skills\merge_skill_prerequisites($prereqs_per_type);
	$skills = implode(', ', array_map(
		function($n) use($minskills) { return isset($minskills[$n]) ? $minskills[$n] : 0; },
		$skillids
	));

	return query(
		'INSERT INTO osmium_loadouts (
		id, restrictedtoaccountid, restrictedtocorporationid, restrictedtoallianceid,
		goodstandingids, excellentstandingids,
		revision, shipid, upvotes, downvotes, score, creationdate, updatedate, build,
		comments, dps, ehp, estimatedprice,
		attship, attshipgroup, attname, atttags, attauthor,
		ship, shipgroup, name, author, tags, description, types,
		'.$skillcolumnnames.'
		) VALUES ('

		.$loadout['loadoutid'].','
		.(int)$loadout['restrictedtoaccountid'].','
		.(int)$loadout['restrictedtocorporationid'].','
		.(int)$loadout['restrictedtoallianceid'].','
		.'('.implode(', ', $goodstandings).')'.','
		.'('.implode(', ', $excellentstandings).'),'

		.$loadout['revision'].','
		.$loadout['shipid'].','
		.$loadout['upvotes'].','
		.$loadout['downvotes'].','
		.$loadout['score'].','
		.$loadout['creationdate'].','
		.$loadout['updatedate'].','
		.$loadout['evebuildnumber'].','
		.$loadout['comments'].','
		.$loadout['dps'].','
		.$loadout['ehp'].','
		.$loadout['estimatedprice'].','

		.'\''.escape($loadout['ship']).'\','
		.'\''.escape($loadout['groups']).'\','
		.'\''.escape($loadout['name']).'\','
		.'\''.escape($tags).'\','
		.'\''.escape($loadout['author']).'\','

		.'\''.escape($loadout['ship']).'\','
		.'\''.escape($loadout['groups']).'\','
		.'\''.escape($loadout['name']).'\','
		.'\''.escape($loadout['author']).'\','
		.'\''.escape($tags).'\','
		.'\''.escape($loadout['description']).'\','
		.'\''.escape($loadout['modules']).'\','

		.$skills

		.')'
	);
}



function get_search_ids($search_query, $more_cond = '', $offset = 0, $limit = 1000) {
	$rawq =
		get_search_query($search_query)
		.' '.$more_cond
		.' LIMIT '.$offset.','.$limit
		.' OPTION field_weights=(ship=100,shipgroup=80,author=100,name=70,description=10,tags=150,types=30)'
		;

	$q = query($rawq);
	if($q === false) return false; /* Invalid query */

	$ids = array();
	while($row = fetch_row($q)) {
			$ids[] = $row[0];
	}

	return $ids;
}



function make_pretty_results(\Osmium\DOM\RawPage $p, $query, $more = '', $paginate = false, $perpage = 20, $pagename = 'p', $message = 'No loadouts matched your query.') {

	$total = get_total_matches($query, $more, $hasmore);

	if($paginate && $total > 0) {
		list($offset, $pmeta, $pol) = $p->makePagination(
			$total, [
				'name' => $pagename,
				'perpage' => $perpage,
				'ftotal' => $p->formatExactInteger($total).($hasmore ? '+' : ''),
			]);
	} else $offset = 0;

	$ids = \Osmium\Search\get_search_ids($query, $more, $offset, $perpage);
	if($ids === false) {
		return [
			[],
			$p->element('p', [
				'class' => 'placeholder',
				'The supplied query is invalid.',
			]),
		];
	}

	$ol = $p->makeLoadoutGridLayout($ids);

	if($paginate && $total > 0 && $pol !== '') {
		return [ $ids, [ $pmeta, $pol, $ol, $pol->cloneNode(true) ] ];
	} else if($ids !== []) {
		return [ $ids, $ol ];
	}

	return [
		[],
		$p->element('p', [
			'class' => 'placeholder',
			$message,
		])
	];
}



function get_search_cond_from_advanced() {
	if(!isset($_GET['build']) && !isset($_GET['op'])) {
		$_GET['op'] = 'gt';
		$_GET['build'] = \Osmium\Fit\get_build_cutoff();
	}

	$operators = get_operator_list();
	$orderby = get_orderby_list();

	$cond = '';
	if(isset($_GET['op']) && isset($_GET['build']) && isset($operators[$_GET['op']])) {
		$cond .= " AND build ".$operators[$_GET['op']][0]." ".((int)$_GET['build']);
	}

	if(isset($_GET['sr']) && $_GET['sr'] && \Osmium\State\is_logged_in()) {
		$ss = isset($_GET['ss']) ? $_GET['ss'] : $ss;

		if($ss !== false) {
			/* XXX: cache this somewhat */
			$ss = \Osmium\Db\fetch_assoc(\Osmium\Db\query_params(
				'SELECT importedskillset, overriddenskillset
				FROM osmium.accountcharacters
				WHERE accountid = $1 AND name = $2',
				array(\Osmium\State\get_state('a')['accountid'], $ss)
			));
		}

		if($ss !== false) {
			$skills = (array)json_decode($ss['importedskillset'], true);
			foreach((array)json_decode($ss['overriddenskillset'], true) as $t => $l) {
				$skills[$t] = $l;
			}
			foreach(get_skillids() as $t) {
				$cond .= ' AND rl'.$t.' <= '.(isset($skills[$t]) ? $skills[$t] : 0);
			}
		}
	}

	if(isset($_GET['vr']) && $_GET['vr'] && isset($_GET['vrs'])) {
		switch($_GET['vrs']) {

		case 'private':
			$cond .= ' AND restrictedtoaccountid > 0';
			break;

		case 'corporation':
			$cond .= ' AND restrictedtocorporationid > 0';
			break;

		case 'alliance':
			$cond .= ' AND restrictedtoallianceid > 0';
			break;

		case 'public':
			$cond .= ' AND restrictedtoaccountid = 0 AND restrictedtocorporationid = 0 AND restrictedtoallianceid = 0';
			break;

		}
	}

	if(isset($_GET['sort']) && isset($orderby[$_GET['sort']]) && $_GET['sort'] !== '') {
		$order = isset($_GET['order']) && in_array($_GET['order'], [ 'asc', 'desc' ]) ? $_GET['order'] : 'DESC';
		$cond .= ' ORDER BY '.$_GET['sort'].' '.$order;
	}

	return $cond;
}
