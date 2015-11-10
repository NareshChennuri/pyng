(function($){
	$.fn.requireTemplate = function(templateName){
		
		if(!this.templateHolder) {
			this.templateHolder = {};
		}
		
		templateNameKey = templateName.replace(/\//g, '_');
		
		this.templateHolder[templateNameKey] = $('#template_' + templateNameKey);
	    if (this.templateHolder[templateNameKey].length === 0) {
	        var tmpl_url = 'data/templates/default/' + templateName + '.html';
	        var tmpl_string = '';
	
	        t = $.ajax({
	            url: tmpl_url,
	            method: 'GET',
	            async: false,
	            contentType: 'text',
	            success: function (data) {
	                tmpl_string = data;
	            }
	        });
	
	        this.templateHolder[templateNameKey] = $('<script id="template_' + 
	        		templateNameKey + '" type="text/template">' + t.responseText + '<\/script>').appendTo(this);
	    }
	    return this.templateHolder[templateNameKey];
	};
})(jQuery);