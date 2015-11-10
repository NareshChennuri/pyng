/* carousel jo */
(function($){
	$.fn.imageCarousel = function(config){

		var config = $.extend({}, {
			onScrollEnd: null,
			onInit: null,
			duration: 200,
			textNext: 'Next',
			textPrev: 'Prev'
		}, config);
		
		return this.each(function(){

			var $holder = $(this);

			var currentPage = 1;
			
			if($('ul',this).size() < 1) {
				return;
			}

			var $contaner = $('ul:first',this);

			var pages = $contaner.children();
			var width = $holder.width();

			$contaner
			.css({position: "relative", padding: "0", margin: "0", listStyle: "none", width: pages.length * width})
			.find('li').css({width: width});

			var next = $('<a>').click(function(){
				if (currentPage === pages.length) {
					return;
			    }
			    var new_width = -1 * width * currentPage;
			    $contaner.animate({ left: new_width}, config.duration, function(){
				    currentPage++;
					if($.isFunction(config.onScrollEnd)) {
						config.onScrollEnd.call(this, $(pages).get(currentPage-1), (currentPage-1), pages.length, $contaner);
					}
				});
				return false;
			}).html(config.textNext).addClass('grey-button image-carousel-next');
			var prev = $('<a>').click(function(){
				if (currentPage === 1) {
					return;
			    }
			    var new_width = -1 * width * (currentPage - 1);
			    $contaner.animate({ left: -1 * width * (currentPage - 2)}, config.duration, function(){
				    currentPage--;
					if($.isFunction(config.onScrollEnd)) {
						config.onScrollEnd.call(this, $(pages).get(currentPage-1), (currentPage-1), pages.length, $contaner);
					}
				});
				return false;
			}).html(config.textPrev).addClass('grey-button image-carousel-prev');
			
			if(pages.length > 1) {
				$holder.append($('<div class="image-carousel-navigation">').append(next).append(prev));
			}

			if($.isFunction(config.onInit)) {
				setTimeout(function(){
					config.onInit.call(this, $(pages).get(currentPage-1), (currentPage-1), pages.length, $contaner);
				}, 10)
			}
			
		});
	}
})(jQuery);