(function($){
	$.fn.joform = function() {

		var disable_action = $(this).attr('disable_action') == 'true' ? true : false;
	    var action = $(this).attr('action'); 
	    var url = (typeof action === 'string') ? $.trim(action) : ''; 
	    if (url) {
		    url = (url.match(/^([^#]+)/)||[])[1]; 
	    }
	    url = url || window.location.href || ''; 
	    
	    if(disable_action) {
	    	url = '';
	    }

	    var objForm = $(this).get(0);
	    var submitDisabledElements = false;
	    var prefix="";
	    
	    var sXml = "";
	    if (objForm && objForm.tagName.toUpperCase() == 'FORM') {
			var formElements = objForm.elements;
			for( var i=0; i < formElements.length; i++) { 
			    if (!formElements[i].name)
				continue;
			    if (formElements[i].name.substring(0, prefix.length) != prefix)
				continue;
			    if (formElements[i].type && (formElements[i].type == 'radio' || formElements[i].type == 'checkbox') && formElements[i].checked == false)
				continue;
			    if (formElements[i].disabled && formElements[i].disabled == true && submitDisabledElements == false)
				continue;
			    var name = formElements[i].name; 
			    if (name) {
					if(formElements[i].type=='select-multiple') { 
					    for (var j = 0; j < formElements[i].length; j++) {
							if (formElements[i].options[j].selected == true) {
							    sXml = sXml + name+"="+encodeURIComponent(formElements[i].options[j].value);
							    if (sXml != '') { sXml = sXml + '&'; }
							}
					    }
					} else {
					    if(formElements[i].value) {
					    	sXml = sXml + name + "=" + encodeURIComponent(formElements[i].value);
					    	if (sXml != '') { sXml = sXml + '&'; }
					    }
					}
			    }
			}
	    }
	    
	    if(disable_action) {
	    	return sXml;
	    }
	    
	    return url + (url.indexOf('?') >= 0 ? '&' : '?') + sXml;
	};
})(jQuery);