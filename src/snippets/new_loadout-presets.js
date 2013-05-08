/* Osmium
 * Copyright (C) 2012, 2013 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

osmium_init_presets = function() {
	$('section#presets textarea#tpresetdesc').change(function() {
		osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']].presetdescription = $(this).val();
		osmium_commit_clf();
	});
	$('section#presets textarea#tcpresetdesc').change(function() {
		var p = osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']].chargepresets;
		var cpid = osmium_clf['X-Osmium-current-chargepresetid'];
		for(var i = 0; i < p.length; ++i) {
			if(p[i].id != cpid) continue;
			p[i].description = $(this).val();
			break;
		}
		osmium_commit_clf();
	});
	$('section#presets textarea#tdpresetdesc').change(function() {
		osmium_clf.drones[osmium_clf['X-Osmium-current-dronepresetid']].presetdescription = $(this).val();
		osmium_commit_clf();
	});

	$('section#presets tr#rpresets select#spreset').change(function() {
		osmium_clf['X-Osmium-current-presetid'] = $(this).val();
		$('section#presets textarea#tpresetdesc').val(
			osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']].presetdescription
		);

		osmium_clf['X-Osmium-current-chargepresetid'] =
			osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']].chargepresets[0].id;

		osmium_gen_charge_presets_only();
		osmium_commit_clf();
		osmium_post_preset_change();
	});
	$('section#presets tr#rchargepresets select#scpreset').change(function() {
		osmium_clf['X-Osmium-current-chargepresetid'] = $(this).val();

		var cps = osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']].chargepresets;
		for(var i = 0; i < cps.length; ++i) {
			if(cps[i].id == osmium_clf['X-Osmium-current-chargepresetid']) {
				$('section#presets textarea#tcpresetdesc').val(cps[i].description);
				break;
			}
		}

		osmium_commit_clf();
		osmium_post_preset_change();
	});
	$('section#presets tr#rdronepresets select#sdpreset').change(function() {
		osmium_clf['X-Osmium-current-dronepresetid'] = $(this).val();
		$('section#presets textarea#tdpresetdesc').val(
			osmium_clf.drones[osmium_clf['X-Osmium-current-dronepresetid']].presetdescription
		);
		osmium_commit_clf();
	});

	var preset_name_exists = function(name) {
		for(var i = 0; i < osmium_clf.presets.length; ++i) {
			if(osmium_clf.presets[i].presetname === name) return true;
		}
		return false;
	};
	$('section#presets tr#rpresets input.createpreset').click(function() {
		var name = osmium_get_unique_preset_name('New preset', preset_name_exists);
		name = prompt('Enter the new preset name:', name);
		if(name !== null) {
			name = osmium_get_unique_preset_name(name, preset_name_exists);
			var id = osmium_clf.presets.push({
				presetname: name,
				presetdescription: '',
				modules: [],
				implants: [],
				boosters: [],
				chargepresets: [{
					id: 0,
					name: 'Default charge preset'
				}]
			}) - 1;
			osmium_clf['X-Osmium-current-presetid'] = id;
			osmium_clf['X-Osmium-current-chargepresetid'] = 0;
			osmium_gen_presets_only();
			osmium_commit_clf();
			osmium_post_preset_change();
		}
	});
	$('section#presets tr#rpresets input.renamepreset').click(function() {
		var name = osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']].presetname;
		name = prompt('Enter the new preset name:', name);
		if(name !== null) {
			name = osmium_get_unique_preset_name(name, preset_name_exists);
			osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']].presetname = name;
			osmium_gen_presets_only();
			osmium_commit_clf();
		}
	});
	$('section#presets tr#rpresets input.clonepreset').click(function() {
		var name = osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']].presetname;
		var id = osmium_clf.presets.push($.extend(
			true, {},
			osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']]
		)) - 1;
		osmium_clf.presets[id].presetname = osmium_get_unique_clone_name(name, preset_name_exists);
		osmium_clf['X-Osmium-current-presetid'] = id;
		osmium_gen_presets_only();
		osmium_commit_clf();
		osmium_post_preset_change();
	});
	$('section#presets tr#rpresets input.deletepreset').click(function() {
		osmium_clf.presets.splice(osmium_clf['X-Osmium-current-presetid'], 1);
		if(osmium_clf['X-Osmium-current-presetid'] >= osmium_clf.presets.length) {
			osmium_clf['X-Osmium-current-presetid'] = osmium_clf.presets.length - 1;
		}
		osmium_clf['X-Osmium-current-chargepresetid'] = 
			osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']].chargepresets[0].id;
		osmium_gen_presets_only();
		osmium_commit_clf();
		osmium_post_preset_change();
	});

	var charge_preset_name_exists = function(name) {
		var cps = osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']].chargepresets;
		for(var i = 0; i < cps.length; ++i) {
			if(cps[i].name === name) return true;
		}
		return false;
	};
	$('section#presets tr#rchargepresets input.createpreset').click(function() {
		var name = osmium_get_unique_preset_name('New charge preset', charge_preset_name_exists);
		name = prompt('Enter the new preset name:', name);
		if(name !== null) {
			name = osmium_get_unique_preset_name(name, charge_preset_name_exists);
			var cps = osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']].chargepresets;
			var id = 0;
			for(var i = 0; i < cps.length; ++i) {
				if(cps[i].id >= id) {
					id = cps[i].id + 1;
				}
			}
			cps.push({
				id: id,
				name: name,
				description: ''
			});
			osmium_clf['X-Osmium-current-chargepresetid'] = id;
			osmium_gen_charge_presets_only();
			osmium_commit_clf();
			osmium_post_preset_change();
		}
	});
	$('section#presets tr#rchargepresets input.renamepreset').click(function() {
		var cps = osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']].chargepresets;
		var name;
		for(var i = 0; i < cps.length; ++i) {
			if(cps[i].id == osmium_clf['X-Osmium-current-chargepresetid']) {
				name = cps[i].name;
				break;
			}
		}
		name = prompt('Enter the new preset name:', name);
		if(name !== null) {
			name = osmium_get_unique_preset_name(name, charge_preset_name_exists);
			cps[i].name = name;
			osmium_gen_charge_presets_only();
			osmium_commit_clf();
		}
	});
	$('section#presets tr#rchargepresets input.clonepreset').click(function() {
		var cps = osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']].chargepresets;
		var cp;
		var id = 0;
		for(var i = 0; i < cps.length; ++i) {
			if(cps[i].id == osmium_clf['X-Osmium-current-chargepresetid']) {
				cp = cps[i];
			}
			if(cps[i].id >= id) {
				id = cps[i].id + 1;
			}
		}
		var index = cps.push($.extend(true, {}, cp)) - 1;
		var oldcpid = osmium_clf['X-Osmium-current-chargepresetid'];
		osmium_clf['X-Osmium-current-chargepresetid'] = id;
		cps[index].id = id;
		cps[index].name = osmium_get_unique_clone_name(cp.name, charge_preset_name_exists);

		for(var i = 0; i < osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']]
			.modules.length; ++i) {
			var m = osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']]
				.modules[i];
			if(!("charges" in m)) continue;

			for(var j = 0; j < m.charges.length; ++j) {
				if(m.charges[j].cpid === oldcpid) {
					osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']]
						.modules[i].charges.push({
							cpid: id,
							typeid: m.charges[j].typeid
						});
					break;
				}
			}
		}

		osmium_gen_charge_presets_only();
		osmium_commit_clf();
		osmium_post_preset_change();
	});
	$('section#presets tr#rchargepresets input.deletepreset').click(function() {
		var cps = osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']].chargepresets;
		var index;
		for(var i = 0; i < cps.length; ++i) {
			if(cps[i].id == osmium_clf['X-Osmium-current-chargepresetid']) {
				index = i;
				break;
			}
		}
		cps.splice(i, 1);
		osmium_clf['X-Osmium-current-chargepresetid'] = cps[0].id;
		osmium_gen_charge_presets_only();
		osmium_commit_clf();
		osmium_post_preset_change();
	});


	var drone_preset_name_exists = function(name) {
		for(var i = 0; i < osmium_clf.drones.length; ++i) {
			if(osmium_clf.drones[i].presetname === name) return true;
		}
		return false;
	};
	$('section#presets tr#rdronepresets input.createpreset').click(function() {
		var name = osmium_get_unique_preset_name('New drone preset', drone_preset_name_exists);
		name = prompt('Enter the new preset name:', name);
		if(name !== null) {
			name = osmium_get_unique_preset_name(name, drone_preset_name_exists);
			var id = osmium_clf.drones.push({
				presetname: name,
				presetdescription: '',
				inbay: [],
				inspace: []
			}) - 1;
			osmium_clf['X-Osmium-current-dronepresetid'] = id;
			osmium_gen_drone_presets_only();
			osmium_commit_clf();
		}
	});
	$('section#presets tr#rdronepresets input.renamepreset').click(function() {
		var name = osmium_clf.drones[osmium_clf['X-Osmium-current-dronepresetid']].presetname;
		name = prompt('Enter the new preset name:', name);
		if(name !== null) {
			name = osmium_get_unique_preset_name(name, drone_preset_name_exists);
			osmium_clf.drones[osmium_clf['X-Osmium-current-dronepresetid']].presetname = name;
			osmium_gen_drone_presets_only();
			osmium_commit_clf();
		}
	});
	$('section#presets tr#rdronepresets input.clonepreset').click(function() {
		var name = osmium_clf.drones[osmium_clf['X-Osmium-current-dronepresetid']].presetname;
		var id = osmium_clf.drones.push($.extend(
			true, {}, 
			osmium_clf.drones[osmium_clf['X-Osmium-current-dronepresetid']]
		)) - 1;
		osmium_clf.drones[id].presetname = osmium_get_unique_clone_name(name, drone_preset_name_exists);
		osmium_clf['X-Osmium-current-dronepresetid'] = id;
		osmium_gen_drone_presets_only();
		osmium_commit_clf();
	});
	$('section#presets tr#rdronepresets input.deletepreset').click(function() {
		osmium_clf.drones.splice(osmium_clf['X-Osmium-current-dronepresetid'], 1);
		if(osmium_clf['X-Osmium-current-dronepresetid'] >= osmium_clf.drones.length) {
			osmium_clf['X-Osmium-current-dronepresetid'] = osmium_clf.drones.length - 1;
		}
		osmium_gen_drone_presets_only();
		osmium_commit_clf();
	});
};

osmium_get_unique_preset_name = function(base, exists) {
	if(!exists(base)) {
		return base;
	} else {
		var i = 2;
		while(exists(base + " #" + i)) {
			++i;
		}
		return base + " #" + i;
	}
};

osmium_get_unique_clone_name = function(base, exists) {
	base = base.replace(/^(Clone of )+/, '').replace(/ #([2-9]|[1-9][0-9]+)$/, '');
	return osmium_get_unique_preset_name('Clone of ' + base, exists);
};

osmium_gen_presets = function() {
	osmium_gen_presets_only();
	osmium_gen_drone_presets_only();
};

osmium_gen_presets_only = function() {
	var p, option;
	var select = $('section#presets select#spreset');

	select.empty();
	for(var i = 0; i < osmium_clf.presets.length; ++i) {
		p = osmium_clf.presets[i];
		option = $(document.createElement('option'));
		option.prop('value', i);
		option.text(p.presetname);
		select.append(option);
	}
	select.val(osmium_clf['X-Osmium-current-presetid']);
	p = osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']];
	$('section#presets textarea#tpresetdesc').val("presetdescription" in p ? p.presetdescription : '');
	$('section#presets tr#rpresets input.deletepreset').prop('disabled', osmium_clf.presets.length < 2);

	osmium_gen_charge_presets_only();
};

osmium_gen_charge_presets_only = function() {
	var option, cp;
	var p = osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']];
	var select = $('section#presets select#scpreset');

	select.empty();
	for(var i = 0; i < p.chargepresets.length; ++i) {
		option = $(document.createElement('option'));
		option.prop('value', p.chargepresets[i].id);
		option.text(p.chargepresets[i].name);
		select.append(option);
	}
	select.val(osmium_clf['X-Osmium-current-chargepresetid']);
	for(var i = 0; i < p.chargepresets.length; ++i) {
		if(p.chargepresets[i].id == osmium_clf['X-Osmium-current-chargepresetid']) {
			cp = p.chargepresets[i];
			break;
		}
	}
	$('section#presets textarea#tcpresetdesc').val("description" in cp ? cp.description : '');
	$('section#presets tr#rchargepresets input.deletepreset').prop('disabled', p.chargepresets.length < 2);
};

osmium_gen_drone_presets_only = function() {
	var p, option;
	var select = $('section#presets select#sdpreset');

	select.empty();
	for(var i = 0; i < osmium_clf.drones.length; ++i) {
		p = osmium_clf.drones[i];
		option = $(document.createElement('option'));
		option.prop('value', i);
		option.text(p.presetname);
		select.append(option);
	}
	select.val(osmium_clf['X-Osmium-current-dronepresetid']);
	p = osmium_clf.drones[osmium_clf['X-Osmium-current-dronepresetid']];
	$('section#presets textarea#tdpresetdesc').val("presetdescription" in p ? p.presetdescription : '');
	$('section#presets tr#rdronepresets input.deletepreset').prop('disabled', osmium_clf.drones.length < 2);	
};

osmium_post_preset_change = function() {
	osmium_user_initiated_push(false);
	osmium_gen_modules();
	osmium_user_initiated_pop();
};
