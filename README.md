# Germania · RedirectUnauthorized

**This package is distilled from legacy code. You certainly do not want this in your production code.**

This PSR-style Middleware checks if a Response object status code is *401 Unauthorized* and stores the current *Request URI* in a *Aura.Session Segment*. If the user is authenticated (i.e. login successful, *204 No Content*), he will be redirected to this very URI as his start URL. The redirect status code is 301.

#### In particular

1. When the Middleware is executed: If Response object is *401 Unauthorized*, store Request URI in session and redirect to login URL.

2. Call next middleware (or Page controller). A Login controller should on success set the Response object to *204 No Content*.

3. After route, check if Response is *204 No Content* and redirect to the URI stored in session.



## Installation

```bash
$ composer require germania-kg/redirect-unauthorized
```



## Customization

The Response status codes needed are *401* or *204* per default. Create your own extension to use other codes:

```php

class MyRedirector exends Germania\RedirectUnauthorized\Middleware
{
    /**
     * HTTP Status Code for Redirection
     * @var string
     */
    public $redirect_status_code  = 301;
    
    /**
     * HTTP Status Code for "Unauthorized". Usually 401.
     * @var string
     */
    public $auth_required_status_code  = 401;

    /**
     * HTTP Status Code for Responses after successful login. Usually 204.
     * @var string
     */
    public $authorized_status_code = 204;
}
```


## Usage

```php
<?php
use Germania\RedirectUnauthorized\Middleware;

// Aura.Session Segment
$session_factory = new \Aura\Session\SessionFactory;
$session = $session_factory->newInstance($_COOKIE);
$segment = $session->getSegment('Vendor\Package\ClassName');

// Where to redirect unauthorized requests to
$login_url = "/login.html"

// Optional: PSR-3 Logger
$logger = new Monolog

$middleware = new Middleware( $segment, $login_url);
$middleware = new Middleware( $segment, $login_url, $logger);

```


## Slim 3 Example

```php
<?php
use Germania\RedirectUnauthorized\Middleware;

$app = new Slim\App;
$app->add( new Middleware ); 
```

## Development and Testing

Develop using `develop` branch, using [Git Flow](https://github.com/nvie/gitflow).   
**Currently, no tests are specified.**

```bash
$ git clone git@github.com:GermaniaKG/RedirectUnauthorized redirect-unauthorized
$ cd redirect-unauthorized
$ cp phpunit.xml.dist phpunit.xml
$ vendor/bin/phpunit
```
