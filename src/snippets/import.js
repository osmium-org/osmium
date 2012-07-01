select = function(id) {
	$("input#url, textarea#source, input#file").parent().parent().hide();
	$("#" + id).parent().parent().fadeIn(500);

	$("div#methodselect > ul > li > a.selected").removeClass('selected');
	$("div#methodselect > ul > li > a." + id).addClass('selected');
};

$(function() {
	$("div#methodselect").html("<ul><li><a class='source' href='javascript:select(\"source\");'>Direct input</a></li><li><a class='url' href='javascript:select(\"url\");'>Fetch a URI</a></li><li><a class='file' href='javascript:select(\"file\");'>File upload</a></li></ul>");
	select("source");

	$("div#methodselect > ul > li > a").click(function() {
		$(this).blur();
	});
});
