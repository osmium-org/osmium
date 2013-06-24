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

osmium_user_initiated = false;
osmium_user_initiated_stack = [];

osmium_undo_stack = [];
osmium_undo_stack_position = 0;

osmium_load_static_client_data = function(relative, staticver, onsuccess) {
	var idx = 'osmium_static_client_data_' + staticver;
	var cdata = localStorage.getItem(idx);

	var onsuccess2 = function(json) {
		osmium_groups = json.groups;
		osmium_types = json.groups.types;
		osmium_charges = json.charges;
		osmium_metagroups = json.metagroups;
		osmium_module_states = json.modulestates;
		osmium_module_state_names = json.modulestatenames;
		osmium_stateful_slot_types = json.statefulslottypes;
		osmium_ship_slots = json.shipslots;

		/* Module states as they are defined in the CLF specification */
		osmium_states = ['offline', 'online', 'active', 'overloaded'];

		return onsuccess(json);
	};

	for(var i = 0; i < staticver; ++i) {
		localStorage.removeItem('osmium_static_client_data_' + i);
	}

	if(cdata !== null) {
		return onsuccess2(JSON.parse(cdata));
	}

	$.getJSON(relative + '/static-' + staticver + '/cache/clientdata.json', function(json) {
		localStorage.setItem(idx, JSON.stringify(json));
		return onsuccess2(json);
	});
};

osmium_user_initiated_push = function(value) {
	osmium_user_initiated_stack.push(osmium_user_initiated);
	osmium_user_initiated = value;
};

osmium_user_initiated_pop = function() {
	osmium_user_initiated = osmium_user_initiated_stack.pop();
};

osmium_undo_trim = function() {
	while(osmium_undo_stack.length > 512) {
		osmium_undo_stack.shift();
		--osmium_undo_stack_position;
	}
}

osmium_undo_push = function() {
	osmium_undo_stack.push($.extend(true, {}, osmium_clf));
	osmium_undo_stack_position = osmium_undo_stack.length - 1;
	osmium_undo_trim();
};

osmium_undo_pop = function() {
	if(osmium_undo_stack_position < 1) {
		alert("No more history for undoing.");
		return;
	}

	--osmium_undo_stack_position;
	osmium_clf = $.extend(true, {}, osmium_undo_stack[osmium_undo_stack_position]);
	osmium_undo_stack.push($.extend(true, {}, osmium_clf));
	osmium_undo_trim();
}
