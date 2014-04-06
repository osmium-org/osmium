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



/* A full-blown page, with the base UI. */
class Page extends RawPage {

	use Formatter;

	/* The title of this page. */
	public $title = 'Osmium page';

	/* Content-Security-Policy (CSP) rules. https?:// variants will be
	 * automatically added if using TLS. */
	public $csp = [
		'default-src' => [ "'none'" ],
		'style-src' => [
			"'self'",
			"'unsafe-inline'",
			'//fonts.googleapis.com',
			'//cdnjs.cloudflare.com',
		],
		'font-src' => [ '//themes.googleusercontent.com' ],
		'img-src' => [ "'self'", '//image.eveonline.com' ],
		'script-src' => [ "'self'", '//cdnjs.cloudflare.com' ],
		'connect-src' => [ "'self'" ],
	];

	/* An array of Javascript snippets to include in the page. Paths
	 * start from /src/snippets/. Don't include the ".js" suffix
	 * either. */
	public $snippets = [];

	/* An array of data to pass as data-* attributes, to share data
	 * with Javascript snippets without using an (unsafe) inline
	 * script. Values will be JSON-encoded. */
	public $data = [];



	/* Decides whether robots can index this page or not. */
	public $index = true;

	/* The theme to use. Must be a key of the array $_themes (see
	 * below), or the value 'auto' to guess from cookie or use the
	 * default. */
	public $theme = 'auto';



	/* The root <html> element. */
	public $html;

	/* The <head> element. */
	public $head;

	/* The <body> element. */
	public $body;

	/* The wrapper <div> inside <body>. Append the page's content to
	 * this element. */
	public $content;



	/* List of available themes. */
	private static $_themes = [
		'Dark' => 'dark.css',
		'Light' => 'light.css',
	];



	/* Minify a bunch of JS snippets.
	 *
	 * @returns URI of minified JS file, from /static/
	 */
	private static function _minify(array $snippets) {
		if($snippets === []) return null;
		/* Like array_unique(), but O(n) */
		$snippets = array_flip(array_flip($snippets));

		$name = 'JS_'.substr(sha1(implode("\n", $snippets)), 0, 7);
		$cachefile = \Osmium\ROOT.'/static/cache/'.$name.'.js';
		$cacheminfile = \Osmium\ROOT.'/static/cache/'.$name.'.min.js';

		foreach($snippets as &$s) {
			$s = escapeshellarg(\Osmium\ROOT.'/src/snippets/'.$s.'.js');
		}

		$snippets = implode(' ', $snippets);

		if(!file_exists($cacheminfile)) {
			$sem = \Osmium\State\semaphore_acquire($name);

			if(!file_exists($cacheminfile)) {
				shell_exec('cat '.$snippets.' >> '.escapeshellarg($cachefile));

				if($min = \Osmium\get_ini_setting('minify_js')) {
					$command = \Osmium\get_ini_setting('minify_command');

					/* Concatenate & minify */
					shell_exec('cat '.$snippets.' | '.$command.' >> '.escapeshellarg($cacheminfile));
				}

				if(!$min || !file_exists($cachefile)) {
					/* Not minifying, or minifier failed for some reason */
					shell_exec('ln -s '.escapeshellarg($cachefile).' '.escapeshellarg($cacheminfile));
				}
			}

			\Osmium\State\semaphore_release($sem);
		}

		return '/cache/'.$name.'.min.js';
	}



	function __construct() {
		parent::__construct();

		$this->html = $this->element('html');
		$this->head = $this->html->appendCreate('head');
		$this->body = $this->html->appendCreate('body');
		$this->content = $this->body->appendCreate('div', [ 'id' => 'wrapper' ]);

		$this->appendChild($this->html);
	}



	/* @see RawPage::finalize() */
	function finalize(RenderContext $ctx) {
		if($this->finalized) return;

		$this->head->appendCreate('title', $this->title.' / '.\Osmium\get_ini_setting('name'));

		if(!$this->index) {
			$this->head->appendCreate('meta', [ 'name' => 'robots', 'content' => 'noindex' ]);
		}

		$this->head->appendCreate('link', [
			'rel' => 'help',
			'o-rel-href' => '/help',
		]);

		$flink = $this->head->appendCreate('link', [
			'rel' => 'icon',
			'type' => 'image/png'
		]);

		$favicon = \Osmium\get_ini_setting('favicon');
		if(substr($favicon, 0, 2) === '//') {
			/* Absolute URI */
			$flink->attr('href', $favicon);
		} else {
			/* Relative, in static/ */
			$flink->attr('o-static-href', '/'.$favicon);
		}

		$this->snippets[] = 'persistent_theme';
		$this->snippets[] = 'notifications';
		$this->snippets[] = 'feedback';

		$this->data['relative'] = $ctx->relative;

		$this->renderThemes();
		$this->renderHeader();
		$this->renderFooter();

		parent::finalize($ctx);
	}

	/* Render this page. Assumes headers have not been sent yet. */
	function render(RenderContext $ctx) {
		$this->finalize($ctx);

		$csp = $this->csp;
		foreach($csp as $k => $rules) {
			$processedrules = [ $k ];
			foreach($rules as $r) {
				if(substr($r, 0, 2) === '//') {
					$processedrules[] = 'https:'.$r;
					if(!\Osmium\HTTPS) {
						$processedrules[] = 'http:'.$r;
					}
				} else {
					$processedrules[] = $r;
				}
			}

			$csp[$k] = implode(' ', $processedrules);
		}
		header('Content-Security-Policy: '.implode(' ; ', $csp));

		if(\Osmium\HTTPS
		   && \Osmium\get_ini_setting('https_available')
		   && \Osmium\get_ini_setting('use_hsts')) {
			$maxage = (int)\Osmium\get_ini_setting('https_cert_expiration') - time() - 86400;
			if($maxage > 0) {
				header('Strict-Transport-Policy: max-age='.$maxage);
			}
		}

		$this->appendChild($this->createComment(' '.(microtime(true) - \Osmium\T0).' '));

		if($ctx->xhtml) {
			header('Content-Type: application/xhtml+xml');
			$this->html->setAttribute('xmlns', 'http://www.w3.org/1999/xhtml');

			$this->save('php://output');
		} else {
			header('Content-Type: text/html; charset=utf-8');
			$this->head->prepend($this->element('meta', [ 'charset' => 'UTF-8' ]));

			echo "<!DOCTYPE html>\n";
			$this->saveHTMLFile('php://output');
		}

		flush(); /* Not sure about its effectiveness, but it doesn't hurt */

		\Osmium\State\put_activity();
	}



	/* Make a link to an account, using the account nickname or
	 * character name appropriately.
	 *
	 * @returns an element. The chosen name (either character or nick)
	 * will be put in $chosenname if present.
	 */
	function makeAccountLink(array $a, &$chosenname = null) {
		if(!isset($a['accountid']) || !($a['accountid'] > 0)) {
			return $this->createTextNode('N/A');
		}

		$span = $this->element('span');

		if(isset($a['apiverified']) && $a['apiverified'] === 't'
		   && isset($a['characterid']) && $a['characterid'] > 0) {
			$span->addClass('apiverified');
			$span->append($chosenname = $a['charactername']);
		} else {
			$span->addClass('normalaccount');
			$span->append($chosenname = $a['nickname']);
		}

		if(isset($a['ismoderator']) && $a['ismoderator'] === 't') {
			$span = $this->element('span', [
				'title' => 'Moderator', 'class' => 'mod',
				\Osmium\Flag\MODERATOR_SYMBOL,
				$span
			]);
		}

		return $this->element('a', [
			'class' => 'profile',
			'o-rel-href' => '/profile/'.$a['accountid'],
			$span,
		]);
	}



	/* For use in makeSearchBox(). */
	const MSB_SEARCH = 1;
	const MSB_FILTER = 2;

	/* Make a search box. */
	function makeSearchBox($mode = self::MSB_SEARCH) {
		static $idx = 0;

		static $examples = [
			"@ship Drake | Tengu @tags missile-boat",
			"@shipgroup Cruiser -Strategic -Heavy @dps >= 500",
			"@tags -armor-tank",
			"@dps >= 400 @ehp >= 40k @tags pvp",
			"battlecruiser @types \"stasis webifier\"",
			"@tags cheap low-sp @estimatedprice <= 10m",
			"battleship @tags pve|l4|missions",
		];

		$f = $this->element('o-form', [ 'method' => 'get' ]);

		if($mode === self::MSB_SEARCH) {
			$f->setAttribute('o-rel-action', '/search');
		} else {
			$f->setAttribute('action', '');
		}

		$sprite = $this->element('o-sprite', [
			'x' => $mode === self::MSB_SEARCH ? 2 : 3,
			'y' => 12,
			'gridwidth' => 64,
			'gridheight' => 64,
			'alt' => '',
		]);

		$label = $f->appendCreate('h1')->appendCreate('label', [
			'for' => 'search'.$idx,
			$sprite,
		]);

		$p = $f->appendCreate('p');
		$p->append([
			[ 'o-input', [
				'id' => 'search'.$idx,
				'type' => 'search',
				'name' => 'q',
				'placeholder' => $examples[mt_rand(0, count($examples) - 1)]
			]],
			[ 'input', [
				'type' => 'submit',
				'value' => 'Go!',
			]],
			[ 'br' ],
		]);

		if(isset($_GET['ad']) && $_GET['ad'] === '1') {
			/* Advanced search mode */
			$label->append('Advanced search');

			$sbuild = $this->element('o-select', [ 'name' => 'build' ]);
			foreach(\Osmium\Fit\get_eve_db_versions() as $v) {
				$sbuild->appendCreate('option', [ 'value' => $v['build'], $v['name'] ]);
			}

			$soperand = $this->element('o-select', [ 'name' => 'op' ]);
			foreach(\Osmium\Search\get_operator_list() as $op => $label) {
				$soperand->appendCreate('option', [ 'value' => $op, $label[1] ]);
			}

			$ssort = $this->element('o-select', [ 'name' => 'sort' ]);
			foreach(\Osmium\Search\get_orderby_list() as $sort => $label) {
				$ssort->appendCreate('option', [ 'value' => $sort, $label ]);
			}

			$sorder = $this->element('o-select', [ 'name' => 'order' ]);
			foreach(\Osmium\Search\get_order_list() as $sort => $label) {
				$sorder->appendCreate('option', [ 'value' => $sort, $label ]);
			}

			$p->append([
				'for ', $sbuild, ' ', $soperand, [ 'br' ],
				'sort by ', $ssort, ' ', $sorder,
			]);

			$availskillsets = \Osmium\Fit\get_available_skillset_names_for_account();
			if(count($availskillsets) > 2) {
				$sskillsets = $this->element('o-select', [ 'name' => 'ss', 'id' => 'ss' ]);
				foreach($availskillsets as $ss) {
					if($ss === 'All 0' || $ss === 'All V') continue;
					$sskillsets->appendCreate('option', [ 'value' => $ss, $ss ]);
				}

				$p->append([
					[ 'br' ],
					[ 'o-input', [ 'type' => 'checkbox', 'name' => 'sr', 'id' => 'sr' ] ],
					[ 'label', [ 'for' => 'sr', ' Only show loadouts ' ] ],
					$sskillsets,
					[ 'label', [ 'for' => 'sr', ' can fly' ] ],
				]);

				$this->snippets[] = 'searchform';
			}

			$p->append([
				[ 'input', [ 'type' => 'hidden', 'name' => 'ad', 'value' => 1 ] ], [ 'br' ],
				[ 'a', [ 'o-rel-href' => '/help/search', [ 'small', 'Help' ] ] ],
			]);

		} else {
			/* Simple search mode, show a link to advanced mode and nothing else */
			$label->append(($mode === self::MSB_SEARCH ? 'Search' : 'Filter').' loadouts');

			/* XXX: get_search_cond_from_advanced() will set some
			 * default values in $_GET. Ugly side effect, get rid of
			 * this ASAP. */
			\Osmium\Search\get_search_cond_from_advanced();

			$p->appendCreate('small', [
				[ 'a', [ 'href' => self::formatQueryString($_GET, [ 'ad' => 1 ]),
						 'Advanced '.($mode === self::MSB_SEARCH ? 'search' : 'filters') ] ],
				' — ',
				[ 'a', [ 'o-rel-href' => '/help/search', 'Help' ] ],
			]);
		}

		++$idx;
		return $f;
	}



	/* @internal */
	private function renderThemes() {
		if($this->theme === 'auto') {
			$curtheme = isset($_COOKIE['t']) && isset(self::$_themes[$_COOKIE['t']])
				? $_COOKIE['t'] : 'Dark';
		} else {
			$curtheme = $this->theme;
		}

		$this->head->appendCreate('link', [
			'rel' => 'stylesheet',
			'title' => $curtheme,
			'type' => 'text/css',
			'o-static-css-href' => '/'.self::$_themes[$curtheme],
		]);
		foreach(self::$_themes as $tn => $turi) {
			if($tn === $curtheme) continue;

			$this->head->appendCreate('link', [
				'rel' => 'alternate stylesheet',
				'title' => $tn,
				'type' => 'text/css',
				'o-static-css-href' => '/'.$turi
			]);
		}

		$this->head->appendCreate('link', [
			'rel' => 'stylesheet',
			'type' => 'text/css',
			'href' => '//fonts.googleapis.com/css?family=Droid+Serif:400,400italic,700,700italic|Droid+Sans:400,700|Droid+Sans+Mono',
		]);
	}



	/* @internal */
	private function renderHeader() {
		$nav = $this->element('nav');
		$this->content->prepend($nav);
		$nav->append($this->makeStateBox());

		$form = $nav->appendCreate('form', [
			'class' => 's',
			'method' => 'get',
			'o-rel-action' => '/search',
		]);
		$form->appendCreate('input', [
			'type' => 'search',
			'placeholder' => 'Search [s]',
			'name' => 'q',
			'accesskey' => 's',
			'title' => 'Search fittings or types',
		]);
		$form->appendCreate('input', [ 'type' => 'submit', 'value' => 'Go!' ]);

		$osmium = \Osmium\get_ini_setting('name');
		$ul = $nav->appendCreate('ul');
		$ul->append([
			$this->makeNavigationLink('/', $osmium, $osmium, 'Go to the home page'),
			$this->makeNavigationLink('/new', 'Create loadout', 'Create', 'Create a new fitting'),
			$this->makeNavigationLink('/import', 'Import', 'Import',
			                          'Import one or more fittings from various formats'),
			$this->makeNavigationLink('/convert', 'Convert', 'Convert',
			                          'Quickly convert fittings from one format to another'),
		]);

		if(\Osmium\State\is_logged_in()) {
			$ul->append($this->makeNavigationLink('/settings', 'Settings'));

			$a = \Osmium\State\get_state('a');
			if(isset($a['ismoderator']) && $a['ismoderator'] === 't') {
				$ul->append($this->makeNavigationLink(
					'/moderation',
					\Osmium\Flag\MODERATOR_SYMBOL.'Moderation',
					\Osmium\Flag\MODERATOR_SYMBOL
				));
			}
		}
	}



	/* @internal */
	private function makeNavigationLink($dest, $label, $shortlabel = null, $title = null) {
		static $current = null;
		if($current === null) {
			$current = explode('?', $_SERVER['REQUEST_URI'], 2)[0];
		}

		if($shortlabel === null) $shortlabel = $label;

		$full = $this->element('span', [ 'class' => 'full',  $label ]);
		$mini = $this->element('span', [ 'class' => 'mini', $shortlabel ]);

		if($title !== null) {
			$full->attr('title', $title);
			$mini->attr('title', $label.' – '.$title);
		} else {
			$mini->attr('title', $label);
		}
		
		$a = $this->element('a', [ 'o-rel-href' => $dest, $full, $mini ]);
		if(substr($current, -strlen($dest)) === $dest) {
			$a = [ 'strong', $a ];
		}

		return $this->element('li', [ $a ]);
	}



	/* @internal */
	private function makeStateBox() {
		$div = $this->element('div', [ 'id' => 'state_box' ]);

		if(\Osmium\State\is_logged_in()) {
			$div->addClass('logout');
			$p = $div->appendCreate('p');

			$a = \Osmium\State\get_state('a');
			$tok = \Osmium\State\get_token();

			if(isset($a['apiverified']) && $a['apiverified'] ===  't'
			   && isset($a['characterid']) && $a['characterid'] > 0) {
				$portrait = [ 'o-eve-img', [
					'src' => '/Character/'.$a['characterid'].'_128.jpg',
					'alt' => '',
					'class' => 'portrait',
				]];
			} else {
				$portrait = '';
			}

			$ncount = \Osmium\Notification\get_new_notification_count();
			if($ncount > 0) {
				$this->head->getElementsByTagName('title')->item(0)->prepend('('.$ncount.') ');
			}

			$p->append([
				[ 'span', [ 'class' => 'wide' , 'Logged in as ' ] ],
				$portrait,
				' ',
				[ 'strong', $this->makeAccountLink($a) ],
				' (',
				[ 'a', [
					'class' => 'rep', 'o-rel-href' => '/privileges',
					$this->formatReputation(\Osmium\Reputation\get_current_reputation()),
				]],
				'). ',
				[ 'a', [
					'id' => 'ncount',
					'data-count' => (string)$ncount,
					'o-rel-href' => '/notifications',
					'title' => $ncount.' new notification(s)',
					(string)$ncount
				]],
				' ',
				[ 'a', [ 'o-rel-href' => '/logout/'.$tok, 'Logout' ] ],
				' ',
				[ 'small', [
					'(',
					[ 'a', [
						'o-rel-href' => '/logout/'.$tok.self::formatQueryString([ 'global' => '1' ]),
						'all',
					]],
					')',
				]],
			]);

		} else {
			$div->addClass('login');
			$form = $div->appendCreate('o-form', [ 'method' => 'post' ]);

			if(!\Osmium\HTTPS
			   && \Osmium\get_ini_setting('https_available')
			   && \Osmium\get_ini_setting('prefer_secure_login')) {
				/* Use explicit https:// URI for the action */
				$form->attr(
					'action',
					rtrim('https://'.$_SERVER['HTTP_HOST']
					      .\Osmium\get_ini_setting('relative_path'), '/').'/login'
				);
			} else {
				$form->attr('o-rel-action', '/login');
			}

			$wide = $this->element('span', [ 'class' => 'wide' ]);
			$wide->appendCreate('input', [
				'type' => 'text',
				'name' => 'account_name',
				'placeholder' => 'Account name [n]',
				'accesskey' => 'n',
			]);
			$wide->append(' ');
			$wide->appendCreate('input', [
				'type' => 'password',
				'name' => 'password',
				'placeholder' => 'Password',
			]);
			$wide->append(' ');
			$wide->appendCreate('input', [
				'type' => 'submit',
				'name' => '__osmium_login',
				'value' => 'Login',
			]);
			$wide->append([
				' (',
				[ 'input', [
					'type' => 'checkbox',
					'name' => 'remember',
					'id' => 'state_box_remember',
					'checked' => 'checked',
				]],
				[ 'small', [ [ 'label', [ 'for' => 'state_box_remember', 'Remember me' ] ] ] ],
				')',
			]);

			$narrow = $this->element('span', [ 'class' => 'narrow' ]);
			$narrow->appendCreate('a', [ 'o-rel-href' => '/login', 'Login' ]);

			$reglink = [ 'a', [ 'o-rel-href' => '/register', 'Register' ] ];
			$requri = [ 'input', [
				'type' => 'hidden',
				'name' => 'request_uri',
				'value' => $_SERVER['REQUEST_URI'],
			]];
			$p = $form->appendCreate('p', [ $wide, $narrow, ' or ', $reglink, $requri ]);
		}

		return $div;
	}



	/* @internal */
	private function renderFooter() {
		$this->content->appendCreate('div', [ 'id' => 'push' ]);
		$footer = $this->body->appendCreate('footer');
		$p = $footer->appendCreate('p');
		$p->append([
			[ 'a', [ 'o-rel-href' => '/changelog', [ 'code', [ \Osmium\get_osmium_version() ] ] ] ],
			' – ',
			[ 'a', [ 'o-rel-href' => '/about', 'rel' => 'jslicense', 'About' ] ],
			' – ',
		    [ 'a', [ 'o-rel-href' => '/help', 'rel' => 'help', 'Help' ] ],
		]);

		$datadiv = $this->body->appendCreate('div', [ 'id' => 'osmium-data' ]);
		foreach($this->data as $k => $v) {
			$datadiv->setAttribute('data-'.$k, is_string($v) ? $v : json_encode($v));
		}

		/* If these scripts are changed, also change the license
		 * information in about.php */
		$this->body->append([
		    [ 'script', [
				'type' => 'application/javascript',
				'src' => '//cdnjs.cloudflare.com/ajax/libs/jquery/1.10.2/jquery.min.js'
			]],
		    [ 'script', [
				'type' => 'application/javascript',
				'src' => '//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js'
			]],
			[ 'script', [
				'type' => 'application/javascript',
				'o-static-js-src' => self::_minify($this->snippets),
			]],
		]);
	}
}
