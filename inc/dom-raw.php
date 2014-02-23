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



		/* An image from the CCP image server. */
		$this->registerCustomElement('o-eve-img', function(Element $e, RenderContext $ctx) {
			$i = $e->ownerDocument->element('img');

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
	}



	/* Finalize this page for rendering. Must only be called once. */
	function finalize(RenderContext $ctx) {
		if($this->finalized) return;

		$ctx->finalize();
		$this->renderCustomElements($ctx);
		$this->finalized = true;
	}
}
