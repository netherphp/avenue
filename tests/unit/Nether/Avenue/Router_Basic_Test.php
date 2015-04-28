<?php

namespace Nether\Avenue;

use \Nether;
use \Codeception\Verify;


class Router_Basic_Test extends \Codeception\TestCase\Test {

	static $RequestData = [
		'Root' => [ 'Domain'=>'www.nether.io', 'Path'=>'/' ],
		'Index' => [ 'Domain'=>'www.nether.io', 'Path'=>'/index' ],
		'IndexTs' => [ 'Domain'=>'www.nether.io', 'Path'=>'/index/' ],
		'Test' => [ 'Domain'=>'www.nether.io', 'Path'=>'/test' ],
		'TestTs' => [ 'Domain'=>'www.nether.io', 'Path'=>'/test/' ],
		'TestQuery' => [ 'Domain'=>'www.nether.io', 'Path'=>'/test?omg=true' ],
		'TestQueryTs' => [ 'Domain'=>'www.nether.io', 'Path'=>'/test/?omg=true' ],
		'TestInt' => [ 'Domain'=>'www.nether.io', 'Path'=>'/test/{#}' ],
		'TestIntTs' => [ 'Domain'=>'www.nether.io', 'Path'=>'/test/{#}/' ],
	];

	public function testRequestParsingFromGlobals() {
	/*//
	testing all the primary features of the router in a method similiar to a
	web request from apache.
	//*/

		$_SERVER['HTTP_HOST'] = static::$RequestData['TestQuery']['Domain'];
		$_SERVER['REQUEST_URI'] = static::$RequestData['TestQuery']['Path'];
		$_GET['omg'] = 'true';

		$router = new Nether\Avenue\Router;

		(new Verify(
			'check GetFullDomain() returns full original HTTP_HOST',
			$router->GetFullDomain()
		))->equals('www.nether.io');

		(new Verify(
			'check GetDomain() returns relevent domain.tld only from HTTP_HOST',
			$router->GetDomain()
		))->equals('nether.io');

		(new Verify(
			'check GetPath() returns REQUEST_URI string without query.',
			$router->GetPath()
		))->equals('/test');

		(new Verify(
			'check that GetPathArray() returns the path array that contains one element.',
			(is_array($router->GetPathArray()) && count($router->GetPathArray()) === 1)
		))->true();

		(new Verify(
			'check that GetPathArray() had good data.',
			$router->GetPathArray()[0]
		))->equals('test');

		(new Verify(
			'check that GetPathSlot() returns proper path chunk.',
			$router->GetPathSlot(1)
		))->equals('test');

		(new Verify(
			'check that GetQuery() returns the input data array that contains one element.',
			(is_array($router->GetQuery()) && count($router->GetQuery()) === 1)
		))->true();

		(new Verify(
			'check that GetQueryVar() returns an existing var.',
			$router->GetQueryVar('omg')
		))->equals('true');

		(new Verify(
			'check that GetQueryVar() returns an null for nonexisting var.',
			$router->GetQueryVar('nope')
		))->null();

		unset(
			$_SERVER['HTTP_HOST'],
			$_SERVER['REQUEST_URI'],
			$_GET['omg']
		);

		return;
	}

	public function testRequestParsingFromInput() {
	/*//
	testing that things work when we specified data instead.
	//*/

		$router = new Nether\Avenue\Router(static::$RequestData['Index']);

		(new Verify(
			'parsed domain from input',
			$router->GetDomain()
		))->equals('nether.io');

		(new Verify(
			'parsed path from input',
			$router->GetPath()
		))->equals('/index');

		return;
	}

	public function testRequestRootIsAndIndex() {
	/*//
	testing that requests for / and /index are both reported as /index
	//*/

		$r1 = new Nether\Avenue\Router(static::$RequestData['Root']);
		$r2 = new Nether\Avenue\Router(static::$RequestData['Index']);

		(new Verify(
			'path / request runs as /index',
			$r1->GetPath()
		))->equals('/index');

		(new Verify(
			'path /index runs as /index',
			$r2->GetPath()
		))->equals('/index');


		return;
	}

	public function testAddingDefinedRoutes() {
	/*//
	testing that adding a basic route works.
	//*/

		$router = new Nether\Avenue\Router(static::$RequestData['Test']);

		$router->AddRoute('(@)//index','Nether\Avenue\RouteTest::Index');
		$router->AddRoute('(@)//test','Nether\Avenue\RouteTest::Test');
		$routes = $router->GetRoutes();

		(new Verify(
			'check that AddRoute() added the routes',
			count($routes)
		))->equals(2);

		(new Verify(
			'check that the route domain condition translated right',
			current($routes)->Domain
		))->equals('/^(.+?)$/');

		(new Verify(
			'check that the route path condition translated right',
			current($routes)->Path
		))->equals('/^\/index$/');

		(new Verify(
			'check that GetRoute() returns an object.',
			is_object($router->GetRoute())
		))->true();

		(new Verify(
			'check that GetRoute() selected the right route.',
			$router->GetRoute()->Handler
		))->equals('Nether\Avenue\RouteTest::Test');

		(new Verify(
			'check that GetRoute() found the arguments.',
			$router->GetRoute()->Argv[0]
		))->equals('www.nether.io');

		return;
	}

	public function testSlottedVsUnslottedConditions() {

		$r1 = new Nether\Avenue\Router(static::$RequestData['Index']);
		$r2 = new Nether\Avenue\Router(static::$RequestData['Index']);

		$r1->AddRoute('{@}//index','Nether\Avenue\RouteTest::Index');
		$r2->AddRoute('(@)//index','Nether\Avenue\RouteTest::Index');

		(new Verify(
			'first route is unslotted.',
			(count($r1->GetRoute()->Argv) === 0)
		))->true();

		(new Verify(
			'second route is slotted.',
			(count($r2->GetRoute()->Argv) === 1)
		))->true();

		return;
	}

	public function testRouteConditionShortcuts() {

		return;
	}



}