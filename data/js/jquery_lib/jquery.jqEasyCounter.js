/* jquery EasyCharCounter */
(function(a) {
	a.fn.extend({
		jqEasyCounter: function(b) {
			return this.each(function() {
				var f = a(this),
	            e = a.extend({
		            maxChars: 100,
		            maxCharsWarning: 80,
		            msgWarning: "warning",
		            template: 'Characters: {length} / {maxChars}',
		            holder: '.jqEasyCounterMsg'
	            }, b);
			    if (e.maxChars <= 0) {
			      return
			    }
			    var d = a(e.holder);
			    
			    g();
			    
		        f.unbind('.jqEasyCounter')
		        .live("keydown.jqEasyCounter keyup.jqEasyCounter keypress.jqEasyCounter", g)
		        .live("focus.jqEasyCounter paste.jqEasyCounter", function() {
		        	setTimeout(g, 10);
		        });

		        function g() {
		        	var i = f.val(),
		        	h = i.length;
		        	
		        	if(h == 0) {
		        		d.hide();
		        	} else {
		        		d.show();
		        	}
		        	
	        		if (h >= e.maxChars) {
        				i = i.substring(0, e.maxChars)
	        		}
	        		if (h > e.maxChars) {
	        			var j = f.scrollTop();
        				f.val(i.substring(0, e.maxChars));
        				f.scrollTop(j)
	        		}
	        		if (h >= e.maxCharsWarning) {
			            d.addClass(e.msgWarning);
	        		} else {
	        			d.removeClass(e.msgWarning);
	        		}
	        		
        			html_text = e.template
	        			.replace(/\{count\}/g, ( e.maxChars - f.val().length ))
	        			.replace(/\{length\}/g, f.val().length)
	        			.replace(/\{maxChars\}/g, e.maxChars);
	        		d.html(html_text);
		        };
			});
		}
	});
})(jQuery);