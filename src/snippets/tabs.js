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
osmium_selected_tabs = [];
osmium_available_tabs = [];
osmium_tab_ul_count = 0;

osmium_tabify = function(ul, selected) {
	if(osmium_orig_anchor === null) {
		if(window.location.hash) {
			osmium_orig_anchor = window.location.hash.substring(1).split(",");
		} else {
			osmium_orig_anchor = [];
		}
	}

	var tabs = [];
	var i = 0;
	var selected_pref = 0;

	ul.find('li > a').each(function() {
		var t = $(this);
		var href = t.attr('href');
		if(href.substring(0, 1) != '#') return;
		t.parent().addClass('anchor');
		tabs.push(href.substring(1));
		++i;
	});

	for(var j = 0; j < osmium_orig_anchor.length; ++j) {
		var index = $.inArray(osmium_orig_anchor[j], tabs);
		if(index !== -1) {
			/* Got one of our tabs in hash */
			if(selected_pref < 1) {
				selected = index;
				selected_pref = 1;
			}
			osmium_orig_anchor.splice(j, 1);

			/* Don't break here, as this choice may be overridden by a
			 * "true" anchor in another tab */
		} else {
			if(selected_pref >= 2) continue; /* Already found */

			/* Not one of our tabs, see if it is an anchor in one of our tabs */
			var target = $('#' + osmium_orig_anchor[j]);
			if(target.length === 0) continue;
			target = target.parent();
			do {
				var id = target.prop('id');
				var index;
				if(id && ((index = $.inArray(id, tabs)) !== -1)) {
					/* Found one of our tabs as parent */
					selected = index;
					selected_pref = 2;
					break;
				}
				target = target.parent();
			} while("length" in target && target.length !== 0);
		}
	}

	i = 0;
	ul.find('li.anchor').each(function() {
		var t = $(this);
		var href = t.children('a').attr('href');
		var tget = $(href).addClass('notarget');

		if(i !== selected) {
			tget.hide().trigger('made_hidden');
		} else {
			t.addClass('active').trigger('made_visible');
		}
		++i;
	});

	var found = false;
	for(i = 0; i < osmium_available_tabs.length; ++i) {
		if($.inArray(tabs[selected], osmium_available_tabs[i]) !== -1) {
			ul.data('tab_ul_index', i);
			found = true;
			break;
		}
	}
	if(!found) {
		osmium_available_tabs.push(tabs);
		osmium_selected_tabs.push(tabs[selected]);
		ul.data('tab_ul_index', osmium_tab_ul_count++);
	}

	ul.find('li.anchor').on('click', osmium_tab_click);

	for(i = 0; i < osmium_orig_anchor.length; ++i) {
		var target = $("#" + osmium_orig_anchor[i]);
		if(target.length === 0) continue;
		if(!target.is(":visible")) continue;
		$(window).scrollTop(target.offset().top);
		return;
	}

	$(window).scrollTop(0);
};

osmium_tab_click = function(e) {
	var li = $(this);
	var ul_index = li.parent().data('tab_ul_index');
	var tabnames = osmium_available_tabs[ul_index];
	var want = li.children('a').blur().attr('href').substring(1);

	for(var i = 0; i < tabnames.length; ++i) {
		if(tabnames[i] === want) {
			$("#" + want).fadeIn(250).trigger('made_visible');
		} else {
			$("#" + tabnames[i]).hide().trigger('made_hidden');
		}
	}

	li.parent().children('li.active').removeClass('active');
	li.addClass('active');

	osmium_selected_tabs[ul_index] = want;

	if(window.history && window.history.replaceState) {
		window.history.replaceState(null, null, '#' + osmium_selected_tabs.join(','));
	}

	e.preventDefault();
	e.stopPropagation();
	return false;
};

osmium_tabify_nohash = function(ul, selected) {
	ul.find('li > a').on('click', function(e) {
		var t = $(this);
		var href = t.attr('href');
		if(href.substring(0, 1) !== '#') return;
		var tgt = $(t.attr('href'));
		var li = t.parent();

		if(li.hasClass('active')) return false;
		ul.find('li.active > a').each(function() {
			var a = $(this);
			var tgt = $(a.attr('href'));
			tgt.hide().trigger('made_hidden');
			a.parent().removeClass('active');
		});

		li.addClass('active');
		tgt.fadeIn(250).trigger('made_visible');

		return false;
	}).each(function() {
		var t = $(this);
		var href = t.attr('href');
		if(href.substring(0, 1) !== '#') return;
		$(t.attr('href')).hide();
	});

	ul.children('li').eq(selected).children('a').click();
};
