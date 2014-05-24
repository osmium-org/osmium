/* Osmium
 * Copyright (C) 2012, 2013, 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

osmium_gen_control = function() {
	var submit = $("section#control input#submit_loadout");
	if(!submit.hasClass('force')) {
		submit
			.prop('disabled', 'disabled')
			.prop('title', 'Select a ship before submitting your loadout.')
		;
	}

	if(!("metadata" in osmium_clf)) return;
	if(!("X-Osmium-update-reason" in osmium_clf.metadata)) return;
	$("section#control input#ureason").val(osmium_clf.metadata['X-Osmium-update-reason']);
};

osmium_init_control = function() {
	$("section#control form").submit(function() {
		return false;
	});


	var lockcontrol = function() {
		var s = $("section#control");
		s.find('input, select').prop('disabled', true);
		s.append($(document.createElement('span')).addClass('spinner'));
	};

	var unlockcontrol = function() {
		var s = $("section#control");
		s.find('input, select').prop('disabled', false);
		s.find('span.spinner').remove();
	};

	$("section#control input#export_loadout").click(function() {
		var b = $(this);
		var exporttype = $("section#control select#export_type").val();

		osmium_commit_clf({
			params: {
				'export': true,
				'exportfmt': exporttype
			},
			success: function(payload) {
				osmium_modal_rotextarea('Exported loadout (' + exporttype + ')', payload['export-payload']);
			},
			before: lockcontrol,
			after: unlockcontrol,
		});
	});

	$("section#control input#submit_loadout").click(function() {
		var b = $(this);

		osmium_commit_clf({
			params: {
				submit: true,
			},
			success: function(payload) {
				if("submit-error" in payload) {
					alert(payload['submit-error']);
				} else if("submit-loadout-uri" in payload) {
					window.location.replace(payload['submit-loadout-uri']);
				}
			},
			before: lockcontrol,
			after: unlockcontrol,
		});
	});

	$("section#control input#ureason").change(function() {
		if(!("metadata" in osmium_clf)) {
			osmium_clf.metadata = {};
		}

		osmium_clf.metadata['X-Osmium-update-reason'] = $("section#control input#ureason").val();
		osmium_commit_clf();
		osmium_undo_push();
	});
};

osmium_loadout_can_be_submitted = function() {
	var submit = $("section#control input#submit_loadout");
	if(!submit.hasClass('force')) {
		submit
			.removeProp('disabled')
			.prop('title', '')
		;
	}
};
