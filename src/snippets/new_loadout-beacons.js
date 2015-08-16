/* Osmium
 * Copyright (C) 2015 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

osmium_gen_beacons = function() {
	var ul = $("section#area > div > ul");
	var clfp = osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']];

	ul.empty();

	if(!('X-beacons' in clfp) || clfp['X-beacons'].length === 0) {
		ul.append(
			$(document.createElement('li'))
				.addClass('placeholder')
				.text('No area effects')
		);
		return;
	}

	for(var i = 0; i < clfp['X-beacons'].length; ++i) {
		var btypeid = clfp['X-beacons'][i];
		var beacon = osmium_types[btypeid];
		var li;

		ul.append(
			li = $(document.createElement('li'))
				.prop('title', beacon[1])
				.append(
					$(document.createElement('img'))
						.prop('src', '//image.eveonline.com/Type/' + btypeid +'_64.png')
						.prop('alt', '')
				)
				.append(
					$(document.createElement('span'))
						.addClass('name')
						.text(beacon[1])
				)
		);

		osmium_ctxmenu_bind(li, (function(li, typeid) {
			return function() {
				var menu = osmium_ctxmenu_create();

				if(!osmium_loadout_readonly) {
					osmium_ctxmenu_add_option(menu, "Remove beacon", function() {
						var clfpb = osmium_clf.presets[osmium_clf['X-Osmium-current-presetid']]['X-beacons'];

						for(var z = 0; z < clfpb.length; ++z) {
							if(typeid === clfpb[z]) {
								var ul = li.parent();

								clfpb.splice(z, 1);
								osmium_undo_push();
								osmium_commit_clf();
								li.remove();

								if(ul.children('li').length === 0) {
									osmium_gen_beacons();
								}
								break;
							}
						}
					}, { 'default': true });

					osmium_ctxmenu_add_separator(menu);

					osmium_add_generic_browse(menu, typeid);
				}

				osmium_ctxmenu_add_option(menu, "Show beacon info", function() {
					osmium_showinfo({ type: 'beacon', typeid: typeid });
				}, { icon: osmium_showinfo_sprite_position, "default": osmium_loadout_readonly });
				
				return menu;
			};
		})(li, btypeid));
	}
};

osmium_init_beacons = function() {

};
