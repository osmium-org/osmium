/* Osmium
 * Copyright (C) 2012 Romain "Artefact2" Dalmaso <artefact2@gmail.com>
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

$(function() {
    $("div#searchbox > form").submit(function() {
		$("img#searchbox_spinner").css("visibility", "visible");
		
		search_params.q = $("div#searchbox input[type='search']").val();
		$.getJSON('./src/json/search_modules.php', search_params, function(json) {
			$("ul#search_results").empty();
			$("p#search_warning").remove();
			for(var i = 0; i < json['payload'].length; ++i) {
				$("ul#search_results").append("<li class='module' data-slottype='" + json['payload'][i]['slottype'] + "' data-typeid='" + json['payload'][i]['typeid'] + "'><img src='http://image.eveonline.com/Type/" + json['payload'][i]['typeid'] + "_32.png' alt='' />" + json['payload'][i]['typename'] + " <span class='links'><a href='javascript:void(0);' class='addmoduletoloadout' title='Add module to the loadout'>+</a><a href='javascript:void(0);' class='addmoduletoshortlist' title='Add module to shortlist'>♡</a></span></li>\n");
			}
			if(json['warning']) {
				$("p#search_filters").after("<p id='search_warning' class='warning_box'>" + json['warning'] + "</p>");
			}
			$("img#searchbox_spinner").css("visibility", "hidden");
		});
		
		return false;
    });

    $("div#searchbox p#search_filters img.meta_filter").click(function(obj) {
		$("#" + $("#" + obj.target.id).hide().data('toggle')).show();
		search_params[$("#" + obj.target.id).data('metagroupid')] = $("#" + obj.target.id).data('filterval');
		$("div#searchbox > form").submit();
    });
});

osmium_shortlist_load = function(json) {
    $("div#shortlistbox > ul#modules_shortlist").empty();
    for(var i = 0; i < json.length; ++i) {
		$("div#shortlistbox > ul#modules_shortlist").append("<li class='module' data-slottype='" + json[i]['slottype'] + "' data-typeid='" + json[i]['typeid'] + "'><img src='http://image.eveonline.com/Type/" + json[i]['typeid'] + "_32.png' alt='' />" + json[i]['typename'] + " <span class='links'><a href='javascript:void(0);' class='addmoduletoloadout' title='Add module to the loadout'>+</a><a href='javascript:void(0);' class='removemodulefromshortlist' title='Remove module from shortlist'>♡</a></span></li>\n");
    }

    $("div#shortlistbox > ul#modules_shortlist").append("<li class='shortlist_dummy'><em>Drag modules here to add to shortlist…</em></li>\n");
};

osmium_shortlist_commit = function() {
    $("img#shortlistbox_spinner").css('visibility', 'visible');

    var typeids = {};
    $("div#shortlistbox > ul#modules_shortlist li.module").each(function(i) {
		typeids["" + i] = $(this).data('typeid');
    });

    typeids['token'] = osmium_tok;

    $.getJSON('./src/json/shortlist_modules.php', typeids, function(json) {
		osmium_shortlist_load(json);
		$("img#shortlistbox_spinner").css('visibility', 'hidden');
    });
};

osmium_populate_slots = function(json, slot_type) {
    var used_slots = json['slots'][slot_type]['used'];
    var max_slots = json['slots'][slot_type]['total'];

	var j = 0;
    for(var i in json['modules'][slot_type]) {
		var c = '';
		var sttoggle = '';
		var ranges = '';

		if(slot_type in json['ranges'] && i in json['ranges'][slot_type]) {
			ranges = "<span class='range' title='"
				+ json['ranges'][slot_type][i][1]
				+ "'>"
				+ json['ranges'][slot_type][i][0]
				+ "</span>";
		}
		if(slot_type in json['states']) {
			var stname = json['states'][slot_type][i]['name'];
			var stpicture = json['states'][slot_type][i]['image'];

			sttoggle = "<a class='toggle' href='javascript:void(0);' title='" + stname 
				+ "; click to toggle'><img src='./static/icons/" 
				+ stpicture + "' alt='" + stname + "' /></a>";
			
		}
		if((j++) >= max_slots) c = ' overflow';

		$("div#" + slot_type + "_slots > ul").append(
			"<li class='module" 
				+ c + "' data-slottype='" 
				+ slot_type
				+ "' data-typeid='" + json['modules'][slot_type][i]['typeid'] 
				+ "' data-state='" + json['modules'][slot_type][i]['state'] 
				+ "' data-index='" + i
				+ "'><img src='http://image.eveonline.com/Type/" 
				+ json['modules'][slot_type][i]['typeid'] + "_32.png' alt='' />"
				+ " <span class='links'><a href='javascript:void(0);' class='removemodule' title='Unfit module'>x</a></span> "
				+ json['modules'][slot_type][i]['typename']
				+ sttoggle + ranges
				+ "</li>\n"
		);
    }
    for(var i = used_slots; i < max_slots; ++i) {
		$("div#" + slot_type + "_slots > ul").append("<li class='" + slot_type + "_slot empty_slot'><img src='./static/icons/slot_" + slot_type + ".png' alt='' /> Empty " + slot_type + " slot</li>\n");
    }

    if(max_slots == 0 && used_slots == 0) {
		$("div#" + slot_type + "_slots").hide();
    } else {
		$("div#" + slot_type + "_slots").show();
    }

    $("strong#" + slot_type + "_count").text(used_slots + " / " + max_slots);
    if(used_slots > max_slots) {
		$("strong#" + slot_type + "_count").addClass('overflow');
    } else {
		$("strong#" + slot_type + "_count").removeClass('overflow');
    }
};

osmium_presets_load = function(json) {
	var select = $("select#preset");
	var option;

	select.empty();
	for(var i = 0; i < json['presets'].length; ++i) {
		option = $(document.createElement('option'));
		option.prop('value', json['presets'][i][0]).text(json['presets'][i][1]);
		select.append(option);
	}
	select.val(json['presetid']);

	if(json['presets'].length === 1) {
		$("button#delete_preset").prop('disabled', 'disabled');
	} else {
		$("button#delete_preset").removeProp('disabled');
	}

	$("textarea#preset_desc").val(json['presetdesc']);
};

osmium_loadout_load = function(json) {
    for(var i = 0; i < osmium_slottypes.length; ++i) {
		$("div#" + osmium_slottypes[i] + "_slots > ul").empty();
		osmium_populate_slots(json, osmium_slottypes[i]);
    }
	$('div#computed_attributes').html(json['attributes']);
	osmium_fattribs_load();
	osmium_presets_load(json);
};

osmium_loadout_commit = function() {
    $("img#loadoutbox_spinner").css("visibility", "visible");
    var params = {};
    params['token'] = osmium_tok;
    for(var i = 0; i < osmium_slottypes.length; ++i) {
		$("div#" + osmium_slottypes[i] + "_slots > ul > li.module").each(function() {
			params[osmium_slottypes[i] + $(this).data('index')] = $(this).data('typeid');
			params[osmium_slottypes[i] + $(this).data('index') + '_state'] = $(this).data('state');
		});
    }
    $.getJSON('./src/json/update_modules.php', params, function(json) {
		osmium_loadout_load(json);
		$("img#loadoutbox_spinner").css('visibility', 'hidden');
    });
};

osmium_loadout_commit_delete = function(index, typeid) {
    $("img#loadoutbox_spinner").css("visibility", "visible");
    var params = {
		token: osmium_tok,
		index: index,
		typeid: typeid
	};
    $.getJSON('./src/json/delete_module.php', params, function(json) {
		osmium_loadout_load(json);
		$("img#loadoutbox_spinner").css('visibility', 'hidden');
    });
};

osmium_loadout_commit_toggle = function(index, typeid, direction) {
   $("img#loadoutbox_spinner").css("visibility", "visible");
    var params = {
		token: osmium_tok,
		index: index,
		typeid: typeid,
		direction: direction
	};
    $.getJSON('./src/json/toggle_module_state.php', params, function(json) {
		osmium_loadout_load(json);
		$("img#loadoutbox_spinner").css('visibility', 'hidden');
    });
};

osmium_presets_commit = function(opts) {
	opts['token'] = osmium_tok;
	opts['type'] = 'module';
	opts['returntype'] = 'module';

	$("img#presets_spinner").css("visibility", "visible");
    $.getJSON('./src/json/update_presets.php', opts, function(json) {
		osmium_loadout_load(json);
		$("img#presets_spinner").css('visibility', 'hidden');
    });
};

osmium_presetdesc_commit = function() {
	opts = {};
	opts['token'] = osmium_tok;
	opts['type'] = 'module';
	opts['returntype'] = 'module';
	opts['action'] = 'updatedesc';
	opts['desc'] = $("textarea#preset_desc").val();

	$("img#presets_spinner").css("visibility", "visible");
    $.post('./src/json/update_presets.php', opts, function(json) {
		osmium_loadout_load(json);
		$("img#presets_spinner").css('visibility', 'hidden');
    }, "json");
};

osmium_get_next_index = function(type) {
	var i = 0;
	while($('div.loadout_slot_cat#' + type + '_slots > ul > li.module[data-index="' + i + '"]').length) {
		++i;
	}
	return i;
};

$(function() {
    $("ul#modules_shortlist").sortable({
		update: osmium_shortlist_commit,
		items: ".module",
		helper: "clone",
		placeholder: "mod_placeholder",
		connectWith: "div.loadout_slot_cat > ul",
		start: function(event, ui) {
			$(ui.item).after(
				$(ui.item).clone().addClass('clone').show()
			);
			$("ul#search_results, div.loadout_slot_cat").css('opacity', '0.2');
			$("div#" + $(ui.item).data("slottype") + "_slots").css('opacity', '1.0');
			$(ui.item).data('index', osmium_get_next_index($(ui.item).data('slottype'))).css('opacity', '1.0');
		},
		remove: function(event, ui) {
			$("ul#modules_shortlist li.clone").removeClass('clone');
		},
		stop: function(event, ui) {
			$("ul#modules_shortlist li.clone").remove();
			$("ul#search_results, div.loadout_slot_cat").css('opacity', '1.0');
		}
    });

    $("ul#search_results").sortable({
		helper: "clone",
		connectWith: "div.loadout_slot_cat > ul, ul#modules_shortlist",
		start: function(event, ui) {
			$(ui.item).after(
				$(ui.item).clone().addClass('clone').show()
			);
			$("ul#search_results, div.loadout_slot_cat").css('opacity', '0.2');
			$("div#" + $(ui.item).data("slottype") + "_slots").css('opacity', '1.0');
			if($("ul#modules_shortlist li.module").filter(function(i) {
				return $(this).data("typeid") == $(ui.item).data("typeid");
			}).length == 1) {
				$("ul#modules_shortlist").css('opacity', '0.2');
			}
			$(ui.item).data('index', osmium_get_next_index($(ui.item).data('slottype'))).css('opacity', '1.0');
		},
		remove: function(event, ui) {
			$("ul#search_results li.clone").removeClass('clone');
		},
		stop: function(event, ui) {
			$("ul#search_results li.clone").remove();
			$("ul#search_results, div.loadout_slot_cat, ul#modules_shortlist").css('opacity', '1.0');
		}
    });

    $("div.loadout_slot_cat > ul").sortable({
		update: osmium_loadout_commit,
		items: ".module",
		placeholder: "mod_placeholder",
		connectWith: "ul#modules_shortlist",
		start: function(event, ui) {
			$(ui.item).after(
				$(ui.item).clone().addClass('clone').show()
			);

			$("ul#search_results, div.loadout_slot_cat").css('opacity', '0.2');
			$("div#" + $(ui.item).data("slottype") + "_slots").css('opacity', '1.0');
		},
		remove: function(event, ui) {
			$("div.loadout_slot_cat > ul > li.clone").removeClass('clone');
		},
		stop: function(event, ui) {
			$("div.loadout_slot_cat > ul > li.clone").remove();
			$("ul#search_results, div.loadout_slot_cat").css('opacity', '1.0');
		}
    });

	$("ul#search_results").on('click', 'li.module > span.links > a.addmoduletoshortlist', function(obj) {
		$("ul#modules_shortlist > li.shortlist_dummy").before($(this).parent().parent().clone());
		osmium_shortlist_commit();
		obj.stopPropagation();
		obj.preventDefault();
	});

    $("ul#search_results, ul#modules_shortlist").on('dblclick', "li.module", function(obj) {
		var phony = $("div#" + $(this).data('slottype') + "_slots > ul > li.empty_slot");
		var clone = $(this).clone();
		var type = $(this).data('slottype');

		clone.data('index', osmium_get_next_index(type));
		clone.find('span.links').remove();

		if(phony.length == 0) {
			$("div#" + type + "_slots > ul").append(clone);
		} else {
			phony.first().before(clone);
			phony.first().remove();
		}
		osmium_loadout_commit();
    }).on('click', "li.module > span.links > a.addmoduletoloadout", function(obj) {
		$(this).parent().parent().trigger('dblclick');
		obj.stopPropagation();
		obj.preventDefault();
	});

    $("div.loadout_slot_cat.stateful").on('click', "ul > li.module > a.toggle", function(obj) {
		osmium_loadout_commit_toggle($(this).parent().data('index'), $(this).parent().data('typeid'), true);
		obj.stopPropagation();
		obj.preventDefault();
		return false;
    }).on('contextmenu', "ul > li.module > a.toggle", function(obj) {
		osmium_loadout_commit_toggle($(this).parent().data('index'), $(this).parent().data('typeid'), false);
		obj.stopPropagation();
		obj.preventDefault();
		return false;
	});

    $("div.loadout_slot_cat").on('dblclick', "ul > li.module", function(obj) {
		osmium_loadout_commit_delete($(this).data('index'), $(this).data('typeid'));
		$(this).remove();
    }).on('click', "li.module > span.links > a.removemodule", function(obj) {
		$(this).parent().parent().trigger('dblclick');
		obj.stopPropagation();
		obj.preventDefault();
	});

    $("ul#modules_shortlist").on('click', "li.module > span.links > a.removemodulefromshortlist", function(obj) {
		$(this).parent().parent().remove();
		osmium_shortlist_commit();
		obj.stopPropagation();
		obj.preventDefault();
    });

	$("button#create_preset").click(function() {
		var new_name = prompt('Enter the name of the new preset (must not conflict with another preset name):', 'Preset #' + ($("select#preset > option").length + 1));
		if(new_name) {
			osmium_presets_commit({
				action: 'create',
				name: new_name
			});
		}
	});

	$("button#delete_preset").click(function() {
		if($("select#preset > option").length > 1) {
			osmium_presets_commit({
				action: 'delete'
			});
		}
	});

	$("button#rename_preset").click(function() {
		var new_name = prompt('Enter the new name for the current preset (must not conflict with another preset name):', $("select#preset > option[value='" + $("select#preset").val() + "']").text());
		if(new_name) {
			osmium_presets_commit({
				action: 'rename',
				name: new_name
			});
		}
	});

	$("button#clone_preset").click(function() {
		var new_name = prompt('Enter the name of the clone (must not conflict with another preset name):', 'Preset #' + ($("select#preset > option").length + 1) + ' (clone of ' + $("select#preset > option[value='" + $("select#preset").val() + "']").text() + ')');
		if(new_name) {
			osmium_presets_commit({
				action: 'clone',
				name: new_name
			});
		}
	});

	$("button#update_desc").click(function() {
		osmium_presetdesc_commit();
	});

	$("select#preset").change(function() {
		osmium_presets_commit({
			action: 'switch',
			presetid: $(this).val()
		});
	});
});
