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

osmium_gen_modules = function() {
	var availslots = ("ship" in osmium_clf) ? osmium_ship_slots[osmium_clf.ship.typeid] : [0, 0, 0, 0, 0];
	var p = osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']];
	var m, i, z, type;

	availslots = {
		high: availslots[0],
		medium: availslots[1],
		low: availslots[2],
		rig: availslots[3],
		subsystem: availslots[4]
	};

	$('section#modules > div.slots > ul').empty();

	if(!("modules" in p)) p.modules = [];

	for(i = 0; i < p.modules.length; ++i) {
		m = p.modules[i];
		availslots[osmium_types[m.typeid][3]]--;
		osmium_add_module(m.typeid, m.index, m.state);
	}

	z = 0;

	for(type in availslots) {
		$('section#modules > div.slots.' + type).data('type', type).data('type-index', z++);
	}

	$('section#modules > div.slots').each(function() {
		osmium_post_update_module($(this));
	});
};

osmium_init_modules = function() {

};

osmium_maybe_hide_slot_type = function(slotsdiv) {
	if(slotsdiv.children('ul').find('li').length > 0) {
		slotsdiv.show();
	} else {
		slotsdiv.hide();
	}
};

osmium_update_overflow = function(slotsdiv) {
	var smallcount = slotsdiv.find('h3 > span > small');
	var used = slotsdiv.children('ul').children('li').not('.placeholder').length;
	var total = ("ship" in osmium_clf) ?
		osmium_ship_slots[osmium_clf.ship.typeid][slotsdiv.data('type-index')]
		: 0;

	smallcount.text(used + '/' + total);

	var placeholders = slotsdiv.find('li.placeholder').length;
	var ideal = total - used;
	while(placeholders < ideal) {
		osmium_add_placeholder_module(slotsdiv);
		++placeholders;
	}
	while(placeholders > ideal) {
		slotsdiv.find('li.placeholder').last().remove();
		--placeholders;
	}

	if(used > total) {
		smallcount.addClass('overflow');
		slotsdiv.children('ul').children('li').slice(total - used).addClass('overflow');
	} else {
		smallcount.removeClass('overflow');
	}
};

osmium_add_module = function(typeid, index, state) {
	var m = osmium_types[typeid];
	var div = $('section#modules > div.' + m[3]);
	var ul = div.children('ul');
	var placeholders = ul.children('li.placeholder');
	var li, img, a, stateimg;
	var stateful;

	if(state === null) {
		if(osmium_module_states[typeid][2]) {
			/* Active state, if possible */
			state = osmium_states[2];
		} else if(osmium_module_states[typeid][1]) {
			/* Online state */
			state = osmium_states[1];
		} else {
			/* Offline state */
			state = osmium_states[0];
		}
	}

	li = $(document.createElement('li'));
	li.data('typeid', typeid);
	li.data('slottype', m[3]);
	li.data('index', index);
	li.data('state', state);
	li.text(m[1]);

	img = $(document.createElement('img'));
	img.prop('src', '//image.eveonline.com/Type/' + typeid + '_64.png');

	li.prepend(img);

	if(stateful = ($.inArray(m[3], osmium_stateful_slot_types) !== -1)) {
		a = $(document.createElement('a'));
		stateimg = $(document.createElement('img'));
		a.addClass('toggle_state');
		a.prop('href', 'javascript:void(0);');
		stateimg.prop('alt', '');
		a.append(stateimg);
		li.append(a);

		li.on('state_changed', function() {
			var a = li.children('a.toggle_state');
			var stateimg = a.children('img');
			var state = li.data('state');

			a.prop('title', osmium_module_state_names[state][0]);
			stateimg.prop('alt', a.prop('title'));
			stateimg.prop('src', osmium_relative + '/static-' + osmium_staticver
						  + '/icons/' + osmium_module_state_names[state][1]);
		}).trigger('state_changed');

		a.on('click', function() {
			var a = $(this);
			var li = a.parent();
			var state = li.data('state');
			var nextstate;

			a.blur();

			if(state === 'offline') {
				nextstate = (osmium_module_states[typeid][1] ? 'online' : 'offline');
			} else if(state === 'online') {
				nextstate = (osmium_module_states[typeid][2] ? 'active' : 'offline');
			} else if(state === 'active') {
				nextstate = (osmium_module_states[typeid][3] ? 'overloaded' : 'offline');
			} else if(state === 'overloaded') {
				nextstate = 'offline';
			}

			osmium_set_module_state(li, nextstate);
			return false;
		}).on('contextmenu', function() {
			var a = $(this);
			var li = a.parent();
			var state = li.data('state');
			var prevstate;

			a.blur();

			if(state === 'offline') {
				if(osmium_module_states[typeid][3]) {
					prevstate = 'overloaded';
				} else if(osmium_module_states[typeid][2]) {
					prevstate = 'active';
				} else if(osmium_module_states[typeid][1]) {
					prevstate = 'online';
				} else {
					prevstate = 'offline';
				}
			} else if(state === 'online') {
				prevstate = 'offline';
			} else if(state === 'active') {
				prevstate = 'online';
			} else if(state === 'overloaded') {
				prevstate = 'active';
			}

			osmium_set_module_state(li, prevstate);
			return false;
		}).on('dblclick', function(e) {
			/* Prevent dblclick fire on the <li> itself */

			e.stopPropagation();
			return false;
		});
	}

	if(placeholders.length > 0) {
		placeholders.first().before(li);
	} else {
		ul.append(li);
	}

	if(osmium_user_initiated) {
		$('a[href="#modules"]').parent().click();
		li.addClass('added_to_loadout');
	}

	osmium_ctxmenu_bind(li, function() {
		var menu = osmium_ctxmenu_create();

		osmium_ctxmenu_add_option(menu, "Unfit module", function() {
			var modules = osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']].modules;
			for(var i = 0; i < modules.length; ++i) {
				if(modules[i].index === index && modules[i].typeid === typeid) {
					osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']].modules.splice(i, 1);
					break;
				}
			}

			li.remove();
			osmium_commit_clf();
			osmium_post_update_module(div);
		}, { default: true });

		osmium_ctxmenu_add_option(menu, "Unfit all of the same type", function() {
			var typeid = li.data('typeid');

			for(var i = 0;
				i < osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']].modules.length;
				++i) {

				if(osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']].modules[i].typeid
				   === typeid) {
					osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']].modules.splice(i, 1);
					--i;
				}
			}

			ul.find('li').filter(function() {
				return $(this).data('typeid') === typeid;
			}).remove();

			osmium_commit_clf();
			osmium_post_update_module(div);
		}, {});

		if(stateful) {
			osmium_ctxmenu_add_separator(menu);

			if(osmium_module_states[typeid][0]) {
				osmium_ctxmenu_add_option(menu, "Offline module", function() {
					osmium_set_module_state(li, "offline");
				}, { icon: osmium_module_state_names['offline'][1] });
			}
			if(osmium_module_states[typeid][1]) {
				osmium_ctxmenu_add_option(menu, "Online module", function() {
					osmium_set_module_state(li, "online");
				}, { icon: osmium_module_state_names['online'][1] });
			}
			if(osmium_module_states[typeid][2]) {
				osmium_ctxmenu_add_option(menu, "Activate module", function() {
					osmium_set_module_state(li, "active");
				}, { icon: osmium_module_state_names['active'][1] });
			}
			if(osmium_module_states[typeid][3]) {
				osmium_ctxmenu_add_option(menu, "Toggle overload", function() {
					if(li.data('state') !== "overloaded") {
						osmium_set_module_state(li, "overloaded");
					} else {
						osmium_set_module_state(li, "active");
					}
				}, { icon: osmium_module_state_names['overloaded'][1] });
			}
		}

		return menu;
	});

	return div;
};

osmium_add_placeholder_module = function(slotsdiv) {
	var ul = slotsdiv.children('ul');
	var li, img;
	var type = slotsdiv.data('type');

	li = $(document.createElement('li'));
	li.addClass('placeholder');
	li.text('Unused ' + type + ' slot');

	img = $(document.createElement('img'));
	img.prop('src', '../static-' + osmium_staticver + '/icons/slot_' + type + '.png');

	li.prepend(img);
	ul.append(li);
};

osmium_post_update_module = function(slotsdiv) {
	osmium_update_overflow(slotsdiv);
	osmium_maybe_hide_slot_type(slotsdiv);
};

osmium_set_module_state = function(li, newstate) {
	var index = li.data('index');
	var typeid = li.data('typeid');

	li.data('state', newstate);

	var modules = osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']].modules;
	for(var i = 0; i < modules.length; ++i) {
		if(modules[i].index === index && modules[i].typeid === typeid) {
			osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']]
				.modules[i].state = newstate;
			break;
		}
	}

	osmium_commit_clf();
	li.trigger('state_changed');
};