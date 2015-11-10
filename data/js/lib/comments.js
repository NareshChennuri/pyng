var Comments = {
	events_list: 'keyup.JoWriteCom change.JoWriteCom paste.JoWriteCom input.JoWriteCom cut.JoWriteCom keydown.JoWriteCom focus.JoWriteCom',
	events_namespace: '.JoWriteCom',
	add_comment: {},
	deleteComment: function() {
		$('.event-delete-comment').live('click', function() {
			var element = $(this).hide();
			loading.load(element);
			var removed_list = element.parents('li');
			var main_box = removed_list.parents('.event-box');
			var href = element.attr('href');
			if(href.indexOf('?') > -1) {
				href += '&RSP=ajax';
			} else {
				href += '?RSP=ajax';
			}
			
			$.post(href, function(result){
				element.show();
				if(result.ok) {
					removed_list.slideUp(function() {
						$(this).remove();
					});
				} else if(result.location) {
					window.location = result.location;
					return;
				} else if(result.error) {
					Pins.error(result.error);
				} else {
					Pins.error(result);
				}
				loading.stop();
			}, 'json');
			
			return false;
		});
	},
	list: function() {
		/********** open/close comments on list **********/
		$('.event-click-open-comment-box').live('click', function(){
			var event_button = $(this);
			var main_box = event_button.parents('.event-box');
			var submit_button = main_box.find('.event-comment-add-list');
			var form_box = main_box.find('form');
			var input_box = form_box.find('textarea');
			var insert_before = main_box.find('.event-open-comment-box');
			var insert_before_list = main_box.find('.event-open-comment-box').parent().find('li');
			
			if(insert_before.is(':visible')) {
				insert_before.slideUp(function(){
					if(Pins.container.data('masonry')) {
						Pins.container.masonry( 'reload' );
					}
					event_button.removeClass('disabled');
				});
			} else {
				head.ready('pins', function() {
					Pins.mentionsInput(input_box);
				});
				
				submit_button.d = function() {
					submit_button
						.addClass('disabled')
						.attr('disabled', true)
						.unbind('.addComm')
						.bind('click.addComm',function() {
							//not submit
							return false;
						});
				};
				submit_button.e = function() {
					submit_button
						.removeClass('disabled')
						.attr('disabled', false)
						.unbind('.addComm')
						.bind('click.addComm', form_box.j);
				};
				form_box.j = function() {
					
					if(Comments.add_comment[form_box.attr('action')]) {
						return false;
					}
					Comments.add_comment[form_box.attr('action')] = true;
					
					submit_button.d();
					var href = form_box.attr('action');
					if(href.indexOf('?') > -1) {
						href += '&RSP=ajax';
					} else {
						href += '?RSP=ajax';
					}
					
					var post_data = form_box.serialize() + '&send_comment=1';
					
					loading.load(submit_button);
					
					$.post(href, post_data, function(result){
						if(result.ok) {
							//if(result.total_comments < pintastic_config.comments_list) {
								var template = $('head').requireTemplate('pin_comment_list');
								template.tmpl(result).css('display','none')
								.insertBefore(insert_before).slideDown();
							//}
							
							for(i in result.stats) {
								main_box.find('.stats .' + i).html(result.stats[i]);
							}
							
							if(result.total_comments >= pintastic_config.comments_list) {
								if( main_box.find('.event-coments-total').size() > 0 ) {
									main_box.find('.event-coments-total a').html(result.stats.all_comments);
								}/* else {
									main_box.find('.event-coments-box').append('<p class="all event-coments-total"><a href="' + result.stats.all_comments_href + '">' + result.stats.all_comments + '</a></p>');
								}*/
							}
							insert_before.slideUp(function(){
								if(Pins.container.data('masonry')) {
									Pins.container.masonry( 'reload' );
								}
								event_button.removeClass('disabled');
								submit_button.d();
								input_box.val('').trigger('focus').trigger('blur');
							});
							
						} else if(result.location) {
							window.location = result.location;
							loading.stop();
							return;
						} else if(result.error) {
							Pins.error(result.error);
							loading.stop();
							return;
						} else {
							Pins.error(result);
						}
						Comments.add_comment[form_box.attr('action')] = false;
						submit_button.e();
						loading.stop();
					}, 'json');
					return false;
				};
				insert_before.slideDown(function(){
					if(Pins.container.data('masonry')) {
						Pins.container.masonry( 'reload' );
					}
					event_button.addClass('disabled');
					submit_button.d();
				});
				var inp = input_box.unbind(Comments.events_namespace)
				.bind(Comments.events_list, function(){
					var placeholder_data = input_box.data('placeholder');
					if($.trim(input_box.val()) && input_box.val() != placeholder_data) {
						submit_button.e();
					} else {
						submit_button.d();
					}
				}).val('').trigger('focus').trigger('blur');
				
			}
			
			return false;
		});
	},
	////details comments
	detail: function(main_box) {
		var submit_button = main_box.find('.event-comment-add');
		var insert_before = main_box.find('.event-comment-box');
		var form_box = main_box.find('.event-comment-form');
		var input_box = form_box.find('textarea');
		var insert_before_list = main_box.find('.event-load-comments');
		
		if(Comments.add_comment[form_box.attr('action')]) {
			return false;
		}
		Comments.add_comment[form_box.attr('action')] = true;
		
		head.ready('main', function() {
			Pins.mentionsInput(input_box);
		});
		submit_button.d = function() {
			submit_button
				.addClass('disabled')
				.attr('disabled', true)
				.unbind('.addComm')
				.bind('click.addComm',function() {
					//not submit
					return false;
				});
		};
		submit_button.e = function() {
			submit_button
				.removeClass('disabled')
				.attr('disabled', false)
				.unbind('.addComm')
				.bind('click.addComm', form_box.j);
		};
		form_box.j = function() {
			submit_button.d();
			var href = form_box.attr('action');
			if(href.indexOf('?') > -1) {
				href += '&RSP=ajax';
			} else {
				href += '?RSP=ajax';
			}
			
			var post_data = form_box.serialize() + '&send_comment=1';
			
			loading.load(submit_button);
			
			$.post(href, post_data, function(result){
				if(result.ok) {
					var template = $('head').requireTemplate('pin_comments');
					template.tmpl({comments:[result]}).css('display','none')
					.insertBefore(insert_before).slideDown(function() {
						submit_button.d();
						input_box.val('').trigger('focus').trigger('blur');
					});
					
				} else if(result.location) {
					window.location = result.location;
					return;
				} else if(result.error) {
					Pins.error(result.error);
					loading.stop();
					return;
				} else {
					Pins.error(result);
					loading.stop();
				}
				Comments.add_comment[form_box.attr('action')] = false;
				loading.stop();
				submit_button.e();
			}, 'json');
			return false;
		};
		
		return input_box.unbind(Comments.events_namespace)
		.bind(Comments.events_list, function(){
			if($.trim(input_box.val())) {
				submit_button.e();
			} else {
				submit_button.d();
			}
		}).val('').trigger('focus').trigger('blur');

	}
};

Comments.list();
Comments.deleteComment();