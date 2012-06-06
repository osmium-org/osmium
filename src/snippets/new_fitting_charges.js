osmium_refresh_presets = function() {
    $('ul#presets').empty();
	var i = 0;
    for(var name in charge_presets) {
		var delete_link = '';
		if((i++) > 0) delete_link = " | <a class='delete_preset' href='javascript:void(0);'>delete</a>";
		$("ul#presets").append("<li><span><a class='update_name' href='javascript:void(0);'>update name</a>" + delete_link + "</span><strong></strong></li>");
		$("ul#presets > li").last().find('strong').text(name);
    }
};

osmium_switch_preset = function(refetch) {
    $('ul#presets > li.selected').removeClass('selected');
    $("ul.chargegroup > li > select").val(-1).trigger('refresh_picture');

	if(selected_preset === null) {
		$('ul#presets > li.selected').removeClass('selected');
		$('ul.chargegroup > li > select').attr('disabled', 'disabled');
		return;
	}

	if(!(selected_preset in charge_presets)) return;
	$('ul.chargegroup > li > select').removeAttr('disabled');

    $('ul#presets > li > strong').filter(function() {
		return $(this).text() === selected_preset;
	}).parent().addClass('selected');

    for(var i = 0; i < osmium_slottypes.length; ++i) {
		if(osmium_slottypes[i] in charge_presets[selected_preset]) {
			for(j in charge_presets[selected_preset][osmium_slottypes[i]]) {
				$("li#" + osmium_slottypes[i] + "_" + j + " > select")
					.val(charge_presets[selected_preset][osmium_slottypes[i]][j]['typeid'])
					.trigger('refresh_picture');
			}
		} else charge_presets[selected_preset][osmium_slottypes[i]] = {};
    }

	if(refetch) {
		$("img#presetsbox_spinner").css('visibility', 'visible');
		$.getJSON('./src/json/new_fit_switch_preset.php', {
			token: osmium_tok,
			name: selected_preset
		}, function(data) {
			$("div#computed_attributes").html(data);
			osmium_fattribs_load();
			$("img#presetsbox_spinner").css('visibility', 'hidden');
		});
	}
};

osmium_commit_deleted_preset = function(name) {
    $("img#presetsbox_spinner").css('visibility', 'visible');

    var opts = {
		token: osmium_tok,
		action: 'delete',
		name: name
    };

    $.getJSON('./src/json/update_charge_preset.php', opts, function(data) {
		$("div#computed_attributes").html(data);
		osmium_fattribs_load();
		$("img#presetsbox_spinner").css('visibility', 'hidden');
    });
};

osmium_commit_preset = function(name, oldname) {
    $("img#chargegroupsbox_spinner").css('visibility', 'visible');

    var serialized_current_preset = {
		token: osmium_tok,
		action: 'update',
		name: name,
		old_name: oldname
    };

    for(var i = 0; i < osmium_slottypes.length; ++i) {
		if(osmium_slottypes[i] in charge_presets[name]) {
			for(var j in charge_presets[name][osmium_slottypes[i]]) {
				serialized_current_preset[osmium_slottypes[i] + j] 
					= charge_presets[name][osmium_slottypes[i]][j]['typeid'];
			}
		} else charge_presets[name][osmium_slottypes[i]] = {};
    }

    $.getJSON('./src/json/update_charge_preset.php', serialized_current_preset, function(data) {
		$("div#computed_attributes").html(data);
		osmium_fattribs_load();
		$("img#chargegroupsbox_spinner").css('visibility', 'hidden');
    });
};

osmium_set_charge = function(slottype, index, new_val) {
    if(new_val == -1) {
		delete charge_presets[selected_preset][slottype]["" + index];
    } else {
		if(!(slottype in charge_presets[selected_preset])) {
			charge_presets[selected_preset][slottype] = {};
		}

		if(!(("" + index) in charge_presets[selected_preset][slottype])) {
			charge_presets[selected_preset][slottype]["" + index] = {};
		}

		charge_presets[selected_preset][slottype]["" + index]['typeid'] = new_val;
    }
};

$(function() {
    $("ul#chargegroups > li > ul.chargegroup").selectable({
		items: 'li',
		autoRefresh: false
    });
    $(document).on('change', 'ul#chargegroups > li > ul.chargegroup > li.ui-selected > select', function(obj) {
		var new_val = $(this).val();
		$(this).parent().parent().find('li.ui-selected > select').each(function() {
			osmium_set_charge($(this).data('slottype'), $(this).data('index'), new_val);
			$(this).val(new_val);
		});
		$(this).parent().parent().find('select').trigger('refresh_picture');
		osmium_commit_preset(selected_preset, selected_preset);
    });
    $('ul#chargegroups > li > ul.chargegroup > li > select').bind('refresh_picture', function(obj) {
		var new_src = '';
		if($(this).val() == -1) {
			new_src = './static/icons/no_charge.png';
		} else {
			new_src = 'http://image.eveonline.com/Type/' + $(this).val() + '_32.png';
		}

		$(this).parent().children('img.charge_icon').attr('src', new_src);
    });
    $(document).on('change', 'ul#chargegroups > li > ul.chargegroup > li:not(.ui-selected) > select', function(obj) {
		osmium_set_charge($(this).data('slottype'), $(this).data('index'), $(this).val());
		$(this).trigger('refresh_picture');
		osmium_commit_preset(selected_preset);
    });
    $('a#new_preset').click(function() {
		charge_presets['New preset #' + (osmium_preset_num++)] = {};
		osmium_refresh_presets();
		$("ul#presets > li").last().trigger('update_name');
    });
    $(document).on('click', 'ul#presets > li:hover > span > a.delete_preset', function(obj) {
		var to_delete = $(this).parent().parent().find('strong').text();
		delete charge_presets[to_delete];
		if(to_delete == selected_preset) {
			var key;
			for(key in charge_presets) break;
			selected_preset = key;
			osmium_switch_preset(true);
		}
		$(this).parent().parent().remove();
		osmium_commit_deleted_preset(to_delete);
		obj.stopPropagation();
    });
    $(document).on('click', 'ul#presets > li:hover > span > a.update_name', function(obj) {
		$(this).parent().parent().trigger('update_name');
		obj.stopPropagation();
    });
    $(document).on('update_name', 'ul#presets > li', function(obj) {
		var name = $(this).find('strong').text();
		$(this).click();
		$("ul#presets").find('span').hide();
		$(this).find('strong').remove();
		$(this).append("<form action='./new' method='get'><input type='text' class='new_name' /><input type='submit' value='OK' /></form>");
		$(this).find('input.new_name').data('oldname', name)
			.val(name).focus().select();
    });
    $(document).on('submit', 'ul#presets > li > form', function(obj) {
		var input = $(this).find('input.new_name');
		var li = $(this).parent();
		var new_name;
		var old_name;
		old_name = $(input).data('oldname');
		$(this).remove();
		li.append('<strong></strong>');
		li.find('strong').text($(input).val());
		new_name = li.find('strong').text();
		$("ul#presets").find('span').show();
		if(new_name !== old_name) {
			charge_presets[new_name] = charge_presets[old_name];
			delete charge_presets[old_name];
		}
		osmium_commit_preset(new_name, old_name);
		li.click();
		selected_preset = new_name;
		return false;
    });
    $(document).on('click', 'ul#presets > li', function(obj) {
		selected_preset = $(this).find('strong').text();
		osmium_switch_preset(true);
    });

    osmium_refresh_presets();
    osmium_switch_preset(false);
	$('ul#presets > li').first().click();
});
