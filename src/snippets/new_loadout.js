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
		/* Generate all the missing DOM elements from the CLF */
		osmium_gen_ship();
		osmium_gen_metadata();
		osmium_gen_presets();
		osmium_gen_modules();

		/* Set up event listeners that alter the CLF appropriately */
		osmium_init_sources();
		osmium_init_metadata();
		osmium_init_presets();
		osmium_init_modules();

		/* Fetch computed attributes, etc. */
		osmium_commit_clf();

		/* Everything done from now on is user initiated */
		osmium_user_initiated_push(true);
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

	$.post('../src/json/process_clf.php?' + $.param(getopts), postopts, function(payload) {
		$('div#computed_attributes').html(payload.attributes);
		setTimeout(osmium_send_clf, 500);
	}, 'json');
};

osmium_add_to_clf = function(item) {
	var cat = item.data('category');
	var sub = item.data('subcategory');
	var typeid = item.data('typeid');

	if(cat === 'ship') {
		osmium_clf.ship = { typeid: typeid };

		osmium_gen_ship();

		/* Regen all modules as the number of available slots has
		 * likely changed. */
		osmium_user_initiated_push(false);
		osmium_gen_modules();
		osmium_user_initiated_pop();
	} else if(cat === 'module') {
		var state, index, m;

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

		osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']].modules.push({
			typeid: typeid,
			state: state,
			index: index
		});

		osmium_post_update_module(osmium_add_module(typeid, index, state, null));
	} else if(cat === 'charge') {
		/* Try to find a suitable location for the charge */
		var location = null;
		var candidatelevel;

		for(var i = 0; i < osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']]
			.modules.length; ++i) {
			m = osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']].modules[i];

			var validcharge = false, currentchargeid = null;
			if(!(m.typeid in osmium_charges)) {
				/* Module can't accept charges */
				continue;
			}
			for(var j = 0; j < osmium_charges[m.typeid].length; ++j) {
				if(osmium_charges[m.typeid][j] === typeid) {
					validcharge = true;
					break;
				}
			}
			if(!validcharge) continue;

			if(location === null) {
				/* As a fallback, fit the charge to the first suitable location */
				location = i;
				candidatelevel = 0; /* This candidate is not very good */
			}

			if(!("charges" in m)) {
				/* The module has no charges and can accept the charge, perfect */
				location = i;
				break;
			}

			var charges = osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']]
				.modules[i].charges;
			var cpid;

			/* Check if charge already present */
			for(var j = 0; j < charges.length; ++j) {
				if("cpid" in charges[j]) {
					cpid = charges[j].cpid;
				} else {
					cpid = 0;
				}

				if(cpid == osmium_clf['X-Osmium-current-chargepresetid']) {
					currentchargeid = charges[j].typeid;
					break;
				}
			}
			if(currentchargeid === null) {
				/* The module has no charge in this preset and can accept the charge */
				location = i;
				break;
			} else if(currentchargeid !== typeid && candidatelevel < 1) {
				/* The module has a different charge, but it's still a
				 * better candidate than the fallback */
				location = i;
				candidatelevel = 1;
			}
		}

		if(location !== null) {
			var m = osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']]
				.modules[location];

			if(!("charges" in m)) {
				osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']]
					.modules[location].charges = [];
			}

			var charges = osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']]
				.modules[location].charges;
			var cpid;

			/* Remove previous charge (if there is one) */
			for(var j = 0; j < charges.length; ++j) {
				if("cpid" in charges[j]) {
					cpid = charges[j].cpid;
				} else {
					cpid = 0;
				}

				if(cpid !== osmium_clf['X-Osmium-current-chargepresetid']) {
					continue;
				}

				osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']]
					.modules[location].charges.splice(j, 1);
				break;
			}

			/* Finally, add the new charge */
			osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']]
				.modules[location].charges.push({
					typeid: typeid,
					cpid: osmium_clf['X-Osmium-current-chargepresetid']
				});

			osmium_add_charge_by_location(m.typeid, m.index, typeid);
		} else {
			alert("This charge cannot be used with any fitted type.");
		}
	}

	osmium_commit_clf();
};
