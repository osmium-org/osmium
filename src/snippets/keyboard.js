/* Osmium
 * Copyright (C) 2014 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

osmium_keyboard_commands = {};

/* Register a keyboard command.
 *
 * @param shortnames a Mousetrap compatible shortcut definition, or an
 * array of definitions, or null.
 *
 * @param longname a long descriptive name of the command ([a-z-]
 * only).
 *
 * @param description a description of what the command does.
 *
 * @param action called when the command is used.
 */
osmium_register_keyboard_command = function(shortnames, longname, description, action) {
	if(longname in osmium_keyboard_commands) {
		osmium_unregister_keyboard_command(longname);
	}

	if($.isEmptyObject(osmium_keyboard_commands)) {
		/* Bind M-x */
		Mousetrap.bind([ 'meta+x', 'alt+x', 'command+x' ], function() {
			if($('div#mx-cont').length !== 0) {
				$('div#mx-cont > form > input[type="text"]').focus();
				return false;
			}

			var c = $(document.createElement('div')).prop('id', 'mx-cont');

			var bg = $(document.createElement('div')).prop('id', 'mx-bg');
			c.append(bg);

			var form = $(document.createElement('form'));
			form.prop('method', 'get');
			form.prop('action', '?');
			c.append(form);

			var inp = $(document.createElement('input'));
			inp.prop('type', 'text');
			inp.prop('placeholder', 'Enter command… (Press C-g or ESC twice to exit)');
			inp.addClass('mousetrap'); /* Fire events even if this input has focus */
			form.append(inp);

			var submit = $(document.createElement('input'));
			submit.prop('type', 'submit');
			form.append(submit); /* Form won't submit on RET without a submit button */

			var exit = function(e, after) {
				Mousetrap.unbind([ 'esc esc', 'ctrl+g' ]);

				c.fadeOut(100, function() {
					c.remove();
					if(typeof after === 'function') {
						after();
					}
				});

				return false;
			};

			Mousetrap.bind([ 'esc esc', 'ctrl+g' ], exit);
			bg.click(exit);

			form.submit(function(e) {
				var command = inp.val();
				if(command in osmium_keyboard_commands) {
					inp.removeClass('error');
					inp.addClass('success');
					exit(e, function() {
						osmium_keyboard_commands[command].action();
					});
				} else {
					inp.addClass('error');
				}

				return false;
			});

			c.hide();
			$('body').append(c);

			c.fadeIn(100);
			inp.focus();

			return false;
		});
	}

	osmium_keyboard_commands[longname] = {
		shortnames: shortnames,
		longname: longname,
		description: description,
		action: action,
	};

	if(shortnames !== null) {
		Mousetrap.bind(shortnames, action);
	}
};

/* Unregister a keyboard command. Use the same longname you used in
 * osmium_register_keyboard_command. */
osmium_unregister_keyboard_command = function(longname) {
	if(!(longname in osmium_keyboard_commands)) return;

	if(osmium_keyboard_commands[longname].shortnames !== null) {
		Mousetrap.unbind(osmium_keyboard_commands[longname].shortnames);
	}

	delete osmium_keyboard_commands[longname];

	if($.isEmptyObject(osmium_keyboard_commands)) {
		Mousetrap.unbind([ 'meta+x', 'alt+x', 'command+x' ]);
	}
};
