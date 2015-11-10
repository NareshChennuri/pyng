function Facebook(appId) {
	"use strict";
	this.user = {};
	this.status = '';
	this.authResponse = {};
	this.appId = appId;
	this.init();
};

Facebook.prototype = {
	init : function() {
		FB.init({
		    appId: this.appId,
		    status: true,
		    cookie: true,
		    xfbml: true
		});
	}/*,
	_fetchUserData : function(user_id) {
		var user_id = user_id || "me";
		FB.getLoginStatus(function(response) {
			if(response.status == 'connected') {
				FB.api('me/',function(user_response){
					Pins.fbApi.user = user_response;
				});
			}
		});
	},
	getUser : function(user_id) {
		for(i in FB) {
			console.log(i,FB[i])
		}
		$.when(this._fetchUserData(user_id)).then(function(){
			console.log(Pins.fbApi.user, this.user);
			return this.user;
		});
	}*/
}

Pins.fbApi = new Facebook(pintastic_config.facebook_app_id);

/*Facebook.prototype.isLoged = function() {
	
};

Facebook.prototype.checkStatusUpdate = function() {
	FB.getLoginStatus(function(response) {
		FB.api({
			method: 'users.hasAppPermission',
			ext_perm: 'publish_stream'
			}, function(response) {
				console.log('Do we really have permission ?', response);
		});
	});
};

fb = new Facebook(pintastic_config.facebook_app_id);
fb.checkStatusUpdate();*/

/*Facebook = function(appId) {
	this.user = {};
	this.appId = appId;
	this.init = function() {
		FB.init({
		    appId: this.appId,
		    status: true,
		    cookie: true,
		    xfbml: true
		});
		return this;
	};
	this.prototype.checkStatusUpdate = function() {
		a = FB.api({
				method: 'users.hasAppPermission',
				ext_perm: 'publish_stream'
				}, function(response) {
					console.log('Do we really have permission ?', response);
				});
		console.log(a)
	}
	
}

fb = new Facebook(pintastic_config.facebook_app_id).init();
console.log(fb)
fb.checkStatusUpdate();*/




/*FB.api('me/',function(a){
	console.log(a)
});*/

//FB.getLoginStatus(function(response) {
	
	/*FB.api('me/',function(a){
		console.log(a)
	});
	  if (response.status === 'connected') {
	    // the user is logged in and has authenticated your
	    // app, and response.authResponse supplies
	    // the user's ID, a valid access token, a signed
	    // request, and the time the access token 
	    // and signed request each expire
	    var uid = response.authResponse.userID;
	    var accessToken = response.authResponse.accessToken;
	  } else if (response.status === 'not_authorized') {
	    // the user is logged in to Facebook, 
	    // but has not authenticated your app
	  } else {
	    // the user isn't logged in to Facebook.
	  }*/

	/*FB.api(
			  {
			    method: 'users.hasAppPermission',
			    ext_perm: 'publish_stream'
			  },
			  function(response) {
				  console.log('Do we really have permission ?', response);
			  }
			);*/
	
	
	
/*		  var cb = function(response) {
			  
			  FB.api(
					  {
					    method: 'users.hasAppPermission',
					    ext_perm: 'publish_stream'
					  },
					  function(response) {
						  console.log('Do we really have permission ?', response);
					  }
					);
			  
			  console.log('FB.login callback', response);
		    if (response.session) {
		    	console.log('User logged in');
		      if (response.perms) {
		    	  console.log('User granted permissions');
		      }
		    } else {
		    	console.log('User is logged out');
		    }
		  };
		  FB.login(cb, { scope: 'status_update', display: 'iframe' });
		
		  
});*/