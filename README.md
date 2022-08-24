# Nether Avenue

[![Packagist](https://img.shields.io/packagist/v/netherphp/avenue.svg)](https://packagist.org/packages/netherphp/avenue) [![Packagist](https://img.shields.io/packagist/dt/netherphp/avenue.svg)](https://packagist.org/packages/netherphp/avenue) [![Build Status](https://travis-ci.org/netherphp/avenue.svg?branch=master)](https://travis-ci.org/netherphp/avenue) [![codecov](https://codecov.io/gh/netherphp/avenue/branch/redux/graph/badge.svg?token=6OLA0S797J)](https://codecov.io/gh/netherphp/avenue)

A PHP 8+ annotation based request router. It is able to scan through a directory
of routes/controllers and determine which ones should get executed to satisfy
incoming request.

Routes are able to define via the `RouteHandler` attribute, the path, domain,
and HTTP verb they are willing to answer. They can also extract information
from the URI and pass them to the method. It will make sure the data is of the
types declared on the method signature.

Routes are *checked if they can* handle a request based on the request
parameters (Verb, Domain, and Path). Then routes are *asked if they will*
handle a request. A method just for checking the willingness of a route
handler can be defined with the `ConfirmWillAnswerRequest` attribute.

*Example Router (www\index.php)*
```php
require('vendor/autoload.php');

$Config = new Nether\Object\Datastore;
Nether\Avenue\Library::Init($Config);

$Router = new Nether\Avenue\Router($Config);
$Router->Run();
```

*Example Route (routes\Home.php)*
```php
namespace Routes;

class Home
extends Nether\Avenue\Route {

	#[Nether\Avenue\Meta\RouteHandler('/index')]
	public function
	Index():
	void {

		echo 'Home Page';
		return;
	}

	#[Nether\Avenue\Meta\RouteHandler('/dashboard')]
	#[Nether\Avenue\Meta\ConfirmWillAnswerRequest]
	public function
	Dashboard():
	void {

		echo 'User Dashboard';
		return;
	}

	public function
	DashboardWillAnswerRequest():
	int {

		// allow the user to visit this page.
		if(YourAppSessionLib::GetCurrentUser())
		return Nether\Avenue\Response::CodeOK;

		return Nether\Avenue\Response::CodeForbidden;
	}

}

```


# Quickstart

How-to on getting the library up and running on a fresh project.

* https://github.com/netherphp/avenue/wiki/Quickstart


