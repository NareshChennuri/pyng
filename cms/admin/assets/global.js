/* IE Clear type fix */
(function ($) {
    $.fn.customFadeIn = function (speed, callback) {
        $(this).fadeIn(speed, function () {
            if (jQuery.browser.msie) $(this).get(0).style.removeAttribute('filter');
            if (callback != undefined) callback();
        });
    };
    $.fn.customFadeOut = function (speed, callback) {
        $(this).fadeOut(speed, function () {
            if (jQuery.browser.msie) $(this).get(0).style.removeAttribute('filter');
            if (callback != undefined) callback();
        });
    };
    $.fn.customFadeTo = function (speed, callback) {
        $(this).fadeTo(speed, function () {
            if (jQuery.browser.msie) $(this).get(0).style.removeAttribute('filter');
            if (callback != undefined) callback();
        });
    };
    $.fn.customToggle = function (speed, callback) {
        $(this).toggle(speed, function () {
            if (jQuery.browser.msie) $(this).get(0).style.removeAttribute('filter');
            if (callback != undefined) callback();
        });
    };

})(jQuery);

jQuery.fn.extend({
	  slideRightShow: function() {
	    return this.each(function() {
	        $(this).show('slide', {direction: 'right'}, 1000);
	    });
	  },
	  slideLeftHide: function() {
	    return this.each(function() {
	      $(this).hide('slide', {direction: 'left'}, 1000);
	    });
	  },
	  slideRightHide: function() {
	    return this.each(function() {
	      $(this).hide('slide', {direction: 'right'}, 1000);
	    });
	  },
	  slideLeftShow: function() {
	    return this.each(function() {
	      $(this).show('slide', {direction: 'left'}, 1000);
	    });
	  }
});


function multiActionSelected(type) {
	var checks = $('.check_list:checked').serializeArray();
	if(checks) {
		if (confirm(lang.confirm)) {
            $.ajax({
                type: 'post',
                url: ADMINURL + "/" + CONTROLLER + "/" + type,
                data: $.param(checks),
                beforeSend: function () {
                    
                },
                success: function () {
                	window.location = window.location.href;
                }
            });
		}
	}
}


$(document).ready(function(){
	$('.check_all_list').click(function(){
		if($(this).is(':checked')) {
			$('.check_list').attr('checked', 'checked');
		} else {
			$('.check_list').attr('checked', '');
		}
	});
	
	$(".tooltip").simpletooltip();
	
	$("#admin_form").validate({
		meta: "validate",
		invalidHandler: function(e, validator) {
			var errors = validator.numberOfInvalids();
			if (errors) {
				var message = errors == 1
					? lang.error_validate_1
					: lang.error_validate_1.replace('%d',errors);
				$("#fader .msgError .error_text").html(message);
				$("#fader").show();
			} else {
				$("#fader").hide();
			}
		}
	});
	
	$('#admin_form input[type=radio]').prettyCheckboxes({
		'display': 'inline'
	});

	//dialog
	$("#dialog").dialog({
	  bgiframe: true, autoOpen: false, width:"auto", height:"auto", zindex:9998, modal: false
	});
	
	$(".scrollbox").each(function(i) {
    	$(this).attr('id', 'scrollbox_' + i);
		sbox = '#' + $(this).attr('id');
    	$(this).after('<span class="check_all"><a onclick="$(\'' + sbox + ' :checkbox\').attr(\'checked\', \'checked\');"><u>' + lang.select_all + '</u></a> / <a onclick="$(\'' + sbox + ' :checkbox\').attr(\'checked\', \'\');"><u>' + lang.remove_all + '</u></a></span>');
	});
	
});


$().ready(function() {
	var opts = {
		cssClass : 'el-rte',
		// lang     : 'ru',
		height   : 450,
		toolbar  : 'complete',
//		cssfiles : ['data/css/main.css'],
		fmOpen : function(callback) {
			$('<div id="myelfinder" />').elfinder({
				url : BASE_URL + ADMINURL + '/elfinder',
				lang : 'en',
				dialog : { width : 900, modal : true, title : 'WM Cms - file manager for web' },
				closeOnEditorCallback : true,
				editorCallback : callback
			});
		}
	};
	$('textarea.ckeditor_replace').elrte(opts);
});