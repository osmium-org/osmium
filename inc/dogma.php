<?php
/* Osmium
 * Copyright (C) 2012 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

namespace Osmium\Dogma;

const USEFUL_SKILLGROUPS = '(273, 272, 271, 255, 269, 256, 275, 257, 989)';

/* ----------------------------------------------------- */

function get_expression_maybe_overriden($effectname, $type) {
	return file_exists($f =__DIR__.'/effectoverrides/'.$effectname.'-'.$type.'.php') ?
		(require $f) : false;
}

function eval_effect_expression_maybe_overriden(&$fit, $effect, $type) {
	eval_expression($fit, 
	                get_expression_maybe_overriden($effect['effectname'], $type) ?: 
	                unserialize($effect[$type.'exp']));
}

function eval_ship_preexpressions(&$fit) {
	$fit['dogma']['source'] = array('ship');
	$fit['dogma']['self'] =& $fit['dogma']['ship'];

	foreach($fit['cache'][$fit['ship']['typeid']]['effects'] as $effect) {
		if(!isset($effect['preexp'])) {
			trigger_error('eval_ship_preexpressions(): effect '.$effect['effectid'].' has no preexpression!', E_USER_ERROR);
			continue;
		}
		eval_effect_expression_maybe_overriden($fit, $effect, 'pre');
	}

	unset($fit['dogma']['source']);
	unset($fit['dogma']['self']);
}

function eval_ship_postexpressions(&$fit) {
	$fit['dogma']['source'] = array('ship');
	$fit['dogma']['self'] =& $fit['dogma']['ship'];

	foreach($fit['cache'][$fit['ship']['typeid']]['effects'] as $effect) {
		if(!isset($effect['postexp'])) {
			trigger_error('eval_ship_postexpressions(): effect '.$effect['effectid'].' has no postexpression!', E_USER_WARNING);
			continue;
		}
		eval_effect_expression_maybe_overriden($fit, $effect, 'post');
	}

	unset($fit['dogma']['source']);
	unset($fit['dogma']['self']);
}

function eval_skill_preexpressions(&$fit) {
	/* Check if a cached version exists; looping through all skills is expensive */
	if($fit['dogma'] === array() && ($cache = \Osmium\State\get_cache('dogma_all_skills', null)) !== null) {
		$fit['dogma'] = $cache;
		return;
	}


	$typeids = array();
	$q = \Osmium\Db\query('SELECT invskills.typeid FROM osmium.invskills WHERE groupid IN '.USEFUL_SKILLGROUPS);
	while($row = \Osmium\Db\fetch_row($q)) {
		$typeids[] = $row[0];
	}

	\Osmium\Fit\get_attributes_and_effects($typeids, $fit['cache']);

	foreach($typeids as $typeid) {
		foreach($fit['cache'][$typeid]['attributes'] as $attr) {
			$fit['dogma']['skills'][$typeid][$attr['attributename']] = $attr['value'];
		}
		$fit['dogma']['skills'][$typeid]['skillLevel'] = 5;
		$fit['dogma']['skills'][$typeid]['skillPoints'] = 0; /* Stuff breaks if it's not zero */
		$fit['dogma']['skills'][$typeid]['typeid'] = $typeid; /* This may seem redundant, but we need it */

		$fit['dogma']['source'] = array('skill', $typeid);
		$fit['dogma']['self'] =& $fit['dogma']['skills'][$typeid];

		foreach($fit['cache'][$typeid]['effects'] as $effect) {
			eval_effect_expression_maybe_overriden($fit, $effect, 'pre');
		}

		\Osmium\Fit\maybe_remove_cache($fit, $typeid);
	}

	unset($fit['dogma']['source']);
	unset($fit['dogma']['self']);

	\Osmium\State\put_cache('dogma_all_skills', $fit['dogma']);
}

function eval_module_preexpressions(&$fit, $moduletype, $index, array $categories) {
	$fit['dogma']['source'] = array('module', $moduletype, $index);
	$fit['dogma']['self'] =& $fit['dogma']['modules'][$moduletype][$index];
	$fit['dogma']['other'] =& $fit['dogma']['self'];
	/* ^ Doesn't seem logical, but it is needed by
	 * scriptWarpDisruptionFieldGeneratorSetScriptCapacitorNeedHidden
	 * for typeid 4248 */

	foreach($fit['cache'][$fit['modules'][$moduletype][$index]['typeid']]['effects'] as $effect) {
		if(!in_array($fit['cache']['__effects'][$effect['effectname']]['effectcategory'], $categories)) {
			continue;
		}

		if(!isset($effect['preexp'])) {
			trigger_error('eval_module_preexpressions(): effect '.$effect['effectid'].' has no preexpression!', E_USER_WARNING);
			continue;
		}
		eval_effect_expression_maybe_overriden($fit, $effect, 'pre');
	}

	unset($fit['dogma']['source']);
	unset($fit['dogma']['self']);
	unset($fit['dogma']['other']);
}

function eval_module_postexpressions(&$fit, $moduletype, $index, array $categories) {
	$fit['dogma']['source'] = array('module', $moduletype, $index);
	$fit['dogma']['self'] =& $fit['dogma']['modules'][$moduletype][$index];
	$fit['dogma']['other'] =& $fit['dogma']['self'];

	foreach($fit['cache'][$fit['modules'][$moduletype][$index]['typeid']]['effects'] as $effect) {
		if(!in_array($fit['cache']['__effects'][$effect['effectname']]['effectcategory'], $categories)) {
			continue;
		}

		if(!isset($effect['postexp'])) {
			trigger_error('eval_module_postexpressions(): effect '.$effect['effectid'].' has no postexpression!', E_USER_WARNING);
			continue;
		}
		eval_effect_expression_maybe_overriden($fit, $effect, 'post');
	}

	unset($fit['dogma']['source']);
	unset($fit['dogma']['self']);
	unset($fit['dogma']['other']);
}

function eval_charge_preexpressions(&$fit, $presetname, $type, $index) {
	$fit['dogma']['source'] = array('charge', $presetname, $type, $index);
	$fit['dogma']['self'] =& $fit['dogma']['charges'][$presetname][$type][$index];
	$fit['dogma']['other'] =& $fit['dogma']['modules'][$type][$index];

	foreach($fit['cache'][$fit['charges'][$presetname][$type][$index]['typeid']]['effects'] as $effect) {
		if(!isset($effect['preexp'])) {
			trigger_error('eval_charge_preexpressions(): effect '.$effect['effectid'].' has no preexpression!', E_USER_WARNING);
			continue;
		}
		eval_effect_expression_maybe_overriden($fit, $effect, 'pre');
	}

	unset($fit['dogma']['source']);
	unset($fit['dogma']['self']);
	unset($fit['dogma']['other']);
}

function eval_charge_postexpressions(&$fit, $presetname, $type, $index) {
	$fit['dogma']['source'] = array('charge', $presetname, $type, $index);
	$fit['dogma']['self'] =& $fit['dogma']['charges'][$presetname][$type][$index];
	$fit['dogma']['other'] =& $fit['dogma']['modules'][$type][$index];

	foreach($fit['cache'][$fit['charges'][$presetname][$type][$index]['typeid']]['effects'] as $effect) {
		if(!isset($effect['postexp'])) {
			trigger_error('eval_charge_postexpressions(): effect '.$effect['effectid'].' has no preexpression!', E_USER_WARNING);
			continue;
		}
		eval_effect_expression_maybe_overriden($fit, $effect, 'post');
	}

	unset($fit['dogma']['source']);
	unset($fit['dogma']['self']);
	unset($fit['dogma']['other']);
}

function get_ship_attribute(&$fit, $name, $failonerror = true) {
	if($name === 'upgradeLoad') {
		/* Just to make things easy and consistent */
		if(!isset($fit['dogma']['modules']['rig'])) return 0;

		return array_sum(array_map(function($rig) {
					return $rig['upgradeCost'];
				}, $fit['dogma']['modules']['rig'])) ?: 0;
	} else if(in_array($name, array('hiSlots', 'medSlots', 'lowSlots'))) {
		if(isset($fit['dogma']['ship'][$name])) {
			$base = $fit['dogma']['ship'][$name];
		} else {
			$base = 0;
		}

		$name = substr($name, 0, -1);

		if(isset($fit['dogma']['modules']['subsystem'])) {
			foreach($fit['dogma']['modules']['subsystem'] as $subsystem) {
				if(isset($subsystem[$name.'Modifier'])) {
					$base += $subsystem[$name.'Modifier'];
				}
			}
		}

		return $base;
	} else if(in_array($name, array('turretSlots', 'launcherSlots'))) {
		if(isset($fit['dogma']['ship'][$name.'Left'])) {
			$base = $fit['dogma']['ship'][$name.'Left'];
		} else {
			$base = 0;
		}

		$name = substr($name, 0, -5);

		if(isset($fit['dogma']['modules']['subsystem'])) {
			foreach($fit['dogma']['modules']['subsystem'] as $subsystem) {
				if(isset($subsystem[$name.'HardPointModifier'])) {
					$base += $subsystem[$name.'HardPointModifier'];
				}
			}
		}

		return $base;
	}

	return get_final_attribute_value($fit,
	                                 array('name' => $name,
	                                       'source' => array('ship')),
	                                 $failonerror);
}

function get_module_attribute(&$fit, $slottype, $index, $name, $failonerror = true) {
	return get_final_attribute_value($fit,
	                                 array('name' => $name,
	                                       'source' => array('module', $slottype, $index)),
	                                 $failonerror);
}

function get_charge_attribute(&$fit, $preset, $slottype, $index, $name, $failonerror = true) {
	return get_final_attribute_value($fit,
	                                 array('name' => $name,
	                                       'source' => array('charge', $preset, $slottype, $index)),
	                                 $failonerror);
}

function get_final_attribute_value(&$fit, $attribute, $failonerror = true) {
	static $hardcoded = array(
		'cpu OutputBonus' => 'cpuOutputBonus2',
		);

	$name = $attribute['name'];
	$stype = $attribute['source'][0];
	$modifiers = array();

	if($stype == 'ship') {
		$src = $fit['dogma']['ship'];
	} else if($stype == 'self') {
		$src = $fit['dogma']['self'];
	} else if($stype == 'module' || $stype == 'charge') {
		if($stype == 'module') {
			list(, $type, $index) = $attribute['source'];
			$src = $fit['dogma']['modules'][$type][$index];
		} else if($stype == 'charge') {
			list(, $preset, $type, $index) = $attribute['source'];
			$src = $fit['dogma']['charges'][$preset][$type][$index];
		}

		$typeid = $src['typeid'];
		for($i = 1; $i <= 6; ++$i) {
			if(!isset($src['requiredSkill'.$i])) continue;
			$requiresskillid = $src['requiredSkill'.$i];

			if(isset($fit['dogma']['ship']['__modifiers']['__requires_skill'][$requiresskillid][$name])) {
				$modifiers = array_merge_recursive($modifiers,
				                                   $fit['dogma']['ship']['__modifiers']
				                                   ['__requires_skill'][$requiresskillid][$name]);
			}
			if(isset($fit['dogma']['char']['__modifiers']['__requires_skill'][$requiresskillid][$name])) {
				$modifiers = array_merge_recursive($modifiers,
				                                   $fit['dogma']['char']['__modifiers']
				                                   ['__requires_skill'][$requiresskillid][$name]);
			}
		}

		$groupid = $fit['cache'][$typeid]['groupid'];
		if(isset($fit['dogma']['ship']['__modifiers']['__group'][$groupid][$name])) {
			$modifiers = array_merge_recursive($modifiers,
			                                   $fit['dogma']['ship']['__modifiers']
			                                   ['__group'][$groupid][$name]);
		}
		if(isset($fit['dogma']['char']['__modifiers']['__group'][$groupid][$name])) {
			$modifiers = array_merge_recursive($modifiers,
			                                   $fit['dogma']['char']['__modifiers']
			                                   ['__group'][$groupid][$name]);
		}
	} else if($stype == 'skill') {
		list(, $typeid) = $attribute['source'];
		$src = $fit['dogma']['skills'][$typeid];
	} else {
		trigger_error('get_final_attribute_value(): unknown source type ("'.$stype.'")', E_USER_ERROR);
	}

	if(isset($src['__modifiers'][$name])) {
		$modifiers = array_merge_recursive($modifiers, $src['__modifiers'][$name]);
	}

	if(!isset($src[$name])) {
		if(!isset($hardcoded[$name])) {
			/* Try to fetch the defaultvalue from the DB */
			$val = \Osmium\State\get_cache('default_value_'.$name, 'undefined');
			if($val === 'undefined') {
				$row = \Osmium\Db\fetch_row(
					\Osmium\Db\query_params(
						'SELECT defaultvalue FROM eve.dgmattribs WHERE attributename = $1', 
						array($name)));

				if($row !== false) {
					$val = $row[0];
				} else {
					$val = null;
				}

				\Osmium\State\put_cache('default_value_'.$name, $val);
			}

			if($val === null) $val = 0;
			if($val === null && $failonerror) {
				trigger_error('get_final_attribute_value(): '.$name.' not defined', E_USER_WARNING);
			}
		} else {
			$name = $hardcoded[$name];
			$val = $src[$name];
		}
	} else {
		$val = $src[$name];
	}

	$stackable = isset($fit['cache']['__attributes'][$name]['stackable']) ?
		$fit['cache']['__attributes'][$name]['stackable'] : 1;
	$highisgood = isset($fit['cache']['__attributes'][$name]['highisgood']) ?
		$fit['cache']['__attributes'][$name]['highisgood'] : 1;

	return apply_modifiers($fit, $modifiers, $val, $stackable, $highisgood);
}

function apply_modifiers(&$fit, $modifiers, $base_value, $stackable, $highisgood) {
	/* Evaluation order generously "stolen" from:
	 * https://github.com/DarkFenX/Eos/blob/master/fit/attributeCalculator/map.py#L42 */
	static $actions = null;
	/* Ugly hack due to a PHP parser limitation… */
	if($actions === null) $actions = array(
		'preassignment'  => function(&$v, $m) { $v = $m; },
		'premul'         => function(&$v, $m) { $v *= $m; },
		'prediv'         => function(&$v, $m) { $v /= $m; },
		'modadd'         => function(&$v, $m) { $v += $m; },
		'modsub'         => function(&$v, $m) { $v -= $m; },
		'postmul'        => function(&$v, $m) { $v *= $m; },
		'postdiv'        => function(&$v, $m) { $v /= $m; },
		'postpercent'    => function(&$v, $m) { $v *= (1.00 + 0.01 * $m); },
		'postassignment' => function(&$v, $m) { $v = $m; },
		);

	static $penaltygroups = array(
		'premul' => true,
		'postmul' => true,
		'postpercent' => true,
		'prediv' => true,
		'postdiv' => true
		);

	static $immunetopenalty = null;
	if($immunetopenalty === null) $immunetopenalty = function($attr) {
		return $attr['source'][0] != 'module' || $attr['source'][1] == 'subsystem';
	};

	/* TODO: optimize stuff if we have a postassignment (skip everything before) */

	foreach($actions as $name => $func) {
		if(isset($modifiers[$name])) {
			foreach($modifiers[$name] as $type => $a) {
				$penalize = array();

				foreach($a as $attr) {
					if($stackable || $immunetopenalty($attr)) {
						$func($base_value, get_final_attribute_value($fit, $attr));
					} else {
						$penalize[] = get_final_attribute_value($fit, $attr);
					}
				}

				$penalized = penalize($penalize, $highisgood);
				foreach($penalized as $v) {
					$func($base_value, $v);
				}
			}
		}
	}

	return $base_value;
}

function penalize($values, $highisgood) {
	if($values === array()) return array();

	/* Taken from Aenigma's guide: http://eve.battleclinic.com/guide/9196-Aenigma-s-Stacking-Penalty-Guide.html */
	static $penaltymultiplier = array(
		1.000000000000,
		0.869119980800,
		0.570583143511,
		0.282955154023,
		0.105992649743,
		0.029991166533,
		0.006410183118,
		0.001034920483,
		0.000126212683,
		0.000011626754,
		0.000000809046,
		);

	$positive = array();
	$negative = array();

	foreach($values as &$v) {
		$v -= 1;

		if($v >= 0) $positive[] = $v;
		else $negative[] = $v;
	}

	if($highisgood) {
		rsort($positive);
		rsort($negative);
	} else {
		sort($positive);
		sort($negative);
	}

	$out = array();
	foreach($positive as $i => $v) {
		if(!isset($penaltymultiplier[$i])) continue;
		$out[] = (1 + $v * $penaltymultiplier[$i]);
	}
	foreach($negative as $i => $v) {
		if(!isset($penaltymultiplier[$i])) continue;
		$out[] = (1 + $v * $penaltymultiplier[$i]);
	}

	return $out;
}

function &traverse_nested(&$fit, $subarrays) {
	$res =& $fit['dogma'];
	foreach($subarrays as $k => $v) {
		if($k === 'source') continue;

		$res =& $res[$v];
	}
	return $res;
}

function insert_nested(&$fit, $subarrays, $element, $key = null) {
	$res =& traverse_nested($fit, $subarrays);
	if($key === null) $res[] = $element;
	else if($key === false) $res = $element;
	else $res[$key] = $element;
}

function remove_nested(&$fit, $subarrays, $element) {
	$res =& traverse_nested($fit, $subarrays);
	foreach($res as $i => $val) {
		if($val['name'] === $element['name'] && $val['source'] == $element['source']) {
			unset($res[$i]);
			return;
		}
	}

	trigger_error('remove_nested(): element '.$element['name'].' not found', E_USER_WARNING);
}

function insert_modifier(&$fit, $exp, $name) {
	$path = (array)eval_expression($fit, $exp['arg1']);

	insert_nested($fit,
	              array_merge(array(array_shift($path), '__modifiers'), 
	                          $path,
	                          array($name)),
	              eval_expression($fit, $exp['arg2']));
	
}

function remove_modifier(&$fit, $exp, $name) {
	$path = (array)eval_expression($fit, $exp['arg1']);
	
	remove_nested($fit,
	              array_merge(array(array_shift($path), '__modifiers'),
	                          $path,
	                          array($name)),
	              eval_expression($fit, $exp['arg2']));
}

function operate_on_attribute(&$fit, $exp, $func) {
	$arg1 = eval_expression($fit, $exp['arg1']);
	if($arg1[0] == 'target') {
		/* We don't care about that */
	} else if($arg1[0] == 'ship') {
		$attribute = eval_expression($fit, $exp['arg2']);
		$val =& traverse_nested($fit, $arg1);
		$func($val, $fit['dogma']['self'][$attribute['name']]);
	} else {
		trigger_error('eval_inc(): unhandled arg1 ('.$k.')', E_USER_ERROR);
	}
}

/* ----------------------------------------------------- */

function eval_expression(&$fit, $expression) {
	$funcname = __NAMESPACE__.'\\eval_'.strtolower($expression['op']);
	return $funcname($fit, $expression);
}

function eval_add(&$fit, $exp) {
	return eval_expression($fit, $exp['arg1']) + eval_expression($fit, $exp['arg2']);
}

function eval_agim(&$fit, $exp) {
	insert_modifier($fit, $exp, 'gang_ship');
}

function eval_agrsm(&$fit, $exp) {
	insert_modifier($fit, $exp, 'gang_required_skill');
}

function eval_aim(&$fit, $exp) {
	insert_modifier($fit, $exp, 'item');
}

function eval_algm(&$fit, $exp) {
	insert_modifier($fit, $exp, 'location_group');
}

function eval_alm(&$fit, $exp) {
	insert_modifier($fit, $exp, 'location');
}

function eval_alrsm(&$fit, $exp) {
	insert_modifier($fit, $exp, 'location_required_skill');
}

function eval_and(&$fit, $exp) {
	return eval_expression($fit, $exp['arg1']) && eval_expression($fit, $exp['arg2']);
}

function eval_aorsm(&$fit, $exp) {
	insert_modifier($fit, $exp, 'owner_required_skill');
}

function eval_att(&$fit, $exp) {
	return array_merge((array)eval_expression($fit, $exp['arg1']), (array)eval_expression($fit, $exp['arg2']));
}

function eval_attack(&$fit, $exp) {}

function eval_cargoscan(&$fit, $exp) {}

function eval_combine(&$fit, $exp) {
	eval_expression($fit, $exp['arg1']);
	return eval_expression($fit, $exp['arg2']);
}

function eval_dec(&$fit, $exp) {
	operate_on_attribute($fit, $exp, function(&$v, $m) { $v -= $m; });
}

function eval_defassociation(&$fit, $exp) {
	return strtolower($exp['value']);
}

function eval_defattribute(&$fit, $exp) {
	$name = lcfirst($exp['name']);
	return array('name' => $name, 'source' => $fit['dogma']['source']);
}

function eval_defbool(&$fit, $exp) {
	return (bool)$exp['value'];
}

function eval_defenvidx(&$fit, $exp) {
	return strtolower($exp['value']);
}

function eval_defgroup(&$fit, $exp) {
	static $hardcoded = array(
		'EnergyWeapon' => 53,
		'HybridWeapon' => 74,
		'ProjectileWeapon' => 55,
		'    None' => 0,
		);

	if(!isset($exp['groupid'])) {
		if(!isset($hardcoded[$exp['value']])) {
			trigger_error('eval defgroup(): no groupID given for "'.$exp['value'].'"', E_USER_ERROR);
		}

		return $hardcoded[$exp['value']];
	}

	return intval($exp['groupid']);
}

function eval_defint(&$fit, $exp) {
	return intval($exp['value']);
}

function eval_defstring(&$fit, $exp) {
	return $exp['value'];
}

function eval_deftypeid(&$fit, $exp) {
	static $hardcoded = array(
		'Shield Emission Systems' => 3422,
		'Energy Emission Systems' => 3423,
		);

	if(!isset($exp['typeid'])) {
		if(!isset($hardcoded[$exp['value']])) {
			trigger_error('eval_deftypeid(): no typeID given for "'.$exp['value'].'"', E_USER_ERROR);
		}

		return $hardcoded[$exp['value']];
	}

	return intval($exp['typeid']);
}

function eval_ecmburst(&$fit, $exp) {}

function eval_eff(&$fit, $exp) {
	return array_merge((array)eval_expression($fit, $exp['arg2']), (array)eval_expression($fit, $exp['arg1']));
}

function eval_empwave(&$fit, $exp) {}

function eval_get(&$fit, $exp) {
	$context = eval_expression($fit, $exp['arg1']);
	$attribute = eval_expression($fit, $exp['arg2']);

	$name = $attribute['name'];

	if($context == 'ship') {
		return get_final_attribute_value($fit, array('name' => $name, 'source' => array('ship')));
	} else if($context == 'self') {
		return get_final_attribute_value($fit, array('name' => $name, 'source' => $fit['dogma']['source']));
	} else {
		trigger_error('eval_get(): does not know what to do! ($context = '.$context.')', E_USER_ERROR);
	}
}

function eval_gettype(&$fit, $exp) {
	$context = eval_expression($fit, $exp['arg1']);

	if($context == 'ship') {
		return $fit['dogma']['ship']['typeid'];
	} else if($context == 'self') {
		return $fit['dogma']['self']['typeid'];
	} else {
		trigger_error('eval_gettype(): does not know what to do! ($context = '.$context.')', E_USER_ERROR);
	}
}

function eval_gt(&$fit, $exp) {
	return eval_expression($fit, $exp['arg1']) > eval_expression($fit, $exp['arg2']);
}

function eval_gte(&$fit, $exp) {
	return eval_expression($fit, $exp['arg1']) >= eval_expression($fit, $exp['arg2']);
}

function eval_ia(&$fit, $exp) {
	return eval_expression($fit, $exp['arg1']);
}

function eval_if(&$fit, $exp) {
	$cond = eval_expression($fit, $exp['arg1']);
	/* Always assume $cond is true, even when it's not, because we want to
	 * allow overflowing stuff */
	eval_expression($fit, $exp['arg2']);
	return $cond;
}

function eval_inc(&$fit, $exp) {
	operate_on_attribute($fit, $exp, function(&$v, $m) { $v += $m; });
}

function eval_launch(&$fit, $exp) {}

function eval_launchdefendermissile(&$fit, $exp) {}

function eval_launchfofmissile(&$fit, $exp) {}

function eval_lg(&$fit, $exp) {
	return array_merge((array)eval_expression($fit, $exp['arg1']), 
	                   array('__group'), 
	                   (array)eval_expression($fit, $exp['arg2']));
}

function eval_ls(&$fit, $exp) {
	return array_merge((array)eval_expression($fit, $exp['arg1']), 
	                   array('__requires_skill'), 
	                   (array)eval_expression($fit, $exp['arg2']));
}

function eval_mine(&$fit, $exp) {}

function eval_or(&$fit, $exp) {
	return eval_expression($fit, $exp['arg1']) || eval_expression($fit, $exp['arg2']);
}

function eval_powerboost(&$fit, $exp) {}

function eval_rgim(&$fit, $exp) {
	remove_modifier($fit, $exp, 'gang_ship');
}

function eval_rgrsm(&$fit, $exp) {
	remove_modifier($fit, $exp, 'gang_required_skill');
}

function eval_rim(&$fit, $exp) {
	remove_modifier($fit, $exp, 'item');
}

function eval_rlgm(&$fit, $exp) {
	remove_modifier($fit, $exp, 'location_group');
}

function eval_rlm(&$fit, $exp) {
	remove_modifier($fit, $exp, 'location');
}

function eval_rlrsm(&$fit, $exp) {
	remove_modifier($fit, $exp, 'location_required_skill');
}

function eval_rorsm(&$fit, $exp) {
	remove_modifier($fit, $exp, 'owner_required_skill');
}

function eval_rsa(&$fit, $exp) {
	return array_merge((array)eval_expression($fit, $exp['arg1']), 
	                   array('__requires_skill'),
	                   (array)eval_expression($fit, $exp['arg2']));
}

function eval_set(&$fit, $exp) {
	$context = eval_expression($fit, $exp['arg1']);
	$value = eval_expression($fit, $exp['arg2']);

	if($context[0] != 'self' && $context[0] != 'ship') {
		trigger_error('eval_set(): unknown context ('.$context[0].')', E_USER_ERROR);
	}
	insert_nested($fit, $context, $value, false);
}

function eval_shipscan(&$fit, $exp) {}

function eval_skillcheck(&$fit, $exp) {
	return true;
}

function eval_surveyscan(&$fit, $exp) {}

function eval_targethostiles(&$fit, $exp) {}

function eval_targetsilently(&$fit, $exp) {}

function eval_tooltargetskills(&$fit, $exp) {}

function eval_ue(&$fit, $exp) {
	$fit['dogma']['__errors'][] = eval_expression($fit, $exp['arg1']);
}

function eval_verifytargetgroup(&$fit, $exp) {}

/* ----------------------------------------------------- */

function get_expression_uncached($expressionid) {
	if($expressionid === null) return null;

	/* Assume we have dgmexpressions and dgmoperands. */
	$row = \Osmium\Db\fetch_row(\Osmium\Db\query_params('SELECT operandkey, arg1, arg2, expressionvalue, expressionname, expressiontypeid, expressiongroupid, expressionattributeid FROM eve.dgmexpressions JOIN eve.dgmoperands ON dgmoperands.operandid = dgmexpressions.operandid WHERE expressionid = $1', array($expressionid)));
	if($row === false) return 'NOEXPRESSION';

	list($operandkey, $arg1, $arg2, $value, $name, $typeid, $groupid, $attributeid) = $row;
	$r = array(
		'op' => $operandkey, 
		'value' => $value,
		'name' => $name,
		'typeid' => $typeid,
		'groupid' => $groupid,
		'attributeid' => $attributeid,
		'arg1' => get_expression_uncached($arg1),
		'arg2' => get_expression_uncached($arg2),
		);

	if($r['arg1'] === null) unset($r['arg1']);
	if($r['arg2'] === null) unset($r['arg2']);
	if($r['value'] === null) unset($r['value']);
	if($r['name'] === null
	   || $r['op'] !== 'DEFATTRIBUTE') unset($r['name']);
	if($r['typeid'] === null) unset($r['typeid']);
	if($r['groupid'] === null) unset($r['groupid']);
	if($r['attributeid'] === null) unset($r['attributeid']);

	return $r;
}
