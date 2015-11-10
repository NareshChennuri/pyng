/* jquery contextmenu */
(function($){
	$.fn.contextmenu = function(callback) { 
		callback = callback || function(){};
		return $(this).live("contextmenu", function(e) {
			callback.call(this, e);
	        e.preventDefault();
	        return false;
	    });
	}
})(jQuery);