<?php

namespace Osmium\Fit;

const EFFECT_LoPower = 11;
const EFFECT_MedPower = 13;
const EFFECT_HiPower = 12;
const EFFECT_RigSlot = 2663;
const EFFECT_SubSystem = 3772;


/* XXX: refactor and improve the mess below */

function get_attributename($attributeid) {
	static $cache = null;
	if($cache === null) {
		$cache = \Osmium\State\get_cache_memory('dogma_attribute_map', null);
		if($cache === null) {
			$cache = array();
			$q = \Osmium\Db\query('SELECT attributename, attributeid FROM eve.dgmattribs');
			while($r = \Osmium\Db\fetch_row($q)) {
				$cache[$r[1]] = $r[0];
			}
			\Osmium\State\put_cache_memory('dogma_attribute_map', $cache);
		}
	}

	if(!isset($cache[$attributeid])) {
		// @codeCoverageIgnoreStart
		trigger_error('get_attributename(): unknown attributeid "'.$attributeid.'"', E_USER_ERROR);
		// @codeCoverageIgnoreEnd
	}

	return $cache[$attributeid];
}

function get_attributeid($attributename) {
	static $cache = null;
	if($cache === null) {
		$cache = \Osmium\State\get_cache_memory('dogma_attribute_map_flipped', null);
		if($cache === null) {
			$cache = array();
			$q = \Osmium\Db\query('SELECT attributename, attributeid FROM eve.dgmattribs');
			while($r = \Osmium\Db\fetch_row($q)) {
				$cache[$r[0]] = $r[1];
			}
			\Osmium\State\put_cache_memory('dogma_attribute_map_flipped', $cache);
		}
	}

	if(!isset($cache[$attributename])) {
		// @codeCoverageIgnoreStart
		trigger_error('get_attributeid(): unknown attributename "'.$attributename.'"', E_USER_ERROR);
		// @codeCoverageIgnoreEnd
	}

	return (int)$cache[$attributename];
}

function get_typename($typeid) {
	static $cache = null;
	if($cache === null) {
		$cache = \Osmium\State\get_cache_memory('type_map', null);
		if($cache === null) {
			$cache = array();
			$q = \Osmium\Db\query('SELECT typename, typeid FROM eve.invtypes');
			while($r = \Osmium\Db\fetch_row($q)) {
				$cache[$r[1]] = $r[0];
			}
			\Osmium\State\put_cache_memory('type_map', $cache);
		}
	}

	if(!isset($cache[$typeid])) {
		// @codeCoverageIgnoreStart
		trigger_error('get_typename(): unknown typeid "'.$typeid.'"', E_USER_ERROR);
		// @codeCoverageIgnoreEnd
	}

	return $cache[$typeid];
}

function get_typeid($typename) {
	static $cache = null;
	if($cache === null) {
		$cache = \Osmium\State\get_cache_memory('type_map_flipped', null);
		if($cache === null) {
			$cache = array();
			$q = \Osmium\Db\query('SELECT typename, typeid FROM eve.invtypes');
			while($r = \Osmium\Db\fetch_row($q)) {
				$cache[$r[0]] = $r[1];
			}
			\Osmium\State\put_cache_memory('type_map_flipped', $cache);
		}
	}

	if(!isset($cache[$typename])) {
		// @codeCoverageIgnoreStart
		trigger_error('get_typeid(): unknown typename "'.$typename.'"', E_USER_ERROR);
		// @codeCoverageIgnoreEnd
	}

	return (int)$cache[$typename];
}
