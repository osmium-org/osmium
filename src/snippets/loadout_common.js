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

osmium_must_send_clf = false;
osmium_sending_clf = false;

osmium_load_static_client_data = function(staticver, onsuccess) {
	var idx = 'osmium_static_client_data_' + staticver;

	var onsuccess2 = function(json) {
		osmium_groups = json.groups;
		osmium_types = json.groups.types;
		osmium_charges = json.charges;
		osmium_metagroups = json.metagroups;
		osmium_module_states = json.modulestates;
		osmium_module_state_names = json.modulestatenames;
		osmium_stateful_slot_types = json.statefulslottypes;
		osmium_ship_slots = json.shipslots;
		osmium_chargedmg = json.chargedmg;

		/* Module states as they are defined in the CLF specification */
		osmium_states = ['offline', 'online', 'active', 'overloaded'];

		return onsuccess(json);
	};

	try {
		for(var i = 0; i < staticver; ++i) {
			localStorage.removeItem('osmium_static_client_data_' + i);
		}

		var cdata = localStorage.getItem(idx);
		if(cdata !== null) {
			return onsuccess2(JSON.parse(cdata));
		}
	} catch(e) { /* Incognito mode probably */ }

	$.ajax({
		type: 'GET',
		url: osmium_relative + '/static-' + staticver + '/cache/clientdata.json',
		dataType: 'json',
		error: function(xhr, error, httperror) {
			alert('Could not fetch static client data: ' + error + ' (' + httperror
				  + '). Try refreshing the page and report if the problem persists.');
		},
		success: function(json) {
			try { localStorage.setItem(idx, JSON.stringify(json)); }
			catch(e) { /* Incognito mode probably */ }
			return onsuccess2(json);
		}
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

/* Synchronize the CLF with the server and update the attribute list,
 * etc. It is safe to call this function repeatedly in a short amount
 * of time, it has built-in rate limiting. Requires osmium_clftype and
 * osmium_on_clf_payload global variables to be set. */
osmium_commit_clf = function(onsuccess) {
	osmium_must_send_clf = true;

	if(osmium_sending_clf) return;
	osmium_sending_clf = true;

	osmium_send_clf(onsuccess);
};

/** @internal */
osmium_send_clf = function(onsuccess) {
	if(!osmium_must_send_clf) {
		osmium_sending_clf = false;
		return;
	}
	osmium_must_send_clf = false;

	var deflated = btoa(RawDeflate.deflate(
		JSON.stringify(osmium_clf).replace(/[\u007F-\uFFFF]/g, function(m) {
			/* Thanks to Jason S. for this neat code, see
			 * http://stackoverflow.com/a/4901205/615776 */
			return "\\u" + ('0000' + m.charCodeAt(0).toString(16)).slice(-4);
		})
	));

	var postopts = {
		clf: deflated
	};

	var getopts = {
		type: osmium_clftype,
		token: osmium_token,
		clftoken: osmium_clftoken,
		relative: osmium_relative
	};

	$.ajax({
		type: 'POST',
		url: osmium_relative + '/src/json/process_clf.php?' + $.param(getopts),
		data: postopts,
		dataType: 'json',
		error: function(xhr, error, httperror) {
			alert('Could not sync loadout with remote: ' + error + ' (' + httperror 
				  + '). This shouldn\'t normally happen, try again or refresh the page. Please report if the problem persists.');
			setTimeout(osmium_send_clf, 500);
		},
		success: function(payload) {
			osmium_clftoken = payload.clftoken;
			osmium_on_clf_payload(payload);
			if((typeof onsuccess) === "function") onsuccess(payload);
			setTimeout(osmium_send_clf, 500);
		}
	});
};
