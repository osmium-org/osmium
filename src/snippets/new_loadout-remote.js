/* Osmium
 * Copyright (C) 2013 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

osmium_gen_remote = function() {
	var selects = $("section#fleet select");
	selects.empty();

	for(var i = 0; i < osmium_skillsets.length; ++i) {
		selects.append(
			$(document.createElement('option'))
				.text(osmium_skillsets[i])
				.prop('value', osmium_skillsets[i])
		);
	}

	$("section#fleet").find('input, select')
		.not("[type='checkbox']").prop('disabled', true);

	if(!("X-Osmium-fleet" in osmium_clf)) {
		osmium_clf['X-Osmium-fleet'] = {};
	}

	for(var t in osmium_clf['X-Osmium-fleet']) {
		$("section#fleet select#" + t + "_skillset").val(osmium_clf['X-Osmium-fleet'][t].skillset);
		$("section#fleet input#" + t + "_fit").val(osmium_clf['X-Osmium-fleet'][t].fitting);
		$("section#fleet input#" + t + "_enabled").prop('checked', true).change();
		$("section#fleet").find("input, select").filter("." + t).prop('disabled', false);
	}
};

osmium_init_remote = function() {
	$("section#fleet").on('change', "input[type='checkbox']", function() {
		var c = $(this);
		var tr = c.closest('tr');
		var table = tr.closest('table');
		var type = tr.data('type');

		if(!("X-Osmium-fleet" in osmium_clf)) {
			osmium_clf['X-Osmium-fleet'] = {};
		}
		var fleet = osmium_clf['X-Osmium-fleet'];

		if(c.is(':checked')) {
			fleet[type] = {
				skillset: table.find('select#' + type + '_skillset').val(),
				fitting: table.find('input#' + type + '_fit').val()
			};
			table.find('input, select')
				.filter('.' + type).prop('disabled', false);
		} else {
			delete(fleet[type]);
			table.find('input, select')
				.filter('.' + type).not("[type='checkbox']")
				.prop('disabled', true);
		}

		if(osmium_user_initiated) {
			osmium_undo_push();
			osmium_commit_clf();
		}
	}).on('change', 'select', function() {
		var s = $(this);
		var tr = s.closest('tr');
		var table = tr.closest('table');
		var checkbox = table.find("input#" + tr.data('type') + "_enabled");

		if(!checkbox.is(':checked')) {
			return;
		}

		checkbox.trigger('change');
	}).on('click', 'input.set', function() {
		var s = $(this);
		var tr = s.closest('tr');
		var table = tr.closest('table');
		var checkbox = table.find("input#" + tr.data('type') + "_enabled");

		if(!checkbox.is(':checked')) {
			return;
		}

		checkbox.trigger('change');
	}).on('click', 'input.clear', function() {
		var s = $(this);
		var tr = s.closest('tr');
		var table = tr.closest('table');
		var checkbox = table.find("input#" + tr.data('type') + "_enabled");

		if(!checkbox.is(':checked')) {
			return;
		}

		table.find('input#' + tr.data('type') + '_fit').val('');
		checkbox.trigger('change');
	}).on('keypress', 'input.fit', function(e) {
		if(e.which != 13) return;
		e.preventDefault();

		var s = $(this);
		var tr = s.closest('tr');
		var table = tr.closest('table');
		var checkbox = table.find("input#" + tr.data('type') + "_enabled");

		if(!checkbox.is(':checked')) {
			return false;
		}

		checkbox.trigger('change');
		return false;
	});
};
