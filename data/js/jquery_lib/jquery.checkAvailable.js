/* jquery checkAvailable */
(function(a) {
	a.fn.extend({
		checkAvailable: function(b) {
			return this.each(function() {
				var f = a(this),
	            e = a.extend({
		            url: './',
		            holder: '.Msg',
		            method: 'POST',
		            type: 'json',
		            data: {},
		            key: 'raw',
		            cache: false
	            }, b);
	
			    var d = a(e.holder);
			    var original_text = d.html();
			    
			    var load = false;
			    
		        f.bind("keyup", g).bind("focus paste", function() {
		        	setTimeout(g, 10);
		        });

		        function g() {
		        	var i = f.val();
		        
		        	if(load) {
		        		return;
		        	}
		        	
		        	load = true;
		        	
		        	if(i.length < 1) {
		        		d.html(original_text).css('color', '');
		        		load = false;
		        		return;
		        	}
		        	
		        	data = {};
		        	data[e.key] = i;
		        	
		        	$.ajax({
	        			url			: e.url,
	        			data		: a.extend(e.data, data),
	        			type		: e.method,
	        			cache		: e.cache,
	        			dataType	: 'json',
	        			success		: function(json){
		        			if(json.error) {
		        				d.html(json.error).css({color: 'red'});
		        			} else if(json.success) {
		        				d.html(json.success).css({color: 'green'});
		        			}
		        			load = false;
		        		}
	        		});
		        	
		        };
			});
		}
	});
})(jQuery);