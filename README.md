# owfHybridauth

This is a module of the Open Web Framework.

It is a ported project from the hybridauth.

You can find it at : https://github.com/hybridauth/hybridauth.

See https://github.com/binarysec/owfCore for OpenWF.

# Integration

## This is the hybridauth section which should be added to your .ini file
```ini
[hybridauth]
Facebook_id = 523....
Facebook_secret = 35cb3....
Google_id = ...
Google_secret = ...
....
```

## API provided with this module :
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
$tpl->fetch("myProject/login");
```

## How to, inside a template file :
```html
%{if isset($hybridauth['tpl'])}%
	%{$hybridauth['tpl']}%
%{/if}%
<!-- my login form here -->
%{if isset($hybridauth['config']['providers']['Facebook'])}%
<div id="fb-root"></div>
<button type="button" class="btn btn-primary btn-sm" onclick="hybridauth.login.fb();">
	<img src='https://fbstatic-a.akamaihd.net/rsrc.php/v2/yR/r/teE39sffXW8.png' alt='Facebook' />
	%{@ "Login with Facebook"}%
</button>
%{/if}%
```

## Javascript API provided :
```js
hybridauth.login.fb();
```
