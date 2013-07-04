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

osmium_showinfo = function(opts) {
	opts.loadoutsource = osmium_clftype;
	opts.clftoken = osmium_clftoken;

	$.ajax({
		type: 'GET',
		url: osmium_relative + '/src/json/show_info.php',
		data: opts,
		dataType: 'json',
		error: function(xhr, error, httperror) {
			alert('Could not show info: ' + error + ' (' + httperror
				  + '). Try refreshing the page and report if the problem persists.');
		},
		success: function(json) {
			osmium_modal(json['modal']);
			osmium_tabify($('ul#showinfotabs'), 0);
		}
	});
};
