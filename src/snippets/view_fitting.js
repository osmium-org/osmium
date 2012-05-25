$(function() {
    $("ul#vpresets > li").click(function() {
	var p = osmium_presets[$(this).data('index')];
	$("ul#vpresets > li > a").removeClass('active');
	$(this).find('a').addClass('active');

	$("div.slots > ul > li > span.charge").html();
	for(var type in p) {
	    if(type === "name") continue;
	    for(var index in p[type]) {
		$("div#" + type + "_slots > ul > li.index_" + index + " > span.charge").html(",<br /><img src='http://image.eveonline.com/Type/" + p[type][index]['typeid'] + "_32.png' alt='' />" + p[type][index]['typename']);
	    }
	}
    });
});
