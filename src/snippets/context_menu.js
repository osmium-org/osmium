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

osmium_ctxmenu_bind = function(element, menu_constructor) {
	var indicator = $(document.createElement('span'));

	element.children('.contextmenuindicator').remove();
	indicator.text('≡');
	indicator.addClass('contextmenuindicator');

	element.append(indicator);

	var showmenu = function(e) {
		var menu = menu_constructor();
		menu.prop('id', 'ctxmenu');

		div = $(document.createElement('div'));
		div.prop('id', 'ctxbg');

		div.bind('click contextmenu', function(e2) {
			menu.click();
			$(document.elementFromPoint(e2.pageX, e2.pageY)).trigger(e2);
			return false;
		});

		menu.click(function() {
			$("ul#ctxmenu, div#ctxbg, ul.subctxmenu").remove();
		});

		$('ul#ctxmenu, div#ctxbg').remove();
		$('body').append(div).append(menu);

		var x = Math.min(e.pageX, $(document).width() - menu.width() - 5);
		var y = e.pageY;
		menu.css('left', x);
		menu.css('top', y);

		return false;
	};

	element.on('dblclick do_default_ctxmenu_action', function() {
		var menu = menu_constructor();
		menu.children('li.default').trigger('do_action');
	});

	element.on('contextmenu', showmenu);
	indicator.on('click', showmenu);

	element.addClass('hascontextmenu');
};

osmium_ctxmenu_create = function() {
	return $(document.createElement('ul'));
};

/* opts is a Hashtable that can accept the properties:
 * - icon: URI of the icon to show
 * - title: tooltip of this option (uses title attribute)
 * - enabled: whether this option is enabled or not (default yes)
 * - default: whether this option is the default when the element is double-clicked (default false)
 */
osmium_ctxmenu_add_option = function(menu, name, action, opts) {
	var li = $(document.createElement('li'));

	li.append($(document.createElement('span')).text(name));

	if("title" in opts) {
		li.prop('title', opts.title);
	}

	if("icon" in opts) {
		var img = $(document.createElement('img'));
		img.prop('alt', '');
		if(opts.icon.substring(0, 2) === '//') {
			/* Absolute URI of type //foo.tld/path.png */
			img.prop('src', opts.icon);
		} else {
			/* Relative URI */
			img.prop('src', osmium_relative + '/static-' + osmium_staticver + '/icons/' + opts.icon);
		}
		img.addClass('icon');
		li.prepend(img);
	}

	if(("enabled" in opts) && !opts.enabled) {
		li.addClass('disabled');
	} else {
		li.on('do_action click', function() {
			action();
			li.closest('ul#ctxmenu').click();
		});
	}

	if(("default" in opts) && opts['default'] && !li.hasClass('disabled')) {
		li.addClass('default');
	}

	menu.append(li);
};

osmium_ctxmenu_add_separator = function(menu) {
	var li = $(document.createElement('li'));
	li.addClass('separator');
	li.text(' ');
	menu.append(li);
};

/* Same parameters as osmium_ctxmenu_add_option(), but the action is
 * replaced by a submenu constructor function. */
osmium_ctxmenu_add_subctxmenu = function(menu, name, submenu_ctor, opts) {
	var li = $(document.createElement('li'));
	var timeout_in, timeout_out;
	var show_submenu;

	li.append($(document.createElement('span')).text(name));

	if("title" in opts) {
		li.prop('title', opts.title);
	}

	if("icon" in opts) {
		var img = $(document.createElement('img'));
		img.prop('alt', '');
		if(opts.icon.substring(0, 2) === '//') {
			/* Absolute URI of type //foo.tld/path.png */
			img.prop('src', opts.icon);
		} else {
			/* Relative URI */
			img.prop('src', osmium_relative + '/static-' + osmium_staticver + '/icons/' + opts.icon);
		}
		img.addClass('icon');
		li.prepend(img);
	}

	show_submenu = function() {
		var submenu = submenu_ctor();

		submenu.addClass('subctxmenu');
		if(li.children('ul.subctxmenu').length == 1) return;

		li.parent().find('ul.subctxmenu').remove();
		li.append(submenu);

		var offset = li.offset();
		submenu.offset({
			top: offset.top,
			left: offset.left + li.outerWidth()
		});
	};

	if(("enabled" in opts) && !opts.enabled) {
		li.addClass('disabled');
	} else {
		li.on('show_submenu', function(e) {
			show_submenu();
			e.stopPropagation();
			return false;
		}).on('hide_submenu', function(e) {
			li.children('ul.subctxmenu').remove();
			e.stopPropagation();
			return false;
		}).on('click', function(e) {
			li.trigger('show_submenu');
			e.stopPropagation();
			return false;
		}).on('mouseenter', function(e) {
			clearTimeout(timeout_out);
			timeout_in = setTimeout(function() {
				li.trigger('show_submenu');
			}, 100);
		}).on('mouseleave', function(e) {
			clearTimeout(timeout_in);
			timeout_out = setTimeout(function() {
				li.trigger('hide_submenu');
			}, 250);
		});
	}

	li.addClass('hassubcontextmenu');
	menu.append(li);
};
