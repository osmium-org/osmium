/* Osmium
 * Copyright (C) 2012, 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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
	var hidden = $('div#osmium-data').data('fattribshidden');
	if(!hidden) return;

	var hc = hidden.length;
	var s = $("div#computed_attributes");
	for(var i = 0; i < hc; ++i) s.children("section#" + hidden[i]).addClass('hidden').children('div').hide();
};

osmium_fattribs_toggle = function(section) {
	if(section.hasClass('hidden')) {
		section.children('div').fadeIn(250);
		section.removeClass('hidden');
	} else {
		section.children('div').fadeOut(250);
		section.addClass('hidden');
	}

	var hidden = [];

	$("div#computed_attributes > section.hidden").each(function() {
		hidden.push($(this).prop('id'));
	});

	osmium_put_setting('fattribs_hidden', hidden);
};

$(function() {
	$(document).on('click', "div#computed_attributes > section > h4", function() {
		osmium_fattribs_toggle($(this).parent());
	});

	osmium_fattribs_load();
});
