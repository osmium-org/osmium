/* Osmium
 * Copyright (C) 2017 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

/*<<< require snippet localstorage_fallback >>>*/

$(function() {
    $('p.alert').each(function() {
	var p = $(this);
	var id = '__osmium_alert_' + p.data('id');

	if(localStorage.getItem(id) === "1") {
	    p.css('display', 'none');
	} else {
	    p.css('animation', 'none');
	    p.css('-moz-animation', 'none');
	    p.css('-webkit-animation', 'none');
	    p.css('-o-animation', 'none');
	}

	var a = $(document.createElement('a')).text('Got it, never show this alert again!');
	a.click(function() {
	    localStorage.setItem(id, "1");
	    p.hide('slow');
	});

	p.append($(document.createElement('p')).append(a));
    });
});
