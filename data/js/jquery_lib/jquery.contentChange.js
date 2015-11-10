(function($) {
	$.event.special.contentChange = {
			setup: function () {
				var el = $(this);
	            function h() {
	                if(el.html() != el.data('contentChange')) {
	                    el.data('contentChange', el.html());
	                    el.trigger('contentChange');
	                }
	            };           
	            el.data('contentChange', el.html());
	            this.contentChangeInterval= setInterval(h,10);
	        },
	        teardown: function () {
	            clearInterval(this.contentChangeInterval);
	        },
	        handler: function (a) {
	            var d = this,
	                f = arguments;
	            $.event.handle.apply(d, f);
	        }
	    }
	                
	$.fn.contentChange = function (a) {
		return a ? this.bind("contentChange", a) : this.trigger("contentChange");
	}
})(jQuery);