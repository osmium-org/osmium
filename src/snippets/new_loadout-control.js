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

osmium_gen_control = function() {
	$("section#control input#submit_loadout")
		.prop('disabled', 'disabled')
		.prop('title', 'Select a ship before submitting your loadout.')
	;
	$("section#control input#export_loadout")
		.prop('disabled', 'disabled')
		.prop('title', 'Select a ship before exporting your loadout.')
	;
};

osmium_init_control = function() {
	$("section#control input#reset_loadout").click(function() {
		if(confirm("Are you sure you want to reset your current work? This cannot be undone.")) {
			/* This is a hack, rather than resetting the CLF to the
			 * true "blank fit", we just create a new fit
			 * altogether */
			window.location.replace('../new');
		}

		return false;
	});

	$("section#control input#export_loadout").click(function() {
		var b = $(this);
		var bval = b.prop('value');
		b.prop('disabled', 'disabled').prop('value', 'Exporting loadout…');

		var postopts = {
			clf: JSON.stringify(osmium_clf)
		};

		var getopts = {
			type: 'new',
			token: osmium_token,
			clftoken: osmium_clftoken,
			'export': 1,
			exportfmt: $("section#control select#export_type").val()
		};

		$.ajax({
			type: "POST",
			url: "../src/json/process_clf.php?" + $.param(getopts),
			data: postopts,
			dataType: "json",
			error: function(xhr, error, httperror) {
				b.prop('value', bval).removeProp('disabled');
				alert('An error occured: ' + error + ' (' + httperror 
					  + '). This shouldn\'t normally happen, try again.'); 
			},
			success: function(payload) {
				b.prop('value', bval).removeProp('disabled');

				var textarea = $(document.createElement('textarea'));
				textarea.text(payload['export-payload']);
				textarea.prop('readonly', 'readonly');
				textarea.css('width', '100%').css('height', '20em');
				osmium_modal(textarea);
				textarea.parent().css('overflow-x', 'hidden'); /* XXX */
			}
		});
	});

	$("section#control input#submit_loadout").click(function() {
		var b = $(this);
		var bval = b.prop('value');
		b.prop('disabled', 'disabled').prop('value', 'Submitting loadout…');

		var postopts = {
			clf: JSON.stringify(osmium_clf)
		};

		var getopts = {
			type: 'new',
			token: osmium_token,
			clftoken: osmium_clftoken,
			submit: 1
		};

		$.ajax({
			type: "POST",
			url: "../src/json/process_clf.php?" + $.param(getopts),
			data: postopts,
			dataType: "json",
			error: function(xhr, error, httperror) {
				b.prop('value', bval).removeProp('disabled');
				alert('An error occured: ' + error + ' (' + httperror 
					  + '). This shouldn\'t normally happen, try again.'); 
			},
			success: function(payload) {
				if("submit-error" in payload) {
					b.prop('value', bval).removeProp('disabled');

					if("submit-tab" in payload) {
						$("a[href='#"  + payload['submit-tab'] +  "']").click();
					}

					$("section#metadata tr.error").removeClass('error');
					$("section#metadata tr.error_message").remove();
					if("submit-form-error" in payload) {
						/* Use fancy error messages */

						var tr = $(document.createElement('tr'));
						var td = $(document.createElement('td'));
						var p = $(document.createElement('p'));

						tr.addClass('error_message');
						td.attr('colspan', '2');
						p.text(payload["submit-error"]);
						td.append(p);
						tr.append(td);

						$(payload["submit-form-error"]).closest('tr').addClass('error').before(tr);
					} else {
						/* Alert the error as a backup */
						alert(payload['submit-error']);
					}
				} else if("submit-loadout-uri" in payload) {
					window.location.replace(payload['submit-loadout-uri']);
				}
			}
		});
	});
};

osmium_loadout_can_be_submitted = function() {
	$("section#control input#submit_loadout, section#control input#export_loadout")
		.removeProp('disabled')
		.prop('title', '')
	;
};
