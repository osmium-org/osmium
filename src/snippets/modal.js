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

osmium_modal = function(inside) {
	$("body > div#modalbg").remove();
	$('body').append("<div id='modalbg'> </div>\n<div id='modal'><a href='javascript:void(0);' title='Close' id='closemodal'>X</a></div>");

	var modal = $('body > div#modal');
	var link = modal.find('a#closemodal');
	modal.append(inside)
		.css('margin-left', -modal.width() / 2)
		.css('margin-top', -modal.height() / 2);
	link
		.css('margin-left', modal.width() / 2 - link.width() / 2)
		.css('margin-top', -modal.height() / 2 - link.height() / 2);

	$('body > div#modal > a#closemodal').click(function() {
		$('body > div#modal, body > div#modalbg').remove();
	});

	$('body > div#modalbg').click(function() {
		$("body > div#modal > a#closemodal").click();
	});
};
