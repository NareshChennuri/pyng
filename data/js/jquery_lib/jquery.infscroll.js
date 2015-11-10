(function($){
	$.fn.infscrollPage = {};
	$.fn.infscrollDef = {
		url: '',
		callback: 'responseResult',
		offset: $(window).height(),
		onLoad: null,
		onComplete: null,
		loading: {
			finished: undefined,
			finishedMsg: "<em>Congratulations, you've reached the end of the internet.</em>",
			img: "data/images/loading.gif",
			msg: null,
			msgText: "<em>Loading the next set of posts...</em>",
			selector: null,
			speed: 'fast',
			start: undefined
		},
		loadingAppendTo: null
	}
	$.fn.infscroll = function(options) {
		
		var loaded = 1;
		var holder = this;
		var $holder = $(this);

		holder.infscrollPause = false;
		
		if(options == 'destroy') {
			$(window).unbind('smartscroll.infscroll');
			return this;
		}
		
		if(options == 'pause') {
			holder.infscrollPause = true;
			return this;
		}
		
		if(options == 'resume') {
			holder.infscrollPause = false;
			return this;
		} 

		options = $.extend({},$.fn.infscrollDef,options);
		
		if(options.loadingAppendTo) {
			options.loading.selector = options.loadingAppendTo || options.loading.selector || holder
		} else {
			options.loading.selector = options.loading.selector || holder;
		}
		
		options.loading.msg = $('<div id="infscr-loading"><img alt="Loading..." src="' + options.loading.img + '" /><div>' + options.loading.msgText + '</div></div>');

        // Preload loading.img
        (new Image()).src = options.loading.img;
        
        // determine loading.start actions
        options.loading.start = options.loading.start || function() {
			if((options.loading.selector[0].tagName || options.loading.selector[0].nodeName) == 'UL') {
				$appendTo = options.loading.selector.parent();
			} else {
				$appendTo = options.loading.selector;
			}
			options.loading.msg
				.appendTo($appendTo)
				.show(options.loading.speed, function () {
	            	//beginAjax(opts);
				});
		};
		
		// determine loading.finished actions
		options.loading.finished = options.loading.finished || function() {
			options.loading.msg.fadeOut('normal');
		};
		
		
		holder.doScroll = function() {
			
			if(!$.fn.infscrollPage[options.callback]) { $.fn.infscrollPage[options.callback] = 2; }
			var load_more = ($(window).scrollTop() + (( $(window).height() * 2 )+options.offset)) > (holder.outerHeight() + holder.offset().top);

			if(!holder.infscrollPause && load_more && loaded < $.fn.infscrollPage[options.callback]) {
				(options.onLoad || options.loading.start).call(this);
				url = options.url + (options.url.indexOf('?')>-1?"&":"?") +"page="+$.fn.infscrollPage[options.callback]+"&RSP=ajax";
				loaded = $.fn.infscrollPage[options.callback];
				ajx = $.ajax({
					url:url,
					dataType:"jsonp",
					type:"GET",
					jsonpCallback: options.callback,
					complete: function() {
						(options.onComplete || options.loading.finished).call(this);
					}
				});
			}
		};
		
		$holder.data('infscroll_options', options);
		
		if( $holder.size() > 0 ) {
			$(window).bind('smartscroll.infscroll', holder.doScroll);
		};
		return $holder;
	};
	
	var scrollTimeout;
	$.event.special.smartscroll = {
	    setup: function () {
	        $(this).bind("scroll", $.event.special.smartscroll.handler);
	    },
	    teardown: function () {
	        $(this).unbind("scroll", $.event.special.smartscroll.handler);
	    },
	    handler: function (event, execAsap) {
	        var context = this,
		      args = arguments;
	
	        event.type = "smartscroll";
	
	        if (scrollTimeout) { clearTimeout(scrollTimeout); }
	        scrollTimeout = setTimeout(function () {
	            $.event.handle.apply(context, args);
	        }, execAsap === "execAsap" ? 0 : 100);
	    }
	};
	
	$.fn.smartscroll = function (fn) {
	    return fn ? this.bind("smartscroll", fn) : this.trigger("smartscroll", ["execAsap"]);
	};
	
})(jQuery);