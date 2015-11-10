/* jo LazyLoad */
(function($){
	$.joQueue = {
	    _timer: null,
	    _joQueue: [],
	    add: function(fn, context, time) {
	        var setTimer = function(time) {
	            $.joQueue._timer = setTimeout(function() {
	                time = $.joQueue.add();
	                if ($.joQueue._joQueue.length) {
	                    setTimer(time);
	                }
	            }, time || 2);
	        };

	        if (fn) {
	            $.joQueue._joQueue.push([fn, context, time]);
	            if ($.joQueue._joQueue.length == 1) {
	                setTimer(time);
	            }
	            return;
	        };

	        var next = $.joQueue._joQueue.shift();
	        if (!next) {
	            return 0;
	        };
	        next[0].call(next[1] || window);
	        return next[2];
	    },
	    clear: function() {
	        clearTimeout($.joQueue._timer);
	        $.joQueue._joQueue = [];
	    }
	};
	
	$.fn.LazyLoad = function() {
		
		loadImage = function(el, src) {
			if(!src) return;
			var image = new Image();
			image.src = src;
			image.onload = function() {
				el.src = image.src;
			};
			image.onerror = function() {
				if(!el.src_test) { el.src_test = 0; }
				el.src_test++;
				if(el.src_test < 10) {
					loadImage(el, src);
				}
			};
		};
		
		return this.each(function(i, item){
			$.joQueue.add(function () { loadImage(this, $(this).data('original'), 1); }, this);
		});
	};
})(jQuery);