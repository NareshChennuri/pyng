$(document).ready(function(){
	
	var i = 2;
	var loaded = 1;
	
	window.onscroll = function(){
		var currPos = window.pageYOffset;
		var docLength = $(document).height();
		var ratio = parseFloat(currPos/docLength);
		
		if(ratio > 0.45 && loaded < i){
			
			$("#floader").show();
		
			//ratio = '';
		
				var url=window.location+((window.location.href).indexOf('?')>-1?"&":"?")+"page="+i+"&RSP=ajax";
			
			
			loaded = i;
			$.ajax({
					url:url,
					dataType:"text",
					type:"POST",
					success:function(d){
						
						i++;
						var d = $.parseJSON(d);
						
						//$("#container").append("<div class='batch"+loaded+"'/></div>")
					
						/*$.each($(d),function(c,e){
						
							
								
							if($(e).hasClass('pin')){
								setTimeout(function(){
									$("#container").append($(e));
									
									$.each($(".notLoadedImg"),function(){
									var img = new Image();
									img.src = $(this).data('src');
									
									var that = this;
									
										
										$(that).attr('src',img.src);
										$(that).removeClass('notLoadedImg');
									
									})
								})
							}

							
								$("#floader").hide();
							
							
						
						})*/
						
							var cc = $(document).find($("#ColumnContainer"))[0];
							if(!cc){
								model.getTemplate('pinBox',function(pinBox){
									$.each(d,function(i,pin){
										if(pin.template === 'pins'){
											
											pin.grayImg = model.grayImg;
											var pin = $(pinBox).tmpl(pin).appendTo("#container");
											pin.find('img').LazyLoad();
										}
									});
								
								});
							}else{
							model.getTemplate('userBoardPin',function(pinBox){
										var mb = $('.masonry-brick')[0];
									
										
									$.each(d,function(i,pin){
										if(pin.template === 'pins'){
											
											var contaner_width = $('#ColumnContainer .pinboard').width();
											var item_width = (Math.round(contaner_width / 4)-3);
											pin.grayImg = model.grayImg;
											var pin = $(pinBox).tmpl(pin).appendTo("#ColumnContainer .pinboard");
											var image = new Image(); 
											$(pin).addClass('hidden');
											$($(pin).find('img')).css({
														"width": $(mb).width()+'px',
														"height": Math.ceil(item_width / (this.width/this.height))
											}).attr('src', this.src).removeAttr('width').removeAttr('height');
										}
									
										if(d.length === parseInt(i+1)){
											
											$('#ColumnContainer .pinboard').masonry('reload');
											
										
										}
										
										$(".hidden").removeClass('hidden');
									});
								
								});
							}
					
						
						
						
								
					},
					
					error:function(error){
					
					}
					
			});
			
		}
	}
	
	
	

})
