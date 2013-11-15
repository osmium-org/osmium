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
		}, { icon: [ 1, 13, 64, 64 ] });

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

		osmium_ctxmenu_add_option(menu, "DPS graphs…", function() {
			var hdr = $(document.createElement('header')).append(
				$(document.createElement('h2')).text('Damage per second graph')
			);
			var form = $(document.createElement('form'))
				.prop('id', 'm-dpsg');
			var table = $(document.createElement('table'));
			var tbody = $(document.createElement('tbody'));
			var tr = $(document.createElement('tr'));
			tbody.append(tr);
			table.append(tbody);
			form.append(table);

			var td, label, input;

			td = $(document.createElement('td')).addClass('signatureradius');
			label = $(document.createElement('label'))
				.prop('for', 'm-dpsg-signatureradius')
				.text('Target signature radius: ')
			;
			input = $(document.createElement('input'))
				.prop('type', 'text')
				.prop('id', 'm-dpsg-signatureradius')
			;
			td.append([ label, input, " m" ]);
			tr.append(td);

			td = $(document.createElement('td')).addClass('velocity');
			label = $(document.createElement('label'))
				.prop('for', 'm-dpsg-velocity')
				.text('Target velocity: ')
			;
			input = $(document.createElement('input'))
				.prop('type', 'text')
				.prop('id', 'm-dpsg-velocity')
			;
			td.append([ label, input, " m/s" ]);
			tr.append(td);

			td = $(document.createElement('td')).addClass('distance');
			label = $(document.createElement('label'))
				.prop('for', 'm-dpsg-distance')
				.text('Target distance: ')
			;
			input = $(document.createElement('input'))
				.prop('type', 'text')
				.prop('id', 'm-dpsg-distance')
			;
			td.append([ label, input, " km" ]);
			tr.append(td);

			td = $(document.createElement('td'));
			input = $(document.createElement('input'))
				.prop('type', 'submit')
				.val('Generate graph')
			;
			td.append(input);
			tr.append(td);

			var ctx = $(document.createElement('div')).prop('id', 'm-dpsg-ctx');

			form.on('submit', function(e) {
				e.preventDefault();

				var tsr, tv, td;
				tsr = parseFloat(form.find('td.signatureradius input').val());
				tv = parseFloat(form.find('td.velocity input').val());
				td = parseFloat(form.find('td.distance input').val());

				var nans = 0;
				if(isNaN(tsr)) ++nans;
				if(isNaN(tv)) ++nans;
				if(isNaN(td))  ++nans;

				if(nans !== 1 && nans !== 2) {
					alert('You must fill exactly one or two fields with number values.');
					return false;
				}

				ctx.empty();

				if(nans === 1) {
					osmium_gen_dps_graph_1d(osmium_ia, ctx, tsr, tv, td);
				} else if(nans === 2) {
					osmium_gen_dps_graph_2d(osmium_ia, ctx, tsr, tv, td);
				}

				return false;
			});

			var limits = osmium_probe_boundaries_from_ia(osmium_ia, NaN, NaN, NaN);
			form.find('td.signatureradius input').val(Math.round(limits[0] / 3).toString());

			osmium_modal([ hdr, form, ctx ]);
			form.trigger('submit');
		}, {});

		osmium_ctxmenu_add_separator(menu);

		osmium_ctxmenu_add_option(menu, "Show ship info", function() {
			if("ship" in osmium_clf && "typeid" in osmium_clf.ship) {
				osmium_showinfo({ type: "ship" });
			} else {
				alert("No ship is selected. Please select one first (by searching for it or by using the browser).");
			}
		}, { icon: osmium_showinfo_sprite_position, 'default': true });

		return menu;
	});

	/* This isn't pretty */
	$(document).keydown(function(e) {
		/* Chromium doesn't issue a keypress event */
		if(!e.ctrlKey || e.which != 189) return true;
		e.preventDefault();

		osmium_undo_pop();
		osmium_commit_clf();
		osmium_user_initiated_push(false);
		osmium_gen();
		osmium_user_initiated_pop();

		return false;
	}).keypress(function(e) {
		/* Firefox behaves as expected */
		if(!e.ctrlKey || e.which != 95) return true;
		e.preventDefault();

		osmium_undo_pop();
		osmium_commit_clf();
		osmium_user_initiated_push(false);
		osmium_gen();
		osmium_user_initiated_pop();

		return false;
	});
};



/** @internal */
osmium_get_dps_internal = function(ia, args) {
	var dps = 0;
	for(var j = 0; j < ia.length; ++j) {
		dps += osmium_get_dps_from_type_internal(ia[j].raw, args[0], args[1], args[2]);
	}
	return 1000 * dps;
};

/* Expects exactly one of the tsr, tv, td parameters to be NaN. */
osmium_gen_dps_graph_1d = function(ia, ctx, tsr, tv, td) {
	var genfunc, xlabel, xmax;

	if(isNaN(tsr)) {
		genfunc = function(x) { return [ x, tv, td ]; };
		xlabel = "Target signature radius (m)";
		xmax = osmium_probe_boundaries_from_ia(ia, tsr, tv, td)[0];
	} else if(isNaN(tv)) {
		genfunc = function(x) { return [ tsr, x, td ]; };
		xlabel = "Target velocity (m/s)";
		xmax = osmium_probe_boundaries_from_ia(ia, tsr, tv, td)[1];
	} else if(isNaN(td)) {
		genfunc = function(x) { return [ tsr, tv, x ]; };
		xlabel = "Target distance (km)";
		xmax = osmium_probe_boundaries_from_ia(ia, tsr, tv, td)[2];
	} else return false;

	var canvas = document.createElement('canvas');
	var cctx = canvas.getContext('2d');
	var cw, ch;
	canvas = $(canvas);
	ctx.append($(document.createElement('div')).addClass('cctx').append(canvas));
	canvas.attr('width', cw = canvas.width());
	canvas.attr('height', ch = canvas.height());

	osmium_graph_gen_labels(ctx, canvas, xlabel, "Damage per second");

	var x, dps, maxdps = 10, px, py;

	for(var i = 0; i <= cw; ++i) {
		x = (i / cw) * xmax;
		maxdps = Math.max(maxdps, osmium_get_dps_internal(ia, genfunc(x)));
	}

	maxdps *= 1.05;

	osmium_graph_draw_grid(cctx, cw, ch, 0, xmax, 8, 0, maxdps, 4, 0.15, 0.5);

	cctx.beginPath();
	cctx.moveTo(0, 0);

	for(var i = 0; i <= cw; ++i) {
		x = (i / cw) * xmax;
		dps = osmium_get_dps_internal(ia, genfunc(x));
		px = i + 0.5;
		py = Math.min(ch - 2, Math.floor(ch * (1 - dps / maxdps))) + 0.5;

		if(i === 0) {
			cctx.moveTo(px, py);
		} else {
			cctx.lineTo(px, py);
		}
	}

	cctx.strokeStyle = "hsl(0, 100%, 50%)";
	cctx.lineWidth = 3;
	cctx.stroke();
};

/* Expects exactly two of the tsr, tv, td parameters to be NaN. */
osmium_gen_dps_graph_2d = function(ia, ctx, tsr, tv, td) {
	var genfunc, xlabel, ylabel, xmax, ymax, b;
	b = osmium_probe_boundaries_from_ia(ia, tsr, tv, td);

	if(!isNaN(tsr)) {
		genfunc = function(x, y) { return [ tsr, y, x ]; };
		ylabel = "Target velocity (m/s)";
		xlabel = "Target distance (km)";
		ymax = b[1]; xmax = b[2];
	} else if(!isNaN(tv)) {
		genfunc = function(x, y) { return [ y, tv, x ]; };
		ylabel = "Target signature radius (m)";
		xlabel = "Target distance (km)";
		ymax = b[0]; xmax = b[2];
	} else if(!isNaN(td)) {
		genfunc = function(x, y) { return [ y, x, td ]; };
		ylabel = "Target signature radius (m)";
		xlabel = "Target velocity (m/s)";
		ymax = b[0]; xmax = b[1];
	} else return false;

	var canvas = document.createElement('canvas');
	var cctx = canvas.getContext('2d');
	var cw, ch;
	canvas = $(canvas);
	ctx.append($(document.createElement('div')).addClass('cctx').addClass('twodim').append(canvas));
	canvas.attr('width', cw = canvas.width());
	canvas.attr('height', ch = canvas.height());

	osmium_graph_gen_labels(ctx, canvas, xlabel, ylabel);

	cctx.moveTo(0, ch);
	var x, y, fracdps, px, py, maxdps = 10, pixelsize = 2;

	for(var i = 0; i <= cw; i += 4) {
		x = (i / cw) * xmax;

		for(var j = 0; j <= ch; j += 4) {
			y = (j / ch) * ymax;
			maxdps = Math.max(maxdps, osmium_get_dps_internal(ia, genfunc(x, y)));
		}
	}

	var lcanvas = document.createElement('canvas');
	var lctx = lcanvas.getContext('2d');
	var lw, lh;
	lcanvas = $(lcanvas);
	ctx.append($(document.createElement('div')).addClass('legend').append(lcanvas));
	lcanvas.attr('width', lw = lcanvas.width());
	lcanvas.attr('height', lh = lcanvas.height());

	for(var i = 0; i <= lh; ++i) {
		lctx.fillStyle = osmium_heat_color(i / lh);
		lctx.fillRect(0, lh - i, 100, 1);
	}

	var dlabel = $(document.createElement('span')).text('DPS');
	ctx.append(dlabel);
	var lpos = lcanvas.parent().offset();
	dlabel.offset({
		top: lpos.top + lcanvas.parent().height() + 5,
		left: lpos.left + lcanvas.parent().width() / 2 - dlabel.width() / 2
	});

	lpos = lcanvas.offset();
	var nlabels = 6;
	for(var i = 0; i <= nlabels; ++i) {
		dlabel = $(document.createElement('span')).addClass('dpslabel')
			.text(Math.round((i / nlabels) * maxdps).toString());
		ctx.append(dlabel);
		dlabel.offset({
			top: Math.min(
				Math.max(
					lpos.top + lcanvas.height() * (1 - i / nlabels) - dlabel.height() / 2,
					lpos.top
				),
				lpos.top + lcanvas.height() - dlabel.height()
			),
			left: lpos.left - dlabel.width() - 4
		});
	}

	for(var i = 0; i <= cw; i += pixelsize) {
		x = (i / cw) * xmax;

		for(var j = 0; j <= ch; j += pixelsize) {
			y = (j / ch) * ymax;
			fracdps = Math.min(1, osmium_get_dps_internal(ia, genfunc(x, y)) / maxdps);

			cctx.fillStyle = osmium_heat_color(fracdps);
			cctx.fillRect(i, ch - j, pixelsize, pixelsize);
		}
	}

	osmium_graph_draw_grid(cctx, cw, ch, 0, xmax, 8, 0, ymax, 4, 0.15, 0.75);
};
