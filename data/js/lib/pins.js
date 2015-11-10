if(typeof Pins == 'undefined') {
	Pins = { };
}

/* current page for infscroll*/
if(typeof Pins.cur_page == 'undefined') {
	Pins.cur_page = 1;
}

/* current page for infscroll*/
if(typeof Pins.autoload == 'undefined') {
	Pins.autoload = true;
}

/* current page for infscroll*/
if(typeof Pins.data_url == 'undefined') {
	Pins.data_url = window.location.href;
}

/* pin contaner */
if(typeof Pins.container == 'undefined') {
	Pins.container = $('.event-masonry-container');
}

/* enable masonry */
if(typeof Pins.masonryEnable == 'undefined') {
	Pins.masonryEnable = true;
}

/* page title */
Pins.meta_title = document.title;

/* current pin */
Pins.current = null;

/* browser histori for url modification*/
Pins.History = window.History;
Pins.State = Pins.History.getState();

Pins.infscr = null;

/*cache data to browser*/
Pins.cache = {};

/* masonry default options */
Pins.masonryOptions = {
	itemSelector : '.event-masonry',
	isAnimated: false,
	isFitWidth: true,
	isResizable: true,
	animate: false,
	gutterWidth: 15,
	columnWidth: 222,
	animationOptions: {
		duration: 500,
		queue: false/*,
		complete: onComplete*/
	}
};

Pins.masonryRightBox = function() {
	
	Pins.masonryOptions.cornerStampSelector = '.event-corner-stamp';
	
	// Masonry corner stamp modifications
	$.Mason.prototype.resize = function() {
	    this._getColumns();
	    this._reLayout();
	};
	  
	$.Mason.prototype._reLayout = function( callback ) {
	    var freeCols = this.cols,
	    $cornerStamp = null;
	    if ( this.options.cornerStampSelector && $(this.options.cornerStampSelector).size() > 0 ) {
	    	$cornerStamp = this.element.find( this.options.cornerStampSelector ),
	    		cornerStampX = $cornerStamp.offset().left - ( this.element.offset().left + this.offset.x + parseInt($cornerStamp.css('marginLeft')) );
    		freeCols = Math.floor( cornerStampX / this.columnWidth );
	    }
	    // reset columns
	    var i = this.cols;
	    this.colYs = [];
	    while (i--) {
	    	this.colYs.push( this.offset.y );
	    }

	    if($cornerStamp) {
		    for ( i = freeCols; i < this.cols; i++ ) {
		    	this.colYs[i] = this.offset.y + $cornerStamp.outerHeight(true);
		    }
	    }

	    // apply layout logic to all bricks
	    this.layout( this.$bricks, callback );
	};
};

/* load pins */
Pins.loadPins = function(){
	
	if(!Pins.autoload) {
		return false;
	}
	
	var url = Pins.data_url + (Pins.data_url.indexOf('?') > -1 ? '&' : '?') + 'RSP=ajax';
	
	Pins.infscr = this.container.infscroll({
		url: url,
		callback: "Pins.getPins",
		offset: Math.ceil(Math.max($(window).height()*2,($(document).height()/2))),
		loadingAppendTo: $('body')
	}).data('infscroll_options');
	
	//show loader
	if(Pins.infscr) {
		Pins.infscr.loading.start.call(this);
	}
	
	if(!pintastic_config.disable_js) {
		$.ajax({
			url: url + '&page=' + this.cur_page,
			dataType: "jsonp",
			type:"GET",
			jsonpCallback: "Pins.getPins",
			complete: function() {
				if(Pins.infscr) {
					Pins.infscr.loading.finished.call(this);
				}
			}
		});
	} else {
		if(Pins.infscr) {
			$.fn.infscrollPage["Pins.getPins"]++;
			Pins.infscr.loading.finished.call(this);
		}
		if(Pins.masonryEnable) {
			Pins.container.masonry( Pins.masonryOptions );
		}
	}
};

/* append pins to contaner*/
Pins.fillPins = function(temporary) {
	var apended = Pins.container.append(temporary);
	if(apended) {
		apended.find('#event-bottom-clear').remove();
		apended.append('<br id="event-bottom-clear" class="clear" />');
	}
	Pins.lazyLoad(temporary.find('.event-load-lazy-load'));
	Pins.cur_page++;
	
	if(Pins.masonryEnable) { 
		if(Pins.container.data('masonry')) {
			Pins.container.masonry( "appended", temporary );
		} else {
			Pins.container.masonry( Pins.masonryOptions );
		}
	}
	
	$.fn.infscrollPage["Pins.getPins"]++;
	/* detail page autoopen */
	if(window.open_from_pin_detail_page) {
		Pins.openDetailBox();
		var link = $('<a>').attr('href', window.open_from_pin_detail_page).addClass('event-click-open-detail-view');
		link.appendTo('body').click().remove();
		window.open_from_pin_detail_page = false;
	} else if($.browser.msie && Pins.State.hash.split('?')[0] && Pins.State.hash.split('?')[0] != './' && Pins.State.hash.split('?')[0] && Pins.State.hash.split('?')[0] == window.location.hash.split('?')[0].replace('#','')) {
		Pins.openDetailBox();
		var link = $('<a>').attr('href', Pins.State.url).addClass('event-click-open-detail-view');
		link.appendTo('body').click().remove();
	}
	return Pins.container;
};

/* get pins */
Pins.getPins = function(response) {
	if(response.error) {
		Pins.error(response.error);
	} else {
		if($(response).size() > 0) {
			var $newElements = $('<div>'),
			has_more = true;
			$(response).each(function(i,item){
				if(item.template == 'no_results') {
					Pins.container.infscroll('destroy');
					var template = $('head').requireTemplate(item.template);
					var new_item = template.tmpl(item);
					new_item.insertAfter(Pins.container);
				} else {
					var template = $('head').requireTemplate(item.template);
					var new_item = template.tmpl(item);
					$newElements.append(new_item);
				}
			});
			
			return Pins.fillPins($newElements.find('>div'));
		} else {
			return false;
		}
	}
};

/* get invated boards */
Pins.getInvateBoards = function() {
	$.ajax({
		url: '?controller=users&action=getInvateBoards',
		dataType: "jsonp",
		type:"GET",
		jsonpCallback: "Pins.fillInvateBoards",
		complete: function() {}
	});
};

Pins.fillInvateBoards = function(response) {
	
	if(response.error) {
		Pins.error(response.error);
	} else {
		if($(response).size() > 0) {
			var $newElements = $('<div>'),
			has_more = true;
			$(response).each(function(i,item){
				var template = $('head').requireTemplate(item.template);
				var new_item = template.tmpl(item);
				$newElements.append(new_item);
			});
			var temporary = $newElements.find('>.box');
			$('#inv-boards').append(temporary);
			Pins.lazyLoad(temporary.find('.event-load-lazy-load'));
			/*if($('#inv-boards').data('masonry')) {
				$('#inv-boards').masonry( "appended", temporary );
			} else {
				$('#inv-boards').masonry( Pins.masonryOptions );
			} */
			return $('#inv-boards');
			
		}
	}
};

/* load detail view for pin */
Pins.loadPinDetails = function(response) { 
	var template = $('head').requireTemplate(response.template);
	var template_response = template.tmpl(response);
	if(jQuery.fancybox.isOpened) {
		jQuery.fancybox.inner.empty().html(template_response);
		if(jQuery.fancybox.direction && jQuery.isFunction(jQuery.fancybox.direction.restore)) {
			jQuery.fancybox.direction.restore.call(this, function() {
				Pins.EventsDetailView(jQuery.fancybox.inner);
				jQuery.fancybox._afterLoad();
			});
		}
	} else {
		jQuery.fancybox.coming.content = template_response;
		jQuery.fancybox._afterLoad();
	}
	
	Pins.History.pushState({pin:response.pin_url}, response.meta_title, response.pin_url);
	
	if(response.comments) {
		$.ajax({
			url:'?controller=pin&action=getComments',
			data: {pin_id: response.pin_id},
			dataType:"jsonp",
			type:"GET",
			jsonpCallback: "Pins.loadPinDetailComments"
		});
	}
	$.ajax({
		url:'?controller=pin&action=getOtherData',
		data: {pin_id: response.pin_id},
		dataType:"jsonp",
		type:"GET",
		jsonpCallback: "Pins.loadPinDetailFooter"
	});
	
	var box = $("a.event-click-open-detail-view[href='"+response.pin_url+"']").parents('.event-box');
	if(box.size()) {
		var window_top = $(window).scrollTop();
		var box_top = parseInt(box.css('top'));
		if(box_top && box_top > window_top) {
			$('body,html').animate({
				scrollTop: box_top
			}, 800);
			Pins.container.infscroll('resume');
		}
	}
};

/* detail view footer part*/
Pins.loadPinDetailFooter = function(response) {
	var template = $('head').requireTemplate('pinDetailFooter');
	var template_response = template.tmpl(response).hide();
	if(pintastic_config.disable_js) {
		$('.event-inline-load-pin .event-load-footer-data').empty().append(template_response).find('.event-slideDown-auto').slideDown();
	} else {
		jQuery.fancybox.inner.find('.event-load-footer-data').empty().append(template_response).find('.event-slideDown-auto').slideDown();
	}
};

/* load detail view comments */
Pins.loadPinDetailComments = function(response) {
	var template = $('head').requireTemplate('pin_comments');
	var template_response = template.tmpl({comments:response}).hide();
	if(pintastic_config.disable_js) {
		$('.event-inline-load-pin .event-load-comments').prepend(template_response).find('.event-comment-row').slideDown();
	} else {
		jQuery.fancybox.inner.find('.event-load-comments').prepend(template_response).find('.event-comment-row').slideDown();
	}
};

/* load detail view for pin from prev and next navigation */
Pins.openDetailBoxWithoutClose = function(original_href, direction) {
	jQuery.fancybox.showLoading();
	if(jQuery.fancybox.direction && jQuery.isFunction(jQuery.fancybox.direction[direction])) {
		jQuery.fancybox.direction[direction].call(jQuery.fancybox, function() {
			var ajax_href = original_href + (original_href.indexOf('?') > -1 ? '&RSP=ajax' : '?RSP=ajax');
			var xrs = $.ajax({
				url:ajax_href,
				dataType:"jsonp",
				type:"GET",
				jsonpCallback: "Pins.loadPinDetails",
				complete: function() {
					jQuery.fancybox.hideLoading();
				}
			});
		});
	}
};

/* event click open detail view for pin */
Pins.openDetailBox = function() {
	
	if(pintastic_config.disable_js) {
		return $('.event-click-open-detail-view');
	}
	
	return $('.event-click-open-detail-view').fancybox({
		openMethod : 'dropIn',
		openSpeed : 250,
		closeMethod : 'dropOut',
		closeSpeed : 250,
		padding: 0,
		margin: 0,
		topRatio: 0,
		keys: {
			next:false,
			prev:false,
			play:false,
			toggle:false
		},
		helpers:  {
			title:  null
		},
		closeBtn  : false,
		//arrows    : false,
		type	  : 'ajax',
		live	  : true,
		mouseWheel : false,
		ajax	  : {
			dataType: "jsonp",
			jsonpCallback: "Pins.loadPinDetails",
			error: function(){}
		},
		beforeLoad: function() {
			this.original_href = this.href;
			this.href += (this.href.indexOf('?') > -1 ? '&RSP=ajax' : '?RSP=ajax');
            if(Pins.current === null) { Pins.current = this.index; }
            //pause infscroll
            Pins.container.infscroll('pause');
		},
		beforeClose: function() {
			Pins.History.pushState({pin:Pins.data_url}, Pins.meta_title, Pins.data_url);
            Pins.current = null;
            //resume infscroll for scroll
            Pins.container.infscroll('resume');
            $(window).scroll();
		},
		afetrLoad: function() {
			
		},
		afterShow: function() {  
			$(window).scroll();
			Pins.EventsDetailView(this.inner);
            var self = this;
            var next = Pins.current - 1;
            if(next < 0) { next = (self.group.length - 1); }
            var prev = (Pins.current + 1);
            if(prev > (self.group.length - 1)) { prev = 0; }
            
            $('.fancybox-outer .fancybox-prev').unbind('click').bind('click',function(){
                Pins.openDetailBoxWithoutClose(self.group[prev].href,'prev');
                Pins.current = prev;
                return false;
            });
            
            $('.fancybox-outer .fancybox-next').unbind('click').bind('click',function(){
                Pins.openDetailBoxWithoutClose(self.group[next].href,'next');
                Pins.current = next;
                return false;
            });
            
            $(document).unbind('.fancyboxNav').bind('keydown.fancyboxNav','keydown',function(e){
            	if(e.keyCode == 39) {
            		$('.fancybox-outer .fancybox-next').click();
            	} else if(e.keyCode == 37) {
            		$('.fancybox-outer .fancybox-prev').click();
            	} else if(e.keyCode == 27) {
            		//jQuery.fancybox.close();
            	}
            });
            
		},
		fitToView: false
	}).contextmenu();
};

/* event click open popup */
Pins.openPopupBox = function() {
	$('.event-click-open-popup').fancybox({
		openMethod : 'dropIn',
		openSpeed : 250,
		closeMethod : 'dropOut',
		closeSpeed : 250,
		helpers:  {
	        title : {
	            type : 'inside',
	            position: 'top'
	        },
	        overlay : {
	            closeClick : false
	        }
	    },
	    tpl: {
	    	closeBtn : '<a title="Close" class="fancybox-item fancybox-close" href="javascript:;"><span class="icon"></span></a>'
	    },
		padding: 0,
		margin: 0,
		closeBtn  : true,
		arrows    : false,
		type	  : 'ajax',
		live: true,
		mouseWheel : false,
		beforeLoad: function() {
			this.href += (this.href.indexOf('?') > -1 ? '&RSP=ajax' : '?RSP=ajax');
		}
	}).contextmenu();
};

Pins.openPopupBoxDefault = function() {
	$('.event-click-open-popup-default').fancybox({
		openMethod : 'dropIn',
		openSpeed : 250,
		closeMethod : 'dropOut',
		closeSpeed : 250,
		helpers:  {
	        title : {
	            type : 'inside',
	            position: 'top'
	        },
	        overlay : {
	            closeClick : false
	        }
	    },
	    tpl: {
	    	closeBtn : '<a title="Close" class="fancybox-item fancybox-close" href="javascript:;"><span class="icon"></span></a>'
	    },
		padding: 0,
		margin: 0,
		closeBtn  : true,
		arrows    : false,
		live: true,
		mouseWheel : false
	}).contextmenu();
};

/* event click open popup */
Pins.openPopupBoxWithHtml = function(html) {
	if(jQuery.fancybox.isOpened) {
		jQuery.fancybox.inner.html(html);
		jQuery.fancybox.update();
	} else {
		jQuery.fancybox.coming.content = html;
		jQuery.fancybox._afterLoad();
	}
};

/* events in detail box */
Pins.EventsDetailView = function(el) {
	/********** init lazi load onload **********/
	Pins.lazyLoad(el.find('img'));

	/********** init comments load onload **********/
    if(Comments && Comments.detail) {
    	var input_box = Comments.detail(el);
    	//Pins.mentionsInput(input_box);
    }
    
    //
    Pins.onOpened();
};

Pins.onOpened = function(fnc) {
	if(jQuery.Pins_onOpened) { 
		var location = document.location.href;
		for( i in jQuery.Pins_onOpened) {
			jQuery.Pins_onOpened[i].call(this, location);
		}
	}
};

Pins.mentionsInput = function(input_box) {
	//check is user loged
	if(pintastic_config.loged) {
		head.ready('mentionsInput',function() {
			input_box.mentionsInput({
			    onDataRequest:function (mode, query, callback) {
			    	if(typeof Pins.cache.mentionsInput == 'undefined') {
			    		Pins.cache.mentionsInput = {};
			    	}
			    	if(typeof Pins.cache.mentionsInput[query] != 'undefined') {
			    		callback(Pins.cache.mentionsInput[query]);
	            	} else {
	            		  $.post(pintastic_config.get_user_friends, {value: query}, function(data){
	            			  var response_data = [];
	            			  $(data.users).each(function(i, user) {
	            				  response_data.push({
	            					  type: 'friends',
	            					  avatar: user.avatars.avatar_image_a,
	            					  name: user.fullname,
	            					  id: user.user_id
	            				  });
	            			  });
	            			  callback(response_data);
	            			  Pins.cache.mentionsInput[query] = response_data;
	            		  }, 'json');
	            	}
			    },
			    elasticCallback: function() {
			    	if(Pins.container.data('masonry')) {
						Pins.container.masonry( 'reload' );
					}
			    },
			    templates     : {
			        mentionItemHighlight       	: '<strong>${value}<input type="hidden" name="friends[${id}]" value="${value}"></strong>',
			        mentionItemSyntax			: '${value}'
			    }
		    }).trigger('focus').trigger('blur');
		});
	}
};

Pins.lazyLoad = function(selector) {
	$(selector).LazyLoad();
};

Pins.autoComplete = function() {
	$('.event-auto-complete-search').autoComplete({
		ajax: pintastic_config.search_autocomplete,
		maxHeight: 331,
		onListFormat: function(event, data) { 
			data.ul.empty();
			if(data.list && data.list.items) {
				$(data.list.items).each(function(i, item){
					var template = $('head').requireTemplate('autocomplete/' + item.template);
					data.ul.append( template.tmpl(item) );
				});
			}
			data.ul.unbind('click.autoComplete');
		}
	});
};

Pins.initLikes = function() {
	$('.event-click-like-box').live('click', function() {
		var element = $(this);
		loading.load(element);
		var main_box = element.parents('.event-box');
		$.post(element.attr('href'), function(data){
			if(data.error) {
				Pins.error(data.error);
			} else if(data.location) {
				window.location = data.location;
			} else if(data.ok) {
				element[(data.disabled?'addClass':'removeClass')]('disabled').html(data.text);
				for(i in data.stats) {
					main_box.find('.stats .' + i).html(data.stats[i]);
				}
			} else {
				Pins.error(data);
			}
			loading.stop();
		},'json');
		return false;
	});
};

Pins.initFollow = function(selector, callback) {
	selector = selector || '.event-click-follow-user';
	$(selector).unbind('click').live('click',function(){
		var element = $(this);
		
		loading.load(element);
		
		var href = element.attr('href');
		if(href.indexOf('?') > -1) {
			href += '&RSP=ajax';
		} else {
			href += '?RSP=ajax';
		}
		
		jQuery.post(href, function(data){
			if(data.ok) {
				element.text(data.ok);
				if(data.classs == 'add') {
					element.removeClass('disabled');
				} else if(data.classs == 'remove') {
					element.addClass('disabled');
				}
				if($.isFunction(callback)) {
					callback.call(element, data);
				}
			} else if(data.error) {
				Pins.error(data.error);
			} else if(data.location) {
				window.location = data.location;
			} else {
				Pins.error(data);
			}
			loading.stop();
		},'json');
		return false;
	});
};

Pins.initSelectBoxCreateBoard = function(selector) {
	selector = selector || '.event-board-style';
	var selectBox = $(selector).selectBox().hide(1,function(){
		var options = $(this).selectBox('options');
		var selectBox = $(this);
		var template = $('head').requireTemplate('create_board_selectbox').tmpl({
			'text_create_board_input' : pintastic_config.text.text_create_board_input,
			'text_create_board_button': pintastic_config.text.text_create_board_button
		});
		template.find('.event-newboard-submit').unbind('click').bind('click',function(){
			var button = $(this).attr('disabled', true).addClass('disabled');
			loading.load(button);
			$.post(pintastic_config.createboardwithoutcategory, {newboard: template.find('.event-newboard-creator').val()},function(data){
				if(data.data) {
					selectBox.append('<option value="'+data.data.board_id+'" selected="selected">'+data.data.title+'</option>')
					.selectBox('destroy').hide(1,function() {
						Pins.initSelectBoxCreateBoard(this);
					});
				} else if(data.error) {
					Pins.error(data.error);
					loading.stop();
					button.attr('disabled', false).removeClass('disabled');
				} else {
					Pins.error(data);
					loading.stop();
					button.attr('disabled', false).removeClass('disabled');
				}			

			}, 'json');
			return false;
		});
		options.append(template);
	});
};

Pins.initMessage = function($class, $title, $text) {
	$('.message-box').remove();
	$text = $text || '';
	var box = $('<div class="message-box">').addClass($class).css('display', 'none');
	if(typeof $title == 'object' && $title.error) {
		res = '';
		for(i in $title.error) {
			res += i+': '+$title.error[i]+'<br />';
		}
		box.append(res);
	} else {
		box.append($title);
	}
	if($text) {
		box.append($('<p>').html($text));
	}
	$('body').append(box);
	box.slideDown();
	box.click(hideBox);
	setTimeout(hideBox, 2500);
	function hideBox() {
		box.slideUp(function(){
			box.remove();
		});
	}
};

Pins.messagesJson = function(data){
	if(data.error) { 
		if(typeof data.error == 'object') {
			res = '';
			for(i in data.error) {
				res += i+': '+data.error[i]+'<br />';
			}
		} else {
			res = data.error;
		}
		if(res) {
			Pins.error(res);
		}
	}
};
Pins.error = function(text){
	Pins.initMessage('error',text);
};
Pins.success = function(text){
	Pins.initMessage('success',text);
};
Pins.notify = function(text){
	Pins.initMessage('info',text);
};
Pins.warning = function(text){
	Pins.initMessage('warning',text);
};

