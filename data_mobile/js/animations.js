function lazyLoad(){
	
	
		$.each($(".notLoadedImg"),function(i,item){
			var img = new Image();
			img.src = $(item).attr('data-src');
		
			img.addEventListener('load',function(){
					$(item).attr("src",img.src);
					$(item).removeClass("notLoadedImg").addClass('loadedImg')
			})
		})
				
	
}

$(document).ready(function(){
	lazyLoad();
})


var outerWrapperInterval = setInterval(function(){
	
	//close all menus when clicking somewhere else in the wrapper
	
	var wrapper = document.getElementById('wrapper');

	
	
	var outerWrapper = document.getElementById('outer_wrapper');

	
	if(outerWrapper){
		if($("#logo_wrapper a h1")){
									$("#logo_wrapper a h1").css('background',"url('"+logoUrl+"')");
									$("#logo_wrapper a h1").css('-webkit-background-size','112px 28px');
									$("#logo_wrapper a h1").css('-moz-background-size','112px 28px');
									$("#logo_wrapper a h1").css('background-size','112px 28px');
									

								
					}
		
		clearInterval(outerWrapperInterval);
		$("#outer_wrapper").tappable(function(){
				if($("#search_dropdown").hasClass('dropped')){
					$("#search_dropdown").animate({
						'marginTop':'-300px'
					},function(){
						$(this).removeClass('dropped');
							$("#categories").animate({
								marginLeft: "0px"
							});
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
		document.addEventListener('scroll',function(){
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
		
		
		
		
		
		$("#touch_arrow").tappable(function(event){
			event.preventDefault();
			$("#categories").animate({
				marginLeft: "-235px"
			});
		})
		
		$("#pinBox").tappable(function(){
			if($("#search_dropdown").hasClass('dropped')){
					$("#search_dropdown").animate({
						'marginTop':'-300px'
					},function(){
						$(this).removeClass('dropped');
							$("#categories").animate({
								marginLeft: "0px"
							});
					})
					
				
				}
		})
	
	}
	
	$("#profile_btn").tappable(function(){
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



function commentPin(){
	
	
		$(".comment_btn").tappable(function(event){
			
		function clearComment(){
		   	$("#white-dim").hide();
		   			$("#comment").hide().remove();
		   			$(that).removeClass('disabled');
		   }	
			
		
		var pinId = $(this).parent().parent().attr('data-id');
		
		var that = $(this);
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
					
					
				
					$("#white-dim").css('margin-top',(elPosition+$(that).parent().height())+"px").css("height",docHeight-elPosition).show();
					if(!$("#comment")){
					
					}
					$(that).parent().append("<div class='sheet' id='comment' style='overflow: visible;z-index:11000;max-widht:640px'>"+
												"<div class='comment'>"+
													"<textarea class='comment_text'></textarea>"+
													"<a id='closeCommentButton' class='closeCommentButton' style='float:left;margin-left:-8px'><img  class='closeGrey' src='../data/images/fancy_close1.png'/><img class='closeRed' src='../data/images/fancy_close1.png' style='display:none' /></a>"+
													"<a class='submit_comment red mbtn'>"+
													"<strong>Comment</strong>"+
													"<span></span>"+
													"</a>"+
												"</div>"+
												"<div class='mobile_arrow'>"+
												"</div>"+
												
											+"</div>");
											
										
					var commInterval = setInterval(function(){
						if($("#comment")){
							clearInterval(commInterval);
							$("#comment").animate({
							"top":($(that).height()/1.2)+"px",
							duration:'100',
							 
							})
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
		   document.getElementById('closeCommentButton').addEventListener('touchend',function(){
		   		clearComment();
		   		
		   },false);
		   
		  
		
		   $(".submit_comment").click(function(event){
		   	$(this).addClass('disabled').text('Posting');
		   	event.preventDefault();
		 	$.ajax({
		   		url: "../pin/"+pinId,
		   		dataType:"JSON",
		   		type:"POST",
		   		data:{"write_comment":$("#comment .comment .comment_text").val()},
		   		success:function(data){
		   			
		   		    
		   			if(data.ok == true){
		   				
		   				clearComment();
		   				if(!(that).parent().parent().parent().find($(".pin_comments").find($(".icon")))){
		   				
		   					$(that).parent().parent().find(".pin_comments .comment_text").append("<p>"+
		   												"<a class='link' href= '"+data.profile_href+"'>"+data.comment.user.fullname+"</a>"+
		   												"<span>"+data.comment.comment+"</span>"+
		   											"</p>")
		   				}
		   				else{
		   					if(window.location.href.search(pinId) == -1){
		   					$(that).parent().parent().find($(".pin_comments")).append("<table>"+
		   								"<tbody>"+
		   									"<tr>"+
		   										"<td class='icon'></td>"+
		   										"<td class='comment_text'>"+
		   											"<p>"+
		   												"<a class='link' href= '"+data.profile_href+"'>"+data.comment.user.fullname+"</a>"+
		   												"<span>"+data.comment.comment+"</span>"+
		   											"</p>"+
		   										"</td>"+
		   									"<tr>"+
		   								"<tbody/>"+
		   							"</table>");
		   					}
		   					else{
		   						
		   					}
		   				}
		   			}
		   		},
		   		error:function(error){
		   			
		   		}		
		   	})
		   })
		   
				
	})
}



function likePin(){
		$("a.like_btn").tappable(function(event){
			var that = $(this);
					var likesText = parseInt($(that).parent().prev().find('.pin_likes span').text());
					
				
					if($(that).attr('data-liked') == 0){
						that.find('strong').text('Unlike')
						$(that).attr('data-liked',1)
						$(that).addClass('pressed');
						$(that).parent().prev().find('.pin_likes span').text(likesText+1);
					
						
					}
					else{
						that.find('strong').text('Like')
							$(that).attr('data-liked',0)
							$(that).removeClass('pressed');
							$(that).parent().prev().find('.pin_likes span').text(likesText-1);
						}
						
						console.log(window.location.href.search('pin'));
					if(window.location.href.search('pin')){
						console.log("pin");
					}
			
			
			event.preventDefault();
			$.ajax({
				url:$(this).attr('href'),
				type:'JSON',
				success:function(data){
				
				},
				error:function(error){
					$(that).find('strong').append("<b style='color:red'> !</b>")
				}		
		
			})
		})		
			
}



function repin(){
	$(".repin_btn ").tappable(function(event){
				event.preventDefault();
						
								



				function clearRepin(){
		  		 	$("#white-dim").hide();
		   			$("#repin").hide().remove();
		   			$(that).removeClass('disabled');
		   }	
			
		
		var pinId = $(this).parent().parent().attr('data-id');
		
		var that = $(this);
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
					
					
				
					$("#white-dim").css('margin-top',(elPosition+$(that).parent().height())+"px").css("height",1200+"px").show();
					
					$(that).addClass('disabled');
					
					var repinCount = parseInt($(that).parent().parent().find(".pin_repins span").text());
				
					
					$.ajax({
					url:$(this).attr('href'),
					dataType:"html",
					success:function(data){
							$(that).parent().append(data);
							//animate the div
			var commInterval = setInterval(function(){
						if($("#repin")){
							clearInterval(commInterval);
							$("#repin").animate({
							"top":($(that).height()/1.2)+"px",
							duration:'100',
							 
							})
						}
					})
							
										//clear events
					   $("#white-dim").scroll(function(){
					   	clearRepin();
					   })
					   
					   document.getElementById('white-dim').addEventListener('touchstart',clearRepin,false);
					   document.getElementById('white-dim').addEventListener('scroll',clearRepin,false);
					   document.getElementById('closeRepinButton').addEventListener('touchend',function(){
		   				clearRepin();
		   			   },false);
			   		
			   		
			   		  //SENDING POST TO REPIN CONTROLLER
			   		  $(".submit_repin").tappable(function(event){
						event.preventDefault();
					  
						  	$.ajax({
						  		url:$(that).attr('href'),
						  		type:"POST",
						  		dataType:"text",
						  		data:{'message':$("#message").text(),'board_id':$("#board_id option:selected").val()},
						  		success:function(d){
						  		
						  			$(that).parent().parent().find(".pin_repins span").text(repinCount+1);
						  				clearRepin();
						  			
						  			
						  		},
						  		error:function(error){
						  			
						  		}
						  	})
					  })	
			
					}
				})		
					
			}
					

		})
}

$(document).ready(function(){
		
		
	
			
				
		//LIKE UNLIKE PIN
		

			
		
		
			
		//FOLOW UNFLOW BUTTON
				
		document.addEventListener('touchstart',function(e){
		
			var target = $(e.target).parent();
				console.log(target);
			if($(target).attr('id') == 'follow') { submitQuery(target,'follow') };
			$(target).attr('id') == 'unfollow' ? submitQuery(target,'unfollow') : null;
		
		})
		
		
		function clearLoader(target){
			$("#loader").remove();
			target.parent().removeClass('loading');
		}
		
		function follow(target){
			clearLoader(target);
			target.hide();
			target.parent().find($("a#follow")).show();
			
		}
		
		function unfollow(target){
			clearLoader(target);
			target.hide();
			target.parent().find($("a#unfollow")).show();
		}
		
		function submitQuery(target,type){
			
		if(	!target.parent().hasClass('loading')){
				
			target.parent().append("<img id='loader' src='/data/images/loading_2.gif'/ style='float:right;height:10px;width:10px'>").addClass('loading');
			$.ajax({
				url:target.attr('data-link'),
				dataType:'json',
				success:function(data){
				
					data.ok == 'Follow' ? follow(target) : unfollow(target)
				}
			})
		   
		   }
			
  		}
  		
  		
		
		})
		



