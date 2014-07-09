<script type="text/javascript">
	(function(w) {
		var hybridauth = {};
		w.hybridauth = hybridauth;
		
		var message = function(text) {
			if(typeof $.growl != 'undefined')
				$.growl(text);
			else
				alert(text);
		};
		var notLoaded = function() { hybridauth.message("Library not loaded"); }
		var loading = function() { hybridauth.message("Library is loading, please wait"); }
		
		hybridauth.login = { 'fb': notLoaded };
		
		%{if isset($config["providers"]["Facebook"])}%
			hybridauth.fb = {};
			(function(fb) {
				hybridauth.login.fb = loading;
				
				var statusChangeCallback = function(response) {
					if(response.status === 'connected') {
						$(location).attr('href', "%{link '/session/hybridauth'}%");
					}
					else if(response.status === 'not_authorized') {
						$.growl('Please log into this app.', {type: 'warning'});
					}
					else {
						$.growl('Please log into Facebook.', {type: 'warning'});
					}
				}
				
				var checkLoginState = function() {
					FB.getLoginStatus(function(response) {
						statusChangeCallback(response);
					});
				}
				
				window.fbAsyncInit = function() {
					FB.init({
						appId      : '%{$config["providers"]["Facebook"]["keys"]["id"]}%',
						cookie     : true,
						xfbml      : true,
						version    : 'v2.0'
					});
					
					hybridauth.login.fb = fb.login;
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
						scope: 'public_profile,email',
						auth_type: 'rerequest'
					});
				}
				
			})(hybridauth.fb);
			
			hybridauth.fb.init(document, 'script', 'facebook-jssdk');
		%{/if}%
		
	})(this);
</script>
