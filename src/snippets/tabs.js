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

osmium_orig_anchor = null;

osmium_tabify = function(ul, selected) {
	var targets = [];

	ul.find('li > a').each(function() {
		var href = $(this).attr('href');
		if(href.substring(0, 1) != '#') return;
		targets.push(href.substring(1));
		$(href).addClass('notarget');
	});

	if(osmium_orig_anchor === null) {
		if(window.location.hash) {
			osmium_orig_anchor = $(window.location.hash.split(',')[0]);
			if(osmium_orig_anchor.length) {
				var p = osmium_orig_anchor.parent();
				var id, a;
				var found = false;

				while(p.length) {
					if(id = p.prop('id')) {
						var a = $("a[href='#" + id + "']");
						if(a.length && $.inArray(id, targets) > 0) {
							selected = a.parent().index();
							found = true;
							break;
						}
					}

					p = p.parent();
				}

				if(!found) osmium_orig_anchor = null;
			}
		}
	}

	ul.on('osmium_select_tab', 'li', function(event) {
		var li = $(this);
		var a = li.children('a');
		var href = a.attr('href');
		if(href.substring(0, 1) != '#') return;
		var dest = $(href);

		if(!li.hasClass('active')) {
			li.parent().children('li').each(function() {
				var li = $(this);
				var a = li.children('a');
				var href = a.attr('href');
				if(href.substring(0, 1) != '#') return;
				var dest = $(li.children('a').attr('href'));

				li.removeClass('active');
				dest.hide();
			});

			$(a.attr('href')).fadeIn(500);
			li.addClass('active').delay(501).trigger('afteractive');
		}

		a.blur();
		event.preventDefault();
		event.stopPropagation();
		return false;
	}).on('click', 'li', function() {
		var li = $(this);
		var a = li.children('a');
		var href = a.attr('href');
		if(href.substring(0, 1) != '#') return;
		var selectedid = href.substring(1);

		li.trigger('osmium_select_tab');

		var cur_tabs;
		var found = false;

		if(window.location.hash) {
			cur_tabs = window.location.hash.substring(1).split(',');
		} else {
			cur_tabs = [];
		}

		for(var i = 0; i < cur_tabs.length; ++i) {
			var j = $.inArray(cur_tabs[i], targets);
			if(j == -1) continue;

			cur_tabs[i] = selectedid;
			found = true;
			break;
		}

		if(!found) {
			cur_tabs.push(selectedid);
		}

		if(window.history && window.history.replaceState) {
			window.history.replaceState(
				null, null,
				window.location.href.substring(0, -window.location.hash) + '#' + cur_tabs.join(','));
		} else {
			var s_top = document.body.scrollTop;
			window.location.hash = '#' + cur_tabs.join(',');
			document.body.scrollTop = s_top;
		}

		if(osmium_orig_anchor && osmium_orig_anchor.length) {
			osmium_orig_anchor.addClass('pseudoclasstarget');
			$(window).scrollTop(osmium_orig_anchor.offset().top);
			osmium_orig_anchor = false;
		}

		return false;
	});

	var cur_tabs;
	if(window.location.hash) {
		cur_tabs = window.location.hash.substring(1).split(',');
	} else {
		cur_tabs = [];
	}

	for(var i = 0; i < cur_tabs.length; ++i) {
		var j = $.inArray(cur_tabs[i], targets);
		if(j == -1) continue;

		$('a[href="#' + cur_tabs[i] + '"]').closest('li').click();
		osmium_orig_anchor = false;
		return;
	}

	ul.children('li').eq(selected).click();
	osmium_orig_anchor = false;
};
