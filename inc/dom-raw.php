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



/* Context needed to render a page. */
class RenderContext {

	/* Relative URI, without trailing /, to the root page. */
	public $relative = '.';

	/* If true, output XHTML (with application/xhtml+xml). If false,
	 * output HTML (with text/html). If 'auto', output XHTML if the
	 * client has support for it. */
	public $xhtml = 'auto';



	/* Fill out adequate defaults for the current client. Ideally only
	 * called once. */
	function finalize() {
		if($this->xhtml === 'auto') {
			$this->xhtml = isset($_SERVER['HTTP_ACCEPT'])
				&& strpos($_SERVER['HTTP_ACCEPT'], 'application/xhtml+xml') !== false;
		}
	}
}



/* A raw page. */
class RawPage extends Document {

	use Formatter;



	/* Has finalized() been called yet? */
	protected $finalized = false;



	function __construct() {
		parent::__construct();

		/* Relative href attribute. Does not include trailing /. */
		$this->registerCustomAttribute('o-rel-href', function(Element $e, $v, RenderContext $ctx) {
			$e->setAttribute('href', $ctx->relative.$v);
		});

		/* Same as o-rel-href, but for action. */
		$this->registerCustomAttribute('o-rel-action', function(Element $e, $v, RenderContext $ctx) {
			$e->setAttribute('action', $ctx->relative.$v);
		});

		/* Relative href attribute to a static file. Does not include trailing /. */
		$this->registerCustomAttribute('o-static-href', function(Element $e, $v, RenderContext $ctx) {
			$e->setAttribute('href', $ctx->relative.'/static-'.\Osmium\STATICVER.$v);
		});

		/* Same as o-static-href, but for src. */
		$this->registerCustomAttribute('o-static-src', function(Element $e, $v, RenderContext $ctx) {
			$e->setAttribute('src', $ctx->relative.'/static-'.\Osmium\STATICVER.$v);
		});

		/* Relative href attribute to a static CSS file. Does not include trailing /. */
		$this->registerCustomAttribute('o-static-css-href', function(Element $e, $v, RenderContext $ctx) {
			$e->setAttribute('href', $ctx->relative.'/static-'.\Osmium\CSS_STATICVER.$v);
		});

		 /* Relative src attribute to a static JS file. Does not include trailing /. */
		$this->registerCustomAttribute('o-static-js-src', function(Element $e, $v, RenderContext $ctx) {
			$e->setAttribute('src', $ctx->relative.'/static-'.\Osmium\JS_STATICVER.$v);
		});



		/* A form. Be sure to use this (at least for post), as it
		 * automatically adds the CSRF token. */
		$this->registerCustomElement('o-form', function(Element $e, RenderContext $ctx) {
			$f = $e->ownerDocument->createElement('form');
			$f->setAttribute('accept-charset', 'utf-8');

			if($e->getAttribute('method') === 'post') {
				$f->appendCreate('input', [
					'type' => 'hidden',
					'name' => 'o___csrf',
					'value' => \Osmium\State\get_token(),
				]);
			}

			while($e->attributes->length > 0) $f->setAttributeNode($e->attributes->item(0));
			while($e->childNodes->length > 0) $f->appendChild($e->childNodes->item(0));

			return $f;
		});

		/* An input element. By default, will remember values when
		 * appropriate (ie not when type=password). You can override
		 * it by setting attribute "remember" to "auto", "on" or
		 * "off". Remembered values will never override the "value"
		 * attribute if it already set. */
		$this->registerCustomElement('o-input', function(Element $e, RenderContext $ctx) {
			$i = $e->ownerDocument->createElement('input');

			$remember = 'auto';
			$j = 0;
			while($j < $e->attributes->length) {
				$attr = $e->attributes->item($j);
				if($attr->name === 'remember') {
					$remember = $attr->value;
					++$j;
					continue;
				}
				$i->setAttributeNode($attr);
			}

			if($remember !== 'off' && ($remember === 'on' || $i->getAttribute('type') !== 'password')) {
				$_vals = $e->closestParent('form')->getAttribute('method') === 'get' ? $_GET : $_POST;
				$n = $i->getAttribute('name');
				if(isset($_vals[$n]) && !($i->hasAttribute('value'))) {
					$i->setAttribute('value', $_vals[$n]);
				}
			}

			return $i;
		});

		/* A select element. Supply regular <option>s and <optgroup>s
		 * as children. Will remember its value from $_POST, see
		 * <o-input> for details. You can set the "selected" attribute
		 * of the <o-select> element and it will transfer to the
		 * correct <option>. */
		$this->registerCustomElement('o-select', function(Element $e, RenderContext $ctx) {
			$s = $e->ownerDocument->createElement('select');

			$i = 0;
			while($i < $e->attributes->length) {
				$attr = $e->attributes->item($i);
				if($attr->name === 'selected' || $attr->name === 'remember') {
					${$attr->name} = $attr->value;
					++$i;
					continue;
				}
				$s->setAttributeNode($attr);
			}

			$n = $s->getAttribute('name');
			if(!isset($selected) && (!isset($remember) || $remember !== 'off')) {
				$_vals = $e->closestParent('form')->getAttribute('method') === 'get' ? $_GET : $_POST;
				if(isset($_vals[$n])) $selected = $_vals[$n];
			}

			while($e->childNodes->length > 0) {
				$c = $e->childNodes->item(0);
				/* TODO: support <optgroup> */
				if(isset($selected) && $c->getAttribute('value') === $selected) {
					$c->setAttribute('selected', 'selected');
				}
				$s->appendChild($c);
			}

			return $s;
		});



		/* An image from the CCP image server. */
		$this->registerCustomElement('o-eve-img', function(Element $e, RenderContext $ctx) {
			$i = $e->ownerDocument->createElement('img');

			/* Move attributes over, and alter src */
			while($e->attributes->length > 0) {
				$attr = $e->attributes->item(0);
				if($attr->name === 'src') {
					$attr->value = '//image.eveonline.com'.$attr->value;
				}

				$i->setAttributeNode($attr);
			}

			return $i;
		});



		/* A sprite from the main sprite image. */
		$this->registerCustomElement('o-sprite', function(Element $e, RenderContext $ctx) {
			$span = $e->ownerDocument->createElement('span');
			$span->setAttribute('class', 'mainsprite');

			$img = $e->ownerDocument->createElement('img');
			$span->appendChild($img);

			$i = 0;
			while($i < $e->attributes->length) {
				$attr = $e->attributes->item($i);

				if(in_array($attr->name, [ 'x', 'y', 'gridwidth', 'gridheight', 'width', 'height' ], true)) {
					${$attr->name} = $attr->value;
					++$i;
					continue;
				}

				/* Copy "normal" attributes to the <img> element (like alt, title, etc.). */
				$img->setAttributeNode($attr);
			}

			if(!isset($x) || !isset($y) || (!isset($gridwidth) && !isset($gridheight))) {
				throw new \Exception('not enough parameters: need x, y and at least gridwidth or gridheight.');
			}

			if(!isset($gridwidth)) $gridwidth = $gridheight;
			if(!isset($gridheight)) $gridheight = $gridwidth;
			if(!isset($width)) $width = $gridwidth;
			if(!isset($height)) $height = $gridheight;

			$posx = $x * $width;
			$posy = $y * $height;
			$imgwidth = $width / $gridwidth * 1024;
			$imgheight = $height / $gridheight * 1024;

			$span->setAttribute('style', 'width: '.$width.'px; height: '.$height.'px;');
			$img->setAttribute('o-static-src', '/icons/sprite.png');
			$img->setAttribute(
				'style',
				'width: '.$imgwidth.'px; height: '.$imgheight.'px; top: -'.$posx.'px; left: -'.$posy.'px;'
			);

			return $span;
		});
	}



	/* Finalize this page for rendering. Must only be called once. */
	function finalize(RenderContext $ctx) {
		if($this->finalized) return;

		$ctx->finalize();
		$this->renderCustomElements($ctx);
		$this->finalized = true;
	}
}
