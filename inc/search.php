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
	$q = query('SHOW META;');
	$meta = array();

	while($r = fetch_row($q)) {
		$meta[$r[0]] = $r[1];
	}

	return $meta;
}

function get_total_matches($search_query, $more_cond = '') {
	$q = query(get_search_query($search_query).' '.$more_cond);
	
	if($q === false) return 0;

	$meta = get_meta();
	return $meta['total_found'];
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
	)';
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

	return query(
		'INSERT INTO osmium_loadouts (
		id, restrictedtoaccountid, restrictedtocorporationid, restrictedtoallianceid,
		goodstandingids, excellentstandingids,
		revision, shipid, upvotes, downvotes, score, creationdate, updatedate, build,
		comments, dps, ehp, estimatedprice,
		attship, attshipgroup, attname, atttags, attauthor,
		ship, shipgroup, name, author, tags, description, types
		) VALUES ('

		.$loadout['loadoutid'].','
		.$loadout['restrictedtoaccountid'].','
		.$loadout['restrictedtocorporationid'].','
		.$loadout['restrictedtoallianceid'].','
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
		.'\''.escape($loadout['modules']).'\''
		.')'
	);
}



function get_search_ids($search_query, $more_cond = '', $offset = 0, $limit = 1000) {
	$q = query(
		$rawq = get_search_query($search_query)
		.' '.$more_cond
		.' LIMIT '.$offset.','.$limit
		.' OPTION field_weights=(ship=100,shipgroup=80,author=100,name=70,description=10,tags=150,types=30)'
	);
	if($q === false) return false; /* Invalid query */

	$ids = array();
	while($row = fetch_row($q)) {
			$ids[] = $row[0];
	}

	return $ids;
}



function make_pretty_results(\Osmium\DOM\RawPage $p, $query, $more = '', $paginate = false, $perpage = 20, $pagename = 'p', $message = 'No loadouts matched your query.') {

	$total = get_total_matches($query, $more);
	if($paginate && $total > 0) {
		$offset = \Osmium\Chrome\paginate($pagename, $perpage, $total, $pageresult, $pageinfo);
	} else $offset = 0;

	$ids = \Osmium\Search\get_search_ids($query, $more, $offset, $perpage);
	if($ids === false) {
		return $p->element('p', [
			'class' => 'placeholder',
			'The supplied query is invalid.',
		]);
	}

	$ol = $p->makeLoadoutGridLayout($ids);

	if($paginate && $total > 0 && $pageresult !== '') {
		return [
			$ids,
			[
				$p->fragment($pageinfo), /* XXX */
				$p->fragment($pageresult), /* XXX */
				$ol,
				$p->fragment($pageresult), /* XXX */
			]
		];
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
		/* Use sane defaults, ie hide absurdly outdated loadouts by
		 * default */

		$vercutoff = array_values(\Osmium\Fit\get_eve_db_versions())[2]['build'];

		$_GET['op'] = 'gt';
		$_GET['build'] = $vercutoff;
	}

	$operators = get_operator_list();
	$orderby = get_orderby_list();

	$cond = '';
	if(isset($_GET['op']) && isset($_GET['build']) && isset($operators[$_GET['op']])) {
		$cond .= " AND build ".$operators[$_GET['op']][0]." ".((int)$_GET['build']);
	}

	if(isset($_GET['sort']) && isset($orderby[$_GET['sort']]) && $_GET['sort'] !== '') {
		$order = isset($_GET['order']) && in_array($_GET['order'], [ 'asc', 'desc' ]) ? $_GET['order'] : 'DESC';
		$cond .= ' ORDER BY '.$_GET['sort'].' '.$order;
	}

	return $cond;
}



/* @deprecated XXX */
function print_type_list($relative, $query, $limit = 20) {
	if(!$query) return [];

	$q = query(get_type_search_query($query, [], $limit));

	if($q === false) return [];
	$typeids = [];

	while($t = fetch_row($q)) {
		if($typeids === []) {
			echo "<ol class='type_sr'>\n";
		}

		$typeid = $t[0];
		$typeids[] = $typeid;

		$tn = \Osmium\Chrome\escape(\Osmium\Fit\get_typename($typeid));

		echo "<li><a href='{$relative}/db/type/{$typeid}' title='{$tn}'>"
			."<img src='//image.eveonline.com/Type/{$typeid}_64.png' alt='' />"
			."{$tn}</a></li>\n";
	}

	if($typeids !== []) {
		echo "</ol>\n";
	}

	return $typeids;
}
