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

osmium_gen_ship = function() {
	var section = $('div#nlattribs > section#ship');
	var img, h, shipname, groupname;

	if("ship" in osmium_clf && "typeid" in osmium_clf.ship) {
		groupname = osmium_types[osmium_clf.ship.typeid][3];
		shipname = osmium_types[osmium_clf.ship.typeid][1];

		img = $(document.createElement('img'));
		img.prop('src', '//image.eveonline.com/Render/' + osmium_clf.ship.typeid + '_256.png');

		osmium_loadout_can_be_submitted();
	} else {
		groupname = '';
		shipname = '(No ship selected)';

		img = $(document.createElement('div'));
		img.addClass('notype');
	}

	h = $(document.createElement('h1'));
	h.append(img);
	h.append($(document.createElement('small')).text(groupname));
	h.append($(document.createElement('strong')).text(shipname));

	section.empty();
	section.append(h);

	if(osmium_user_initiated) {
		/* XXX: this is needed to restart the animation. Get rid of
		 * this asap! */
		var newsection = section.clone(true);
		newsection.addClass('added_to_loadout');
		section.after(newsection);
		section.remove();
	}
};
