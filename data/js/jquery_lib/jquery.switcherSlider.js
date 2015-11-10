/* jquery switcherSlider */
var number_id = 1;
(function($){
	
	$.fn.switcherSlider = function(options){
		if(typeof options == 'string') {
			var action = options;
			var options = $.fn.switcherSlider.defaults;
		} else {
			var options = $.extend({}, $.fn.switcherSlider.defaults, options);
			var action = 'init';
		}
		
		var switcherSlider = {
			init: function(element, number){
				var element_id = options.sysId.replace('%d', number);
				var element = $(element);
				if( !element.data('switcherSlider.init') ) {
					element.data('switcherSlider',options).data('switcherSlider.init', element_id).hide();
					var stage = $('<span class="stage'+(options.stageClass?' '+options.stageClass:'')+'" id="' + element_id + '">').insertAfter(element);
					if(element.is(':checked')) {
						var slider_button = stage.append('<span class="slider-button on">' + options.text.on + '</span>');
					} else {
						var slider_button = stage.append('<span class="slider-button">' + options.text.off + '</span>');
					}
					
					var disabled = element.is(':disabled');
					
					if(disabled) {
						$('.slider-button', slider_button).addClass('disabled');
					} else {
						$('.slider-button', slider_button).click(function(){
							if( $(this).hasClass('on') ) {
								$(this).animate({"left": $(this).width()}, options.animationSpeed, function(){
									$(this).removeClass('on').html(options.text.off);
							        element.attr('checked', false);
							        options.onSwitch.call(this, {element:element, checked:false});
								});
							} else {
								$(this).animate({"left": "0px"}, options.animationSpeed, function(){
									$(this).addClass('on').html(options.text.on);
							        element.attr('checked', true);
							        options.onSwitch.call(this, {element:element, checked:true});
								});
							}
						});
					}
				}
			}, 
			remove: function(element){
				var element = $(element);
				if(element_id = element.data('switcherSlider.init')) {
					element.data('switcherSlider','').data('switcherSlider.init', '');
					$('#' + element_id).remove();
					element.show();
				}
			}, 
			reload: function(element){
				var element = $(element);
				if(element_id = element.data('switcherSlider.init')) {
					options = $.extend({}, $.fn.switcherSlider.defaults, element.data('switcherSlider'));
					var number = element_id.replace( options.sysId.replace('%d',''), '' );
					this.remove(element);
					this.init(element, number);
				}
			},
			on: function(element, callback){
				var element = $(element);
				if(element_id = element.data('switcherSlider.init')) {
					options = $.extend({}, $.fn.switcherSlider.defaults, element.data('switcherSlider'));
					$('#'+element_id+' .slider-button').animate({"left": "0px"}, options.animationSpeed, function(){
						$(this).addClass('on').html(options.text.on);
				        element.attr('checked', true);
				        if(callback) {
				        	options.onSwitch.call(this, {element:element, checked:true});
				        }
					});
				}
			},
			off: function(element, callback) {
				var element = $(element);
				if(element_id = element.data('switcherSlider.init')) {
					options = $.extend({}, $.fn.switcherSlider.defaults, element.data('switcherSlider'));
					$('#'+element_id+' .slider-button').animate({"left": $(this).width()}, options.animationSpeed, function(){
						$(this).removeClass('on').html(options.text.off);
				        element.attr('checked', false);
				        if(callback) {
				        	options.onSwitch.call(this, {element:element, checked:false});
				        }
					});
				}
			},
			getTotalKey: function(){
				return $('#'+options.sysId).size();
			}
		};
		
		return this.each(function(i){
			switch(action) {
				case 'remove':
					switcherSlider.remove(this);
				break;
				case 'reload':
					switcherSlider.reload(this);
				break;
				case 'on':
					switcherSlider.on(this, true);
				break;
				case 'off':
					switcherSlider.off(this, true);
				break;
				case 'on_without_callback':
					switcherSlider.on(this, false);
				break;
				case 'off_without_callback':
					switcherSlider.off(this, false);
				break;
				default:
					switcherSlider.init(this, number_id);
					number_id++;
				break;
			}
		});
	};
	
	$.fn.switcherSlider.defaults = {
		text : {
			on 	: 'ON',
			off	: 'OFF'
		},
		animationSpeed: "slow",
		onSwitch: function(){},
		sysId: 'checkbox_init_%d',
		stageClass: null
	};
	
})(jQuery);