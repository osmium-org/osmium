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
	use LoadoutFormatter;



	/* A list of form errors. Will be rendered before the referenced
	 * element(s). */
	public $formerrors = [];



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
		 * attribute if it already set. For checkboxes and radios, you
		 * can specify a default checked state with the "default"
		 * attribute. */
		$this->registerCustomElement('o-input', function(Element $e, RenderContext $ctx) {
			$i = $e->ownerDocument->createElement('input');
			$this->_render_form_errors($e);

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

			$type = $i->getAttribute('type');
			$parentform = $e->closestParent('form');

			if($remember === 'auto' && in_array($type, [ 'password', 'file' ], true)) {
				$remember = 'off';
			}

			if($remember !== 'off') {
				$_vals = $parentform->getAttribute('method') === 'get' ? $_GET : $_POST;
				$n = $i->getAttribute('name');

				if($type === 'radio' || $type === 'checkbox') {
					if(!($i->hasAttribute('checked'))) {
						if($_vals === []) {
							/* No POST/GET data, use default value */
							if($i->hasAttribute('default')) {
								if($i->getAttribute('default') === 'checked') {
									$i->setAttribute('checked', 'checked');
								}
								$i->removeAttribute('default');
							}
						} else if(self::_get_post_value($_vals, $n)) {
							/* Has POST/GET data _and_ checkbox was checked */
							$i->setAttribute('checked', 'checked');
						}
					}
				} else {
					$value = self::_get_post_value($_vals, $n, false);
					if($value !== false && !($i->hasAttribute('valueh'))) {
						$i->setAttribute('value', $value);
					}
				}
			}

			if($type === 'file') {
				$parentform->setAttribute('enctype', 'multipart/form-data');
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
			$this->_render_form_errors($e);

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
				$selected = self::_get_post_value($_vals, $n, false);
				if($selected === false) unset($selected);
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

		/* A textarea. See o-input for more details. */
		$this->registerCustomElement('o-textarea', function(Element $e, RenderContext $ctx) {
			$ta = $e->ownerDocument->createElement('textarea');
			$this->_render_form_errors($e);

			$remember = 'auto';
			$j = 0;
			while($j < $e->attributes->length) {
				$attr = $e->attributes->item($j);
				if($attr->name === 'remember') {
					$remember = $attr->value;
					++$j;
					continue;
				}
				$ta->setAttributeNode($attr);
			}

			if($remember !== 'off') {
				$_vals = $e->closestParent('form')->getAttribute('method') === 'get' ? $_GET : $_POST;
				$n = $ta->getAttribute('name');
				$contents = self::_get_post_value($_vals, $n, false);
				if($contents !== false && $e->childNodes->length === 0) {
					$ta->appendChild(
						$ctx->xhtml === true ?
						$this->createCDATASection($contents)
						: $this->createTextNode($contents)
					);
				}
			}

			return $ta;
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
			if(!$img->hasAttribute('o-static-src')) {
				$img->setAttribute('o-static-src', '/icons/sprite.png');
			}
			$img->setAttribute(
				'style',
				'width: '.$imgwidth.'px; height: '.$imgheight.'px; top: -'.$posx.'px; left: -'.$posy.'px;'
			);

			return $span;
		});
	}



	/* @internal */
	private function _render_form_errors(Element $e) {
		$n = $e->getAttribute('name');
		if($n === '') return;
		if(!isset($this->formerrors[$n])) return;

		$parent = $e->parentNode;
		foreach($this->formerrors[$n] as $error) {
			if(!$error) continue;

			$p = $this->createElement('p');
			$p->setAttribute('class', 'error_box');
			$p->append($error);
			$parent->insertBefore($p, $e);
		}

		$e->addClass('error');
	}

	/* @internal */
	private static function _get_post_value(array $data, $name, $default = null) {
		$p = strpos($name, '[');
		if($p === false) return isset($data[$name]) ? $data[$name] : $default;

		$parts = explode('[', $name);
		$i = 0;
		foreach($parts as $part) {
			if($i !== 0) {
				if(substr($part, -1) !== ']') throw new \Exception('malformed post name: '.$name);
				$part = substr($part, 0, -1);
			}
			++$i;

			if(isset($data[$part])) {
				$data = $data[$part];
			} else return $default;
		}

		return $data;
	}



	/* Finalize this page for rendering. Must only be called once. */
	function finalize(RenderContext $ctx) {
		if($this->finalized) return;

		$ctx->finalize();
		$this->renderCustomElements($ctx);
		$this->finalized = true;
	}
}
