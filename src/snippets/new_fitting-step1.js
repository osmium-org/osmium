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

$(function() {
	$("div#selectship").on('click', 'h2, h3, h4, h5, h6', function() {
		var d = $(this).parent();
		var hidden = d.hasClass('hidden');
		var marketgroupid = d.data('marketgroupid');
		var key = 'new_fitting_step1_' + marketgroupid;

		if(hidden) {
			d.children('ul.subgroups, ul.types').fadeIn(500);
			d.removeClass('hidden');
		} else {
			d.children('ul.subgroups, ul.types').hide();
			d.addClass('hidden');
		}

		localStorage.setItem(key, hidden ? "0" : "1");
	});

	$("div#selectship div.mgroup div.mgroup").each(function() {
		var mgroup = $(this);
		var marketgroupid = mgroup.data('marketgroupid');
		var hidden = localStorage.getItem("new_fitting_step1_" + marketgroupid);
		if(hidden === null) hidden = "1";

		if(hidden === "1") {
			mgroup.children('ul.subgroups, ul.types').hide();
			mgroup.addClass('hidden');
		}
	});

	$("div#selectship ul.types > li[data-typeid]").click(function() {
		$("div#selectship ul.types > li.selected").removeClass('selected');
		$(this).addClass('selected');
		$("input#hullidhidden").val($(this).data('typeid'));
	}).dblclick(function() {
		$("input#hullidhidden").val($(this).data('typeid'));
		$("div#selectship > form input.next_step").click();
	});

	$("div#selectship ul.types > li > input[type='submit']").hide();

	var selected = $("input#hullidhidden").val();
	$("div#selectship ul.types > li[data-typeid]").filter(function() {
		return $(this).data('typeid') == selected;
	}).addClass('selected');
});
