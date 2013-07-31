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
};

osmium_init_remote = function() {
	$("section#fleet").on('change', "input[type='checkbox']", function() {
		var c = $(this);
		var tr = c.closest('tr');
		var type = tr.data('type');

		if(!("X-Osmium-fleet" in osmium_clf)) {
			osmium_clf['X-Osmium-fleet'] = {};
		}
		var fleet = osmium_clf['X-Osmium-fleet'];

		if(c.is(':checked')) {
			fleet[type] = {
				skillset: tr.find('select').val(),
				fitting: tr.parent().find('input.fit.' + type).val()
			};
			tr.parent().find('input, select')
				.filter('.' + type).prop('disabled', false);
		} else {
			delete(fleet[type]);
			tr.parent().find('input, select')
				.filter('.' + type).not("[type='checkbox']")
				.prop('disabled', true);
		}

		osmium_undo_push();
		osmium_commit_clf();
	}).on('change', 'select', function() {
		var s = $(this);
		var tr = s.closest('tr');
		var checkbox = tr.find("input[type='checkbox']");

		if(!checkbox.is(':checked')) {
			return;
		}

		checkbox.trigger('change');
	}).on('click', 'input.set', function() {
		var b = $(this);
		var tr = b.closest('tr');
		tr = tr.parent().find('tr').eq(tr.index() - (tr.index() % 3));
		var checkbox = tr.find("input[type='checkbox']");

		if(!checkbox.is(':checked')) {
			return;
		}

		checkbox.trigger('change');
	}).on('click', 'input.clear', function() {
		var b = $(this);
		var tr = b.closest('tr');
		tr = tr.parent().find('tr').eq(tr.index() - (tr.index() % 3));
		var checkbox = tr.find("input[type='checkbox']");

		if(!checkbox.is(':checked')) {
			return;
		}

		tr.parent().find('input.fit.' + tr.data('type')).val('');
		checkbox.trigger('change');
	}).on('keypress', 'input.fit', function(e) {
		if(e.which != 13) return;
		e.preventDefault();

		var i = $(this);
		var tr = i.closest('tr');
		tr = tr.parent().find('tr').eq(tr.index() - (tr.index() % 3));
		var checkbox = tr.find("input[type='checkbox']");

		if(!checkbox.is(':checked')) {
			return;
		}

		checkbox.trigger('change');
		return false;
	});
};
