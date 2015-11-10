(function($){
	$.fn.simpletooltip=function(options){
		$('#tooltip').remove();
		$("<div id='tooltip'></div>").appendTo("body");
		
		$(this).each(function(i, element){
			if($(element).attr('title')) {
				jQuery.data(element, 'tip_text', $(element).attr('title'));
				$(element).hover(function(event) {
					rel = $(this).attr("title");
					twidth = parseInt($("#tooltip").width());
					window_width = parseInt($(window).width());
					theight = parseInt($("#tooltip").height());
					window_height = parseInt($(window).height());
					if((event.pageX + twidth) < (window_width-twidth)) {
						tleft = event.pageX +15;
					} else {
						tleft = event.pageX -(twidth+15);
					}
					if((event.pageY + theight + 15) < (window_height-theight)) {
						ttop = event.pageY +15;
					} else {
						ttop = event.pageY -(theight+15);
					}
					$('#tooltip')
					.css("position", "absolute")
					.css("top", ttop)
					.css("left", tleft)
					.css('max-width', 250)
					.show().html(jQuery.data(element, 'tip_text'));
					$(this).removeAttr("title");
				}, function() {
					$("#tooltip").hide();
				});
				
				$(element).mousemove(function(event) {
					twidth = parseInt($("#tooltip").width());
					window_width = parseInt($(window).width());
					theight = parseInt($("#tooltip").height());
					window_height = parseInt($(window).height());
					if((event.pageX + twidth+15) < (window_width-15)) {
						tleft = event.pageX +15;
					} else {
						tleft = event.pageX -(twidth+15);
					}
					if((event.pageY + theight + 15) < (window_height-15)) {
						ttop = event.pageY +15;
					} else {
						ttop = event.pageY -(theight+15);
					}
					$("#tooltip").css("top", ttop).css("left", tleft);
				});
			}
		});
		
	};
})(jQuery);