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

$(function() {
	$('div#nlsources > ul.tabs, div#nlmain > ul.tabs').each(function() {
		osmium_tabify($(this), 0);
	});

	$.getJSON('../static-' + osmium_staticver + '/cache/clientdata.json', function(cdata) {
		osmium_groups = cdata.groups;
		osmium_types = cdata.groups.types;
		osmium_charges = cdata.charges;

		/* Generate all the missing DOM elements from the CLF */
		osmium_gen_ship();
		osmium_gen_metadata();
		osmium_gen_presets();

		/* Set up event listeners that alter the CLF appropriately */
		osmium_init_sources();
		osmium_init_metadata();
		osmium_init_presets();

		/* Fetch computed attributes, etc. */
		osmium_commit_clf();
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
	}

	osmium_commit_clf();
};
