<?php

namespace Osmium\Fit;

const CATEGORY_Celestial = 2;
const CATEGORY_Charge = 8;
const CATEGORY_Drone = 18;
const CATEGORY_Implant = 20;
const CATEGORY_Module = 7;
const CATEGORY_Ship = 6;
const CATEGORY_Skill = 16;
const CATEGORY_Subsystem = 32;

const EFFECT_ArmorRepair = 27;
const EFFECT_EMPWave = 38;
const EFFECT_EnergyNeutralizerFalloff = 6187;
const EFFECT_EnergyNosferatuFalloff = 6197;
const EFFECT_EnergyTransfer = 31;
const EFFECT_FighterMissile = 4729;
const EFFECT_FueledArmorRepair = 5275;
const EFFECT_FueledShieldBoosting = 4936;
const EFFECT_HiPower = 12;
const EFFECT_LoPower = 11;
const EFFECT_MedPower = 13;
const EFFECT_MiningLaser = 67;
const EFFECT_ProjectileFired = 34;
const EFFECT_RemoteArmorRepairFalloff = 6188;
const EFFECT_RemoteHullRepairFalloff = 6185;
const EFFECT_RemoteShieldTransferFalloff = 6186;
const EFFECT_RigSlot = 2663;
const EFFECT_ShieldBoosting = 4;
const EFFECT_StructureRepair = 26;
const EFFECT_SubSystem = 3772;
const EFFECT_TargetAttack = 10;
const EFFECT_UseMissiles = 101;

const ATT_Charisma = 164;
const ATT_Intelligence = 165;
const ATT_Memory = 166;
const ATT_Perception = 167;
const ATT_Willpower = 168;

const ATT_Boosterness = 1087;
const ATT_Capacity = 38;
const ATT_HiSlots = 14;
const ATT_Implantness = 331;
const ATT_LauncherSlotsLeft = 101;
const ATT_LowSlots = 12;
const ATT_MedSlots = 13;
const ATT_PrimaryAttribute = 180;
const ATT_ReloadTime = 1795;
const ATT_ScanResolution = 564;
const ATT_SecondaryAttribute = 181;
const ATT_SignatureRadius = 552;
const ATT_SkillTimeConstant = 275;
const ATT_TurretSlotsLeft = 102;
const ATT_UpgradeLoad = 1152;

const TYPE_1MNMicrowarpdriveII = 440;
const TYPE_10MNMicrowarpdriveII = 12076;
const TYPE_100MNMicrowarpdriveII = 12084;

const GROUP_Booster = 303;
const GROUP_EffectBeacon = 920;
const GROUP_FighterBomber = 1023;
const GROUP_FighterDrone = 549;
const GROUP_ShipModifiers = 1306;



/** @internal */
function get_cached_thing_generic($query, array $params = []) {
	$key = 'NameCache_'.$query.'_'.json_encode($params);
	$cache = \Osmium\State\get_cache_memory($key);

	if($cache !== null) {
		return $cache;
	}

	$r = \Osmium\Db\fetch_row(\Osmium\Db\query_params($query, $params));

	$val = ($r === false) ? false : $r[0];
	\Osmium\State\put_cache_memory($key, $val, 86400);
	return $val;
}

function get_attributedisplayname($attributeid) {
	return get_cached_thing_generic(
		/* Use display name if available, if not patch up the attribute name */
		'SELECT CASE displayname
        WHEN \'\' THEN regexp_replace(upper(left(attributename, 1))
        || right(attributename, -1), \'([a-z])([A-Z0-9])\', \'\1 \2\', \'g\')
        ELSE displayname END
        FROM eve.dgmattribs WHERE attributeid = $1',
		[ $attributeid ]
	);
}

function get_attributename($attributeid) {
	return get_cached_thing_generic(
		'SELECT attributename FROM eve.dgmattribs WHERE attributeid = $1',
		[ $attributeid ]
	);
}

function get_attributeid($attributename) {
	return get_cached_thing_generic(
		'SELECT attributeid FROM eve.dgmattribs WHERE attributename = $1',
		[ $attributename ]
	);
}

function get_unitid($attributeid) {
	return get_cached_thing_generic(
		'SELECT unitid FROM osmium.dgmattribs WHERE attributeid = $1',
		[ $attributeid ]
	);
}

function get_attributedefaultvalue($attributeid) {
	return get_cached_thing_generic(
		'SELECT defaultvalue FROM eve.dgmattribs WHERE attributeid = $1',
		[ $attributeid ]
	);
}

function get_unitdisplayname($unitid) {
	return get_cached_thing_generic(
		'SELECT displayname FROM eve.dgmunits WHERE unitid = $1',
		[ $unitid ]
	);
}

function get_typename($typeid) {
	return get_cached_thing_generic(
		'SELECT typename FROM eve.invtypes WHERE typeid = $1',
		[ $typeid ]
	);
}

function get_effectname($effectid) {
	return get_cached_thing_generic(
		'SELECT effectname FROM eve.dgmeffects WHERE effectid = $1',
		[ $effectid ]
	);
}

function get_marketgroupname($mgid) {
	return get_cached_thing_generic(
		'SELECT marketgroupname FROM eve.invmarketgroups WHERE marketgroupid = $1',
		[ $mgid ]
	);
}

function get_typeid($typename) {
	/* XXX: use patch history for typename changes */
	return (int)get_cached_thing_generic(
		'SELECT typeid FROM eve.invtypes WHERE typename = $1',
		[ $typename ]
	);
}

function get_groupid($typeid) {
	return (int)get_cached_thing_generic(
		'SELECT groupid FROM eve.invtypes WHERE typeid = $1',
		[ $typeid ]
	);
}

function get_volume($typeid) {
	return get_cached_thing_generic(
		'SELECT volume FROM eve.invtypes WHERE typeid = $1',
		[ $typeid ]
	);
}

function get_average_market_price($typeid) {
	return get_cached_thing_generic(
		'SELECT averageprice FROM eve.averagemarketprices WHERE typeid = $1',
		[ $typeid ]
	);
}

function get_parent_typeid($typeid) {
	$parent = (int)get_cached_thing_generic(
		'SELECT parenttypeid FROM eve.invmetatypes WHERE typeid = $1',
		[ $typeid ]
	);

	return $parent ?: $typeid;
}

function get_categoryid($typeid) {
	return (int)get_cached_thing_generic(
		'SELECT categoryid FROM eve.invtypes
		JOIN eve.invgroups ON invgroups.groupid = invtypes.groupid
        WHERE typeid = $1',
		[ $typeid ]
	);
}

function get_groupname($groupid) {
	return get_cached_thing_generic(
		'SELECT groupname FROM eve.invgroups WHERE groupid = $1',
		[ $groupid ]
	);
}

function get_categoryname($catid) {
	return get_cached_thing_generic(
		'SELECT categoryname FROM eve.invcategories WHERE categoryid = $1',
		[ $catid ]
	);
}

function get_group_any_typeid($groupid) {
	return get_cached_thing_generic(
		'SELECT typeid FROM eve.invtypes WHERE groupid = $1',
		[ $groupid ]
	);
}

function get_required_skills($typeid) {
	$typeid = (int)$typeid;
	$key = 'NameCache_required_skills_'.$typeid;
	$cache = \Osmium\State\get_cache_memory($key);

	if($cache !== null) {
		return $cache;
	}

	static $rs = [
		182 => 277, /* RequiredSkill1 => RequiredSkill1Level */
		183 => 278, /* etc… */
		184 => 279,
		1285 => 1286,
		1289 => 1287,
		1290 => 1288,
	];

	$vals = [];

	static $ctx = null;
	if($ctx === null) dogma_init_context($ctx);

	/* XXX: this is hackish */
	dogma_init_context($ctx);
	dogma_set_ship($ctx, $typeid);
	foreach($rs as $rsattid => $rslattid) {
		if(dogma_get_ship_attribute($ctx, $rsattid, $skill) === DOGMA_OK
		   && dogma_get_ship_attribute($ctx, $rslattid, $level) === DOGMA_OK) {
			if($skill > 0 && $level > 0) {
				$vals[$skill] = $level;
			}
		}
	}

	\Osmium\State\put_cache_memory($key, $vals, 86400);
	return $vals;
}

function get_implant_slot($typeid) {
	$typeid = (int)$typeid;
	$key = 'NameCache_implantness_'.$typeid;
	$cache = \Osmium\State\get_cache_memory($key);

	if($cache !== null) {
		return $cache;
	}

	static $ctx = null;
	if($ctx === null) dogma_init_context($ctx);

	/* XXX */
	dogma_set_ship($ctx, $typeid);

	if(get_groupid($typeid) == GROUP_Booster) {
		dogma_get_ship_attribute($ctx, ATT_Boosterness, $slot);
	} else {
		dogma_get_ship_attribute($ctx, ATT_Implantness, $slot);
	}

	\Osmium\State\put_cache_memory($key, $slot, 86400);
	return $slot;
}

function get_skill_rank($typeid) {
	$typeid = (int)$typeid;
	$key = 'NameCache_skill_rank_'.$typeid;
	$cache = \Osmium\State\get_cache_memory($key);

	if($cache !== null) {
		return $cache;
	}

	static $ctx = null;
	if($ctx === null) dogma_init_context($ctx);

	/* XXX */
	dogma_set_ship($ctx, $typeid);

	dogma_get_ship_attribute($ctx, ATT_SkillTimeConstant, $rank);

	\Osmium\State\put_cache_memory($key, $rank, 86400);
	return $rank;
}

function get_skill_attributes($typeid) {
	$typeid = (int)$typeid;
	$key = 'NameCache_skill_attribs_'.$typeid;
	$cache = \Osmium\State\get_cache_memory($key);

	if($cache !== null) {
		return $cache;
	}

	static $ctx = null;
	if($ctx === null) dogma_init_context($ctx);

	$attribs = [ null, null ];
	dogma_get_skill_attribute($ctx, $typeid, ATT_PrimaryAttribute, $attribs[0]);
	dogma_get_skill_attribute($ctx, $typeid, ATT_SecondaryAttribute, $attribs[1]);

	\Osmium\State\put_cache_memory($key, $attribs, 86400);
	return $attribs;
}

function get_type_category_str($typeid) {
	switch(get_categoryid($typeid)) {
		
	case CATEGORY_Module:
	case CATEGORY_Subsystem:
		return 'module';

	case CATEGORY_Ship:
		return 'ship';

	case CATEGORY_Charge:
		return 'charge';

	case CATEGORY_Drone:
		return 'drone';

	case CATEGORY_Implant:
		return (int)get_groupid($typeid) === GROUP_Booster ? 'booster' : 'implant';

	case CATEGORY_Celestial:
		return (int)get_groupid($typeid) === GROUP_EffectBeacon ? 'beacon' : 'unknown';

	default:
		return 'unknown';
		
	}
}

function is_charge_fittable($moduleid, $chargeid) {
	return (bool)get_cached_thing_generic(
		'SELECT chargeid FROM osmium.invcharges
        WHERE moduleid = $1 AND chargeid = $2',
		[ $moduleid, $chargeid ]
	);
}