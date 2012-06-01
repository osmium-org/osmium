$(function() {
    $("div#searchbox > form").submit(function() {
		$("img#searchbox_spinner").css("visibility", "visible");
		
		search_params.q = $("div#searchbox input[type='text']").val();
		$.getJSON('./src/json/search_modules.php', search_params, function(json) {
			$("ul#search_results").empty();
			$("p#search_warning").remove();
			for(var i = 0; i < json['payload'].length; ++i) {
				$("ul#search_results").append("<li class='module' data-slottype='" + json['payload'][i]['slottype'] + "' data-typeid='" + json['payload'][i]['typeid'] + "'><img src='http://image.eveonline.com/Type/" + json['payload'][i]['typeid'] + "_32.png' alt='' />" + json['payload'][i]['typename'] + "</li>\n");
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
		$("div#shortlistbox > ul#modules_shortlist").append("<li class='module' data-slottype='" + json[i]['slottype'] + "' data-typeid='" + json[i]['typeid'] + "'><a class='delete' href='javascript:void(0);' title='Delete from shortlist'><img src='./static/icons/delete.png' alt='X' /></a><img src='http://image.eveonline.com/Type/" + json[i]['typeid'] + "_32.png' alt='' />" + json[i]['typename'] + "</li>\n");
    }

    $("div#shortlistbox > ul#modules_shortlist").append("<li class='shortlist_dummy'><em>Drag here to add to shortlist…</em></li>\n");
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
    var used_slots = json['attributes']['ship']['usedslots'][slot_type];
    var max_slots = json['attributes']['ship']['slotcount'][slot_type];

	var j = 0;
    for(var i in json['modules'][slot_type]) {
		var c = '';
		var stname;
		var stpicture;
		if((j++) >= max_slots) c = ' overflow';

		if(json['modules'][slot_type][i]['state'] == 0) {
			stname = 'Offline';
			stpicture = 'offline.png';
		} else if(json['modules'][slot_type][i]['state'] == 1) {
			stname = 'Online';
			stpicture = 'online.png';
		} else if(json['modules'][slot_type][i]['state'] == 2) {
			stname = 'Active';
			stpicture = 'active.png';
		} else if(json['modules'][slot_type][i]['state'] == 3) {
			stname = 'Overloaded';
			stpicture = 'overloaded.png';
		}

		$("div#" + slot_type + "_slots > ul").append(
			"<li class='module" 
				+ c + "' data-slottype='" 
				+ slot_type
				+ "' data-typeid='" + json['modules'][slot_type][i]['typeid'] 
				+ "' data-state='" + json['modules'][slot_type][i]['state'] 
				+ "' data-index='" + i
				+ "'><img src='http://image.eveonline.com/Type/" 
				+ json['modules'][slot_type][i]['typeid'] + "_32.png' alt='' />" 
				+ json['modules'][slot_type][i]['typename'] 
				+ "<a class='toggle' href='javascript:void(0);' title='" + stname 
				+ "; click to toggle'><img src='./static/icons/" 
				+ stpicture + "' alt='" + stname + "' /></a></li>\n"
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

osmium_setstrong = function(json, name, strong, display_percent) {
	var text = json['used' + name] + ' / ' + json[name];
	if(display_percent) {
		text = text + '<br />';
		if(json[name] > 0) {
			text = text + (100 * json['used' + name] / json[name]).toFixed(2) + ' %';
		}
	}

	strong.html(text);
	if(json['used' + name] > json[name]) {
		strong.addClass('overflow');
	} else {
		strong.removeClass('overflow');
	}
};

osmium_loadout_load = function(json) {
    for(var i = 0; i < osmium_slottypes.length; ++i) {
		$("div#" + osmium_slottypes[i] + "_slots > ul").empty();
		osmium_populate_slots(json, osmium_slottypes[i]);
    }

	osmium_setstrong(json['attributes']['ship'], 'turretslots', $("strong#turret_count"), false);
	osmium_setstrong(json['attributes']['ship'], 'launcherslots', $("strong#launcher_count"), false);
	osmium_setstrong(json['attributes']['ship'], 'cpu', $("strong#cpu"), true);
	osmium_setstrong(json['attributes']['ship'], 'power', $("strong#power"), true);
	osmium_setstrong(json['attributes']['ship'], 'upgradecapacity', $("strong#upgradecapacity"), true);
	$("strong#capacitorstability").text(json['attributes']['ship']['capacitorstability']);
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
}

osmium_loadout_commit_toggle = function(index, typeid) {
   $("img#loadoutbox_spinner").css("visibility", "visible");
    var params = {
		token: osmium_tok,
		index: index,
		typeid: typeid
	};
    $.getJSON('./src/json/toggle_module_state.php', params, function(json) {
		osmium_loadout_load(json);
		$("img#loadoutbox_spinner").css('visibility', 'hidden');
    });
}

$(function() {
    $("ul#modules_shortlist").sortable({
		update: osmium_shortlist_commit,
		items: ".module",
		helper: "clone",
		placeholder: "mod_placeholder",
		connectWith: "div.loadout_slot_cat > ul",
		start: function(event, ui) {
			$("ul#modules_shortlist li.module").eq($(ui.item).index()).after(
				$(ui.item).clone().addClass('clone').show()
			);
			$("ul#search_results, div.loadout_slot_cat").css('opacity', '0.2');
			$("div#" + $(ui.item).data("slottype") + "_slots").css('opacity', '1.0');
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
			$("ul#search_results li.module").eq($(ui.item).index()).after(
				$(ui.item).clone().addClass('clone').show()
			);
			$("ul#search_results, div.loadout_slot_cat").css('opacity', '0.2');
			$("div#" + $(ui.item).data("slottype") + "_slots").css('opacity', '1.0');
			if($("ul#modules_shortlist li.module").filter(function(i) {
				return $(this).data("typeid") == $(ui.item).data("typeid");
			}).length == 1) {
				$("ul#modules_shortlist").css('opacity', '0.2');
			}
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
		start: function(event, ui) {
			$("ul#search_results, div.loadout_slot_cat").css('opacity', '0.2');
			$("div#" + $(ui.item).data("slottype") + "_slots").css('opacity', '1.0');
		},
		stop: function(event, ui) {
			$("ul#search_results, div.loadout_slot_cat").css('opacity', '1.0');
		}
    });

    $(document).on('dblclick', "ul#search_results > li.module, ul#modules_shortlist > li.module", function(obj) {
		var phony = $("div#" + $(this).data('slottype') + "_slots > ul > li.empty_slot");
		var clone = $(this).clone();
		var type = $(this).data('slottype');
		var i = 0;
		while($('div.loadout_slot_cat#' + type + '_slots > ul > li.module[data-index="' + i + '"]').length) {
			++i;
		}

		clone.data('index', i);

		if(phony.length == 0) {
			$("div#" + type + "_slots > ul").append(clone);
		} else {
			phony.first().before(clone);
			phony.first().remove();
		}
		osmium_loadout_commit();
    });

    $(document).on('click', "div.loadout_slot_cat.stateful > ul > li.module > a.toggle", function(obj) {
		osmium_loadout_commit_toggle($(this).parent().data('index'), $(this).parent().data('typeid'));
		obj.stopPropagation();
    });

    $(document).on('dblclick', "div.loadout_slot_cat > ul > li.module", function(obj) {
		osmium_loadout_commit_delete($(this).data('index'), $(this).data('typeid'));
		$(this).remove();
    });

    $(document).on('click', "ul#modules_shortlist > li.module > a.delete", function(obj) {
		$(this).parent().remove();
		osmium_shortlist_commit();
    });
});
