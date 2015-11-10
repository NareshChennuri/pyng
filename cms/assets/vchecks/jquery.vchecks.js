jQuery.fn.vchecks = function() {
	
	object = jQuery(this);
	object.addClass('geogoer_vchecks');
	object.find("li:first").addClass('first');
	if(object.find("li").size() > 1) {
		object.find("li:last").addClass('last');
	}
	//removing checkboxes
	object.find("input[type=checkbox]").each(function(){
		$(this).hide();
	});
	//adding images true false
	object.find("li").each(function(){
		if($(this).find("input[type=checkbox]").attr('readonly') == true){
			$(this).addClass('readonly');
		}
		if($(this).find("input[type=checkbox]").attr('disabled') == true){
			$(this).addClass('disabled');
		}
		if($(this).find("input[type=checkbox]").attr('checked') == true){
			$(this).addClass('checked');
			$(this).append('<div class="check_div"></div>');
		}
		else{
			$(this).addClass('unchecked');
			$(this).append('<div class="check_div"></div>');
		}
	});
	//binding onClick function
	object.find("li").click(function(e){ 
		e.preventDefault();
		check_li = $(this);
		
		if(check_li.hasClass('readonly') || check_li.hasClass('disabled')) {
			return false;
		}
		
		checkbox = $(this).find("input[type=checkbox]");
		if(checkbox.attr('checked') == true){
			checkbox.attr('checked',false);
			check_li.removeClass('checked');
			check_li.addClass('unchecked');
		}
		else{
			checkbox.attr('checked',true);
			check_li.removeClass('unchecked');
			check_li.addClass('checked');
		}
	});
	
	//mouse over / out
	//simple
	object.find("li:not(:last,:first)").bind('mouseover', function(e){
		$(this).addClass('hover');
	});
	object.find("li:not(:last,:first)").bind('mouseout', function(e){
		$(this).removeClass('hover');
	});
	//first
	object.find("li:first").bind('mouseover', function(e){
		$(this).addClass('first_hover');
	});
	object.find("li:first").bind('mouseout', function(e){
		$(this).removeClass('first_hover');
	});
	//last
	object.find("li:last").bind('mouseover', function(e){
		$(this).addClass('last_hover');
	});
	object.find("li:last").bind('mouseout', function(e){
		$(this).removeClass('last_hover');
	});
}