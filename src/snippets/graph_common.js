/* Osmium
 * Copyright (C) 2013 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

/**
 * Try to auto-guess graph boundaries with some given
 * constraints. Parameters tsr, tv, td can be filled or left
 * out. Returns an array of three values [ tsrMax, tvMax, tdMax ].
 */
osmium_probe_boundaries_from_ia = function(ia, tsr, tv, td) {
	var tsrmax = 50, tvmax = 50, tdmax = 5000;
	var a;

	if(isNaN(td)) {
		for(var j = 0; j < ia.length; ++j) {
			a = ia[j].raw;
			if(!("damagetype" in a)) continue;

			var m = Math.min(
				("range" in a && "falloff" in a) ? (a.range + 3 * a.falloff) : Infinity,
				("maxrange" in a) ? (a.maxrange * 1.1) : Infinity,
				("controlrange" in a) ? (a.controlrange * 1.1) : Infinity
			);

			if(isFinite(m)) tdmax = Math.max(tdmax, m);
		}

		tdmax /= 1000;
	} else {
		tdmax = td;
	}

	if(isNaN(tsr)) {
		for(var j = 0; j < ia.length; ++j) {
			a = ia[j].raw;
			if(!("damagetype" in a)) continue;

			if("sigradius" in a) {
				tsrmax = Math.max(tsrmax, a.sigradius * 3);
				continue;
			}

			if("expradius" in a) {
				tsrmax = Math.max(tsrmax, a.expradius * 3);
				continue;
			}
		}
	} else {
		tsrmax = tsr;
	}

	if(isNaN(tv)) {
		for(var j = 0; j < ia.length; ++j) {
			a = ia[j].raw;
			if(!("damagetype" in a)) continue;

			if("trackingspeed" in a && "range" in a && "falloff" in a) {
				if(a.damagetype === "combatdrone" && a.maxvelocity > 0) {
					continue;
				}

				tvmax = Math.max(
					tvmax,
					Math.min(a.damagetype === "combatdrone" ? 2500 : 12000,
							 (a.range + a.falloff) * a.trackingspeed)
				);
				continue;
			}

			if("expvelocity" in a) {
				tvmax = Math.max(tvmax, a.expvelocity * 8);
				continue;
			}
		}
	} else {
		tvmax = tv;
	}

	return [ tsrmax, tvmax, tdmax ];
};

/**
 * Return a color from a parameter between 0 and 1. The color is red
 * for 1, and will smoothly go through all the color spectrum to reach
 * transparent-ish purple at zero.
 */
osmium_heat_color = function(t) {
	return "hsla("
		+ Math.round((1 - t) * 360).toString()
		+ ", 100%, 50%, "
		+ Math.min(1, t).toFixed(2)
		+ ")";
};

/**
 * Generate labels and append them next to a canvas element.
 *
 * @param ctx the root element to add the labels to
 * @param canvas the canvas element
 * @param xlabel text to label the X axis with
 * @param ylabel text to label the Y axis with
 */
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

/**
 * Draw a labeled grid using a given canvas context.
 *
 * @param cctx the canvas context to draw with
 * @param cw canvas width
 * @param ch canvas height
 * @param xmin minimum value for X axis
 * @param xmax maximum value for X axis
 * @param xsteps minimum number of vertical guides to draw
 * @param ymin minimum value for Y axis
 * @param ymax maximum value for Y axis
 * @param ysteps minimum value of horizontal guides to draw
 * @param axisopacity opacity (between 0 and 1) of the drawn guides
 * @param labelopacity opacity (between 0 and 1) of the drawn labels
 */
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

/**
 * Get the average DPS of a turret-like weapon.
 *
 * http://wiki.eveuniversity.org/Turret_Damage
 *
 * @param dps the raw DPS of the turret
 * @param trackingspeed tracking speed of the turret, in rad/s
 * @param sigresolution signature resolution of the turret, in meters
 * @param range optimal range of the turret, in meters
 * @param falloff falloff range of the turret, in meters
 * @param tsr target signature radius, in meters
 * @param tv target velocity, in m/s
 * @param td target distance, in km
 */
osmium_turret_damage_f = function(dps, trackingspeed, sigresolution, range, falloff, tsr, tv, td) {
	if(tv == 0 && td == 0) td = .001;
	if(tsr == 0) return 0;

	var cth = Math.pow(
		0.5,
		Math.pow(
			((tv / (1000 * td)) / trackingspeed) * (sigresolution / tsr),
			2
		) + Math.pow(
			Math.max(0, (1000 * td) - range) / falloff,
			2
		)
	);

	return (
		Math.min(cth, 0.01) * 3 + Math.max(cth - 0.01, 0) * (0.49 + (cth + 0.01) / 2)
	) * dps;
};

/**
 * Get the average DPS of a missile-like weapon.
 *
 * http://wiki.eveuniversity.org/Missile_Damage
 *
 * @param dps the raw DPS of the missile launcher
 * @param maxrange the maximum range of the missile
 * @param expradius explosion radius of the missile
 * @param expvelocity explosion velocity of the missile
 * @param drf damage reduction factor
 * @param drs damage reduction sensitivity
 * @param tsr target signature radius, in meters
 * @param tv target velocity, in m/s
 * @param td target distance, in km
 */
osmium_missile_damage_f = function(dps, maxrange, expradius, expvelocity, drf, drs, tsr, tv, td) {
	if(1000 * td > maxrange || dps == 0) return 0;

	return dps * Math.min(
		1,
		tsr / expradius,
		(tsr != 0 && expvelocity != 0) ?
			Math.pow((tsr / expradius) * (expvelocity / tv), Math.log(drf) / Math.log(drs))
			: 0
	);
};

/** Get the average DPS of a fitted type. */
osmium_get_dps_from_type_internal = function(a, tsr, tv, td) {
	if(!("damagetype" in a)) return 0;

	if(a.damagetype === "combatdrone" || a.damagetype === "fighter" || a.damagetype === "fighterbomber") {
		if(a.damagetype === "combatdrone" && "controlrange" in a && 1000 * td > a.controlrange) return 0;

		if(a.maxvelocity == 0) {
			/* Sentry drone */
			return osmium_turret_damage_f(
				a.damage / a.duration,
				a.trackingspeed, a.sigradius, a.range, a.falloff,
				tsr, tv, td
			);
		}

		/* XXX: this is a very simplistic model, totally inaccurate
		 * guesswork. Critique & improvements most welcomed! */

		/* Drone tries to keep orbit at flyrange m @ cruisespeed m/s */
		/* After a full cycle, assume the drone will use MWD to
		 * reenter orbit distance */
		var ddur = a.duration;

		if(tv > a.cruisespeed) {
			if(tv >= a.maxvelocity) {
				/* Drone will never catch up */
				ddur = Infinity;
			} else {
				ddur += (tv - a.cruisespeed) * a.duration / (a.maxvelocity - tv);
			}
		}

		if(a.damagetype === "fighterbomber") {
			return osmium_missile_damage_f(
				a.damage / ddur,
				a.maxrange, a.expradius, a.expvelocity, a.drf, a.drs,
				tsr, tv, a.flyrange / 1000.0
			);
		}

		return osmium_turret_damage_f(
			a.damage / ddur,
			a.trackingspeed, a.sigradius, a.range, a.falloff,
			tsr, a.cruisespeed, a.flyrange / 1000.0
		);
	}

	if(a.damagetype === "turret") {
		return osmium_turret_damage_f(
			a.damage / a.duration,
			a.trackingspeed, a.sigradius, a.range, a.falloff,
			tsr, tv, td
		);
	}

	if(a.damagetype === "missile") {
		return osmium_missile_damage_f(
			a.damage / a.duration,
			a.maxrange, a.expradius, a.expvelocity, a.drf, a.drs,
			tsr, tv, td
		);
	}

	if(a.damagetype === "smartbomb") {
		if(1000 * td > a.maxrange) return 0;
		return a.damage / a.duration;
	}

	return 0;
};
