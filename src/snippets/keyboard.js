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
			c.data('tabcount', 0);

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
			inp.data('lastval', inp.val());
			form.append(inp);

			var submit = $(document.createElement('input'));
			submit.prop('type', 'submit');
			form.append(submit); /* Form won't submit on RET without a submit button */

			var ul = $(document.createElement('ul'));
			c.append(ul);
			for(var lc in osmium_keyboard_commands) {
				ul.append(
					$(document.createElement('li'))
						.text(lc)
						.prop('title', lc + ' – ' + osmium_keyboard_commands[lc].description)
				);
			}
			ul.find('li').sort(function(a, b) {
				var at = $(a).text();
				var bt = $(b).text();
				return (at < bt) ? -1 : ((at > bt) ? 1 : 0);

			}).appendTo(ul);

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

			inp.on('keyup', function() {
				if(inp.val() !== inp.data('lastval')) {
					inp.data('lastval', inp.val());
					c.data('tabcount', 0);
					inp.removeClass('error');
				}
			});

			Mousetrap.bind('tab', function() {
				var tc = c.data('tabcount');
				var buf = inp.val();

				if(tc == 0) {
					/* Hide non-matching commands, fill up input with
					 * largest prefix of matched commands */
					ul.find('li').each(function() {
						var li = $(this);
						if(li.text().substring(0, buf.length) === buf) {
							li.show();
						} else {
							li.hide();
						}
					});

					var matches = ul.find('li:visible');
					if(matches.length === 0) {
						inp.addClass('error');
					} else {
						inp.removeClass('error');
						var largestprefix = matches.first().text();

						matches.each(function() {
							var v = $(this).text();
							var i = 0;
							var ml = largestprefix.length;

							while(i <= ml && largestprefix.substring(0, i) === v.substring(0, i)) {
								++i;
							}

							largestprefix = v.substring(0, i-1);
						});

						inp.val(largestprefix);
						inp.data('lastval', inp.val());
					}
				} else if(tc >= 2) {
					/* Cycle through matches */

					var matches = ul.find('li:visible');
					if(matches.length >= 1) {
						inp.val($(matches[(tc - 2) % matches.length]).text());
						inp.data('lastval', inp.val());
					}
				}

				c.data('tabcount', tc+1);
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
