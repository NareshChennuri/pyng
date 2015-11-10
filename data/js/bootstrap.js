head.js(
	{jquery: "data/js/jquery_lib/jquery.min.js"}
);
head.ready('jquery', function(){
	$(document).ready(function(){
		head.js({config: 'data/js/config.js'}, function() {
			head.js(
					{loading: 'data/js/lib/loading.js'},
					{masonry: 'data/js/jquery_lib/jquery.masonry.min.js'},
					{infscroll: 'data/js/jquery_lib/jquery.infscroll.js'},
					{template: 'data/js/jquery_lib/jquery.template.js'},
					{requireTemplate: 'data/js/jquery_lib/jquery.requireTemplate.js'},
					{LazyLoad: 'data/js/jquery_lib/jquery.LazyLoad.js'},
					{mousewheel: 'data/js/jquery_lib/jquery.mousewheel-3.0.6.pack.js'},
					{fancybox: 'data/js/jquery_lib/jquery.fancybox.pack.js'},
					{fancybox_extends: 'data/js/jquery_lib/jquery.fancybox.extends.js'},
					{history: 'data/js/jquery_lib/jquery.history.js'},
					{contextmenu: 'data/js/jquery_lib/jquery.contextmenu.js'},
					{switcherSlider: 'data/js/jquery_lib/jquery.switcherSlider.js'},
					{jqEasyCounter: 'data/js/jquery_lib/jquery.jqEasyCounter.js'},
					{imageCarousel: 'data/js/jquery_lib/jquery.imageCarousel.js'},
					{selectBox: 'data/js/jquery_lib/jquery.selectBox.js'},
					{clearOnFocus: 'data/js/jquery_lib/jquery.clearOnFocus.js'},
					{dragsort: 'data/js/jquery_lib/jquery.dragsort.js'},
					{autoComplete: 'data/js/jquery_lib/jquery.autoComplete.js'},
	    			{priceExpresion : 'data/js/jquery_lib/jquery.priceExpresion.js'},
	    			{backtotop : 'data/js/jquery_lib/jquery.backtotop.js'},
					/*friends in comments*/
	    			{elastic : 'data/js/jquery_lib/mentionsInput/jquery.elastic.js'},
	    			{joFunctions : 'data/js/jquery_lib/mentionsInput/jquery.joFunctions.js'},
	    			{mentionsInput : 'data/js/jquery_lib/mentionsInput/jquery.mentionsInput.js'},
					/*other libs*/
					{pins: 'data/js/lib/pins.js'},
					{head_css: 'data/js/lib/head.css.js'},
					//{regExPrice: 'data/js/lib/regExPrice.js'},
					{comments: 'data/js/lib/comments.js'}
					/*facebook*/
					,function(){

						//head.js({facebook: document.location.protocol +"//connect.facebook.net/en_US/all.js"}, function(){
						//	head.js({facebook_init: 'data/js/facebook/init.js'});
						//});
						
						head.js({main: 'data/js/main.js'});
						head.css('data/css/fancybox/jquery.fancybox.css');
						
						if(pintastic_config && pintastic_config.load_dynamically_extensions) {
							for( i in pintastic_config.load_dynamically_extensions ) {
								head.js(pintastic_config.load_dynamically_extensions[i]);
							}
						}
						if(pintastic_config && pintastic_config.load_dynamically_extensions_css) {
							for( i in pintastic_config.load_dynamically_extensions_css ) {
								head.css(pintastic_config.load_dynamically_extensions_css[i]);
							}
						}
					}
			);
		});
	});
});