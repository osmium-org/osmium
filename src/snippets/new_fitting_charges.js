osmium_refresh_presets = function() {
    $('ul#presets').empty();
    for(var i = 0; i < charge_presets.length; ++i) {
	var delete_link = '';
	if(i > 0) delete_link = " | <a class='delete_preset' href='javascript:void(0);'>delete</a>";
	$("ul#presets").append("<li><span><a class='update_name' href='javascript:void(0);'>update name</a>" + delete_link + "</span><strong></strong></li>");
	$("ul#presets > li").last().find('strong').text(charge_presets[i]['name']);
    }
};

osmium_switch_preset = function() {
    $('ul#presets > li.selected').removeClass('selected');
    $('ul#presets > li').eq(selected_preset).addClass('selected');
    
    $("ul.chargegroup > li > select").val(-1).trigger('refresh_picture');
    for(var i = 0; i < osmium_slottypes.length; ++i) {
	if(osmium_slottypes[i] in charge_presets[selected_preset]) {
	    for(j in charge_presets[selected_preset][osmium_slottypes[i]]) {
		$("li#" + osmium_slottypes[i] + "_" + j + " > select")
		    .val(charge_presets[selected_preset][osmium_slottypes[i]][j]['typeid'])
		    .trigger('refresh_picture');
	    }
	} else charge_presets[selected_preset][osmium_slottypes[i]] = {};
    }
};

osmium_commit_deleted_preset = function(index) {
    $("img#presetsbox_spinner").css('visibility', 'visible');

    var opts = {
	token: osmium_tok,
	action: 'delete',
	index: index
    };

    $.get('./src/ajax/update_charge_preset.php', opts, function(result) {
	$("img#presetsbox_spinner").css('visibility', 'hidden');
    });
};

osmium_commit_preset = function(index) {
    $("img#chargegroupsbox_spinner").css('visibility', 'visible');

    var serialized_current_preset = {
	token: osmium_tok,
	action: 'update',
	index: index
    };
    serialized_current_preset['name'] = charge_presets[index]['name'];
    for(var i = 0; i < osmium_slottypes.length; ++i) {
	for(var j in charge_presets[index][osmium_slottypes[i]]) {
	    serialized_current_preset[osmium_slottypes[i] + j] 
		= charge_presets[index][osmium_slottypes[i]][j]['typeid'];
	}
    }

    $.get('./src/ajax/update_charge_preset.php', serialized_current_preset, function(result) {
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
	osmium_commit_preset(selected_preset);
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
	charge_presets.push({ name: 'New preset' });
	osmium_refresh_presets();
	$("ul#presets > li").last().trigger('update_name');
    });
    $(document).on('click', 'ul#presets > li:hover > span > a.delete_preset', function(obj) {
	var idx_to_delete = $(this).parent().parent().index();
	charge_presets.splice(idx_to_delete, 1);
	if(idx_to_delete == selected_preset) {
	    selected_preset = 0;
	    osmium_switch_preset();
	}
	$(this).parent().parent().remove();
	osmium_commit_deleted_preset(idx_to_delete);
	obj.stopPropagation();
    });
    $(document).on('click', 'ul#presets > li:hover > span > a.update_name', function(obj) {
	$(this).parent().parent().trigger('update_name');
	obj.stopPropagation();
    });
    $(document).on('update_name', 'ul#presets > li', function(obj) {
	var name = $(this).find('strong').text();
	$(this).find('span').hide();
	$(this).find('strong').remove();
	$(this).append("<form action='./new' method='get'><input type='text' class='new_name' /><input type='submit' value='OK' /></form>");
	$(this).find('input.new_name').val(name).focus().select();
    });
    $(document).on('submit', 'ul#presets > li > form', function(obj) {
	var new_name = $(this).find('input.new_name').val();
	charge_presets[selected_preset]['name'] = new_name;
	var li = $(this).parent();
	$(this).remove();
	li.append('<strong></strong>');
	li.find('strong').text(new_name);
	li.find('span').show();
	osmium_commit_preset(li.index());
	return false;
    });
    $(document).on('click', 'ul#presets > li', function(obj) {
	selected_preset = $(this).index();
	osmium_switch_preset();
    });

    osmium_refresh_presets();
    osmium_switch_preset();
});
