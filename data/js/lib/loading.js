var loading = {
	el_pos:null,
	el:null,
	load: function(el) { 
		var image = new Image();
		image.src = 'data/images/loading_2.gif';
		image.id = 'loading_button_image';
		$(image).css({
			"position": "absolute",
			"right": '3px',
			"top": '50%',
			"margin-top": '-5px'
		});
		loading.el_pos = $(el).css('position');
		loading.el = $(el).css({'position':(loading.el_pos == 'absolute'?loading.el_pos:'relative')}).append(image);
	},
	stop: function() {
		$('#loading_button_image').remove();
		$(loading.el).css({'position':loading.el_pos});
	}
};