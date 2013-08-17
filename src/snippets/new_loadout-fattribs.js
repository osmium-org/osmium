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

argsh = function(x) {
	return Math.log(x + Math.sqrt(x*x + 1.0));
};

osmium_targeting_time = function(targeter_scanres, targetee_sigradius) {
	/* Formula taken from the official wiki */
	return 40000.0 / (targeter_scanres * Math.pow(argsh(targetee_sigradius), 2));
};

osmium_gen_fattribs = function() {
	osmium_fattribs_load();

	$("section#defense > h4 > span.pname").text(osmium_clf['X-damage-profile'][0]);

	var t = "Scan resolution\n\nTime to lock…";
	var sr = parseFloat($("span#scan_resolution").data('value'));
	var sig = parseFloat($("p#signature_radius").data('value'));

	for(var i = 0; i < osmium_targetclass.length; ++i) {
		if(!osmium_targetclass[i][1]) continue;
		t += "\n" + osmium_targetclass[i][0] + " (" + osmium_targetclass[i][3].toFixed(0) + " m): "
			+ osmium_targeting_time(sr, osmium_targetclass[i][3]).toFixed(1) + " s"
	}

	$("span#scan_resolution").prop('title', t);

	t = "Signature radius\n\nTime to be locked by…";

	for(var i = 0; i < osmium_targetclass.length; ++i) {
		if(!osmium_targetclass[i][2]) continue;
		t += "\n" + osmium_targetclass[i][0] + " (" + osmium_targetclass[i][4].toFixed(0) + " mm) : "
			+ osmium_targeting_time(osmium_targetclass[i][4], sig).toFixed(1) + " s"
	}

	$("p#signature_radius").prop('title', t);
};

osmium_init_fattribs = function() {
	osmium_ctxmenu_bind($("div#computed_attributes > section#defense"), function() {
		var menu = osmium_ctxmenu_create();

		osmium_ctxmenu_add_subctxmenu(menu, "Damage profiles", function() {
			var smenu = osmium_ctxmenu_create();

			for(var k in osmium_damage_profiles) {
				osmium_ctxmenu_add_subctxmenu(smenu, k, (function(profiles) {
					return function() {
						var ssmenu = osmium_ctxmenu_create();

						for(var z in profiles) {
							var s = profiles[z][0] + profiles[z][1] + profiles[z][2] + profiles[z][3];

							var opts = {
								toggled: z === osmium_clf['X-damage-profile'][0],
								title: (100 * profiles[z][0] / s).toFixed(1) + "% EM, "
									+ (100 * profiles[z][1] / s).toFixed(1) + "% Explosive, "
									+ (100 * profiles[z][2] / s).toFixed(1) + "% Kinetic, "
									+ (100 * profiles[z][3] / s).toFixed(1) + "% Thermal"
							};

							if(profiles[z].length >= 5) {
								opts.icon = profiles[z][4];
							}

							osmium_ctxmenu_add_option(ssmenu, z, (function(name, profile) {
								return function() {
									osmium_clf['X-damage-profile'] = [ name, profile.slice(0, 4) ];
									osmium_undo_push();
									osmium_commit_clf();
								};
							})(z, profiles[z]), opts);
						}
						return ssmenu;
					};
				})(osmium_damage_profiles[k]), {});
			}

			osmium_ctxmenu_add_subctxmenu(smenu, "Custom", function() {
				var ssmenu = osmium_ctxmenu_create();
				var count = 0;

				for(var k in osmium_custom_damage_profiles) {
					++count;

					osmium_ctxmenu_add_subctxmenu(ssmenu, k, (function(k, profile) {
						return function() {
							var sssmenu = osmium_ctxmenu_create();

							osmium_ctxmenu_add_option(sssmenu, "Use", function() {
								osmium_clf['X-damage-profile'] = [ k, profile ];
								osmium_undo_push();
								osmium_commit_clf();
							}, { default: true });

							osmium_ctxmenu_add_option(sssmenu, "Remove", function() {
								if(k === osmium_clf['X-damage-profile'][0]) {
									osmium_clf['X-damage-profile'] = [ "Uniform", [ .25, .25, .25, .25 ] ];
									osmium_commit_clf();
								}

								delete osmium_custom_damage_profiles[k];
								osmium_commit_custom_damage_profiles();
							}, {});

							return sssmenu;
						};
					})(k, osmium_custom_damage_profiles[k]), {
						toggled: k === osmium_clf['X-damage-profile'][0]
					});
				}

				if(count >= 1) {
					osmium_ctxmenu_add_separator(ssmenu);
				}

				osmium_ctxmenu_add_option(ssmenu, "Create…", function() {
					var name = prompt("Damage profile name:");
					if(!name) return;
					var prof = prompt("Damage values (EM, Explosive, Kinetic, Thermal):",
									  "[ 0.25, 0.25, 0.25, 0.25 ]");
					if(!prof) return;

					try {
						var p = JSON.parse(prof);
						var s;

						if(p[0] < 0 || p[1] < 0 || p[2] < 0 || p[3] < 0 || (s = p[0] + p[1] + p[2] + p[3]) <= 0) {
							alert("Incorrect damage values: all four numbers must be positive, and at least one of them must be nonzero.");
						} else {
							osmium_custom_damage_profiles[name] = [
								p[0] / s, p[1] / s, p[2] / s, p[3] / s
							];

							osmium_clf['X-damage-profile'] = [ name, osmium_custom_damage_profiles[name] ];
							osmium_undo_push();
							osmium_commit_clf();
							osmium_commit_custom_damage_profiles();
						}
					} catch(e) {
						alert("Incorrect syntax in damage values.");
					}

					$("ul#ctxmenu").trigger('delete_menu');
				}, {});

				return ssmenu;
			}, {});

			return smenu;
		}, {});

		return menu;
	});
};

osmium_commit_custom_damage_profiles = function() {
	$.ajax({
		type: 'POST',
		url: osmium_relative + '/src/ajax/put_custom_damage_profiles.php?' + $.param({ token: osmium_token }),
		data: { payload: JSON.stringify(osmium_custom_damage_profiles) }
	});
};
