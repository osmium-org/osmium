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

$(function() {
	$('div#nlsources > ul.tabs, div#nlmain > ul.tabs').each(function() {
		osmium_tabify($(this), 0);
	});

	osmium_load_static_client_data('..', osmium_cdatastaticver, function(cdata) {

		osmium_gen();
		osmium_init();

		/* Fetch computed attributes, etc. */
		osmium_commit_clf();

		/* Everything done from now on is user initiated */
		osmium_user_initiated_push(true);

		osmium_undo_push();
	});
});

osmium_must_send_clf = false;
osmium_sending_clf = false;

/* Synchronize the CLF with the server and update the attribute list,
 * etc. It is safe to call this function repeatedly in a short amount
 * of time, it has built-in rate limiting. */
osmium_commit_clf = function() {
	osmium_must_send_clf = true;

	if(osmium_sending_clf) return;
	osmium_sending_clf = true;

	osmium_send_clf();
};

/** @internal */
osmium_send_clf = function() {
	if(!osmium_must_send_clf) {
		osmium_sending_clf = false;
		return;
	}
	osmium_must_send_clf = false;

	var postopts = {
		clf: JSON.stringify(osmium_clf)
	};

	var getopts = {
		type: 'new',
		token: osmium_token,
		clftoken: osmium_clftoken
	};

	$.ajax({
		type: 'POST',
		url: '../src/json/process_clf.php?' + $.param(getopts),
		data: postopts,
		dataType: 'json',
		error: function(xhr, error, httperror) {
			alert('Could not sync loadout with remote: ' + error + ' (' + httperror 
				  + '). This shouldn\'t normally happen, try again or refresh the page.');
			setTimeout(osmium_send_clf, 500);
		},
		success: function(payload) {
			$('div#computed_attributes').html(payload.attributes);
			osmium_clf['X-Osmium-slots'] = payload.slots;
			osmium_clf['X-Osmium-hardpoints'] = payload.hardpoints;
			osmium_update_slotcounts();

			$("section#modules div.slots li > small.attribs").remove();
			$("section#modules div.slots li.hasattribs").removeClass('hasattribs');
			for(var i = 0; i < payload.mia.length; ++i) {
				var s = $(document.createElement('small'));
				s.text(payload.mia[i][2]);
				s.prop('title', payload.mia[i][3]);
				s.addClass('attribs');

				$("section#modules div.slots." + payload.mia[i][0] + " li").filter(function() {
					return $(this).data('index') == payload.mia[i][1];
				}).addClass('hasattribs').append(s);
			}

			$("section#metadata tr#recommended_tags").remove();
			if(payload.rectags.length > 0) {
				var tr = $(document.createElement('tr'));
				tr.append(document.createElement('th'));
				tr.prop('id', 'recommended_tags');
				var td = $(document.createElement('td'));
				tr.append(td);
				td.append('Recommended tags: ');
				var ul = $(document.createElement('ul'));
				ul.addClass('tags');
				for(var i = 0; i < payload.rectags.length; ++i) {
					var li = $(document.createElement('li'));
					var a = $(document.createElement('a'));
					a.prop('href', 'javascript:void(0);');
					a.prop('title', 'Add this tag');
					a.text(payload.rectags[i]);
					li.append(a);
					ul.append(li);
					ul.append(' ');
				}
				td.append(ul);

				$("input#tags").closest('tr').after(tr);
			}

			setTimeout(osmium_send_clf, 500);
		}
	});
};

/* Generate all the missing DOM elements from the CLF */
osmium_gen = function() {
	osmium_gen_control();
	osmium_gen_ship();
	osmium_gen_metadata();
	osmium_gen_presets();
	osmium_gen_modules();
	osmium_gen_drones();
};

/* Set up event listeners that alter the CLF appropriately */
osmium_init = function() {
	osmium_init_control();
	osmium_init_ship();
	osmium_init_sources();
	osmium_init_metadata();
	osmium_init_presets();
	osmium_init_modules();
	osmium_init_drones();
};

osmium_add_to_clf = function(item) {
	var cat = item.data('category');
	var sub = item.data('subcategory');
	var typeid = item.data('typeid');

	if(cat === 'ship') {
		osmium_clf.ship = { typeid: typeid };

		/* Regen everything as changing a ship changes pretty much
		 * everything. */
		osmium_user_initiated_push(false);
		osmium_gen();
		osmium_user_initiated_pop();

		osmium_undo_push();
	} else if(cat === 'module') {
		var state, index, m;
		var slotsdiv = $("section#modules > div.slots." + sub);
		var grouped = slotsdiv.hasClass('grouped');
		var other = osmium_types[typeid][6];
		var addcount = 1;
		var remaining = osmium_clf['X-Osmium-slots'][sub]
			- slotsdiv.children('ul').children('li:not(.placeholder)').length;

		if(grouped && (other === 'turret' || other === 'launcher')) {
			addcount = osmium_clf['X-Osmium-hardpoints'][other];
			osmium_clf['X-Osmium-hardpoints'][other] = 0;
		}

		if(addcount > remaining) {
			addcount = remaining;
		}

		index = 0;
		for(var i = 0; i < osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']].modules.length; ++i) {
			m = osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']].modules[i];
			if(m.index >= index) index = m.index + 1;
		}

		if(osmium_module_states[typeid][2]) {
			/* Active state, if possible */
			state = osmium_states[2];
		} else {
			/* Online state */
			state = osmium_states[1];
		}

		if(addcount < 1) addcount = 1;
		for(var i = 0; i < addcount; ++i) {
			osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']].modules.push({
				typeid: typeid,
				state: state,
				index: index
			});

			osmium_add_module(typeid, index, state, null);
			++index;
		}

		osmium_update_slotcounts();
		osmium_undo_push();
	} else if(cat === 'charge') {
		var location = osmium_get_best_location_for_charge(typeid);

		if(location !== null) {
			osmium_auto_add_charge_to_location(location, typeid);
			osmium_undo_push();
		} else {
			alert("This charge cannot be used with any fitted type.");
		}
	} else if(cat === 'drone') {
		var qty = item.data('qty');
		var dest = item.data('dest');
		if(qty < 1 || qty === undefined) qty = 1;
		if(dest !== 'bay' && dest !== 'space') {
			/* TODO: Auto-guess */
			dest = 'bay';
		}
		osmium_add_drone_to_clf(typeid, qty, dest);
		osmium_gen_drones();
		osmium_undo_push();
	}

	osmium_commit_clf();
};
