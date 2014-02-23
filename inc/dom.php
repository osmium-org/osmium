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

/* The classes and methods below are mostly just thin wrappers to the
 * native DOM classes, to make them more bearable to use (and
 * hopefully much less verbose).
 *
 * In most cases, when a parameter expects a Node, you can pass it a
 * string and it will be treated as a Text node. You can also pass
 * nested arrays of strings/Nodes.
 */

/* Why bother with DOM? After all, printing XHTML with echo works just
 * fine?
 *
 * - No more manual escaping. DOM is clever enough to correctly escape
 * entities when necessary.
 *
 * - DOM will always generate syntaxically valid XML. This is crucial
 *   when outputting XHTML.
 *
 * - Dynamic custom elements. They are elements that generate XHTML at
 * render time only, which makes it easy to make complex UI components
 * (like image sprites) or greatly reduce code duplication (like
 * static versioned URIs, relative URI path, etc.).
 *
 * All of this comes with some performance loss and an increased
 * memory usage, though.
 */



class Document extends \DOMDocument {

	use Appendable;



	/* Defined custom elements. */
	private $_customElements = [];

	/* Defined custom attributes. */
	private $_customAttributes = [];



	/* @internal
	 *
	 * Create a DOMNode from a string, a DOMNode or an array that
	 * matches the parameters of Document::element(). */
	static function _to_node(\DOMDocument $owner, $e) {
		if($e instanceof \DOMNode) {
			return $e;
		}

		if(is_string($e)) {
			return $owner->createTextNode($e);
		}

		if(is_array($e)) {
			return call_user_func_array([$owner, 'element'], $e);
		}

		throw new \Exception('Can\'t make a DOMNode out of '.gettype($e));
	}



	/* @internal */
	static function _to_nodes(\DOMDocument $owner, $e) {
		if(!is_array($e) && !($e instanceof \DOMNodeList)) {
			yield self::_to_node($owner, $e);
			return;
		}

		foreach($e as $v) {
			yield self::_to_node($owner, $v);
		}
	}



	/* Register a new custom element.
	 *
	 * @param $name the name of the custom element
	 *
	 * @param $renderfunc a function that takes two parameters, an
	 * Element and an array of render options, that should return an
	 * Element that will replace the custom Element.
	 */
	function registerCustomElement($name, callable $renderfunc) {
		$this->_customElements[$name] = $renderfunc;
	}



	/* Register a new custom attribute.
	 *
	 * @param $name the name of the custom attribute
	 *
	 * @param $renderfunc a function that takes three parameters, an
	 * Element (the element that has the custom attribute), the value
	 * of the custom attribute and an array of render options. The
	 * custom attribute will be automatically removed from its parent.
	 */
	function registerCustomAttribute($name, callable $renderfunc) {
		$this->_customAttributes[$name] = $renderfunc;
	}



	function __construct() {
		parent::__construct('1.0', 'utf-8');

		$this->registerNodeClass('DOMDocument', __NAMESPACE__.'\Document');
		$this->registerNodeClass('DOMElement', __NAMESPACE__.'\Element');
		$this->registerNodeClass('DOMNode', __NAMESPACE__.'\Node');
	}



	/* Create an element. */
	function element($name, $children = []) {
		$e = parent::createElement($name);

		if(!is_array($children)) $children = [ $children ];
		foreach($children as $k => $v) {
			if(is_string($k)) {
				$e->setAttribute($k, $v);
			} else {
				$e->appendChild(self::_to_node($this, $v));
			}
		}

		return $e;
	}



	/* Create a fragment from raw XML markup. */
	function fragment($xml) {
		$fragment = parent::createDocumentFragment();
		$fragment->appendXML($xml);
		return $fragment;
	}



	/* Render all custom elements, ie replace them by their actual
	 * elements. */
	function renderCustomElements($context) {
		$x = new \DOMXPath($this);

		foreach($this->_customElements as $name => $render) {
			foreach($x->query('//'.$name) as $element) {
				$finalizedelement = $render($element, $context);
				$element->parentNode->replaceChild($finalizedelement, $element);
			}
		}

		foreach($this->_customAttributes as $name => $render) {
			foreach($x->query('//@'.$name) as $attr) {
				$render($attr->parentNode, $attr->value, $context);
				$attr->parentNode->removeAttributeNode($attr);
			}
		}
	}
}





class Element extends \DOMElement {

	use Appendable;
	use Removable;
	use Renderable;



	/* Get or set an attribute.
	 *
	 * If called with one parameter: return this attribute's value.
	 *
	 * If called with two parameters ($name, $value): set an
	 * attribute's value, and return $this.
	 */
	function attr() {
		$args = func_get_args();
		$nargs = func_num_args();

		if($nargs === 1) {
			list($n) = $args;
			return $this->getAttribute($n);
		} else if($nargs === 2) {
			list($n, $v) = $args;
			$this->setAttribute($n, $v);
			return $this;
		} else {
			throw new \Exception('called with incorrect parameters');
		}
	}



	/* Checks whether this element has a given class. */
	function hasClass($class) {
		$classes = $this->getAttribute('class');

		/* explode() or preg_match() would be less code, but less
		 * efficient overall. */

		$ll = strlen($classes);
		$cl = strlen($class);
		$cutoff = $ll - $cl;
		$p = 0;

		while(($p = strpos($classes, $class, $p)) !== false) {
			if($p > 0 && $classes[$p - 1] !== ' ') {
				continue;
			}

			if($p < $cutoff && $classes[$p + $cl] !== ' ') {
				continue;
			}

			/* Found class */
			return true;
		}

		return false;
	}



	/* Add a class to this element. */
	function addClass($class) {
		if(!$this->hasClass($class)) {
			$classes = $this->getAttribute('class');
			$this->setAttribute('class', $classes === '' ? $class : $classes.' '.$class);
		}

		return $this;
	}
}





class Node extends \DOMNode {
	use Appendable;
	use Removable;
	use Renderable;
}





trait Appendable {
	/* Append node(s) to this node. */
	function append($content) {
		foreach(Document::_to_nodes($this->ownerDocument, $content) as $e) {
			$this->appendChild($e);
		}

		return $this;
	}


	/* Prepend node(s) to this node. */
	function prepend($content) {
		$fc = $this->firstChild;

		foreach(Document::_to_nodes($this->ownerDocument, $content) as $e) {
			$this->insertBefore($e, $fc);
		}

		return $this;
	}



	/* Create an element and immediately append it to this node.
	 *
	 * @see Document::element
	 *
	 * @return the created element.
	 */
	function appendCreate() {
		$child = call_user_func_array(array($this->ownerDocument, 'element'), func_get_args());
		$this->appendChild($child);
		return $child;
	}
}


trait Removable {
	/* Remove this node from the tree. */
	function remove() {
		$this->parentNode->removeChild($this);
	}
}

trait Renderable {
	/* Get the XML markup of this node. */
	function renderNode() {
		return $this->ownerDocument->saveXML($this);
	}
}



require __DIR__.'/dom-formatter.php';
require __DIR__.'/dom-raw.php';
require __DIR__.'/dom-chrome.php';
