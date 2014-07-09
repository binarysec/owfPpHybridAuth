<script type="text/javascript">
	(function(w) {
		var hybridauth = {};
		w.hybridauth = hybridauth;
		
		var message = function(text, type) {
			if(typeof $.growl != 'undefined') {
				if(typeof type == 'undefined' || !type)
					type = 'info';
				$.growl(text, { type: type });
			}
			else
				alert(text);
		};
		var notLoaded = function() { hybridauth.message("%{@ 'This library is not loaded'}%"); }
		var loading = function() { hybridauth.message("%{@ 'This library is loading, please wait'}%"); }
		
		var redirect = function(api) {
			$(location).attr('href', "%{link '/session/hybridauth'}%?owfha_api="+api);
		};
		
		hybridauth.login = { 'fb': notLoaded, 'gplus': notLoaded };
		
		%{if isset($config["providers"]["Facebook"])}%
		/* * * * * * * * * * * * * * * * * * * *
		 * owfHybridauth
		 * -> Facebook
		 * * * * * * * * * * * * * * * * * * * */
			hybridauth.fb = {};
			(function(fb) {
				hybridauth.login.fb = loading;
				
				var statusChangeCallback = function(response) {
					if(response.status === 'connected')
						redirect('fb');
					else if(response.status === 'not_authorized')
						message("{@ 'Facebook login : please also log into this app.'}");
					else
						message("%{@ 'Facebook login : please log.'}%");
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
						scope: '%{$config["providers"]["Facebook"]["scope"]}%',
						auth_type: 'rerequest'
					});
				}
				
			})(hybridauth.fb);
			
			hybridauth.fb.init(document, 'script', 'facebook-jssdk');
		%{/if}%
		
		%{if isset($config["providers"]["Google"])}%
		/* * * * * * * * * * * * * * * * * * * *
		 * owfHybridauth
		 * -> Google
		 * * * * * * * * * * * * * * * * * * * */
			hybridauth.gplus = {};
			
			(function(gplus) {
				hybridauth.login.gplus = loading;
				
				gplus.onSignInCallback = function(authResult) {
					gapi.client.load('plus','v1', function() {
						if(authResult['access_token']) {
							redirect('gplus');
						}
						else if(authResult['error']) {
							// There was an error, which means the user is not signed in.
							// As an example, you can handle by writing to the console:
							message('Google login : There was an error: ' + authResult['error'])
						}
						console.log('authResult', authResult);
					});
				};
				
				/* Load the Google scripts asynchronously */
				gplus.init = function() {
					var po = document.createElement('script');
					po.type = 'text/javascript'; po.async = true;
					po.src = 'https://plus.google.com/js/client:plusone.js';
					var s = document.getElementsByTagName('script')[0];
					s.parentNode.insertBefore(po, s);
				};
				
			})(hybridauth.gplus);
			
			hybridauth.gplus.init();
		%{/if}%
		w.onSignInCallback = hybridauth.gplus.onSignInCallback;
		
	})(this);
</script>





<!--
<script type="text/javascript">
var helper = (function() {
  var BASE_API_PATH = 'plus/v1/';

  return {
    /**
     * Calls the OAuth2 endpoint to disconnect the app for the user.
     */
    disconnect: function() {
      // Revoke the access token.
      $.ajax({
        type: 'GET',
        url: 'https://accounts.google.com/o/oauth2/revoke?token=' +
            gapi.auth.getToken().access_token,
        async: false,
        contentType: 'application/json',
        dataType: 'jsonp',
        success: function(result) {
          console.log('revoke response: ' + result);
          $('#authOps').hide();
          $('#profile').empty();
          $('#visiblePeople').empty();
          $('#authResult').empty();
          $('#gConnect').show();
        },
        error: function(e) {
          console.log(e);
        }
      });
    },

    /**
     * Gets and renders the list of people visible to this app.
     */
    people: function() {
      var request = gapi.client.plus.people.list({
        'userId': 'me',
        'collection': 'visible'
      });
      request.execute(function(people) {
        $('#visiblePeople').empty();
        $('#visiblePeople').append('Number of people visible to this app: ' +
            people.totalItems + '<br/>');
        for (var personIndex in people.items) {
          person = people.items[personIndex];
          $('#visiblePeople').append('<img src="' + person.image.url + '">');
        }
      });
    },

    /**
     * Gets and renders the currently signed in user's profile data.
     */
    profile: function(){
      var request = gapi.client.plus.people.get( {'userId' : 'me'} );
      request.execute( function(profile) {
        $('#profile').empty();
        if (profile.error) {
          $('#profile').append(profile.error);
          return;
        }
        $('#profile').append(
            $('<p><img src=\"' + profile.image.url + '\"></p>'));
        $('#profile').append(
            $('<p>Hello ' + profile.displayName + '!<br />Tagline: ' +
            profile.tagline + '<br />About: ' + profile.aboutMe + '</p>'));
        if (profile.cover && profile.coverPhoto) {
          $('#profile').append(
              $('<p><img src=\"' + profile.cover.coverPhoto.url + '\"></p>'));
        }
      });
    }
  };
})();
-->
