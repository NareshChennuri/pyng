(function ($, F) {
	/* open and close transitions */
	F.transitions.dropIn = function() {
		var endPos = F._getPosition(true);
		
		endPos.top = (parseInt(endPos.top, 10) - F.inner.height()) + 'px';
		
		F.wrap.css(endPos).show().animate({
			top: '+='+F.inner.height()+'px'
		}, {
			duration: F.current.openSpeed,
			complete: F._afterZoomIn
		});
	};

	F.transitions.dropOut = function() {
		F.wrap.removeClass('fancybox-opened').animate({
			top: '-='+F.inner.height()+'px'
		}, {
			duration: F.current.closeSpeed,
			complete: F._afterZoomOut
		});
	};
	
	/* prev & next direction */
	F.direction = {
		isActiveLoad: false,
		prev: function(callback) {
			if(!F.direction.isActiveLoad) {
				F.direction.isActiveLoad = true;
				F.wrap.data('original_left', F.wrap.css('left'));
				F.wrap.css({'position': 'fixed'}).animate({left: -$(window).width()+'px'}, {
					duration: 250,
					complete: function() {
						F.wrap.css({'left':($(window).width()+100)});
						if($.isFunction(callback)) {
							callback.call(this);
						}
	                    //F.direction.restore.call(this);
					}
				});
			}
		},
		next: function(callback) {
			if(!F.direction.isActiveLoad) {
				F.direction.isActiveLoad = true;
				F.wrap.data('original_left', F.wrap.css('left'));
				F.wrap.css({'position': 'fixed'}).animate({left: +$(window).width()+'px'}, {
					duration: 250,
					complete: function() {
						F.wrap.css({'left': -($(window).width()+100)});
						if($.isFunction(callback)) {
							callback.call(this);
						}
	                    //F.direction.restore.call(this);
					}
				});
			}
		},
		restore: function(callback) {
			if(F.wrap.data('original_left')) {
                F.opts.beforeLoad.call(F.current);
				F.wrap.animate({left: F.wrap.data('original_left')}, {
					duration: 500,
					complete: function() {
						F.direction.isActiveLoad = false;
						F.wrap.css({'position':'absolute'});
						if($.isFunction(callback)) {
							callback.call(this);
						}
                        F.opts.afterShow.call(F.current);
					}
				});
			}
		}
	};
	
	/* ajax jsonp */
	F._loadAjax = function () {
		var coming = F.coming;

		F.showLoading();
		
		F.ajaxLoad = $.ajax($.extend({}, coming.ajax, {
			url: coming.href,
			success: function (data, textStatus) {
				if (textStatus === 'success') {
					coming.content = data;

					F._afterLoad();
				}
			}
		}));
	};

}(jQuery, jQuery.fancybox));