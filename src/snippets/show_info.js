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

osmium_showinfo = function(opts, relative) {
	$.getJSON(relative + '/src/json/show_info.php', opts, function(json) {
		osmium_modal(json['modal']);
		osmium_tabify($('ul#showinfotabs'), 0);
	});
};

osmium_addicon = function(items) {
	var d = $('div#showinfoicon');
	var onorig = false;
	var onicon = false;

	items.hover(function() {
		onorig = true;

		var t = $(this);
		var p = t.offset();

		d.css('top', p.top + "px");
		d.css('left', (p.left + t.width() - d.width()) + "px");

		d.unbind('click');
		d.click(function() { t.click(); });
		d.show();
	}, function() {
		onorig = false;

		if(!onicon) {
			d.hide();
		}
	});

	d.hover(function() {
		onicon = true;

		d.show();
	}, function() {
		onicon = false;

		if(!onorig) {
			d.hide();
		}
	});

	d.css('cursor', 'help');
};

$(function() {
	$('body').append("<div id='showinfoicon' title='Click to show info'> </div>");
});
