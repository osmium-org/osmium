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

osmium_fattribs_load = function() {
	$("div#computed_attributes > section").each(function() {
		var key = 'osmium_fattribs_' + $(this).attr('id');
		if(localStorage.getItem(key) === "0") {
			$("section#" + $(this).attr('id') + " > div").hide()
				.parent().addClass('hidden');
		}
	});
};

osmium_fattribs_toggle = function(id) {
	var key = 'osmium_fattribs_' + id;
	if(localStorage.getItem(key) !== "0") {
		localStorage.setItem(key, "0");
		$("section#" + id + " > div").hide()
			.parent().addClass('hidden');
	} else {
		localStorage.setItem(key, "1");
		$("section#" + id + " > div").fadeIn(500)
			.parent().removeClass('hidden');
	}
};

$(function() {
	$(document).on('click', "div#computed_attributes > section > h4", function() {
		osmium_fattribs_toggle($(this).parent().attr('id'));
	});

	osmium_fattribs_load();
});
