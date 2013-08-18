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

osmium_notifications = function(relative) {
	var fetch = function() {
		$.get(relative + '/internal/nc', function(cnt) {
			var a = $("a#ncount");
			var rawtitle = document.title.replace(/^\([1-9][0-9]*\) /, "");

			a.text(cnt);
			a.data('count', cnt);
			a.prop('title', cnt + ' new notification(s)');

			if(parseInt(cnt, 10) > 0) {
				a.show().css('display', 'inline-block');
				document.title = "(" + cnt + ") " + rawtitle;
			} else {
				a.hide();
				document.title = rawtitle;
			}
		});

		setTimeout(fetch, 63000);
	};

	setTimeout(fetch, 60000);
};

$(function() {
	osmium_notifications(
		$("div#osmium-data").data('relative')
	);
});
