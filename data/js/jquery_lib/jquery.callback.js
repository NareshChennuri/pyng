/* autocomplete */
(function( $ ) {

	$.fn.callback = function(callback) {
		if($.isFunction(callback)) {
			callback.call(this);
		}
	}

})( jQuery );