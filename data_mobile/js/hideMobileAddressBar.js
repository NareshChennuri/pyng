/* 
 * 
 *  This is how we hide the mobile address bar
 *  
 *  
   */
  
    function hideAddressBar(){
        if(!window.location.hash){
            if(document.height < window.outerHeight){
                document.body.style.height = (window.outerHeight + 50) + 'px';
            }
            
            setTimeout(function(){
                window.scrollTo(0,1);
            },50);
        }
    }
    
    window.addEventListener('load',function(){
        if(!window.pageYOffset){
            hideAddressBar();
        }
    })
    
    
    
    document.addEventListener('orientationchange',function(){
    	$("#header").css('100%');
    })
    
   $(document).ready(function(){
    /*   if(navigator.userAgent.match(/iPhone/i)){
          var header = document.getElementById('header');
          header.style.position = 'fixed';
          header.style.width = window.innerWidth+'px';
          header.style.padding = '0px';
          
          console.log(header.style);
       }
    */
   });

