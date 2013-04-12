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

osmium_tabify = function(ul, selected) {
	ul.on('osmium_select_tab', 'li', function(event) {
		var li = $(this);
		var a = li.children('a');
		var dest = $(a.attr('href'));

		if(!li.hasClass('active')) {
			li.parent().children('li').each(function() {
				var li = $(this);
				var dest = $(li.children('a').attr('href'));
				li.removeClass('active');
				dest.hide();
			});

			$(a.attr('href')).fadeIn(500);
			li.addClass('active').delay(501).trigger('afteractive');
		}

		a.blur();
		event.preventDefault();
		event.stopPropagation();
		return false;
	}).on('click', 'li', function() {
		var li = $(this);
		li.trigger('osmium_select_tab');

		var hash = window.location.hash;
		var href = window.location.href;
		if(window.history && window.history.replaceState) {
			window.history.replaceState(
				null,
				null,
				href.substring(0, hash.length - href.length) + li.children('a').attr('href')
			);
		}
		return false;
	});

	var tab_anchor_update = function() {
		if(window.location.hash) {
			var a = ul.find('a').filter(function() {
				return $(this).attr('href') === window.location.hash;
			});

			if(a.length >= 1) {
				$(window.location.hash).addClass('notarget');
				a.first().parent().trigger('osmium_select_tab');
				return true;
			}
		}
	};

	if(tab_anchor_update() === true) {
		return;
	}

	ul.children('li').eq(selected).trigger('osmium_select_tab');
};
