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

toggle_revision_delta = function(li) {
	if(li.hasClass('hidden')) {
		li.find('p > a.toggle').text("hide changes");
		li.find('pre').fadeIn(500);
	} else {
		li.find('p > a.toggle').text("show changes");
		li.find('pre').hide();
	}

	li.toggleClass('hidden');
}

$(function() {
	var togglelink = $(document.createElement('a'))
		.addClass('toggle')
		.text('show changes')
	;

	$("ol#lhistory > li > p > small.anchor").not(":last").before([ togglelink, " — " ]);
	$("ol#lhistory > li > pre").hide();
	$("ol#lhistory > li").addClass('hidden');

	var first = $("ol#lhistory > li").first();
	first.find('pre').show();
	toggle_revision_delta(first);

	$('ol#lhistory > li > p > a.toggle').click(function() {
		toggle_revision_delta($(this).parent().parent());
		$(this).blur();
	});
});
