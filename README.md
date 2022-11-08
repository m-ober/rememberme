# Secure "Remember Me"

This library implements the best practices for implementing a secure
"Remember Me" functionality on web sites. Login information and unique secure 
tokens are stored in a cookie. If the user visits the site, the login information 
from the cookie is compared to information stored on the server. If the tokens 
match, the user is logged in. A user can have login cookies on several 
computers/browsers.

## Upgrading to Version 5.x

Starting from v5.0.0, this library uses `declare(strict_types=1);`, is only compatible
with PHP 8.0 and above and comes with changed default values:

* SHA-2 (256) is used instead of SHA-1
* The token is encoded using URL safe base64 (instead of hex)
* The token separator is now URL safe (`credential` may now contain the token separator)
* The token length was increased from 16 to 32 bytes

While the hash algorithm, token length and token encoding can be configured, the
separator is not configurable. The new default values increase the security while
decreasing the cookie size. **Unfortunately, this makes v5.0.0+ backwards incompatible
with older versions.** You should clear the token storage, because all old tokens
will be detected as invalid.

### New option: Configure Token Rotation

The `Authenticator` class has a new setter `setTokenRotationEnabled()` (default: `true`).
As the name implies, this will enable or disable the token rotation.
This was implemented because of issues with the token rotation:

* Concurrent requests will submit the same Triplet, but only one will be accepted.
The other request will trigger the manipulation detection, because it has the correct *permanent
token* but an invalid *one time token*.
* If page loading is canceled either by the user or due to a network problem, the browser may not
receive the new Triplet. On the next visit, it tries to submit the old cookie, again with a correct
permanent but invalid one time token, and trigger the manipulation detection.

This may lead to users being seemingly "randomly logged out" on all devices. With this option you can
decide whether you want maximum security or a better user experience. Note that users could
also start ignoring *"Your cookie was stolen"* warnings if there are (too many) false positives.

## Installation

For PHP 8.0 or newer, version 5+ will be installed. For PHP 7.4 and older
the no longer maintained version 4 will be installed.

    composer require mober/rememberme

## Usage example
See the `example` directory for an example. You can run it on your local machine with the command

    php -S 127.0.0.1:8085 -t example

To understand the basic application structure, have a look at `index.php` and the
`user_is_looged_in.php` template.

The example uses the file system to store the tokens on the server side. In most
cases it's better to swap the storage with the `PDOStorage` class.

## Cookie configuration
By default the cookie is valid for one week and for all paths in the domain it was set. 
It cannot be accessed/changed via JavaScript and will be transmitted on HTTP connections.
If your application requires a different configuration (for example, if you are using 
HTTPS and want to enhance security by only allowing transmission of the cookie over
the secure connection), you can create your own PHPCookie instance:

```php
$expire = strtotime("1 week", 0);
$cookie = new PHPCookie("REMEMBERME", $expire, "/", "", true, true);
$auth = new Authenticator($storage, null, $cookie);
```

## Token security
This library uses the [`random_bytes`][2] function by default to generate a 16-byte token 
(a 32 char hexadecimal string). That should be sufficiently secure for most applications.

If you need more security, instantiate the `Authenticator` class with a custom token generator.
The following example generates Base64-encoded tokens with 128 characters:
 
 ```php
 $tokenGenerator = new DefaultToken(94, DefaultToken::FORMAT_BASE64);
 $auth = new Authenticator($storage, $tokenGenerator);
 ```
 
If you like even more control over the generation of your random tokens, 
have a look at the [RandomLib][3]. Rememberme has a `RandomLibToken` class that can use it.

## Cleaning up expired tokens
The best way to clean expired tokens from your storage (file system or database) is to write a small script that initializes your token storage class and calls its `cleanExpiredTokens` method.
Run this script regularly with a cron job or other worker method.

If you can't run the cleanup script regularly and have a low-traffic site, you can clean the
storage on every page call by initializing the Authenticator class like this:
 
```php
 $auth = new Authenticator($storage);
 $auth->setCleanExpiredTokensOnLogin(true);
 ```

## How it works

This library is heavily inspired by Barry Jaspan's article
"[Improved Persistent Login Cookie Best Practice][1]". The library protects
against the following attack scenarios:

- The computer of a user is stolen or compromised, enabling the attacker to log
  in with the existing "Remember Me" cookie. The user knows this has happened.
  The user can remotely invalidate all login cookies.
- An attacker has obtained the "Remember Me" cookie and has logged in with it.
  The user does not know this. The next time he tries to log in with the cookie
  that was stolen, he gets a warning and all login cookies are invalidated.
- An attacker has obtained the database of login tokens from the server. The
  stored tokens are hashed so he can't use them without computational effort
  (rainbow tables or brute force).
- An attacker tries to log in with brute force, by systematically generating
  "Remember Me" cookies. With the default security settings and 100 tries per
  second (a very high number which would probably show up in the server logs), it
  would take 8 months for a 50% chance to guess a cookie value right.

 
[1]: https://web.archive.org/web/20170810033354/http://jaspan.com/improved_persistent_login_cookie_best_practice
[2]: http://php.net/manual/en/function.random-bytes.php
[3]: https://github.com/ircmaxell/RandomLib
