<?php
/* Author: Romain "Artefact2" Dal Maso <artefact2@gmail.com>
 *
 * This program is free software. It comes without any warranty, to
 * the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want
 * To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details. */

namespace EasyDOM;



class Document extends \DOMDocument {

	use Appendable;

	/** @internal */
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

	/** @internal */
	static function _to_nodes(\DOMDocument $owner, $e) {
		if(!is_array($e) && !($e instanceof \DOMNodeList)) {
			yield self::_to_node($owner, $e);
			return;
		}

		foreach($e as $v) {
			yield self::_to_node($owner, $v);
		}
	}

	/** @internal */
	function __construct() {
		parent::__construct('1.0', 'utf-8');

		$this->formatOutput = false;
		$this->preserveWhiteSpace = false;

		$this->registerNodeClass('DOMDocument', __NAMESPACE__.'\Document');
		$this->registerNodeClass('DOMElement', __NAMESPACE__.'\Element');
		$this->registerNodeClass('DOMNode', __NAMESPACE__.'\Node');
	}

	/** Create an element.
	 *
	 * @param $name the name of the element. Can use CSS-style syntax
	 * for adding classes and ID.
	 */
	function element($name, $children = []) {
		$elementname = strtok($name, '#.');
		$offset = strlen($elementname);
		$classes = '';
		$id = false;

		while(($tok = strtok('#.')) !== false) {
			switch($name[$offset]) {

			case '.':
				$classes .= ' '.$tok;
				break;

			case '#':
				$id = $tok;
				break;

			}

			$offset += strlen($tok) + 1;
		}

		$e = parent::createElement($elementname);
		if($id !== false) $e->setAttribute('id', $id);
		if($classes !== '') $e->setAttribute('class', substr($classes, 1));

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

}



class Element extends \DOMElement {

	use Appendable;
	use Insertable;
	use Removable;
	use Renderable;

	/** Get or set an attribute.
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

	/*= Checks whether this element has a given class. */
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

	/*= Add a class to this element. */
	function addClass($class) {
		if(!$this->hasClass($class)) {
			$classes = $this->getAttribute('class');
			$this->setAttribute('class', $classes === '' ? $class : $classes.' '.$class);
		}

		return $this;
	}

	/*= Find the closest parent with a certain node name. */
	function closestParent($parentname) {
		$p = $this;
		while($p->nodeType === XML_ELEMENT_NODE && $p->nodeName !== $parentname) {
			$p = $p->parentNode;
		}

		if($p->nodeName === $parentname) return $p;
		throw new \Exception(
			'element '.$this->nodeName.' not a descendant of '.$parentname
		);
	}

}



class Node extends \DOMNode {

	use Appendable;
	use Insertable;
	use Removable;
	use Renderable;

}



trait Appendable {

	/** Append node(s) to this node. */
	function append($content) {
		foreach(Document::_to_nodes($this->ownerDocument, $content) as $e) {
			$this->appendChild($e);
		}

		return $this;
	}

	/** Prepend node(s) to this node. */
	function prepend($content) {
		$fc = $this->firstChild;

		foreach(Document::_to_nodes($this->ownerDocument, $content) as $e) {
			$this->insertBefore($e, $fc);
		}

		return $this;
	}

	/** Create an element and immediately append it to this node.
	 *
	 * @see Document::element
	 *
	 * @return the created element.
	 */
	function appendCreate() {
		$child = call_user_func_array(
			array($this->ownerDocument, 'element'),
			func_get_args()
		);
		$this->appendChild($child);
		return $child;
	}

}



trait Insertable {

	/** Insert another node before this node. */
	function before($node) {
		$this->parentNode->insertBefore($node, $this);
	}

	/** Insert another node after this node. */
	function after($node) {
		$this->parentNode->insertAfter($node, $this);
	}
}



trait Removable {

	/** Remove this node from the tree. */
	function remove() {
		$this->parentNode->removeChild($this);
	}

}



trait Renderable {

	/** Get the XML markup of this node. */
	function renderNode() {
		return $this->ownerDocument->saveXML($this);
	}

}
