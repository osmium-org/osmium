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
				.text('Target transversal velocity: ')
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

			var nturrets = 0, nlaunchers = 0, msr = 1, a;

			for(var i = 0; i < osmium_ia.length; ++i) {
				a = osmium_ia[i][4];
				if(!("damagetype" in a)) continue;

				if(a.damagetype === "turret") {
					++nturrets;
					msr = Math.max(msr, Math.ceil(a.sigradius * 0.9));
					continue;
				}

				if(a.damagetype === "missile") {
					++nlaunchers;
					continue;
				}
			}

			if(nturrets !== 0 || nlaunchers === 0) {
				form.find('td.signatureradius input').val(msr.toString());
			} else {
				form.find('td.distance input').val("1");
			}

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
osmium_get_dps_from_type_internal = function(a, tsr, tv, td) {
	if(!("damagetype" in a)) return 0;

	if(a.damagetype === "turret") {
		/* http://wiki.eveuniversity.org/Turret_Damage */

		if(tv == 0 && td == 0) {
			td = .001;
		}

		if(tsr == 0) return 0;

		var cth = Math.pow(
			0.5,
			Math.pow(
				((tv / (1000 * td)) / a.trackingspeed) * (a.sigradius / tsr),
				2
			) + Math.pow(
				Math.max(0, (1000 * td) - a.range) / a.falloff,
				2
			)
		);

		return (
			Math.min(cth, 0.01) * 3 + Math.max(cth - 0.01, 0) * (0.49 + (cth + 0.01) / 2)
		) * a.damage / a.duration;
	}

	if(a.damagetype === "missile") {
		/* http://wiki.eveuniversity.org/Missile_Damage */

		if(1000 * td > a.maxrange || a.damage == 0) return 0;

		return a.damage / a.duration * Math.min(
			1,
			tsr / a.expradius,
			(tsr != 0 && a.expvelocity != 0) ?
				Math.pow((tsr / a.expradius) * (a.expvelocity / tv), Math.log(a.drf) / Math.log(a.drs))
				: 0
		);
	}

	if(a.damagetype === "smartbomb") {
		if(1000 * td > a.maxrange) return 0;
		return a.damage / a.duration;
	}

	return 0;
};

/** @internal */
osmium_get_dps_internal = function(ia, args) {
	var dps = 0;
	for(var j = 0; j < ia.length; ++j) {
		dps += osmium_get_dps_from_type_internal(ia[j][4], args[0], args[1], args[2]);
	}
	return 1000 * dps;
};

osmium_graph_gen_labels = function(ctx, canvas, xlabel, ylabel) {
	var xl, yl;
	ctx.append(xl = $(document.createElement('span')).addClass('xlabel').text(xlabel));
	ctx.append(yl = $(document.createElement('span')).addClass('ylabel').text(ylabel));

	var cpos = canvas.offset();

	xl.offset({
		top: cpos.top + canvas.height() + 4,
		left: cpos.left + canvas.width() / 2 - xl.width() / 2
	});

	/* Rotating first gives different results on Chromium/Firefox */
	yl.offset({
		top: cpos.top + canvas.height() / 2 - yl.height() / 2,
		left: cpos.left - yl.width() / 2 - yl.height() / 2 - 4
	}).addClass('rotated');
};

osmium_graph_draw_grid = function(cctx, cw, ch, xmin, xmax, xsteps, ymin, ymax, ysteps, axisopacity, labelopacity) {
	var steps = [ 50000, 20000, 10000,
				  5000, 2000, 1000,
				  500, 200, 100,
				  50, 20, 10,
				  5, 2, 1,
				  .5, .2, .1,
				  .05, .02, .01,
				  .005, .002, .001,
				  .0005, .0002, .0001 ];

	var xstep = 1, ystep = 1;
	for(var i = 0; i < steps.length; ++i) {
		if((xmax - xmin) / steps[i] >= xsteps) {
			xstep = steps[i];
			break;
		}
	}
	for(var i = 0; i < steps.length; ++i) {
		if((ymax - ymin) / steps[i] >= ysteps) {
			ystep = steps[i];
			break;
		}
	}

	cctx.beginPath();
	cctx.font = '0.8em "Droid Sans"';
	cctx.fillStyle = "hsla(0, 0%, 50%, " + labelopacity.toString() + ")";

	cctx.textAlign = "center";
	cctx.textBaseline = "bottom";
	for(var x = Math.ceil(xmin / xstep) * xstep; x < xmax; x += xstep) {
		if(x === xmin) continue;

		var xc = Math.floor(cw * (x - xmin) / (xmax - xmin)) + 0.5;
		cctx.moveTo(xc, 0.5);
		cctx.lineTo(xc, ch - 0.5);
		cctx.fillText(x.toString(), xc, ch - 0.5);
	}

	cctx.textAlign = "left";
	cctx.textBaseline = "middle";
	for(var y = Math.ceil(ymin / ystep) * ystep; y < ymax; y += ystep) {
		if(y === ymin) continue;

		var yc = Math.floor(ch * (y - ymin) / (ymax - ymin)) + 0.5;
		cctx.moveTo(0.5, ch - yc);
		cctx.lineTo(cw - 0.5, ch - yc);
		cctx.fillText(y.toString(), 2.5, ch - yc);
	}

	cctx.strokeStyle = "hsla(0, 0%, 50%, " + axisopacity.toString() + ")";
	cctx.stroke();
};

osmium_heat_color = function(t) {
	return "hsla("
		//+ Math.round((1 - t) * 60).toString()
		+ Math.round((1 - t) * 360).toString()
		+ ", 100%, 50%, "
		+ Math.min(1, t).toFixed(2)
		+ ")";
};

/** @internal */
osmium_probe_boundaries_internal = function(ia, tsr, tv, td) {
	var tsrmax = 50, tvmax = 50, tdmax = 5;

	if(isNaN(td)) {
		for(var j = 0; j < ia.length; ++j) {
			if(!("damagetype" in ia[j][4])) continue;

			if("range" in ia[j][4] && "falloff" in ia[j][4]) {
				tdmax = Math.max(tdmax, ia[j][4].range + 3 * ia[j][4].falloff);
				continue;
			}

			if("maxrange" in ia[j][4]) {
				tdmax = Math.max(tdmax, ia[j][4].maxrange * 1.1);
				continue;
			}
		}

		tdmax /= 1000;
	} else {
		tdmax = td;
	}

	if(isNaN(tsr)) {
		for(var j = 0; j < ia.length; ++j) {
			if(!("damagetype" in ia[j][4])) continue;

			if("sigradius" in ia[j][4]) {
				tsrmax = Math.max(tsrmax, ia[j][4].sigradius * 5);
				continue;
			}

			if("expradius" in ia[j][4]) {
				tsrmax = Math.max(tsrmax, ia[j][4].expradius * 2.5);
				continue;
			}
		}
	} else {
		tsrmax = tsr;
	}

	if(isNaN(tv)) {
		for(var j = 0; j < ia.length; ++j) {
			if(!("damagetype" in ia[j][4])) continue;

			if("trackingspeed" in ia[j][4]) {
				tvmax = Math.max(
					tvmax,
					Math.min(15000, (isNaN(td) ? tdmax : (td * 3)) * 1000 * ia[j][4].trackingspeed)
				);
				continue;
			}

			if("expvelocity" in ia[j][4]) {
				tvmax = Math.max(tvmax, ia[j][4].expvelocity * 8);
				continue;
			}
		}
	} else {
		tvmax = tv;
	}

	return [ tsrmax, tvmax, tdmax ];
};

/* Expects exactly one of the tsr, tv, td parameters to be NaN. */
osmium_gen_dps_graph_1d = function(ia, ctx, tsr, tv, td) {
	var genfunc, xlabel, xmax;

	if(isNaN(tsr)) {
		genfunc = function(x) { return [ x, tv, td ]; };
		xlabel = "Target signature radius (m)";
		xmax = osmium_probe_boundaries_internal(ia, tsr, tv, td)[0];
	} else if(isNaN(tv)) {
		genfunc = function(x) { return [ tsr, x, td ]; };
		xlabel = "Target transversal velocity (m/s)";
		xmax = osmium_probe_boundaries_internal(ia, tsr, tv, td)[1];
	} else if(isNaN(td)) {
		genfunc = function(x) { return [ tsr, tv, x ]; };
		xlabel = "Target distance (km)";
		xmax = osmium_probe_boundaries_internal(ia, tsr, tv, td)[2];
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
	b = osmium_probe_boundaries_internal(ia, tsr, tv, td);

	if(!isNaN(tsr)) {
		genfunc = function(x, y) { return [ tsr, y, x ]; };
		ylabel = "Target transversal velocity (m/s)";
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
		xlabel = "Target transversal velocity (m/s)";
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
