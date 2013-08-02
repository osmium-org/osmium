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

osmium_showinfo_sprite_position = [ 6, 58, 16, 16 ];

osmium_showinfo = function(opts) {
	osmium_showinfo_internal(opts, function() {
		/* First error… Try committing CLF and retry once */
		osmium_commit_clf({
			success: function() {
				osmium_showinfo_internal(opts, function(xhr, error, httperror) {
					alert('Could not show info: ' + error + ' (' + httperror
						  + '). Try refreshing the page and report if the problem persists.');
				});
			}
		});
	});
};

osmium_showinfo_internal = function(opts, onerror) {
	opts.loadoutsource = osmium_clftype;
	opts.clftoken = osmium_clftoken;

	osmium_clfspinner_push();

	$.ajax({
		type: 'GET',
		url: osmium_relative + '/src/json/show_info.php',
		data: opts,
		dataType: 'json',
		error: onerror,
		complete: function() {
			osmium_clfspinner_pop();
		},
		success: function(json) {
			osmium_modal(json['modal']);
			osmium_tabify($('ul#showinfotabs'), 0);

			var ul = $("section#sivariations > ul");

			for(var i = 0; i < json.variations.length; ++i) {
				var t = osmium_types[json.variations[i][0]];
				var li = $(document.createElement('li'));

				li.addClass('module');
				li.data('typeid', t[0]);
				li.text(t[1]);
				li.data('category', t[2]);
				li.data('subcategory', t[3]);

				li.prepend(
					$(document.createElement('img'))
					.prop('src', '//image.eveonline.com/Type/' + t[0] + '_64.png')
					.prop('alt', '')
				);

				li.append(
					$(document.createElement('span'))
					.addClass('metalevel')
					.text(', meta level ' + json.variations[i][1])
				);

				if(osmium_loadout_readonly) {
					osmium_ctxmenu_bind(li, (function(typeid) {
						return function() {
							var menu = osmium_ctxmenu_create();

							osmium_ctxmenu_add_option(menu, "Show info", function() {
								osmium_showinfo({
									type: 'generic',
									typeid: typeid
								});
							}, { icon: osmium_showinfo_sprite_position, 'default': true });

							return menu;
						};
					})(t[0]));
				} else {
					osmium_add_non_shortlist_contextmenu(li);
				}

				ul.append(li);
			}
		}
	});
}
