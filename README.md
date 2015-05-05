# Nether Avenue

[![nether.io](https://img.shields.io/badge/nether-avenue-C661D2.svg)](http://nether.io/) [![Code Climate](https://codeclimate.com/github/netherphp/avenue/badges/gpa.svg)](https://codeclimate.com/github/netherphp/avenue) [![Build Status](https://travis-ci.org/netherphp/avenue.svg)](https://travis-ci.org/netherphp/avenue)  [![Packagist](https://img.shields.io/packagist/v/netherphp/avenue.svg)](https://packagist.org/packages/netherphp/avenue) [![Packagist](https://img.shields.io/packagist/dt/netherphp/avenue.svg)](https://packagist.org/packages/netherphp/avenue)

A Request Router. Again. A simple [in my opinion] router. I've written about 10 of these now. But this one... this is the one. I have designed this one with everything that matters in mind: single domain support, multi domain support, argument capture, all sorts of fun stuff.

First you have to setup your webserver so that all requests get pumped to your www/index.php. This can be done multiple ways, and should be familiar to you if you have done web development before. Once you have everything piped to index.php, your file can be as simple as this...

	<?php $router = (new Nether\Avenue\Router)
	->AddRoute('{@}//index','Routes\Home::Index')
	->AddRoute('{@}//about','Routes\Home::About')
	->AddRoute('{@}//member,'Routes\MemberList::ViewList')
	->AddRoute('{@}//member/(#)','Routes\MemberList::ViewByID')
	->Run();

## Route Conditions
Route conditions are straight regular expressions at the end of the day, however I have provided several shortcuts to make writing the routes easier. You can also define your own shortcuts if you find yourself doing something frequently.

They are defined with the format domain//path, and the double slash is important. Domains are tested separately from the path that way you can have one route class that is able to handle the same request, but serve it differently based on the domain if you so choose.

If desired, you can also specify if certain GET variables exist. domain//path??var1&var2&var3 will only match if all 3 of those GETs existed. More may exist, that is fine. Because of this, you must list more specific routes before more generic routes. domain//path and domain//path??var will both match the request, but only the last one will demand that specific GET variable. *Typically you want to avoid having routing depending on Query Variables. They exist because they are suppose to describe the request, not define it - but you have the power so do what you want with it.*

#### Conditions and Shortcut Types

There are two types of shortcuts - slotted and unslotted. Slotted (similar to default preg) is surrounded by parens and the value within them will be passed to the route that will be executed. Unslotted is surrounded by braces and that data will not be passed.

* (@) - slotted - whatever we found will be passed to the routing method.
* {@} - unslotted - whatever we found will not be passed to the routing method.

You can have several slotted or unslotted shortcuts in a route condition. All slotted shortcuts (and slotted regex) will be passed to the routing method in the order they were given.

#### Available Shortcuts
There are several shortcuts for matching data we need to reference out of URLs often.

* @ = match anything, as long as there is something to match.
* ? = match anything, even if there is nothing to match.
* # = match a number (or series of)
* $ = match a string as a path fragment. anything between forward slashes, not including them.
* domain = match a string that is a domain name. it will match the full domain like "www.nether.io", however, it will only pass the relevant domain to the route e.g. without subdomains, in this case "nether.io".

Route shortcuts MUST be surrounded by either () or {}.

Additionally, you can go hardmode with straight on Perl Regex just like you were dumping it into preg_match(). Or you can mix and match straight regex with my shortcuts.

#### Example Route Conditions

	Matches for the homepage request on any domain.
		* {@}//index
		* domain.tld/ => Route::Method();

	Matches the homepage request on any domain.
	Straight Perl instead of shortcuts.
		* .+?//index
		* domain.tld/ => Route::Method();

	Matches the homepage request on any domain.
	DEMANDS that the GET variables omg, wtf, and bbq also exist.
		* {@}//index??omg&wtf&bbq

	Matches for the homepage request on any domain.
	Passes the domain to the routing method.
		* (@)//index
		* domain.tld/ => Route::Method($domain);

	Matches for the homepage request on any domain.
	Straight Perl instead of shortcuts.
	Passes the domain to the routing method.
		* (.+?)//index
		* domain.tld/ => Route::Method($domain);

	Matches for the homepage on a beta domain.
		* beta.{@}//index
		* beta.domain.tld/ => Route::Method();

	Matches for any page on any domain.
	Passes everything after the domain to the routing method as one long string argument.
		* {@}//(@)
		* domain.tld/slender/man/needs/you => Route::Method($path);

	Matches for a two path part request.
	Passes both parts to the routing method as two separate string arguments.
		* {@}//($)/($)
		* domain.tld/user/create => Route::Method($namespace,$action);

	Matches a members path with an integer.
	Passes the integer to the routing method.
		* {@}//members/(#)
		* domain.tld/members/42 => Route::Method($id);

	Matches a members path with an integer.
	Straight Perl instead of shortcuts.
	Passes the integer to the routing method.
		* .+?//members/(\d+)
		* domain.tld/members/42 => Route::Method($id);

#### Class Acceptance

This allows you to have a class make the final call about if it will handle a route or not. Imagine you have a website that has a list of cars, trucks, and motorcycles, and on this site we will use the first segment of the URL Path to determine what vehicle we want to show. We can set routes like this:

	$router
	->AddRoute('{@}//($)','Routes\Profile\Car::Home')
	->AddRoute('{@}//($)','Routes\Profile\Truck::Home')
	->AddRoute('{@}//($)','Routes\Profile\Motorcycle::Home');

All three of these would match, but no matter what only the first route referncing the Car class would be used, causing problems for viewing your trucks and motorcycles. With this style of archetecture we can add a new static method to our classes to determine if we will accept the request or not.

	class Car {
		static function WillHandleRequest($router,$handler) {
			$vehicle = App\Vehicle::GetByAlias($handler->GetArgv()[0]);
			if(!$vehicle || $vehicle->Type !== 'car') return false;

			return true;
		}

		public function Home() {
			// ...
		}
	}

The static method **WillHandleRequest** accepts two arguments, the Nether\Avenue\Router that was run to process the request, and the Nether\Avenue\RouteHandler that the Router created to define the possible route. You could typehint them if you so choose to. This method should return boolean true or false, true if the class decides it will accept the route, false if not.

## Installing
Require this package in your composer.json.

	require {
		"netherphp/avenue": "~1.0.0"
	}

Then install it or update into it.

	$ composer install --no-dev
	$ composer update --no-dev


## Testing
This library uses Codeception for testing. Composer will handle it for you. Install or Update into it.

	$ composer install --dev
	$ composer update --dev

Then run the tests.

	$ php vendor/bin/codecept run unit
	$ vendor\bin\codecept run unit


