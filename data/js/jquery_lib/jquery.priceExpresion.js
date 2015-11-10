(function(){
	
	$.fn.priceExpresion = function(options) {
		
		options = $.extend({}, {
			expresions : {
				price_left: /(\$|\£|\€|\¥|\₪|zł|\฿)([\s]{0,2})?(?:(?:\d{1,5}(?:\,\d{3})+)|(?:\d+))(?:\.\d{2})?/,
				price_right: /(?:(?:\d{1,5}(?:\,\d{3})+)|(?:\d+))(?:\.\d{2})?([\s]{0,2})?(lv)/
			},
			html_display_class: '.event-price-html-display',
			input_display_class: '.event-price-input-holder',
			box_parent_class: '.event-price-holder',
			callback: function() {}
		}, options);
		
		return this.each(function() {
			var f = $(this),
				s = f.parents(options.box_parent_class),
				d = s.find(options.html_display_class),
				w = s.find(options.input_display_class);
			g();
			
			f.unbind('.priceExpresion')
		        .live("keydown.priceExpresion keyup.priceExpresion keypress.priceExpresion", g)
		        .live("focus.priceExpresion paste.priceExpresion", function() {
		        	setTimeout(g, 10);
		        });
			
			function g() {
				var mymatch = c(f.val()); 
				if(mymatch) {
					d.html(mymatch[0]).show(100);
					w.val(mymatch[0]);
				} else {
					d.html('').hide(100);
					w.val('');
				}
				options.callback.call(f, mymatch);
			};
			
			function c(str){
				for(i in options.expresions) {
					if(mymatch = options.expresions[i].exec(str)) {
						return mymatch;
					}
				}
				return false;
			};
			
		});
	};
	
})(jQuery);