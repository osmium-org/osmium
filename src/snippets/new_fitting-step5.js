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
    $('select#view_perms').change(function() {
		if($(this).val() == 1) {
			$("select#visibility").val(1).attr('disabled', 'disabled');
			$("input#pw").removeAttr('disabled');
			$("input#pw").parent().parent().css('opacity', 1.0);
		} else {
			$("select#visibility").removeAttr('disabled');
			$("input#pw").val('').attr('disabled', 'disabled');
			$("input#pw").parent().parent().css('opacity', 0.2);
		}
    });

    $('select#view_perms').trigger('change');

	$('ul.tags > li > a').click(function() {
		var t = $(this);
		var tags = $("input#tags");

		if(tags.val().match(new RegExp("(^|\\s)" + t.text() + "(\\s|$)")) === null) {
			tags.val(tags.val() + " " + t.text());
		}

		t.blur();
		tags.trigger('change');
	});

	$('input#tags').change(function() {
		var t = $(this);

		if(t.val().match(/[^\s]+/g).length > 5) {
			t.addClass('error');
		} else {
			t.removeClass('error');
		}
	});
});
