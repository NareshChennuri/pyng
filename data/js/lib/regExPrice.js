var regExPrice = {
	expresions: {
		price_left: /(\$|\£|\€|\¥|\₪|zł|\฿)([\s]{0,2})?(?:(?:\d{1,5}(?:\,\d{3})+)|(?:\d+))(?:\.\d{2})?/,
		price_right: /(?:(?:\d{1,5}(?:\,\d{3})+)|(?:\d+))(?:\.\d{2})?([\s]{0,2})?(lv)/
	},
	checkRegex: function(str){
		var expresions = $.extend({},regExPrice.expresions,pintastic_config.regExPrice);
		for(i in expresions) {
			if(mymatch = expresions[i].exec(str)) {
				return mymatch;
			}
		}
		return false;
	},
	addExpresionLine: function(str, load) {
		mymatch = regExPrice.checkRegex(str);
		var h = $('.event-price-html-display');
		if(mymatch) {
			h.html(mymatch[0]);
			if(load) { h.css('display','block'); } else { h.fadeIn(100); }
			h.parents('.event-price-holder').find('.event-price-input-holder').val(mymatch[0]);
		} else {
			h.fadeOut(100).html('');
			h.parents('.event-price-holder').find('.event-price-input-holder').val('');
		}
	}
};