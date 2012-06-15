osmium_load_drones = function(json) {
    $('div#dronebay > div > ul').empty();
	var capacity = json['attributes']['dronecapacity'];
	var bandwidth = json['attributes']['dronebandwidth'];
    var used_capacity = 0;
	var used_bandwidth = 0;

	var select = $("select#dronepreset");
	var option;

    for(var i = 0; i < json['drones'].length; ++i) {
		if(json['drones'][i]['quantityinbay'] > 0) {
			osmium_add_drone(json['drones'][i]['typeid'],
							 json['drones'][i]['typename'],
							 json['drones'][i]['quantityinbay'],
							 "div#dronebay > div#inbay > ul");
		}
		if(json['drones'][i]['quantityinspace'] > 0) {
			osmium_add_drone(json['drones'][i]['typeid'],
							 json['drones'][i]['typename'],
							 json['drones'][i]['quantityinspace'],
							 "div#dronebay > div#inspace > ul");
			used_bandwidth += json['drones'][i]['quantityinspace'] * json['drones'][i]['bandwidth'];
		}
		
		used_capacity += (json['drones'][i]['quantityinbay'] 
						  + json['drones'][i]['quantityinspace']) * json['drones'][i]['volume'];
    }

    $('p#dronecapacity > strong').text(used_capacity + ' / ' + capacity);
    if(used_capacity > capacity) {
		$('p#dronecapacity > strong').addClass('overflow');
    } else {
		$('p#dronecapacity > strong').removeClass('overflow');
    }

    $('p#dronebandwidth > strong').text(used_bandwidth + ' / ' + bandwidth);;
    if(used_bandwidth > bandwidth) {
		$('p#dronebandwidth > strong').addClass('overflow');
    } else {
		$('p#dronebandwidth > strong').removeClass('overflow');
    }

    $('div#dronebay > div > ul > li.drone').filter(function() { return $(this).data('count') == 1; })
		.children('strong').hide();
    $('div#dronebay > div > ul').append("<li class='drone_placeholder'>Drag drones here…</li>\n");

	select.empty();
	for(var i = 0; i < json['dronepresets'].length; ++i) {
		option = $(document.createElement('option'));
		option.prop('value', json['dronepresets'][i][0]).text(json['dronepresets'][i][1]);
		select.append(option);
	}
	select.val(json['dpid']);

	if(json['dronepresets'].length === 1) {
		$("button#delete_drone_preset").prop('disabled', 'disabled');
	} else {
		$("button#delete_drone_preset").removeProp('disabled');
	}
	$("textarea#drone_preset_desc").val(json['dronepresetdesc']);

	$('div#computed_attributes').html(json['computed_attributes']);
	osmium_fattribs_load();
};

osmium_add_drone = function(typeid, typename, count, selector) {
	$(selector).append(
		"<li class='drone' data-typeid='"
			+ typeid + "' data-count='" 
			+ count + "'><img src='http://image.eveonline.com/Type/" 
			+ typeid + "_32.png' alt='' title='' />" 
			+ typename + " <strong>×" + count
			+ "</strong></li>");
	
	$(selector + " > li.drone > img").last()
		.attr('alt', typename)
		.attr('title', typename);
}

osmium_drones_commit = function() {
    $("img#dronebay_spinner").css('visibility', 'visible');

    var opts = {
		token: osmium_tok
    };

    $("div#dronebay > div#inbay > ul > li.drone").each(function(i) {
		opts["inbay" + $(this).data('typeid')] = 0;
    });
    $("div#dronebay > div#inspace > ul > li.drone").each(function(i) {
		opts["inspace" + $(this).data('typeid')] = 0;
    });
    $("div#dronebay > div#inbay > ul > li.drone").each(function(i) {
		opts["inbay" + $(this).data('typeid')] += $(this).data('count');
    });
    $("div#dronebay > div#inspace > ul > li.drone").each(function(i) {
		opts["inspace" + $(this).data('typeid')] += $(this).data('count');
    });

    $.getJSON('./src/json/update_drones.php', opts, function(json) {
		osmium_load_drones(json);
		$("img#dronebay_spinner").css('visibility', 'hidden');
    });
};

osmium_pop_drone = function(from, typeid) {
    $("img#dronebay_spinner").css('visibility', 'visible');

    $.getJSON('./src/json/pop_drone.php', {
		token: osmium_tok,
		typeid: typeid,
		from: from
    }, function(json) {
		osmium_load_drones(json);
		$("img#dronebay_spinner").css('visibility', 'hidden');
    });
};

osmium_presets_commit = function(opts) {
	opts['token'] = osmium_tok;
	opts['type'] = 'drone';
	opts['returntype'] = 'drone';

	$("img#presets_spinner").css("visibility", "visible");
    $.getJSON('./src/json/update_presets.php', opts, function(json) {
		osmium_load_drones(json);
		$("img#presets_spinner").css('visibility', 'hidden');
    });
};

osmium_presetdesc_commit = function() {
	opts = {};
	opts['token'] = osmium_tok;
	opts['type'] = 'drone';
	opts['returntype'] = 'drone';
	opts['action'] = 'updatedesc';
	opts['desc'] = $("textarea#drone_preset_desc").val();

	$("img#presets_spinner").css("visibility", "visible");
    $.post('./src/json/update_presets.php', opts, function(json) {
		osmium_load_drones(json);
		$("img#presets_spinner").css('visibility', 'hidden');
    }, "json");
};

$(function() {
    $("div#dronelistbox > form").submit(function() {
		$("img#dronelistbox_spinner").css('visibility', 'visible');

		$.getJSON('./src/json/search_drones.php', {
			q: $("div#dronelistbox input[type='search']").val(),
			token: osmium_tok
		}, function(json) {
			$("ul#search_results").empty();
			$("p#search_warning").remove();
			for(var i = 0; i < json['payload'].length; ++i) {
				$("ul#search_results").append("<li class='drone' data-count='1' data-typeid='" + json['payload'][i]['typeid'] + "'><img src='http://image.eveonline.com/Type/" + json['payload'][i]['typeid'] + "_32.png' alt='' title='' />" + json['payload'][i]['typename'] + "</li>\n");
				$("ul#search_results > li.drone > img").last()
					.attr('alt', json['payload'][i]['typename'])
					.attr('title', json['payload'][i]['typename']);
			}
			if(json['warning']) {
				$("ul#search_results").before("<p id='search_warning' class='warning_box'>" + json['warning'] + "</p>");
			}
			$("img#dronelistbox_spinner").css("visibility", "hidden");
			
		});

		return false;
    });

    $("ul#search_results").sortable({
		helper: 'clone',
		connectWith: 'div#dronebay > div > ul',
		start: function(event, ui) {
			$("ul#search_results li.drone").eq($(ui.item).index()).after(
				$(ui.item).clone().addClass('clone').show()
			);
			$("ul#search_results").css('opacity', '0.2');
			$(ui.item).css('opacity', '1.0');
		},
		remove: function(event, ui) {
			$("ul#search_results li.drone").removeClass('clone');
		},
		stop: function(event, ui) {
			$("ul#search_results li.clone").remove();
			$("ul#search_results").css('opacity', '1.0');
		}
    });

    $("div#dronebay > div > ul").sortable({
		receive: osmium_drones_commit,
		items: '.drone',
		connectWith: "div#dronebay > div > ul",
		placeholder: "drone_placeholder"
    });

    $(document).on('dblclick', "ul#search_results > li.drone", function(obj) {
		$("div#dronebay > div#inbay > ul > li.drone_placeholder").before($(this).clone());
		osmium_drones_commit();
    });

    $(document).on('dblclick', "div#dronebay > div#inbay > ul > li.drone", function(obj) {
		osmium_pop_drone('bay', $(this).data('typeid'));
    });
    $(document).on('dblclick', "div#dronebay > div#inspace > ul > li.drone", function(obj) {
		osmium_pop_drone('space', $(this).data('typeid'));
    });

	$("button#create_drone_preset").click(function() {
		var new_name = prompt('Enter the name of the new drone preset (must not conflict with another drone preset name):', 'Drone preset #' + ($("select#dronepreset > option").length + 1));
		if(new_name) {
			osmium_presets_commit({
				action: 'create',
				name: new_name
			});
		}
	});

	$("button#delete_drone_preset").click(function() {
		if($("select#dronepreset > option").length > 1) {
			osmium_presets_commit({
				action: 'delete'
			});
		}
	});

	$("button#rename_drone_preset").click(function() {
		var new_name = prompt('Enter the new name for the current drone preset (must not conflict with another drone preset name):', $("select#dronepreset > option[value='" + $("select#dronepreset").val() + "']").text());
		if(new_name) {
			osmium_presets_commit({
				action: 'rename',
				name: new_name
			});
		}
	});

	$("button#clone_drone_preset").click(function() {
		var new_name = prompt('Enter the name of the clone (must not conflict with another drone preset name):', 'Drone preset #' + ($("select#dronepreset > option").length + 1) + ' (clone of ' + $("select#dronepreset > option[value='" + $("select#dronepreset").val() + "']").text() + ')');
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

	$("select#dronepreset").change(function() {
		osmium_presets_commit({
			action: 'switch',
			presetid: $(this).val()
		});
	});
});
