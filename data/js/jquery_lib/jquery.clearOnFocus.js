/* jquery clearinginput */
(function($){
	$.fn.clearOnFocus = function(){
	    return this.live('focus',function(){
	        var v = $(this).val();
	        $(this).val( v === this.defaultValue ? '' : v );
	    }).live('blur',function(){
	        var v = $(this).val();
	        $(this).val( v.match(/^\s+$|^$/) ? this.defaultValue : v );
	    });
	};
})(jQuery);