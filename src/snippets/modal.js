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

osmium_modal_clear = function() {
	$("body > div#modalbg").fadeOut(250);
	$("body > div#modal").animate({
		'margin-top': -$('body').width() + "px"
	}, 250);
}

osmium_modal = function(inside) {
	$("body > div#modalbg, body > div#modal").remove();

	var bg = $(document.createElement('div'));
	bg.prop('id', 'modalbg');
	bg.click(osmium_modal_clear);
	bg.hide();

	var modal = $(document.createElement('div'));
	modal.prop('id', 'modal');

	var close = $(document.createElement('a'));
	close.prop('href', 'javascript:void(0);');
	close.prop('title', 'Close this dialog (Escape)');
	close.prop('id', 'closemodal');
	close.text('X');
	close.click(osmium_modal_clear);

	$('body')
		.append(bg)
		.append(modal)
	;

	$(document).on('keydown.osmiummodal', function(e) {
		if(e.which === 27) {
			osmium_modal_clear();
			$(document).off('keydown.osmiummodal');
			return false;
		}
	});

	modal
		.css('margin-left', (-modal.width() / 2) + "px")
		.css('margin-top', -$('body').width() + "px")
		.append(inside)
		.append(close)
		.animate({
			'margin-top': (-modal.height() / 2) + "px"
		}, 500)
	;

	bg.fadeIn(500);
};
