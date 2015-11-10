(function($) {
	$.fn.backToTop = function(settings) {
	    settings = jQuery.extend({
	      min: 1,
	      fadeSpeed: 200,
	      ieOffset: 50
	    }, settings);
	    return this.each(function() {
	      //listen for scroll
	      var el = $(this);
	      el.css('display','none'); //in case the user forgot
	      $(window).scroll(function() {
	        //stupid IE hack
	        if(!$.support.hrefNormalized) {
	          el.css({
	            'position': 'absolute',
	            'top': $(window).scrollTop() + $(window).height() - settings.ieOffset
	          });
	        }
	        if($(window).scrollTop() >= settings.min)
	        {
	          el.fadeIn(settings.fadeSpeed);
	        }
	        else
	        {
	          el.fadeOut(settings.fadeSpeed);
	        }
	      });
	    });
	  };
})(jQuery);