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

osmium_ia_map = {};
osmium_relative = '..';

$(function() {
	var labels = $('form#lsources label');
	var lmaxc = labels.length;
	var color;

	osmium_relative = $("div#osmium-data").data('relative');

	for(var i = 0; i < lmaxc; ++i) {
		var hue = Math.round(360 * (i / lmaxc));
		color = 'hsl(' + hue + ', 100%, 50%)';
		labels.eq(i).css('border-color', color).closest('tr').data('hue', hue);
	}

	var graphctx = $('div#graphcontext');
	graphctx.height(Math.ceil(graphctx.width() / 1.618));

	var gpform = $('form#gparams');
	gpform.on('tick', function() {
		/* Enforce consistency of graph parameters */

		var xchecked = gpform
			.find('ul.x')
			.find('input:radio:not(:disabled):checked')
			.val();

		if(!xchecked) {
			gpform.find('ul.x input:radio#xaxistype_td').prop('checked', true);
			xchecked = 'td';
		}

		var ychecked = gpform
			.find('ul.y')
			.find('input:radio:not(:disabled):checked')
			.val();

		if(!ychecked) {
			gpform.find('ul.y input:radio#yaxistype_dps').prop('checked', true);
			ychecked = 'dps';
		}

		if(xchecked === ychecked) {
			gpform
				.find('ul.y')
				.find('input:radio:checked')
				.prop('checked', false);
			gpform.find('ul.y input:radio#yaxistype_dps').prop('checked', true);
			ychecked = 'dps';
		}

		gpform.find('ul.x').find('input:radio')
			.prop('disabled', false)
			.parent().removeClass('disabled');
		gpform.find('ul.x').find('input:radio#xaxistype_' + ychecked)
			.prop('disabled', true)
			.parent().addClass('disabled');

		gpform.find('ul.y').find('input:radio')
			.prop('disabled', false)
			.parent().removeClass('disabled');
		gpform.find('ul.y').find('input:radio#yaxistype_' + xchecked)
			.prop('disabled', true)
			.parent().addClass('disabled');

		gpform.find('ul.x, ul.y').find('li.selected').removeClass('selected');
		gpform.find('ul.x, ul.y').find('div > input').prop('disabled', true);
		gpform.find('ul.x, ul.y').find('input:radio:checked').each(function() {
			$(this).parent().addClass('selected').find('div > input').prop('disabled', false);
		});

		gpform.find('ul.initvalues li.disabled')
			.removeClass('disabled').find('input').prop('disabled', false);
		gpform.find('ul.initvalues').find('li.' + xchecked + ', li.' + ychecked)
			.addClass('disabled').find('input').prop('disabled', true);
	}).on('click, change', 'input:radio', function() {
		gpform.trigger('tick');
	}).on('submit', function(e) {
		e.preventDefault();
		gpform.trigger('tick');

		var iacnt = 0, tsrs = 0, tvs = 0, tds = 0;
		for(var k in osmium_ia_map) {
			if(!("ia" in osmium_ia_map[k])) continue;
			var ia = osmium_ia_map[k].ia;
			var opts = osmium_probe_optimals_from_ia(ia);
			iacnt++;
			tsrs += opts[0];
			tvs += opts[1];
			tds += opts[2];
		}

		var tsrInit, tvInit, tdInit;
		tsrInit = iacnt === 0 ? 250 : Math.round(tsrs / iacnt);
		tvInit = iacnt === 0 ? 0 : Math.round(tvs / iacnt);
		tdInit = iacnt === 0 ? 1 : Math.round(tds / iacnt);

		var initinp, override;

		initinp = gpform.find('input.init.tsr');
		if(initinp.prop('disabled')) {
			tsrInit = NaN;
		} else {
			override = parseFloat(initinp.val());
			if(isNaN(override)) {
				initinp.prop('placeholder', 'auto (' + tsrInit.toString() + ')');
			} else {
				tsrInit = override;
			}
		}

		initinp = gpform.find('input.init.tv');
		if(initinp.prop('disabled')) {
			tvInit = NaN;
		} else {
			override = parseFloat(initinp.val());
			if(isNaN(override)) {
				initinp.prop('placeholder', 'auto (' + tvInit.toString() + ')');
			} else {
				tvInit = override;
			}
		}

		initinp = gpform.find('input.init.td');
		if(initinp.prop('disabled')) {
			tdInit = NaN;
		} else {
			override = parseFloat(initinp.val());
			if(isNaN(override)) {
				initinp.prop('placeholder', 'auto (' + tdInit.toString() + ')');
			} else {
				tdInit = override;
			}
		}

		var limits = [ 50, 0, 5 ], local_limits;
		for(var k in osmium_ia_map) {
			if(!("ia" in osmium_ia_map[k])) continue;
			local_limits = osmium_probe_boundaries_from_ia(
				osmium_ia_map[k].ia,
				tsrInit, tvInit, tdInit
			);

			for(var z = 0; z < 3; ++z) {
				limits[z] = Math.max(limits[z], local_limits[z]);
			}
		}

		for(var z = 0; z < 3; ++z) {
			limits[z] = Math.ceil(limits[z]);
		}

		var xtype = gpform.find('ul.x').find('input:radio:checked').val();
		var ytype = gpform.find('ul.y').find('input:radio:checked').val();

		var li = gpform.find('ul.x').find('li.' + xtype);
		var xmin = parseFloat(li.find('input.xmin').val());
		var xmax = parseFloat(li.find('input.xmax').val());
		if(isNaN(xmin)) xmin = 0;
		if(isNaN(xmax)) {
			var inp = li.find('input.xmax');

			if(xtype === "tsr") {
				xmax = limits[0];
			} else if(xtype === "tv") {
				xmax = limits[1];
			} else if(xtype === "td") {
				xmax = limits[2];
			} else {
				xmax = 10;
			}

			inp.prop('placeholder', 'auto (' + xmax.toString() + ')');
		}

		li = gpform.find('ul.y').find('li.' + ytype);
		var ymin = parseFloat(li.find('input.ymin').val());
		var ymax = parseFloat(li.find('input.ymax').val());
		if(isNaN(ymin)) ymin = 0;
		if(isNaN(ymax)) {
			var inp = li.find('input.ymax');

			if(ytype === "tsr") {
				ymax = limits[0];
			} else if(ytype === "tv") {
				ymax = limits[1];
			}

			if(ytype !== "dps") {
				inp.prop('placeholder', 'auto (' + ymax.toString() + ')');
			}
		}

		if(ytype === "dps") {
			/* Draw a 1d graph */
			var color_map = {};
			$("form#lsources input.source").each(function() {
				var tr = $(this).closest('tr');
				color_map[(tr.index() - 1).toString()] = "hsla(" + tr.data('hue') + ", 100%, 50%, 0.8)";
			});

			var xlabel, genfunc_x;

			if(xtype === "td") {
				xlabel = "Target distance (km)";
				genfunc_x = function(x) { return [ tsrInit, tvInit, x ]; };
			} else if(xtype === "tv") {
				xlabel = "Target velocity (m/s)";
				genfunc_x = function(x) { return [ tsrInit, x, tdInit ]; };
			} else if(xtype === "tsr") {
				xlabel = "Target signature radius (m)";
				genfunc_x = function(x) { return [ x, tvInit, tdInit ]; };
			}

			osmium_draw_dps_graph_1d(
				osmium_ia_map, color_map,
				$("div#graphcontext"),
				xlabel, xmin, xmax, genfunc_x,
				ymin, ymax
			);
		} else {
			/* Draw a 2d graph */
			/* XXX: todo */
		}

		return false;
	}).trigger('tick');

	var lsform = $("form#lsources");
	lsform.on('submit', function(e) {
		e.preventDefault();

		var postopts = {};
		lsform.find('input.source').each(function() {
			var inp = $(this);
			postopts[inp.prop('name')] = inp.val();
		});

		lsform.find('input:submit').prop('disabled', true).after(
			$(document.createElement('span')).addClass('spinner')
		);

		$.ajax({
			type: 'POST',
			url: osmium_relative + '/internal/compare/dps/ia',
			data: postopts,
			dataType: 'json',
			complete: function() {
				lsform.find('input:submit').prop('disabled', false)
					.parent().find('span.spinner').remove();
			},
			error: function(xhr, error, httperror) {
				alert('Could not fetch loadout attributes: ' + error + ' (' + httperror + '). Please report if the issue persists.');
			},
			success: function(payload) {
				osmium_ia_map = payload;
				gpform.submit();
			}
		});

		return false;
	});
});
