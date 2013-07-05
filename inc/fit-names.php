<?php

namespace Osmium\Fit;

const EFFECT_ArmorRepair = 27;
const EFFECT_FueledArmorRepair = 5275;
const EFFECT_FueledShieldBoosting = 4936;
const EFFECT_HiPower = 12;
const EFFECT_LoPower = 11;
const EFFECT_MedPower = 13;
const EFFECT_MiningLaser = 67;
const EFFECT_ProjectileFired = 34;
const EFFECT_RigSlot = 2663;
const EFFECT_ShieldBoosting = 4;
const EFFECT_StructureRepair = 26;
const EFFECT_SubSystem = 3772;
const EFFECT_TargetAttack = 10;
const EFFECT_UseMissiles = 101;

const ATT_HiSlots = 14;
const ATT_LauncherSlotsLeft = 101;
const ATT_LowSlots = 12;
const ATT_MedSlots = 13;
const ATT_TurretSlotsLeft = 102;
const ATT_UpgradeLoad = 1152;


/** @internal */
function get_cached_thing_generic($table, $field, $wherename, $whereval) {
	$key = 'NameCache_'.$table.'_'.$field.'_'.$wherename.'_'.$whereval;
	$cache = \Osmium\State\get_cache_memory($key);

	if($cache !== null) {
		return $cache;
	}

	$r = \Osmium\Db\fetch_row(\Osmium\Db\query_params(
		"SELECT $field FROM $table WHERE $wherename = $1",
		array($whereval)
	));

	$val = ($r === false) ? false : $r[0];
	\Osmium\State\put_cache_memory($key, $val, 86400);
	return $val;
}

function get_attributename($attributeid) {
	return get_cached_thing_generic(
		'eve.dgmattribs', 'attributename', 'attributeid', (int)$attributeid
	);
}

function get_attributeid($attributename) {
	return get_cached_thing_generic(
		'eve.dgmattribs', 'attributeid', 'attributename', $attributename
	);
}

function get_typename($typeid) {
	return get_cached_thing_generic(
		'eve.invtypes', 'typename', 'typeid', (int)$typeid
	);
}

function get_typeid($typename) {
	return get_cached_thing_generic(
		'eve.invtypes', 'typeid', 'typename', $name
	);
}

function get_groupid($typeid) {
	return get_cached_thing_generic(
		'eve.invtypes', 'groupid', 'typeid', (int)$typeid
	);
}

function get_volume($typeid) {
	return get_cached_thing_generic(
		'eve.invtypes', 'volume', 'typeid', (int)$typeid
	);
}

function get_average_market_price($typeid) {
	return get_cached_thing_generic(
		'eve.averagemarketprices', 'averageprice', 'typeid', (int)$typeid
	);
}
