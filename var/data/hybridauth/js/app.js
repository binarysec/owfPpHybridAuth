(function(w) {
	/* register object for global access */
	var hybridauth = {};
	w.hybridauth = hybridauth;
	
	var root = null, config = null, traductions = {};
	var linker = function(link) {
		return root + link;
	};
	var ts = function(text) {
		return traductions[text] ? traductions[text] : text;
	};
	
	/* private functions */
	var message = function(text, type) {
		if(typeof $.growl != 'undefined') {
			if(typeof type == 'undefined' || !type)
				type = 'info';
			$.growl(text, { type: type });
		}
		else
			alert(text);
	};
	var notLoaded = function() { hybridauth.message(ts("This library is not loaded")); }
	var loading = function() { hybridauth.message(ts("This library is loading, please wait")); }
	
	var redirect = function(api) {
		$(location).attr('href', linker("/session/hybridauth?owfha_api="+api));
	};
	
	hybridauth.login = {
		'fb': notLoaded
	};
	
	/* constructor */
	hybridauth.init = function(config, rootUrl, traductions) {
		config = config;
		root = rootUrl;
		traductions = traductions;
		
		/* Facebook */
		if(config.providers.Facebook && !config.providers.Facebook.noscript) {
			hybridauth.fb = {};
			(function(fb) {
				hybridauth.login.fb = loading;
				
				var statusChangeCallback = function(response) {
					if(response.status === 'connected')
						redirect('fb');
					else if(response.status === 'not_authorized')
						message(ts("Facebook login : please also log into this app."));
					else
						message(ts("Facebook login : please log."));
				}
				
				var checkStatus = function() {
					FB.getLoginStatus(function(response) {
						if(response.status === 'connected') {
							FB.api("/me/friends", function (response) {
								if(response && !response.error) {
									//console.debug(response);
								}
							});
						}
						else if(response.status === 'not_authorized') {} else {}
					});
				};
								
				window.fbAsyncInit = function() {
					FB.init({
						appId      : config.providers.Facebook.keys.id,
						cookie     : true,
						xfbml      : true,
						version    : 'v2.0'
					});
					
					hybridauth.login.fb = fb.login;
					checkStatus();
				};
				
				/* Load the Facebook SDK asynchronously */
				fb.init = function(d, s, id) {
					var js, fjs = d.getElementsByTagName(s)[0];
					if (d.getElementById(id)) return;
					js = d.createElement(s); js.id = id;
					js.src = "//connect.facebook.net/en_US/sdk.js";
					fjs.parentNode.insertBefore(js, fjs);
				};
				
				fb.login = function() {
					FB.login(statusChangeCallback, {
						scope: config.providers.Facebook.scope,
						auth_type: 'rerequest'
					});
				}
				
			})(hybridauth.fb);
		}
		
		/* Google */
		//if(config.providers.Google && !config.providers.Google.noscript) {
			//hybridauth.gplus = {};
			
			//(function(gplus) {
				//hybridauth.login.gplus = loading;
				
				//gplus.onSignInCallback = function(authResult) {
					//gapi.client.load('plus','v1', function() {
						//if(authResult['access_token']) {
							//redirect('gplus');
						//}
						//else if(authResult['error']) {
							//if(authResult['error_subtype'] == "origin_mismatch") {
								//message(ts("Google login error : wrong origin URL"), 'warning')
							//}
							//else
								//message(ts("Google login : There was an error: ") + authResult['error'], 'danger')
						//}
						//console.log('authResult', authResult);
					//});
				//};
				
				///* Load the Google scripts asynchronously */
				//gplus.init = function() {
					//var po = document.createElement('script');
					//po.type = 'text/javascript'; po.async = true;
					//po.src = 'https://plus.google.com/js/client:plusone.js';
					//var s = document.getElementsByTagName('script')[0];
					//s.parentNode.insertBefore(po, s);
				//};
				
			//})(hybridauth.gplus);
		//}
		
		/* LinkedIn */
		//if(config.providers.LinkedIn && !config.providers.LinkedIn.noscript) {
			//hybridauth.li = {};
			
			//(function(li) {
				///* Load the LinkedIn scripts asynchronously */
				//li.init = function() {
					//$.getScript("http://platform.linkedin.com/in.js?async=true", function success() {
						//IN.init({
							//onLoad: "onLinkedInLoad",
							//api_key: config.providers.LinkedIn.keys.id
						//});
					//});
				//};
				//li.onLoad = function() {
					////IN.Event.onOnce(IN, "auth", function() {
						////redirect('li');
					////}, callbackScope, extraData)
					//IN.Event.onOnce(IN, "auth", function() {
						//redirect('li');
					//});
				//}
				
			//})(hybridauth.li);
		//}
		
		$(document).ready(function() {
			if(config.providers.Facebook && !config.providers.Facebook.noscript) {
				hybridauth.fb.init(document, 'script', 'facebook-jssdk');
			}
			//if(config.providers.Google && !config.providers.Google.noscript) {
				//hybridauth.gplus.init();
				//onSignInCallback = hybridauth.gplus.onSignInCallback;
			//}
			//if(config.providers.LinkedIn && !config.providers.LinkedIn.noscript) {
				//hybridauth.li.init();
				//onLinkedInLoad = hybridauth.li.onLoad;
			//}
		});
	}
})(this);
