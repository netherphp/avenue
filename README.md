# Nether Avenue

[![Packagist](https://img.shields.io/packagist/v/netherphp/avenue.svg)](https://packagist.org/packages/netherphp/avenue) [![Packagist](https://img.shields.io/packagist/dt/netherphp/avenue.svg)](https://packagist.org/packages/netherphp/avenue) [![Build Status](https://travis-ci.org/netherphp/avenue.svg?branch=master)](https://travis-ci.org/netherphp/avenue) [![codecov](https://codecov.io/gh/netherphp/avenue/branch/redux/graph/badge.svg?token=6OLA0S797J)](https://codecov.io/gh/netherphp/avenue)

A PHP 8+ annotation based request router. It is able to scan through a directory of routes/controllers and determine which ones should get executed to satisfy incoming request.

Routes are able to define via the `RouteHandler` attribute, the path, domain, and HTTP verb they are willing to answer. They can also extract information from the URI and pass them to the method. It will make sure the data is of the types declared on the method signature.

Routes are *checked if they can* handle a request based on the request parameters (Verb, Domain, and Path). Then routes are *asked if they will* handle a request. A method just for checking the willingness of a route handler can be defined with the `ConfirmWillAnswerRequest` attribute.

The Router is able to both scan a directory on the fly to generate the route map (nice for development), as well as there is a vendor bin script, `netherave`, which is able to generate a static route map file that the Router will load to speed things up.



# Quickstart

How-To on getting the library up and running on a fresh project.

* https://github.com/netherphp/avenue/wiki/Quickstart



# Documentation

All documentation that exists is currently on the GitHub wiki.

* https://github.com/netherphp/avenue/wiki


---


*Example Router (www\index.php)*
```php
require('vendor/autoload.php');

$Config = Nether\Avenue\Library::PrepareDefaultConfig();
$Router = new Nether\Avenue\Router($Config);
$Router->Run();
```

*Example Route (routes\Home.php)*
```php
namespace Routes;

use Nether\Avenue\Route;
use Nether\Avenue\Response;
use Nether\Avenue\Meta\RouteHandler;
use Nether\Avenue\Meta\ErrorHandler;
use Nether\Avenue\Meta\ConfirmWillAnswerRequest;

class Home
extends Nether\Avenue\Route {

	#[RouteHandler('/index')]
	public function
	Index():
	void {

		echo 'Home Page.';
		return;
	}

	#[RouteHandler('/dashboard')]
	#[ConfirmWillAnswerRequest]
	public function
	Dashboard():
	void {

		echo 'User Dashboard.';
		return;
	}

	public function
	DashboardWillAnswerRequest():
	int {

		$User = YourAppSessionLib::GetCurrentUser();

		if($User && $User->CanHasDashboard())
		return Response::CodeOK;

		return Response::CodeForbidden;
	}

	#[ErrorHandler(403)]
	public function
	HandleForbidden():
	void {

		echo "Dude No.";
		return;
	}

	#[ErrorHandler(404)]
	public function
	HandleNotFound():
	void {

		echo "Bruh Wut?";
		return;
	}


}

```

*Example Static Map Generation (CLI)*
```cli
$ netherave gen routes --show
Route Directory: routes
Route File: ./routes.phson

Summary:
 * GET (2)
   Routes\Home::Index
   Routes\Home::Dashboard

 * Error Handlers (2)
   Routes\Home::HandleForbidden
   Routes\Home::HandleNotFound
```



# Notes

This is not a PSR compliant anything. It do as it be.
