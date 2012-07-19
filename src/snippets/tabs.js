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

osmium_tabify = function(ul, selected) {
	ul.on('click', 'li', function(event) {
		var li = $(this);

		li.parent().children('li').each(function() {
			$(this).removeClass('active');
			$($(this).children('a').attr('href')).hide();
		});

		$(li.children('a').attr('href')).fadeIn(500);
		li.addClass('active').delay(501).trigger('afteractive');

		event.preventDefault();
	});

	ul.children('li').eq(selected).click();
};
