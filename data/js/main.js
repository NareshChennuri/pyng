/********** load pin's **********/
Pins.loadPins();

/********** auto Complete for search **********/
Pins.autoComplete();

/********** Like button **********/
Pins.initLikes();

/*///////////////////////////////////////// LIVE/LOAD EVENTS /////////////////////////////////////////*/
/********** pin popup **********/
Pins.openPopupBox();

/********** pin popup default **********/
Pins.openPopupBoxDefault();

/********** pin preview **********/
Pins.openDetailBox();

/********** clear input & text in focus **********/
$('.event-clear-focus').clearOnFocus();

/********** simulate open box for detail view **********/
$('.event-coments-total').live('click', function() {
	$(this).parents('.event-box').find('.event-click-open-detail-view').click();
	return false;
});

/********** show scroll to button **********/
/*var scrolltotop = $('.scrolltotop').click(function(){
	$('body').animate({"scrollTop": 0});
	return false;
});
$(window).unbind('.scrolltotop').bind('scroll.scrolltotop',function () {
	if ($(this).scrollTop() > 150 && !jQuery.fancybox.isOpen) {
		scrolltotop.stop(true,true).slideDown(500);
	} else {
		scrolltotop.stop(true,true).slideUp(500);
	}
});*/

// scroll body to 0px on click
$('.scrolltotop').backToTop().click(function () {
	$('body,html').animate({
		scrollTop: 0
	}, 800);
	return false;
});


/********** invate form submit **********/
$('form.event-invate-form-send').submit(function(){
	var invate_form = $(this);
	$('p.event-inline-response', invate_form).remove();
	var send_data = {
		'note': $('.event-invate-note').val()
	};
	for(i=1; i<pintastic_config.invate_limit; i++) {
		send_data['email-'+i] = $('.event-invate-email-'+i, invate_form).val();
	}
	$.post(window.location.href, send_data, function(response){
		for(i in response.send) {
			if(response.send[i].success) {
				$('<p class="event-inline-response success">'+response.send[i].success+'</p>').insertAfter($('.event-invate-email-'+i, invate_form).parents('p'));
			} else if(response.send[i].error) {
				$('<p class="event-inline-response error">'+response.send[i].error+'</p>').insertAfter($('.event-invate-email-'+i, invate_form).parents('p'));
			}
		}
	}, 'json');
	
	return false;
});

/********** init lazi load onload **********/
Pins.lazyLoad('.event-load-lazy-load');

/********** init follow/unfollow onload users **********/
Pins.initFollow(false, function(data) {
	if(data.classs == 'add') {
		var board = $('a[data-boardauthorid='+data.boardauthorid+']');
		board.removeClass('disabled').text(board.data('follow')?board.data('follow'):data.ok);
		var user = $('a[data-userid='+data.boardauthorid+']');
		user.removeClass('disabled').text(user.data('follow')?user.data('follow'):data.ok);
	} else if(data.classs == 'remove') {
		var board = $('a[data-boardauthorid='+data.boardauthorid+']');
		board.addClass('disabled').text(board.data('unfollow')?board.data('unfollow'):data.ok);
		var user = $('a[data-userid='+data.boardauthorid+']');
		user.addClass('disabled').text(user.data('unfollow')?user.data('unfollow'):data.ok);
	}
});

/********** init follow/unfollow onload boards **********/
Pins.initFollow('.event-click-follow-board', function(data) {
	var element_href = $(this).attr('href');
	if(data.classs == 'add') {
		if(data.is_follow_user == false) {
			user = $('a[data-userid='+data.boardauthorid+']');
			user.removeClass('disabled').text(user.data('follow')?user.data('follow'):data.ok);
		}
		$('a[data-boardauthorid='+data.boardauthorid+']').each(function() {
			if($(this).attr('href') == element_href) {
				$(this).removeClass('disabled').text($(this).data('follow')?$(this).data('follow'):data.ok);
			}
		});
	} else if(data.classs == 'remove') {
		if(data.is_follow_user == true) {
			var user = $('a[data-userid='+data.boardauthorid+']');
			user.addClass('disabled').text(user.data('unfollow')?user.data('unfollow'):data.ok);
		}
		$('a[data-boardauthorid='+data.boardauthorid+']').each(function() {
			if($(this).attr('href') == element_href) {
				$(this).addClass('disabled').text($(this).data('unfollow')?$(this).data('unfollow'):data.ok);
			}
		});
	}
});

/********** init edit profile description **********/
$('.event-click-edit').live('click', function() {
	var self = $(this);
	var template = $('head').requireTemplate('edit_profile_description')
	.tmpl({text_save_description:pintastic_config.text.text_save_description});
	template.find('textarea').jqEasyCounter({
		holder: template.find('.event-character-count'),
		maxChars: 200,
		maxCharsWarning: 170,
		template: '{count}'
	}).bind("keydown keyup keypress focus paste mouseup mousedown change",function() {
		if($.trim(this.value)) {
			template.find('button').removeClass('disabled').attr('disabled',false);
		} else {
			template.find('button').addClass('disabled').attr('disabled',true);
		}
	});
	template.find('button').click(function(){
		if(!$(this).hasClass('disabled')) {
			var val = template.find('textarea').val();
			$.post(pintastic_config.edit_description, {description: val, 'RSP': 'ajax'}, function(data){
				if(data.redirect) {
					window.location = data.redirect;
				} else if(data.ok) {
					template.replaceWith('<p class="description">'+data.ok+'</p>');
				} else if(data.error) {
					Pins.error(data.error);
				}
			}, 'json');
		}
		return false;
	});
	$(this).replaceWith(template);
});

/********** init sorting boards **********/
$('.event-sorting-boards').live('click', function() {
	var self = $(this);
	if( self.data('sort-order') ) {
		var data = [];
		$(".event-drag-sort-boards > div").each(function(i, elm) { data.push($(elm).attr("id").replace('board_','')); });
		$.post(pintastic_config.order_boards, { "ids[]": data, 'RSP': 'ajax' }, function(data){
			if(data.ok) {
				Pins.success(data.ok);
			} else if(data.error) {
				Pins.error(data.error);
			} else if(data.empty) {
				Pins.notify(data.empty);
			} else {
				Pins.error(data);
			}
			self.addClass('grey-button').addClass('rearrange-button').removeClass('red-button').removeClass('confirm-button').attr('title', pintastic_config.text.text_rearrange_boards);
			$(".event-drag-sort-boards").removeClass('drag-sort-enable').dragsort('remove');
			$('.event-sorting-boards-cancel').addClass('hide');
			$('.event-sorting-boards-help').slideUp();
			Pins.container.css({
				'position' : '',
				'left': '',
				'top': ''
			}).infscroll('resume').masonry( Pins.masonryOptions ).find('#fixcontanerheight').remove();
			$(".event-drag-sort-boards .event-masonry a").unbind('click');
			$('.no_results').show();
        }, 'json');
		self.data('sort-order',false);
	} else {
		Pins.container.masonry( 'destroy' ).infscroll('pause').append('<div id="fixcontanerheight" class="clear">');
		self.removeClass('grey-button').removeClass('rearrange-button').addClass('red-button').addClass('confirm-button').attr('title', pintastic_config.text.text_save_arrangement);
		$('.event-sorting-boards-cancel').removeClass('hide');
		$(".event-drag-sort-boards").addClass('drag-sort-enable').dragsort({ itemSelector: ".board.event-masonry",dragSelector: ".board.event-masonry", placeHolderTemplate: "<"+"div class='box board event-masonry'><"+"/div>" });
		$(".event-drag-sort-boards .event-masonry a").click(function(){
			return false;
		});
		
		$('.event-sorting-boards-help').slideDown(function() {
			offset = Pins.container.offset();
			Pins.container.css({
				'width': (parseInt($(".event-drag-sort-boards").width())+15),
				'position' : 'absolute',
				'left': offset.left,
				'top': offset.top
			});
		});
		$('.no_results').hide();
		
		self.data('sort-order',true);
	}
	return false;
});

/********** init resend email verification **********/
$('.event-resend-email-verification').live('click', function() {
	var element = $(this);
	$.post(pintastic_config.resend_email_verification, function(data){
		if(data.redirect) {
			window.location = data.redirect;
		} else if(data.error) {
			Pins.error(data.error);
		} else if(data.ok) {
			element.parents('p').html(data.ok);
		}
	}, 'json');
});

/********** show site if disable js **********/
$('.event-fader-hide').animate({
	'opacity' : '1'
}, {
	duration: 150
});

/********** callback pins **********/
Pins.onOpened();