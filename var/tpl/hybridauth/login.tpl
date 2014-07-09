<script type="text/javascript">
	%{if isset($config["providers"]["Facebook"])}%
		function statusChangeCallback(response) {
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
		
		function checkLoginState() {
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
		};
		
		/* Load the Facebook SDK asynchronously */
		(function(d, s, id) {
			var js, fjs = d.getElementsByTagName(s)[0];
			if (d.getElementById(id)) return;
			js = d.createElement(s); js.id = id;
			js.src = "//connect.facebook.net/en_US/sdk.js";
			fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));
	%{/if}%
</script>
