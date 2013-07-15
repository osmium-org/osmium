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

osmium_gen_ship = function() {
	var section = $('div#nlattribs > section#ship');
	var img, h, shipname, groupname;

	if("ship" in osmium_clf && "typeid" in osmium_clf.ship) {
		groupname = osmium_types[osmium_clf.ship.typeid][5];
		shipname = osmium_types[osmium_clf.ship.typeid][1];

		img = $(document.createElement('img'));
		img.prop('src', '//image.eveonline.com/Render/' + osmium_clf.ship.typeid + '_256.png');

		osmium_loadout_can_be_submitted();

		var availslots = osmium_ship_slots[osmium_clf.ship.typeid];
		osmium_clf_slots = {
			high: availslots[0],
			medium: availslots[1],
			low: availslots[2],
			rig: availslots[3],
			subsystem: availslots[4]
		};
		osmium_clf_hardpoints = {
			turret: 0,
			launcher: 0
		};
	} else {
		groupname = '';
		shipname = '｢No ship selected｣';

		img = $(document.createElement('div'));
		img.addClass('notype');

		osmium_clf_slots = {
			high: 8,
			medium: 8,
			low: 8,
			rig: 3,
			subsystem: 5
		};
		osmium_clf_hardpoints = {
			turret: 0,
			launcher: 0
		};
	}

	h = $(document.createElement('h1'));
	h.append(img);
	h.append($(document.createElement('small')).addClass('groupname').text(groupname));
	h.append($(document.createElement('strong')).text(shipname).prop('title', shipname));

	section.children('h1').remove();
	section.append(h);
};

osmium_init_ship = function() {
	osmium_ctxmenu_bind($("section#ship"), function() {
		var menu = osmium_ctxmenu_create();

		osmium_ctxmenu_add_option(menu, "Undo (Ctrl+_)", function() {
			osmium_undo_pop();
			osmium_commit_clf();
			osmium_user_initiated_push(false);
			osmium_gen();
			osmium_user_initiated_pop();
		}, { icon: "undo.png" });

		osmium_ctxmenu_add_separator(menu);

		osmium_ctxmenu_add_subctxmenu(menu, "Use skills", function() {
			var smenu = osmium_ctxmenu_create();

			for(var i = 0; i < osmium_skillsets.length; ++i) {
				osmium_ctxmenu_add_option(smenu, osmium_skillsets[i], (function(sname) {
					return function() {
						osmium_clf.metadata['X-Osmium-skillset'] = sname;
						osmium_undo_push();
						osmium_commit_clf();
					};
				})(osmium_skillsets[i]), {
					toggled: osmium_clf.metadata['X-Osmium-skillset'] === osmium_skillsets[i]
				});
			}

			return smenu;
		}, { icon: "//image.eveonline.com/Type/3327_64.png" });

		osmium_ctxmenu_add_subctxmenu(menu, "Reload times", function() {
			var smenu = osmium_ctxmenu_create();

			osmium_ctxmenu_add_option(smenu, "Include in capacitor time", function() {
				osmium_clf.metadata['X-Osmium-capreloadtime'] = !osmium_clf.metadata['X-Osmium-capreloadtime'];
				osmium_undo_push();
				osmium_commit_clf();
			}, { toggled: osmium_clf.metadata['X-Osmium-capreloadtime'] });

			osmium_ctxmenu_add_option(smenu, "Include in DPS", function() {
				osmium_clf.metadata['X-Osmium-dpsreloadtime'] = !osmium_clf.metadata['X-Osmium-dpsreloadtime'];
				osmium_undo_push();
				osmium_commit_clf();
			}, { toggled: osmium_clf.metadata['X-Osmium-dpsreloadtime'] });

			osmium_ctxmenu_add_option(smenu, "Include in sustained tank", function() {
				osmium_clf.metadata['X-Osmium-tankreloadtime'] = !osmium_clf.metadata['X-Osmium-tankreloadtime'];
				osmium_undo_push();
				osmium_commit_clf();
			}, { toggled: osmium_clf.metadata['X-Osmium-tankreloadtime'] });

			return smenu;
		}, {});

		osmium_ctxmenu_add_separator(menu);

		osmium_ctxmenu_add_option(menu, "Show ship info", function() {
			if("ship" in osmium_clf && "typeid" in osmium_clf.ship) {
				osmium_showinfo({ type: "ship" });
			} else {
				alert("No ship is selected. Please select one first (by searching for it or by using the browser).");
			}
		}, { icon: "showinfo.png", 'default': true });

		return menu;
	});

	/* This isn't pretty */
	$(document).keydown(function(e) {
		/* Chromium doesn't issue a keypress event */
		if(!e.ctrlKey || e.which != 189) return true;

		osmium_undo_pop();
		osmium_commit_clf();
		osmium_user_initiated_push(false);
		osmium_gen();
		osmium_user_initiated_pop();

		return false;
	}).keypress(function(e) {
		/* Firefox behaves as expected */
		if(!e.ctrlKey || e.which != 95) return true;

		osmium_undo_pop();
		osmium_commit_clf();
		osmium_user_initiated_push(false);
		osmium_gen();
		osmium_user_initiated_pop();

		return false;
	});
};
