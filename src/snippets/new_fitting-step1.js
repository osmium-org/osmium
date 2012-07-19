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
	osmium_togglify_market_sections('new_fitting_step1', $("div#selectship"));

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
