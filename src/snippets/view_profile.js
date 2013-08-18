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
	$("section#reputation > ul > li").addClass('hidden')
		.filter(function() {
			var i = $(this).index();
			return i === 0 || i === 1 || i === 2;
		}).removeClass('hidden');

	$("section#reputation > ul > li > h4").click(function() {
		$(this).parent().toggleClass('hidden');
	});

	osmium_tabify($("div#vprofile > ul.tabs"), $("div#osmium-data").data('defaulttab'));
});
