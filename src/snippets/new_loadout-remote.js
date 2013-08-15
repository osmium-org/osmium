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

osmium_gen_remote = function() {
	osmium_gen_fleet();
	osmium_gen_projected();
};

osmium_gen_fleet = function() {
	var selects = $("section#fleet select");
	selects.empty();

	for(var i = 0; i < osmium_skillsets.length; ++i) {
		selects.append(
			$(document.createElement('option'))
				.text(osmium_skillsets[i])
				.prop('value', osmium_skillsets[i])
		);
	}

	$("section#fleet").find('input, select')
		.not("[type='checkbox']").prop('disabled', true);

	if(!("X-Osmium-fleet" in osmium_clf)) {
		osmium_clf['X-Osmium-fleet'] = {};
	}

	for(var t in osmium_clf['X-Osmium-fleet']) {
		$("section#fleet select#" + t + "_skillset").val(osmium_clf['X-Osmium-fleet'][t].skillset);
		$("section#fleet input#" + t + "_fit").val(osmium_clf['X-Osmium-fleet'][t].fitting);
		$("section#fleet input#" + t + "_enabled").prop('checked', true).change();
		$("section#fleet").find("input, select").filter("." + t).prop('disabled', false);
	}
};

osmium_gen_projected = function() {
	jsPlumb.Defaults.Container = $("section#projected");
	jsPlumb.Defaults.Endpoints = [ "Rectangle", "Rectangle" ];
	jsPlumb.Defaults.Anchors = [ [ 0.5, 0.5, 0, 1 ], [ 0.5, 0, 0, -1 ] ];

	jsPlumb.doWhileSuspended(function() {
		jsPlumb.detachAllConnections();
		jsPlumb.deleteEveryEndpoint();
		jsPlumb.unmakeEverySource();
		jsPlumb.unmakeEveryTarget();
	});

	jsPlumb.unbind();
	jsPlumb.bind("connection", function(info) {
		if(info.source.closest('div.pr-loadout').get(0) === info.target.get(0)) {
			/* Disallow self-projection. While libdogma can handle it
			 * in theory, it doesn't make a lot of sense to allow it
			 * since real dogma dosen't allow it. */
			jsPlumb.detach(info.connection);
			return;
		}

		/* A module can only target one entity at a time, so remove
		 * other connections */
		jsPlumb.doWhileSuspended(function() {
			jsPlumb.select({ source: info.source }).each(function(conn) {
				if(conn !== info.connection) {
					jsPlumb.detach(conn);
				}
			});
		});

		/* Update the target in CLF */
		var tkey = info.target.data('key');
		var skey = info.source.closest('div.pr-loadout').data('key');
		var stid = info.source.data('typeid');
		var sidx = info.source.data('index');
		var clf;

		if(skey === 'local') {
			clf = osmium_clf;
		} else {
			clf = osmium_clf['X-Osmium-remote'][skey];
		}

		var m = clf.presets[clf['X-Osmium-current-presetid']].modules;
		for(var i = 0; i < m.length; ++i) {
			if(m[i].typeid === stid && m[i].index === sidx) {
				m[i]['X-Osmium-target'] = tkey;
				osmium_commit_undo_deferred();
				return;
			}
		}

		alert('Could not create connection in CLF – please report!');
	});
	jsPlumb.bind("connectionDetached", function(info) {
		/* Delete the target in CLF */
		var skey = info.source.closest('div.pr-loadout').data('key');
		var stid = info.source.data('typeid');
		var sidx = info.source.data('index');
		var clf;

		if(skey === 'local') {
			clf = osmium_clf;
		} else {
			clf = osmium_clf['X-Osmium-remote'][skey];
		}

		var m = clf.presets[clf['X-Osmium-current-presetid']].modules;
		for(var i = 0; i < m.length; ++i) {
			if(m[i].typeid === stid && m[i].index === sidx) {
				m[i]['X-Osmium-target'] = false;
				osmium_commit_undo_deferred();
				return;
			}
		}

		alert('Could not delete connection in CLF – please report!');
	});

	var list = $("section#projected form#projected-list");
	list.empty();

	var local = osmium_create_projected("local", osmium_clf, 0);
	list.append(local);

	if(!("X-Osmium-remote" in osmium_clf)) {
		osmium_clf['X-Osmium-remote'] = {};
	}

	var c = 0;
	var remotes = {};
	remotes.local = osmium_clf;

	for(var key in osmium_clf['X-Osmium-remote']) {
		var pfit = osmium_clf['X-Osmium-remote'][key];
		list.append(osmium_create_projected(key, pfit, ++c));
		remotes[key] = pfit;
	}

	for(var key in remotes) {
		var pclf = remotes[key];

		if(!("X-Osmium-current-presetid" in pclf)) continue;
		if(!("presets" in pclf)) continue;
		if(!(pclf['X-Osmium-current-presetid'] in pclf.presets)) continue;
		if(!("modules" in pclf.presets[pclf['X-Osmium-current-presetid']])) continue;

		var modules = pclf.presets[pclf['X-Osmium-current-presetid']].modules;

		for(var i = 0; i < modules.length; ++i) {
			var m = modules[i];
			if("X-Osmium-target" in m && m['X-Osmium-target'] !== false) {
				var source = $('section#projected div.pr-loadout.projected-' + key)
					.find('li').filter(function() {
						var li = $(this);
						return li.data('typeid') === m.typeid && li.data('index') === m.index
					}).first();

				var target = $('section#projected div.pr-loadout.projected-' + m['X-Osmium-target']);

				if(source.length !== 1) {
					alert('Could not find source from CLF connection, please report!');
					continue;
				}
				if(target.length !== 1) {
					alert('Could not find target from CLF connection, please report!');
					continue;
				}

				jsPlumb.connect({ source: source, target: target });
			}
		}
	}
};

osmium_init_remote = function() {
	osmium_init_fleet();
	osmium_init_projected();
};

osmium_init_fleet = function() {
	$("section#fleet").on('change', "input[type='checkbox']", function() {
		var c = $(this);
		var tr = c.closest('tr');
		var table = tr.closest('table');
		var type = tr.data('type');

		if(!("X-Osmium-fleet" in osmium_clf)) {
			osmium_clf['X-Osmium-fleet'] = {};
		}
		var fleet = osmium_clf['X-Osmium-fleet'];

		if(c.is(':checked')) {
			fleet[type] = {
				skillset: table.find('select#' + type + '_skillset').val(),
				fitting: table.find('input#' + type + '_fit').val()
			};
			table.find('input, select')
				.filter('.' + type).prop('disabled', false);
		} else {
			delete(fleet[type]);
			table.find('input, select')
				.filter('.' + type).not("[type='checkbox']")
				.prop('disabled', true);
		}

		if(osmium_user_initiated) {
			osmium_undo_push();
			osmium_commit_clf();
		}
	}).on('change', 'select', function() {
		var s = $(this);
		var tr = s.closest('tr');
		var table = tr.closest('table');
		var checkbox = table.find("input#" + tr.data('type') + "_enabled");

		if(!checkbox.is(':checked')) {
			return;
		}

		checkbox.trigger('change');
	}).on('click', 'input.set', function() {
		var s = $(this);
		var tr = s.closest('tr');
		var table = tr.closest('table');
		var checkbox = table.find("input#" + tr.data('type') + "_enabled");

		if(!checkbox.is(':checked')) {
			return;
		}

		checkbox.trigger('change');
	}).on('click', 'input.clear', function() {
		var s = $(this);
		var tr = s.closest('tr');
		var table = tr.closest('table');
		var checkbox = table.find("input#" + tr.data('type') + "_enabled");

		if(!checkbox.is(':checked')) {
			return;
		}

		table.find('input#' + tr.data('type') + '_fit').val('');
		checkbox.trigger('change');
	}).on('keypress', 'input.fit', function(e) {
		if(e.which != 13) return;
		e.preventDefault();

		var s = $(this);
		var tr = s.closest('tr');
		var table = tr.closest('table');
		var checkbox = table.find("input#" + tr.data('type') + "_enabled");

		if(!checkbox.is(':checked')) {
			return false;
		}

		checkbox.trigger('change');
		return false;
	});
};

osmium_init_projected = function() {
	$("section#projected input#createprojected").on('click', function() {
		if(!("X-Osmium-remote" in osmium_clf)) {
			osmium_clf['X-Osmium-remote'] = {};
		}

		var key = 1;
		while((key + '') in osmium_clf['X-Osmium-remote']) ++key;
		key = key + '';

		osmium_clf['X-Osmium-remote'][key] = {};

		$("section#projected > form#projected-list").append(
			osmium_create_projected(
				key, {},
				$("section#projected > form#projected-list > div.pr-loadout").length
			)
		);

		osmium_commit_undo_deferred();
	});

	$("section#projected input#projectedfstoggle").on('click', function() {
		var section = $("section#projected");
		var fs = section.hasClass('fs');

		jsPlumb.doWhileSuspended(function() {
			jsPlumb.toggleDraggable(
				section.find('div.pr-loadout')
					.draggable("option", "disabled", fs)
					.css('position', fs ? 'static' : 'absolute')
			);

			section.toggleClass('fs');
		});
	});

	$("section#projected a#rearrange-circle").on('click', function() {
		var s = $("section#projected");
		var f = s.children("form#projected-list");

		var mx = s.width() / 2;
		var my = s.height() / 2;
		var alx = Math.max(250, mx - 250);
		var aly = Math.max(150, my - 150);
		var divs = f.find('div.pr-loadout');

		jsPlumb.doWhileSuspended(function() {
			divs.each(function() {
				var d = $(this);
				var angle = d.index() / divs.length * 2 * Math.PI;

				d.offset({
					top: my - aly * Math.cos(angle) - d.height() / 2,
					left: mx - alx * Math.sin(angle) - d.width() / 2,
				});
			});
		});
	});
};

osmium_create_projected = function(key, clf, index) {
	var proj = $(document.createElement('div'));
	var hdr = $(document.createElement('header'));
	var ul = $(document.createElement('ul'));
	var fdiv = $(document.createElement('div'));
	var select = $(document.createElement('select'));
	var h = $(document.createElement('h3'));

	var angle;
	if(index === undefined) {
		angle = (360 * Math.random()).toFixed(0);
	} else if(index === 0) {
		angle = 0;
	} else {
		if(index < 1 || typeof index !== 'number') throw 'Invalid index parameter';

		var low = 1;
		var up = 2;

		while(!(low <= index && index < up)) {
			low = up;
			up = up + up;
		}

		angle = (360 * (2 * (index - low) + 1) / up).toFixed(0);
	}

	proj.data('color', 'hsl(' + angle + ', 40%, 50%)');
	proj.data('conn-color', 'hsla(' + angle + ', 40%, 50%, 0.5)');
	proj.addClass('projected-' + key);
	proj.data('key', key);
	proj.addClass('pr-loadout');
	proj.css('border-color', proj.data('color'));

	h.text((key === 'local') ? 'Local' : ('Remote loadout #' + key));
	if("ship" in clf && "typeid" in clf.ship) {
		h.append(
			$(document.createElement('span'))
				.addClass('ship')
				.text(' (' + osmium_types[clf.ship.typeid][1] + ')')
		);
	}

	hdr
		.append(h)
		.append(
			$(document.createElement('input'))
				.prop('type', 'button')
				.prop('value', 'Remove')
				.on('click', function() {
					var t = $(this).closest('div.pr-loadout');
					var key = t.data('key');

					jsPlumb.doWhileSuspended(function() {
						jsPlumb.detachAllConnections(t);
						t.find('ul > li').each(function() {
							jsPlumb.detachAllConnections($(this));
						});
						t.remove();
					});

					delete osmium_clf['X-Osmium-remote'][key];
					osmium_commit_undo_deferred();
			})
		)
	;

	proj.append(hdr);

	var fittinginput = $(document.createElement('input'))
		.prop('type', 'text')
		.prop('placeholder', 'Loadout URI, DNA string or gzclf:// data')
		.on('keypress', function(e) {
			if(e.which != 13) return;
			e.preventDefault();
			$(this).parent().find('input[type="button"]').click();
			return false;
		});

	if("X-Osmium-remote" in osmium_clf && key in osmium_clf['X-Osmium-remote']
	   && "fitting" in osmium_clf['X-Osmium-remote'][key]) {
		fittinginput.val(osmium_clf['X-Osmium-remote'][key].fitting);
	}

	fdiv.append(
		fittinginput
	).append(
		$(document.createElement('input'))
			.prop('type', 'button')
			.prop('value', 'Set fit')
		.on('click', function() {
			var t = $(this).parent().parent();
			osmium_clf['X-Osmium-remote'][t.data('key')].fitting = t.find('input[type="text"]').val();
			osmium_clf['X-Osmium-remote'][t.data('key')].skillset = t.find('select').val();

			osmium_commit_clf({
				params: { "remoteclf": t.data('key') },
				success: function(payload) {
					if(!("remote-clf" in payload)) return;

					osmium_clf['X-Osmium-remote'][t.data("key")] = payload['remote-clf'];
					osmium_undo_push();

					osmium_projected_replace_graceful(
						proj,
						osmium_create_projected(
							t.data('key'),
							osmium_clf['X-Osmium-remote'][t.data('key')],
							proj.index()
						)
					);
				}
			});
		})
	);

	proj.append(fdiv);

	for(var i = 0; i < osmium_skillsets.length; ++i) {
		select.append(
			$(document.createElement('option'))
				.text(osmium_skillsets[i])
				.prop('value', osmium_skillsets[i])
		);
	}

	if("metadata" in clf && "X-Osmium-skillset" in clf.metadata) {
		select.val(clf.metadata['X-Osmium-skillset']);
	}

	select.change(function() {
		$(this).parent().find('div > input[type="button"]').click();
	});

	proj.append(select);

	var projectable = 0;
	if("presets" in clf && "X-Osmium-current-presetid" in clf
	   && "modules" in clf.presets[clf['X-Osmium-current-presetid']]) {
		var p = clf.presets[clf['X-Osmium-current-presetid']].modules;
		for(var i = 0; i < p.length; ++i) {
			var t = osmium_types[p[i].typeid];
			if(t[8] !== 1) continue;
			++projectable;

			var source = 
				$(document.createElement('li'))
				.data('location', 'module-' + p[i].typeid + '-' + p[i].index)
				.data('typeid', p[i].typeid)
				.data('index', p[i].index)
				.append(
					$(document.createElement('img'))
						.prop('alt', t[1] + ' (#' + p[i].index + ')')
						.prop('title', t[1] + ' (#' + p[i].index + ')')
						.prop('src', '//image.eveonline.com/Type/' + t[0] + '_64.png')
				);

			osmium_ctxmenu_bind(source, (function(source, clfsource) {
				return function() {
					var menu = osmium_ctxmenu_create();
					var t = osmium_types[clfsource.typeid];

					osmium_ctxmenu_add_option(menu, t[1], function() {}, {
						enabled: false
					});

					osmium_ctxmenu_add_separator(menu);

					osmium_ctxmenu_add_subctxmenu(menu, "Project on", function() {
						var smenu = osmium_ctxmenu_create();
						var ntargets = 0;

						$('section#projected div.pr-loadout').each(function() {
							var div = $(this);
							var tkey = div.data('key');
							if(tkey === key) return;
							++ntargets;
							osmium_ctxmenu_add_option(smenu, div.find('h3').text(), function() {
								jsPlumb.connect({
									source: source,
									target: div
								});
							}, {});
						});

						if(ntargets === 0) {
							osmium_ctxmenu_add_option(smenu, "No targets available", function() {}, {
								enabled: false
							});
						}

						return smenu;
					}, {});

					osmium_ctxmenu_add_option(menu, "Clear target", function() {
						jsPlumb.select({ source: source }).detach();
					}, { default: true });

					osmium_ctxmenu_add_separator(menu);

					osmium_ctxmenu_add_option(menu, "Show module info", function() {
						osmium_showinfo({
							remote: key,
							type: "module",
							slottype: osmium_types[source.data('typeid')][3],
							index: source.data('index')
						});
					}, { icon: osmium_showinfo_sprite_position });

					return menu;
				}
			})(source, p[i]));

			jsPlumb.makeSource(source, {
				paintStyle: { fillStyle: proj.data('color') },
				connectorStyle: {
					strokeStyle: proj.data('conn-color'),
					lineWidth: 5
				}
			});

			source.on('contextmenu', function() {
				$("section#projected ._jsPlumb_endpoint").last().remove();
			});

			ul.append(source);
		}
	}

	if(projectable === 0) {
		proj.append(
			$(document.createElement('p')).addClass('placeholder')
				.text('No projectable modules.')
		);
	} else {
		proj.append(ul);
	}

	if(key === "local") {
		proj.children('header').children('input').remove();
		proj.find('div > input, select').prop('disabled', true);
		proj.children('div').find('input[type="text"]').val(window.location.toString());
	}

	jsPlumb.makeTarget(proj, {
		paintStyle: { fillStyle: proj.data('color') }
	});
	jsPlumb.draggable(proj);

	if($("section#projected").hasClass('fs')) {
		proj.css('position', 'absolute');
	} else {
		jsPlumb.toggleDraggable(proj);
	}

	osmium_ctxmenu_bind(hdr, function() {
		var menu = osmium_ctxmenu_create();

		osmium_ctxmenu_add_option(menu, "Show ship info", function() {
			osmium_showinfo({
				remote: key,
				type: "ship"
			});
		}, { icon: osmium_showinfo_sprite_position });

		return menu;
	});

	return proj;
};

osmium_projected_regen_local = function() {
	var oldlocal = $("section#projected div.projected-local");
	if(oldlocal.length === 0) return; /* Not yet generated, will be done later */

	var local = osmium_create_projected("local", osmium_clf, oldlocal.index());
	osmium_projected_replace_graceful(oldlocal, local);
};

osmium_projected_regen_remote = function(key, onsuccess) {
	var t = $("section#projected div.pr-loadout.projected-" + key);

	osmium_clf['X-Osmium-remote'][t.data('key')].fitting = t.find('input[type="text"]').val();
	osmium_clf['X-Osmium-remote'][t.data('key')].skillset = t.find('select').val();

	osmium_commit_clf({
		params: { "remoteclf": t.data('key') },
		success: function(payload) {
			if(!("remote-clf" in payload)) return;

			osmium_clf['X-Osmium-remote'][t.data("key")] = payload['remote-clf'];
			osmium_undo_push();

			osmium_projected_replace_graceful(
				t,
				osmium_create_projected(
					t.data('key'),
					osmium_clf['X-Osmium-remote'][t.data('key')],
					t.index()
				)
			);

			if(typeof onsuccess === 'function') onsuccess(payload);
		}
	});
};

osmium_projected_replace_graceful = function(stale, fresh) {
	var cssprops = [ "position", "left", "top", "right", "bottom" ];
	for(var i = 0; i < cssprops.length; ++i) {
		fresh.css(cssprops[i], stale.css(cssprops[i]));
	}

	/* Transfer errors */
	fresh.children('div').before(stale.children('p.clferror.error_box'));

	if(fresh.data('key') !== 'local') {
		/* Transfer the fitting */
		var fitting = stale.find('div > input[type="text"]').val();
		fresh.find('div > input[type="text"]').val(fitting);
		osmium_clf['X-Osmium-remote'][fresh.data('key')].fitting = fitting;
	}

	jsPlumb.doWhileSuspended(function() {
		var newconnections = [];

		/* Keep all incoming projections */
		jsPlumb.select({
			target: stale
		}).each(function(conn) {
			var source = conn.source;
			jsPlumb.detach(conn);
			newconnections.push({
				source: source,
				target: fresh
			});
		});

		/* Keep outgoing projections if modules match */
		stale.find('ul > li').each(function() {
			var source = $(this);
			var newsource = fresh.find('ul > li').filter(function() {
				return $(this).data('location') === source.data('location')
			}).first();

			var connections = jsPlumb.select({
				source: source
			});

			if(newsource.length === 1) {
				/* Found a matching module, transfer all connections to it */
				connections.each(function(conn) {
					var target = conn.target;
					jsPlumb.detach(conn);
					newconnections.push({
						source: newsource,
						target: target
					});
				});
			} else {
				/* Drop connections */
				connections.detach();
			}

			jsPlumb.unmakeSource(source);
		});

		jsPlumb.unmakeTarget(stale);
		stale.before(fresh);
		stale.remove();

		for(var i = 0; i < newconnections.length; ++i) {
			jsPlumb.connect(newconnections[i]);
		}
	});
};

osmium_commit_undo_deferred_timeoutid = undefined;
osmium_commit_undo_deferred = function(delay) {
	if(!osmium_user_initiated) return;

	if(delay === undefined) delay = 100;

	if(osmium_commit_undo_deferred_timeoutid !== undefined) {
		clearTimeout(osmium_commit_undo_deferred_timeoutid);
	} else {
		osmium_clfspinner_push();
	}

	osmium_commit_undo_deferred_timeoutid = setTimeout(function() {
		osmium_commit_undo_deferred_timeoutid = undefined;
		osmium_commit_clf();
		osmium_undo_push();
		osmium_clfspinner_pop();
	}, delay);
};
