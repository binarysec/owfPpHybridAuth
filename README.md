# owfHybridauth

This is a module of the Open Web Framework.

It is a ported project from the hybridauth.

You can find it at : https://github.com/hybridauth/hybridauth.

See https://github.com/binarysec/owfCore for OpenWF.

It requires the session module to work. See https://github.com/binarysec/owfSession.

## Support

It currently only support Facebook and Google login. (07/09/2014)

# Integration

## In the code

### Your .ini file
```ini
[hybridauth]
Facebook_id = 523....
Facebook_secret = 35cb3....
Google_id = ...
Google_secret = ...
....
```
We will see later how to get credentials : https://github.com/Lazarus974/owfHybridauth#how-to-get-credentials

### API provided with this module :
```php
/* get the main aggregator */
$hybrid = $this->wf->hybridauth();

/* configuration location */
$config = $hybrid->config;

/* get the javascript running login buttons */
$js = hybrid->get_login_tpl();

/* make a template and add this */
$tpl = new core_tpl($this->wf);
$tpl->set("hybridauth", array('tpl' => $js, 'config' => $config));
echo $tpl->fetch("myProject/login");
```

### How to, inside a template file :
```html
%{if isset($hybridauth['tpl'])}%
	%{$hybridauth['tpl']}%
%{/if}%

<form><!-- my login form here --></form>

%{if isset($hybridauth['config']['providers']['Facebook'])}%
<div id="fb-root"></div>
<button type="button" class="btn btn-primary btn-sm" onclick="hybridauth.login.fb();">
	<img src='https://fbstatic-a.akamaihd.net/rsrc.php/v2/yR/r/teE39sffXW8.png' alt='Facebook' />
	%{@ "Login with Facebook"}%
</button>
%{/if}%

%{if isset($hybridauth['config']['providers']['Google'])}%
	<button class="g-signin"
	data-scope="https://www.googleapis.com/auth/plus.login"
	data-requestvisibleactions="http://schemas.google.com/AddActivity"
	data-clientId="%{$hybridauth['config']['providers']['Google']['keys']['id']}%"
	data-callback="onSignInCallback"
	data-theme="dark"
	data-cookiepolicy="single_host_origin">
%{/if}%
```

### Javascript API provided :
```js
hybridauth.login.fb();
```

## How to get credentials

### Facebook
See http://hybridauth.sourceforge.net/userguide/IDProvider_info_Facebook.html

### Google
See first step of this link : https://developers.google.com/+/quickstart/javascript

Then don't forget to add APIs "Contacts API" and "Google+ API" in your project.

More help : http://hybridauth.sourceforge.net/userguide/IDProvider_info_Google.html
