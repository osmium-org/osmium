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

osmium_contextmenu = function(e, populatefunc, source) {
	var ul, div, clickfunc, w;

	ul = $(document.createElement('ul'));
	ul.prop('id', 'ctxmenu');

	populatefunc(ul, source);

	div = $(document.createElement('div'));
	div.prop('id', 'ctxbg');

	div.bind('click contextmenu', function(e2) {
		$("ul#ctxmenu, div#ctxbg").remove();
		$(document.elementFromPoint(e2.pageX, e2.pageY)).trigger(e2);
		return false;
	});

	ul.click(function() {
		$("ul#ctxmenu, div#ctxbg").remove();
	});

	$('body').append(div).append(ul);

	var x = Math.min(e.pageX, $(document).width() - ul.width() - 5);
	var y = e.pageY;
	ul.css('left', x);
	ul.css('top', y);
};
