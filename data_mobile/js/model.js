/*
 * jQuery Templates Plugin 1.0.0pre
 * http://github.com/jquery/jquery-tmpl
 * Requires jQuery 1.4.2
 *
 * Copyright 2011, Software Freedom Conservancy, Inc.
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 */

/*jquery ajax upload*/ 


 
(function(a){var r=a.fn.domManip,d="_tmplitem",q=/^[^<]*(<[\w\W]+>)[^>]*$|\{\{\! /,b={},f={},e,p={key:0,data:{}},i=0,c=0,l=[];function g(g,d,h,e){var c={data:e||(e===0||e===false)?e:d?d.data:{},_wrap:d?d._wrap:null,tmpl:null,parent:d||null,nodes:[],calls:u,nest:w,wrap:x,html:v,update:t};g&&a.extend(c,g,{nodes:[],parent:d});if(h){c.tmpl=h;c._ctnt=c._ctnt||c.tmpl(a,c);c.key=++i;(l.length?f:b)[i]=c}return c}a.each({appendTo:"append",prependTo:"prepend",insertBefore:"before",insertAfter:"after",replaceAll:"replaceWith"},function(f,d){a.fn[f]=function(n){var g=[],i=a(n),k,h,m,l,j=this.length===1&&this[0].parentNode;e=b||{};if(j&&j.nodeType===11&&j.childNodes.length===1&&i.length===1){i[d](this[0]);g=this}else{for(h=0,m=i.length;h<m;h++){c=h;k=(h>0?this.clone(true):this).get();a(i[h])[d](k);g=g.concat(k)}c=0;g=this.pushStack(g,f,i.selector)}l=e;e=null;a.tmpl.complete(l);return g}});a.fn.extend({tmpl:function(d,c,b){return a.tmpl(this[0],d,c,b)},tmplItem:function(){return a.tmplItem(this[0])},template:function(b){return a.template(b,this[0])},domManip:function(d,m,k){if(d[0]&&a.isArray(d[0])){var g=a.makeArray(arguments),h=d[0],j=h.length,i=0,f;while(i<j&&!(f=a.data(h[i++],"tmplItem")));if(f&&c)g[2]=function(b){a.tmpl.afterManip(this,b,k)};r.apply(this,g)}else r.apply(this,arguments);c=0;!e&&a.tmpl.complete(b);return this}});a.extend({tmpl:function(d,h,e,c){var i,k=!c;if(k){c=p;d=a.template[d]||a.template(null,d);f={}}else if(!d){d=c.tmpl;b[c.key]=c;c.nodes=[];c.wrapped&&n(c,c.wrapped);return a(j(c,null,c.tmpl(a,c)))}if(!d)return[];if(typeof h==="function")h=h.call(c||{});e&&e.wrapped&&n(e,e.wrapped);i=a.isArray(h)?a.map(h,function(a){return a?g(e,c,d,a):null}):[g(e,c,d,h)];return k?a(j(c,null,i)):i},tmplItem:function(b){var c;if(b instanceof a)b=b[0];while(b&&b.nodeType===1&&!(c=a.data(b,"tmplItem"))&&(b=b.parentNode));return c||p},template:function(c,b){if(b){if(typeof b==="string")b=o(b);else if(b instanceof a)b=b[0]||{};if(b.nodeType)b=a.data(b,"tmpl")||a.data(b,"tmpl",o(b.innerHTML));return typeof c==="string"?(a.template[c]=b):b}return c?typeof c!=="string"?a.template(null,c):a.template[c]||a.template(null,q.test(c)?c:a(c)):null},encode:function(a){return(""+a).split("<").join("&lt;").split(">").join("&gt;").split('"').join("&#34;").split("'").join("&#39;")}});a.extend(a.tmpl,{tag:{tmpl:{_default:{$2:"null"},open:"if($notnull_1){__=__.concat($item.nest($1,$2));}"},wrap:{_default:{$2:"null"},open:"$item.calls(__,$1,$2);__=[];",close:"call=$item.calls();__=call._.concat($item.wrap(call,__));"},each:{_default:{$2:"$index, $value"},open:"if($notnull_1){$.each($1a,function($2){with(this){",close:"}});}"},"if":{open:"if(($notnull_1) && $1a){",close:"}"},"else":{_default:{$1:"true"},open:"}else if(($notnull_1) && $1a){"},html:{open:"if($notnull_1){__.push($1a);}"},"=":{_default:{$1:"$data"},open:"if($notnull_1){__.push($.encode($1a));}"},"!":{open:""}},complete:function(){b={}},afterManip:function(f,b,d){var e=b.nodeType===11?a.makeArray(b.childNodes):b.nodeType===1?[b]:[];d.call(f,b);m(e);c++}});function j(e,g,f){var b,c=f?a.map(f,function(a){return typeof a==="string"?e.key?a.replace(/(<\w+)(?=[\s>])(?![^>]*_tmplitem)([^>]*)/g,"$1 "+d+'="'+e.key+'" $2'):a:j(a,e,a._ctnt)}):e;if(g)return c;c=c.join("");c.replace(/^\s*([^<\s][^<]*)?(<[\w\W]+>)([^>]*[^>\s])?\s*$/,function(f,c,e,d){b=a(e).get();m(b);if(c)b=k(c).concat(b);if(d)b=b.concat(k(d))});return b?b:k(c)}function k(c){var b=document.createElement("div");b.innerHTML=c;return a.makeArray(b.childNodes)}function o(b){return new Function("jQuery","$item","var $=jQuery,call,__=[],$data=$item.data;with($data){__.push('"+a.trim(b).replace(/([\\'])/g,"\\$1").replace(/[\r\t\n]/g," ").replace(/\$\{([^\}]*)\}/g,"{{= $1}}").replace(/\{\{(\/?)(\w+|.)(?:\(((?:[^\}]|\}(?!\}))*?)?\))?(?:\s+(.*?)?)?(\(((?:[^\}]|\}(?!\}))*?)\))?\s*\}\}/g,function(m,l,k,g,b,c,d){var j=a.tmpl.tag[k],i,e,f;if(!j)throw"Unknown template tag: "+k;i=j._default||[];if(c&&!/\w$/.test(b)){b+=c;c=""}if(b){b=h(b);d=d?","+h(d)+")":c?")":"";e=c?b.indexOf(".")>-1?b+h(c):"("+b+").call($item"+d:b;f=c?e:"(typeof("+b+")==='function'?("+b+").call($item):("+b+"))"}else f=e=i.$1||"null";g=h(g);return"');"+j[l?"close":"open"].split("$notnull_1").join(b?"typeof("+b+")!=='undefined' && ("+b+")!=null":"true").split("$1a").join(f).split("$1").join(e).split("$2").join(g||i.$2||"")+"__.push('"})+"');}return __;")}function n(c,b){c._wrap=j(c,true,a.isArray(b)?b:[q.test(b)?b:a(b).html()]).join("")}function h(a){return a?a.replace(/\\'/g,"'").replace(/\\\\/g,"\\"):null}function s(b){var a=document.createElement("div");a.appendChild(b.cloneNode(true));return a.innerHTML}function m(o){var n="_"+c,k,j,l={},e,p,h;for(e=0,p=o.length;e<p;e++){if((k=o[e]).nodeType!==1)continue;j=k.getElementsByTagName("*");for(h=j.length-1;h>=0;h--)m(j[h]);m(k)}function m(j){var p,h=j,k,e,m;if(m=j.getAttribute(d)){while(h.parentNode&&(h=h.parentNode).nodeType===1&&!(p=h.getAttribute(d)));if(p!==m){h=h.parentNode?h.nodeType===11?0:h.getAttribute(d)||0:0;if(!(e=b[m])){e=f[m];e=g(e,b[h]||f[h]);e.key=++i;b[i]=e}c&&o(m)}j.removeAttribute(d)}else if(c&&(e=a.data(j,"tmplItem"))){o(e.key);b[e.key]=e;h=a.data(j.parentNode,"tmplItem");h=h?h.key:0}if(e){k=e;while(k&&k.key!=h){k.nodes.push(j);k=k.parent}delete e._ctnt;delete e._wrap;a.data(j,"tmplItem",e)}function o(a){a=a+n;e=l[a]=l[a]||g(e,b[e.parent.key+n]||e.parent)}}}function u(a,d,c,b){if(!a)return l.pop();l.push({_:a,tmpl:d,item:this,data:c,options:b})}function w(d,c,b){return a.tmpl(a.template(d),c,b,this)}function x(b,d){var c=b.options||{};c.wrapped=d;return a.tmpl(a.template(b.tmpl),b.data,c,b.item)}function v(d,c){var b=this._wrap;return a.map(a(a.isArray(b)?b.join(""):b).filter(d||"*"),function(a){return c?a.innerText||a.textContent:a.outerHTML||s(a)})}function t(){var b=this.nodes;a.tmpl(null,null,null,this).insertBefore(b[0]);a(b).remove()}})(jQuery);
var Model = function(){
	var self = this;
	
	 
	this.get = function(url,callback){
		showFancyLoader('body');
		var callback = callback;
		$.ajax({
			url:url,
			type:"GET",
			dataType:"jsonp",
			success:function(data){
				callback(data);
			}
		});
	},
	
	this.getHtml = function(url,callback){
		showFancyLoader();
		var callback = callback;
		$.ajax({
			url:url,
			type:"GET",
			dataType:"html",
			success:function(data){
				callback(data);
			}
		});
	},
	
	this.getTemplate = function(templateName,callback){
		
		$.ajax({
			url:'data_mobile/templates/'+templateName+".html",
			type:"GET",
			dataType:"HTML",
			success:function(data){
				
				callback(data);
				hideFancyLoader();
			}
		});
	}
	
	
	this.showCommentButton = function(){
		
	}
	
	
	this.submitFollowAction = function(element){
		console.log(element[0]);
		showLoader();
		
		$.ajax({
				
				url:element.data('link'),
				dataType:'json',
				success:function(data){
					console.log(data.classs);
					if(data.classs==='remove'){
						element.parent().find($(".follow")).hide();
						element.parent().find($(".unfollow")).show();
						hideLoader();
					}else{
						element.parent().find($(".follow")).show();
						element.parent().find($(".unfollow")).hide();
						hideLoader();
					}
				}
			});
			
		function showLoader(){
			element.parent(find($('a.follow'))).append("<img id='loader' src='/data/images/loading_2.gif' style='float:right;height:10px;width:10px'>").addClass('loading');
			console.log(element.parent(find($('a.follow img'))));
		}
		
		function hideLoader(){
			$("#loader").remove();
		}
		
		return false;
	}
	
	
	function showFancyLoader(element){
		$(element).append("<div id='loaderOverlay' style='position:absolute;background-color:white;width:100%;height:100%;z-index:10000;left:0px'></div>")
		$("#loaderOverlay").append("<img src='data_mobile/images/fancy_loading.gif' style='position:absolute;top:10%;left:50%;margin-left:-32px;'/>");
	}
	
	function hideFancyLoader(){
		$("#loaderOverlay").remove();
	}
	
	this.post = function(url,data,callback){
		$.ajax({
			url:url,
			data:data,
			dataType:"JSONP",
			type:"POST",
			success:function(response){
				callback(response);
			}
		});
	}
	
	this.postFile = function(url,data,callback){
		$.ajax({
			url:url,
			data:data,
			dataType:"JSON",
			type:"POST",
			processData: false,  
			contentType: false, 
			success:function(response){
				callback(response);
			},
			error:function(error){
				console.log(error);
			}
		});
	}
	
	this.upload = function(element){
		element.append("<form id='uploadForm' enctype='multipart/form-data' method='POST' action='addpin_fromfile/upload_images'><input id='upload' type='file' name='file' accept='image/*'/></form>");
		$("#uploadForm").css({
			'width':'35px',
		});
		$("#upload").css({
			'width':"48px",
			'height':"35px",
			'position':'absolute',
			'margin-top':'0px',
			'margin-left':'0px',
			'padding':'0px',
			'left':'0px',
			'background-color':'trasparent',
			'opacity':'0',
			'filter':'alpha(opacity = 0);'
		});
		
		$("#upload").change(function(event){
			var data = $("#uploadForm").serialize();
			var formdata = false;
			if(window.FormData){
				var input = document.getElementById('upload');
				formdata = new FormData();
				var file = this.files
				var reader = new FileReader();
				
				window.scrollTo(0)
				$("#wrapper").append("<div class='sheet addpin' style='margin-top:-30px;padding:15px;min-height:"+$(window).height()+"px;'></div>");
				showFancyLoader('.addpin');
				
				
				formdata.append('file',file[0]);
				console.log(formdata);

				self.postFile('addpin_fromfile/upload_images',formdata,function(data){
					
					if(data.error){
						alert(data.error);
					}else{
						console.log(data);
						if(data.success){
							//window.location.href = data.from_url;
							//window.location.href = 'addpin_fromfile/steptwo'   
							self.getHtml('addpin_fromfile/steptwo',function(res){
								//DEFINE EMPTY VAR FOR BOARD ID
								var boardId = '';
								
								//$(".header_wrapper").after(res);
								//HIDE LOADER
								hideFancyLoader();
								//CANCEL PIN CREATION
								$("#addPinCancel").live('tap',function(event){
										console.log("OK");
										event.preventDefault();
										$(".addpin").remove();
										$(".pin, ul li.tappable").show();
								});
								//HIDE THE PINS OR BOARDS LIST VIEW TO REDUCE WINDOW LENGTH
								$('.pin, ul li.tappable').hide();
								//APPEND THE HTML RESULT FROM STEP TWO VIEW
								$(".addpin").html(res);
								//create new board
								$("#board-id").change(function(){
									var option = $(this).find($('option:selected'))[0];
									 if($(option).attr('value') === 'cb'){
										$("#board-id").after("<input type='text' id='new-board' name='newboard' class='event-price-textarea' style='width:97% !important'/>");
										
									 }else{
										$("#new-board").remove();
									 }
								});
								
								//WHEN SUBMIT PIN
								$("#addPinSubmit").click(function(event){
									//DEFINE FORM
									var form = $(this).parent();
									//DEFINE TEXTEREA
									var textarea = form.find($('textarea'));
									//VALIDATE IF TEXAREA IS EMPTY
									if(!self.validate('notEmpty',textarea)){
										alert(notEmptyMsg);
										return false;
									}
									//REMOVE THE DIV WITH THE PIC AND FORM
								
									//SHOW THE LOADER
									
									//$("#addPinForm").submit();
									//var url = $("#addPinForm").attr('action');
									//var postData = $("#addPinForm").serialize();
									if($("#new-board")[0]){
									
										var title = $(form).find("input[name='newboard']");							
										if(!self.validate('notEmpty',title)){
											alert(addBoadrMsg);
											return false;
										}
										
										
										
										self.addBoard(title,function(data){
												if(data){
													var boardId = data.data.board_id
													var postData = {
														"board_id" : boardId,
														"media" : $(form).find('input[name="media"]').val(),
														"description" : textarea.val(),
														"from":"Mobile"
														
													}
													addPin(postData);
												}
										});
									}else{
										if(!self.validate('notEmptyOption')){
											alert (chooseBoardMsg);
											return false;
										}		
									
									
									
										addPin(form.serialize());
									}		
									
									showFancyLoader('.addpin');
									$(".pin").remove();
									
									function addPin(pinData){
										
										
										$.ajax({
											url:'pin/createpin',
											dataType:"json",
											type:"post",
											data:pinData,
											success:function(res){
												console.log(res);
												showFancyLoader('.addpin');
												if(res.pin_url){
													
													window.location.href = res.pin_url;
													
												}else if(res.error){
													alert($(res.error).text());
												}							
											},
											error:function(error){
												alert("There was an error :(");
												console.log(error);
											}
										});
									}
								});
							});
						}
					}
				});
			}else{
				alert("sorry your phone does not support this functionality");
			}
		});
		
		
		this.addBoard = function(title,callback){
			$.ajax({
				url:'boards/createboardwithoutcategory',
				dataType:"JSON",
				type:"POST",
				data:title,
				success:function(data){
					callback(data);
				},
				error:function(error){
					alert('Ooops! For some reason we could not create a new board. :(')
				}
			});
		}
		
		this.validate = function(type,element){
			switch(type){
				case 'notEmpty':
					if(element.val() === ''){
						return false;
					}else{
						return true;
					}
				
				case "notEmptyOption":
					
					if($("#board-id option:selected").attr('value') === ''){
						
						return false;
					}else{
						return true;
					}
				break;
			}
		}
	}
	
	//likes

	

	
}