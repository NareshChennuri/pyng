


var searchInt = setInterval(function(){
	if($("#query")){
		
		$("#query").focus(function(){
			$(this).val('');
		})
		
		clearInterval(searchInt);
		
		
	}
})




var outerWrapperInterval = setInterval(function(){
	
	//close all menus when clicking somewhere else in the wrapper
	
	var wrapper = document.getElementById('wrapper');

	
	
	var outerWrapper = document.getElementById('outer_wrapper');

	
	if(outerWrapper){
		if($("#logo_wrapper a h1")){
									$("#logo_wrapper a h1").css('background',"url('"+logoUrl+"')");
									$("#logo_wrapper a h1").css('-webkit-background-size','125px 37px');
									$("#logo_wrapper a h1").css('-moz-background-size','125px 37px');
									$("#logo_wrapper a h1").css('background-size','125px 37px');
									

								
					}
					
					
		

		
		clearInterval(outerWrapperInterval);
		$("#outer_wrapper").live('tap',function(){
				
				if($("#search_dropdown").hasClass('dropped')){
					$("#search_dropdown").animate({
						'marginTop':'-300px'
					},function(){
						$(this).removeClass('dropped');
							$("#categories").animate({
								marginLeft: "0px"
							});
					 	$("#search_dropdown").val('');
					})
					
				
				}
				else{
					$("#search_dropdown").animate({
						'marginTop': '0px'
					},function(){
					$(this).addClass('dropped');
					})
					
					
					if($("#user_dropdown").hasClass('dropped')){
						$("#user_dropdown").animate({
							'marginTop':"-300px"
						},function(){
							$(this).removeClass('dropped')
						})
					}
					
				}
		});
		
		
	
		
		
		// close menus on scroll
	/*	document.addEventListener('scroll',function(){
			if($("#search_dropdown").css('margin-top') == '0px'){
				$("#search_dropdown").animate({
					"marginTop":"-300px"
				})
			}
			
			if($("#user_dropdown").css('margin-top') == '0px'){
				$("#user_dropdown").animate({
					"marginTop":"-300px"
				})
			}
		})
		
		
		*/
		
	$("#touch_arrow").live("touchend",function(){
			event.preventDefault();
			$("#categories").animate({
				marginLeft: "-235px"
			});
			
			
		})
	
	}
	
	
	
	$("#profile_btn").live('tap',function(){
		
		if($("#user_dropdown").hasClass('dropped')){
			$("#user_dropdown").animate({
				'marginTop':"-300px"
			},function(){
				$(this).removeClass('dropped');
			})
		}
		else
		{
			$("#user_dropdown").animate({
				'marginTop':"0px"
			},function(){
				$(this).addClass('dropped');
			})
			
			if($("#search_dropdown").hasClass('dropped')){
				$("#search_dropdown").animate({
					'marginTop':"-300px"
				},function(){
					$(this).removeClass('dropped')
					$("#categories").animate({
								marginLeft: "0px"
					});
				})
			}
		}
	})
	
	


})



$(".comment_btn").live('tap',function(){
		var url = $(this).attr('data-comment');
		
		if(document.getElementById("repin")){
			$("#white-dim").hide();
			$("#repin").hide().remove();
			$(".repin_btn").removeClass('disabled');
		}
		
			
		function clearComment(){
		   			$("#white-dim").hide();
		   			$("#comment").hide().remove();
		   			$(that).removeClass('disabled');
		   }	
			
		
		var pinId = $(event.target).parent().parent().parent().attr('data-id');
		var that = $(event.target).parent();
		
		
		//position of the next pinBox related to thew clicked commentButton
		var elPosition = $(that).parent().offset().top;
		//the height of the document
		var docHeight = $(document).height();
		//the top-margin of the dim layer
		var top = parseInt((docHeight-elPosition)-$(that).parent().height());
		
			if($(that).hasClass('disabled')){
				clearComment();
				
			}
			
			else{
					
					$(that).addClass('disabled');
					//scroll the boddy so the comments are on the top of the window
					$("html, body").animate({
						scrollTop: $(that).parent().prev().prev().prev().offset().top
					})
					
					
				
					$("#white-dim").css('margin-top',(elPosition+$(that).parent().height())+"px").css("min-height",'2000px').show();
					if(!$("#comment")){
					
					}
					$(that).parent().append("<div class='sheet' id='comment' style='overflow: visible;z-index:11000;max-widht:640px;'>"+
												"<div class='comment'>"+
													"<textarea class='comment_text'></textarea>"+
													//"<a id='closeCommentButton' class='closeCommentButton' style='float:left;margin-left:-8px'><img  class='closeGrey' src='../data/images/fancy_close1.png'/><img class='closeRed' src='../data/images/fancy_close1.png' style='display:none' /></a>"+
													"<a class='submit_comment red mbtn'>"+
													"<strong>"+commentButtonText+"</strong>"+
													"<span></span>"+
													"</a>"+
												"</div>"+
												"<div class='mobile_arrow'>"+
												"</div>"+
												
											+"</div>");
											
										
					var commInterval = setInterval(function(){
						if($("#comment")){
							clearInterval(commInterval);
						
							
							
							
						}
					})
				
					}
					
					
						
			/*	//tapping on the #white-dim
				$("#white-dim").tappable(function(){
					$("#comment").fadeOut().remove();
					$("#white-dim").css('margin-top','0px').fadeOut();
					$(that).removeClass('disabled');
				})
				
		    */
			
	
		   
		   
		  
		   
		   $("#white-dim").scroll(function(){
		   	clearComment();
		   })
		   
		   document.getElementById('white-dim').addEventListener('touchstart',clearComment,false);
		   document.getElementById('white-dim').addEventListener('scroll',clearComment,false);
						
			
		
		   $(".submit_comment").click(function(event){
			
		   	$(this).addClass('disabled').text('Posting');
		   	event.preventDefault();
		 	$.ajax({
		   		url: url,
		   		dataType:"JSON",
				
		   		type:"POST",
		   		data:{"write_comment":$("#comment .comment .comment_text").val(),'send_comment':'1'},
		   		success:function(data){
				

		   			checkForLocation(data);
					 
		   			if(data.ok == true){
		   				var pinBox = $(that).parent().parent();
						
						
						if(!pinBox.find($('.pin_comments'))[0]){
							var pinSource = pinBox.find($('.pin_source'));
							pinSource.after("<div>"+
									"<div  class='pin_comments' style='display:block'>"+
										"<div class='inner'>"+
											"<table>"+
												"<tbody>"+
													"<tr>"+
														"<td class='icon'></td>"+
															"<td class='comment_text'>"+
																"<p>"+
																	"<a class='link' href='"+data.user.profile+"'>"+data.user.fullname+"</a>"+
																	"<a><span></span>"+data.comment+"</a>"+
																"</p>"+
															"</td>"+
													"</tr>"+
												"</tbody>"+
											"</table>"+
										"</div>"+
									"<div>"+
							"</div>");
						}else{
		   					if(window.location.href.search(pinId) == -1){
		   					$(that).parent().parent().find($(".pin_comments")).append("<table>"+
		   								"<tbody>"+
		   									"<tr>"+
		   										"<td class='icon'></td>"+
		   										"<td class='comment_text'>"+
		   											"<p>"+
		   												"<a class='link' href= '"+data.profile_href+"'>"+data.user.fullname+"</a>"+
		   												"<span>"+data.comment+"</span>"+
		   											"</p>"+
		   										"</td>"+
		   									"<tr>"+
		   								"<tbody/>"+
		   							"</table>");
		   					}
		   					
		   				}
						clearComment();
		   			}
		   		},
		   		error:function(error){
		   			
		   		}		
		   	})
		   })
		   
				


})



$(".like_btn").live('tap',function(event){
			event.preventDefault();
			
			var self = $(event.target);
			var that = $(event.target).parent();
					
					var likesText = parseInt($(that).parent().prev().find('.pin_likes span').text());
					var lt = ''; //lt is like text but we check if its present in the DOM or not
					if(!likesText){
						lt = 0;
					}
					else{
						lt = likesText;
					}
					
					if($(that).parent().prev().find($('.icon'))){
						
						if($(that).attr('data-liked') == 0){
							that.find('strong').text('Unlike')
							$(that).attr('data-liked',1)
							$(that).addClass('pressed');
							$(that).parent().prev().find('.pin_likes .likeIcon').addClass('icon');
							
							$(that).parent().prev().find('.pin_likes span').text(lt+1);
						}
						else{
							/*that.find('strong').text('Like')
							$(that).attr('data-liked',0)
							$(that).removeClass('pressed');
							$(that).parent().prev().find('.pin_likes span').text(lt-1);*/
							
								
						}
						
					}
					else{
				
					}	
			
			function iconShown(){
				
				if($(that).parent().parent().find($('.pin_stats').find($('.likeIcon')))[0])
				{
					return true;
				}else{
					
					return false;
				}
			}
			
			function iconManager(numberOfLikes){
				iconShown();
				var pinBox = $(that).parent().parent();
				if(numberOfLikes == 0 ) {
					if(iconShown()){
						$(pinBox).find($('.pin_likes')).remove();
					}
				}else{
					if(!iconShown()){
						$(pinBox).find($('.pin_stats')).append("<div class='pin_likes' style='display:block'>"+
							"<div class='likeIcon icon'></div>"+
							"<span>"+numberOfLikes+"</span>"
							+"</div>");
					}else{
						$(".pin_likes").find($('span')).text(numberOfLikes)
					}
				}
			}
								
			
			
			$.ajax({
				
				url:$(that).attr('href'),
				type:'JSON',
				success:function(data){
					
					var data = $.parseJSON(data);
					checkForLocation(data);
					if(data.ok){
						$(that).find($('a')).text(data.text);
						var likesNumber = data.stats.likes.split(' ')[0];
						console.log(data);
						$(that).find($('strong')).text(data.text);
						iconManager(likesNumber);
						
					}
					
					return false;
				},
				error:function(error){
					$(that).find('strong').append("<b style='color:red'> !</b>");
				}		
		
			})
		
			
});






$(".repin_btn").live('tap',function(event){
				
		event.preventDefault();
						
			
		if(document.getElementById("comment")){
			$("#white-dim").hide();
			$("#comment").hide().remove();
			$(".comment_btn ").removeClass('disabled');
		}
		



				function clearRepin(){
		  		 	$("#white-dim").hide();
		   			$("#repin").hide().remove();
		   			$(that).removeClass('disabled');
		   }	
			
		
		var pinId = $(this).parent().parent().attr('data-id');
		
		var that = $(event.target).parent();
		//position of the next pinBox related to thew clicked commentButton
		var elPosition = $(that).parent().offset().top;
		//the height of the document
		var docHeight = $(document).height();
		//the top-margin of the dim layer
		var top = parseInt((docHeight-elPosition)-$(that).parent().height());
		
		
			
			
			if($(that).hasClass('disabled')){
				clearRepin();
				
			}
			
			else{
					
					$(that).addClass('disabled');
					
					//scroll the boddy so the comments are on the top of the window
					$("html, body").animate({
						scrollTop: $(that).parent().prev().prev().prev().offset().top
					})
					
					
				
					$("#white-dim").css('margin-top',(elPosition+$(that).parent().height())+"px").css("min-height",'2000px').show();
					
					$(that).addClass('disabled');
					
					var repinCount = parseInt($(that).parent().parent().find(".pin_repins span").text());
					if(repinCount){
						rc = repinCount
					}
					else{
						rc = 0;
					}
					
					$.ajax({
					url:$(event.target).parent().attr('href'),
					dataType:"html",
					success:function(data){
							$(that).parent().append(data);
							//animate the div
			var commInterval = setInterval(function(){
						if($("#repin")){
							clearInterval(commInterval);
							$("#repin").animate({
							"top":($(that).height()/1.2)+"px",
							duration:'100'
							 
							})
							
						}
					})
							
										//clear events
					   $("#white-dim").scroll(function(){
					   	clearRepin();
					   })
					   
					   document.getElementById('white-dim').addEventListener('touchstart',clearRepin,false);
					   document.getElementById('white-dim').addEventListener('scroll',clearRepin,false);
					   
			   		  $("#board_id").change(function(){
			   		  	if($("#board_id option:selected").attr('id') == 'create_new_board'){
			   		  		$("#create_board_wrapper").show();
			   		  		$("#createBoardInput").addClass('opened');
			   		  	}
			   		  	else{
			   		  			$("#create_board_wrapper").hide();
			   		  			$("#createBoardInput").removeClass('opened');
			   		  	}
			   		  })
			   	
					
			   		  //SENDING POST TO REPIN CONTROLLER
			   		  $("#submitRepin").live('tap',function(event){
						event.preventDefault();
						var url = $(this).attr('href');
						
						
			   		  	function submit(boardId){
						var data = {
							'title':pinData.title,
							'price':pinData.price,
							'image':pinData.images.thumb_original_a,
							'from':pinData.from,
							'via':pinData.via,
							'from_repin':pinData.from_repin,
							'description':$("#message").val()
						}
							
							data.board_id = boardId;
			   		  		$.ajax({
						  		url:url,
						  		type:"POST",
						  		dataType:"json",
						  		data:data,
						  		success:function(d){
						  		    
						  		    $(that).parent().parent().find(".pin_repins .repinIcon").addClass('icon');
						  			$(that).parent().parent().find(".pin_repins span").text(rc+1);
						  			clearRepin();
									window.location.href=d.pin_url;
						  			
						  			
						  		},
						  		error:function(error){
									
						  		}
						  	})
			   		  	}
					
						
					  
					  		if($("#createBoardInput").hasClass('opened')){
					  			
					  				var boardName = $("#createBoardInput").val();
					  				
					  			    if(boardName == ''){
					  				alert($("#createBoardInput").data('msg'))
					  				
					  				}
					  			else{
										$.ajax({
											url:$("#createBoardInput").data('cb'),
											type:"POST",
											dataType:"JSON",
											data:{"newboard":boardName},
											success:function(d){
												
												submit(d.data.board_id);
											},
											error:function(error){
											
											}
										})				  				
						  			}
					  		}
					  		
					  		else{
					  			submit($("#board_id option:selected").val());					  			
					  		}
					  		
					  })	
			
					}
				})		
					
			}
					

	
})


$(document).ready(function(){
		
		
	
		//reduce height of body in user profile!!!!!!!!!!!!!!!
		//document.getElementsByTagName('body').style.height='';
		//console.log(document.getElementsByTagName('body'));
	
		
		$.each($(".notLoadedImg"),function(){
			var img = new Image();
			img.src = $(this).data('src');
			
			var that = this;
			img.onload = function(){
				
				$(that).attr('src',img.src);
				$(that).removeClass('notLoadedImg');
			}
		})
		
		$("#categories").live('swiperight',function(){
			
			$("#categories").animate({
				"marginLeft":"235px"
			})
			
		})
	
			
		
		

			
		
		
			

		
		})
		
		
		
		


	
  		
  		$('#white-dim').live('tap',function(event){
  		  event.preventDefault();
  		 
  		})
  		
  		$('#white-dim').live('swipe',function(event){
  		  event.preventDefault();
  	
  		})
  		
  		$("#container").live('tap',function(event){
  			if($(".dropped")){
  				$(".dropped").animate({
  					"marginTop" : "-300px"
  				})
  			}
  		})
  		
  	/*	$(document).scroll(function(){
  			if($(".dropped")){
  				$(".dropped").animate({
  					"marginTop" : "-300px"
  				})
  			}
  		})
  	*/	
  	
  		
	function checkForLocation(data){
	if(data.location){
		window.location.href = data.location;
	}
}

			

