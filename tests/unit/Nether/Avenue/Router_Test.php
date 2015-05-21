<?php

namespace Nether\Avenue;

use \Nether;
use \Codeception\Verify;

////////
////////

class LocalTestRouteAckHandle {
	static function WillHandleRequest($r,$h) {
		(new Verify($r instanceof Nether\Avenue\Router))->true();
		(new Verify($h instanceof Nether\Avenue\RouteHandler))->true();

		// our test route only wants to run on nether.io
		if($r->GetDomain() !== 'nether.io') return false;

		// and if the url is test.
		if($h->GetArgv()[1] === 'test') return true;
		else return false;
	}
	public function Test() {
		echo "Test";
		return;
	}
}


////////
////////

class Router_Test extends \Codeception\TestCase\Test {

	static $RequestData = [
		'Root' => [ 'Domain'=>'www.nether.io', 'Path'=>'/' ],
		'Index' => [ 'Domain'=>'www.nether.io', 'Path'=>'/index' ],
		'IndexTs' => [ 'Domain'=>'www.nether.io', 'Path'=>'/index/' ],
		'Test' => [ 'Domain'=>'www.nether.io', 'Path'=>'/test' ],
		'TestTs' => [ 'Domain'=>'www.nether.io', 'Path'=>'/test/' ],
		'TestQuery' => [ 'Domain'=>'www.nether.io', 'Path'=>'/test?omg=true&bbq=yum' ],
		'TestQueryTs' => [ 'Domain'=>'www.nether.io', 'Path'=>'/test/?omg=true&bbq=yum' ],
		'TestInt' => [ 'Domain'=>'www.nether.io', 'Path'=>'/test/42' ],
		'TestIntTs' => [ 'Domain'=>'www.nether.io', 'Path'=>'/test/42/' ],
		'TestDeep' => [ 'Domain'=>'www.nether.io', 'Path'=>'/one/two/three/four' ],
		'TestDeepTs' => [ 'Domain'=>'www.nether.io', 'Path'=>'/one/two/three/four/' ],
		'LocalhostRoot' => [ 'Domain'=>'localhost', 'Path'=>'/' ]
	];

	public function testRequestParsingFromGlobals() {
	/*//
	testing all the primary features of the router in a method similiar to a
	web request from apache.
	//*/

		$_SERVER['HTTP_HOST'] = static::$RequestData['TestQuery']['Domain'];
		$_SERVER['REQUEST_URI'] = static::$RequestData['TestQuery']['Path'];
		$_GET['omg'] = 'true';
		$_GET['bbq'] = 'yum';

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
			'check that GetQuery() returns the input data array that contains two element.',
			(is_array($router->GetQuery()) && count($router->GetQuery()) === 2)
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
			$_GET['omg'],
			$_GET['bbq']
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
			current($routes)->GetDomain()
		))->equals('`^(.+?)$`');

		(new Verify(
			'check that the route path condition translated right',
			current($routes)->GetPath()
		))->equals('`^\/index$`');

		(new Verify(
			'check that GetRoute() returns an object.',
			is_object($router->GetRoute())
		))->true();

		(new Verify(
			'check that GetRoute() selected the right route.',
			($router->GetRoute() instanceof Nether\Avenue\RouteHandler)
		))->true();

		(new Verify(
			'check that TranslateRouteHandler() parsed the class right.',
			"Class: {$router->GetRoute()->GetClass()}, Method: {$router->GetRoute()->GetMethod()}"
		))->equals('Class: Nether\Avenue\RouteTest, Method: Test');

		(new Verify(
			'check that GetRoute() found the arguments.',
			$router->GetRoute()->GetArgv()[0]
		))->equals('www.nether.io');

		return;
	}

	public function testSlottedVsUnslottedConditions() {
	/*//
	test that an unslotted shortcut {} results in an empty Argv array whilst a
	slotted shortcut () results in a populated Argv array.
	//*/

		$r1 = new Nether\Avenue\Router(static::$RequestData['Index']);
		$r2 = new Nether\Avenue\Router(static::$RequestData['Index']);

		$r1->AddRoute('{@}//index','Nether\Avenue\RouteTest::Index');
		$r2->AddRoute('(@)//index','Nether\Avenue\RouteTest::Index');

		(new Verify(
			'first route is unslotted.',
			(count($r1->GetRoute()->GetArgv()) === 0)
		))->true();

		(new Verify(
			'second route is slotted.',
			(count($r2->GetRoute()->GetArgv()) === 1)
		))->true();

		return;
	}

	public function testRouteConditionTranslation() {
	/*//
	test that the shortcuts translate as expected.
	//*/


		$router = new Nether\Avenue\Router(static::$RequestData['Test']);

		foreach(Nether\Option::Get('nether-avenue-condition-shortcuts') as $old => $new)
		(new Verify(
			"pattern {$old} translates as expected.",
			$router->TranslateRouteCondition($old)
		))->equals($new);

		return;
	}

	public function testNoRouteFound() {
	/*//
	test that when no routes are found we give back false.
	//*/

		$router = new Nether\Avenue\Router(static::$RequestData['TestDeep']);
		$router->AddRoute('{@}//index','herp::derp');
		(new Verify(
			'no routes found return null',
			$router->GetRoute()
		))->equals(null);

		return;
	}

	public function testRouteConditionQueryVars() {
	/*//
	test that query vars are being accounted for in the route conditions.
	//*/

		$_GET['omg'] = 'true';
		$_GET['bbq'] = 'yey';

		$router = new Nether\Avenue\Router(static::$RequestData['TestQuery']);

		$router->ClearRoutes()->AddRoute('{@}//test??tacobell','herp::derp');
		$route = $router->GetRoute();
		(new Verify(
			'this route fails because there is no tacobell in get.',
			($route instanceof Nether\Avenue\RouteHandler)
		))->false();

		$router->ClearRoutes()->AddRoute('{@}//test??omg','herp::derp');
		$route = $router->GetRoute();
		(new Verify(
			'this route passes because we had omg',
			($route instanceof Nether\Avenue\RouteHandler)
		))->true();

		$router->ClearRoutes()->AddRoute('{@}//test??omg&bbq','herp::derp');
		$route = $router->GetRoute();
		(new Verify(
			'this route passes because we had omg and bbq',
			($route instanceof Nether\Avenue\RouteHandler)
		))->true();

		$router->ClearRoutes()->AddRoute('{@}//test??omg&wtf&bbq','herp::derp');
		$route = $router->GetRoute();
		(new Verify(
			'this route fails because we had omg and bbq, but no wtf.',
			($route instanceof Nether\Avenue\RouteHandler)
		))->false();

		return;
	}

	public function testRouteConditionAtSignMeaning() {
	/*//
	testing that the (@) {@} shortcuts means what i think they mean after
	translating and dropping them on preg_match. at-sign means nice-match for
	anything as long as there is something.
	//*/

		$router = new Nether\Avenue\Router(static::$RequestData['TestDeep']);

		$router->AddRoute('(@)//one/two/three/four','herp::derp');
		(new Verify($router->GetRoute()->GetArgv()[0]))->equals('www.nether.io');

		$router->ClearRoutes()->AddRoute('(@)//one/two/three/four(@)','herp::derp');
		(new Verify($router->GetRoute()))->equals(false);

		$router->ClearRoutes()->AddRoute('www.(@)//one/two/three/four','herp::derp');
		(new Verify($router->GetRoute()->GetArgv()[0]))->equals('nether.io');

		$router->ClearRoutes()->AddRoute('www.(@).io//one/two/three/four','herp::derp');
		(new Verify($router->GetRoute()->GetArgv()[0]))->equals('nether');

		$router->ClearRoutes()->AddRoute('www.(@).io//(@)','herp::derp');
		(new Verify($router->GetRoute()->GetArgv()[0]))->equals('nether');
		(new Verify($router->GetRoute()->GetArgv()[1]))->equals('one/two/three/four');

		$router->ClearRoutes()->AddRoute('www.(@).io//(@)/(@)/(@)/(@)','herp::derp');
		(new Verify($router->GetRoute()->GetArgv()[0]))->equals('nether');
		(new Verify($router->GetRoute()->GetArgv()[1]))->equals('one');
		(new Verify($router->GetRoute()->GetArgv()[2]))->equals('two');
		(new Verify($router->GetRoute()->GetArgv()[3]))->equals('three');
		(new Verify($router->GetRoute()->GetArgv()[4]))->equals('four');

		return;
	}

	public function testRouteConditionQuestionMarkMeaning() {
	/*//
	test that the (?) {?} shortcuts mean nice-match for anything even if
	there is nothing.
	//*/

		$router = new Nether\Avenue\Router(static::$RequestData['TestDeep']);

		$router->AddRoute('(?)//one/two/three/four','herp::derp');
		(new Verify($router->GetRoute()->GetArgv()[0]))->equals('www.nether.io');

		$router->ClearRoutes()->AddRoute('(?)//one/two/three/four(?)','herp::derp');
		(new Verify($router->GetRoute()->GetArgv()[1]))->equals('');

		$router->ClearRoutes()->AddRoute('www.(?)//one/two/three/four','herp::derp');
		(new Verify($router->GetRoute()->GetArgv()[0]))->equals('nether.io');

		$router->ClearRoutes()->AddRoute('www.(?).io//one/two/three/four','herp::derp');
		(new Verify($router->GetRoute()->GetArgv()[0]))->equals('nether');

		$router->ClearRoutes()->AddRoute('www.(?).io//(?)','herp::derp');
		(new Verify($router->GetRoute()->GetArgv()[0]))->equals('nether');
		(new Verify($router->GetRoute()->GetArgv()[1]))->equals('one/two/three/four');

		$router->ClearRoutes()->AddRoute('www.(?).io//(?)/(?)/(?)/(?)','herp::derp');
		(new Verify($router->GetRoute()->GetArgv()[0]))->equals('nether');
		(new Verify($router->GetRoute()->GetArgv()[1]))->equals('one');
		(new Verify($router->GetRoute()->GetArgv()[2]))->equals('two');
		(new Verify($router->GetRoute()->GetArgv()[3]))->equals('three');
		(new Verify($router->GetRoute()->GetArgv()[4]))->equals('four');

		return;
	}

	public function testRouteConditionPoundSignMeaning() {
	/*//
	test that the (#) {#} shortcuts mean match any numbers.
	//*/

		$router = new Nether\Avenue\Router(static::$RequestData['Test']);

		$router->AddRoute('{@}//test/(#)','herp::derp');
		(new Verify($router->GetRoute()))->false();

		////////

		$router = new Nether\Avenue\Router(static::$RequestData['TestInt']);

		$router->AddRoute('{@}//test/(#)','herp::derp');
		(new Verify($router->GetRoute()->GetArgv()[0]))->equals('42');

		$router->AddRoute('{@}//test/(#)(?:/photos/{#})?','herp::derp');
		(new Verify($router->GetRoute()->GetArgv()[0]))->equals('42');

		return;
	}

	public function testRouteConditionDollarSignMeaning() {
	/*//
	test that the ($) {$} shortcuts mean match any strings between slashes.
	//*/

		$router = new Nether\Avenue\Router(static::$RequestData['TestDeep']);

		$router->AddRoute('{@}//test/($)','herp::derp');
		(new Verify($router->GetRoute()))->false();

		////////

		$router = new Nether\Avenue\Router(static::$RequestData['TestDeep']);

		$router->AddRoute('{@}//($)','herp::derp');
		(new Verify($router->GetRoute()))->false();

		$router->ClearRoutes()->AddRoute('{@}//($)/($)/($)/($)','herp::derp');
		(new Verify($router->GetRoute()->GetArgv()[0]))->equals('one');
		(new Verify($router->GetRoute()->GetArgv()[1]))->equals('two');
		(new Verify($router->GetRoute()->GetArgv()[2]))->equals('three');
		(new Verify($router->GetRoute()->GetArgv()[3]))->equals('four');

		return;
	}

	public function testRouteConditionDomainMeaning() {
	/*//
	test that the (domain) shortcut pulls domain.tld without any subdomains and
	that it works as expected on dotless domains e.g. localhost.
	//*/

		$router = new Nether\Avenue\Router(static::$RequestData['Root']);
		$router->AddRoute('(domain)//index','herp::derp');
		(new Verify($router->GetRoute()->GetArgv()[0]))->equals('nether.io');

		$router = new Nether\Avenue\Router(static::$RequestData['LocalhostRoot']);
		$router->AddRoute('(domain)//index','herp::derp');
		(new Verify($router->GetRoute()->GetArgv()[0]))->equals('localhost');


		return;
	}

	public function testHitHashAndTime() {
	/*//
	test that the hit hashing stuff is working from web requests.
	//*/

		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

		$r1 = new Nether\Avenue\Router(static::$RequestData['Root']);
		$r2 = new Nether\Avenue\Router(static::$RequestData['Test']);

		(new Verify(
			'verify that GetHitHash() returned a hashy looking thing.',
			strlen($r1->GetHitHash())
		))->equals(32);

		(new Verify(
			'verify that r1 and r2 GetHitHash() do not match.',
			($r1->GetHitHash() === $r2->GetHitHash())
		))->false();

		(new Verify(
			'verify GetHitTime() returned a float',
			is_float($r1->GetHitTime())
		))->true();

		// it is actually plausable that a machine could be so fast that r1 and
		// r2 register at the exact same microsecond. if you are seeing a failed
		// test for r1 and r2 hit times matching, put a sleep(0.01) or something
		// between them. i am not doing that now intentionally to see how long
		// it takes for it to happen.

		// if they do end up matching, that is not a failure of the libarary.
		// in fact, that is a success. it means that two hits happend so fast
		// that you should ignore one of them because seriously, stop spamming
		// my server.

		// i don't expect this to happen until we have warp capabilities anyway.
		// at which point microtime is probably going to naturally be further
		// down the decimal point hole anyway.

		// it sure as fuck isn't going to happen on travis-ci.

		(new Verify(
			'verify that r1 and r2 GetHitTime()s do not match.',
			($r1->GetHitTime() === $r2->GetHitTime())
		))->false();

		$h1 = $r1->GetHit();

		(new Verify(
			'verify that GetHit() returned data matching GetHitHash().',
			$h1->Hash
		))->equals($r1->GetHitHash());

		(new Verify(
			'verify that GetHit() returned data matching GetHitTime().',
			$h1->Time
		))->equals($r1->GetHitTime());

		return;
	}

	public function testProtocolDetect() {
	/*//
	test https detection.
	//*/

		$router = new Nether\Avenue\Router(static::$RequestData['Test']);
		(new Verify(
			'check that GetProtocol() thinks its on HTTP right now.',
			$router->GetProtocol()
		))->equals('http');

		$_SERVER['HTTPS'] = true;
		(new Verify(
			'check that GetProtocol() thinks its on HTTPS right now.',
			$router->GetProtocol()
		))->equals('https');

		return;
	}

	public function testUrlReconstruction() {
	/*//
	test that the url reconstruction builds accurate urls from the parsed
	request data.
	//*/

		$router = new Nether\Avenue\Router(static::$RequestData['TestQuery']);

		(new Verify(
			'check that GetURL() reconstructed an accurate URL.',
			$router->GetURL()
		))->equals('http://www.nether.io/test');

		return;
	}

	public function testRouteConfirmWillHandle() {

		$router = new Nether\Avenue\Router(static::$RequestData['Test']);
		$router->AddRoute('(@)//(test)','Nether\Avenue\LocalTestRouteAckHandle::Test');

		$route = $router->GetRoute();
		(new Verify(
			'found a route to match.',
			($route instanceof Nether\Avenue\RouteHandler)
		))->true();

		$router = new Nether\Avenue\Router(static::$RequestData['Index']);
		$router->AddRoute('(@)//(test)','Nether\Avenue\LocalTestRouteAckHandle::Test');

		$route = $router->GetRoute();
		(new Verify(
			'did not find a route to match.',
			($route instanceof Nether\Avenue\RouteHandler)
		))->false();

		return;
	}

	public function testWillTheQueryBlend() {

		$_GET['omg'] = 'lol';
		$_GET['bbq'] = 'saucey';

		$router = new Nether\Avenue\Router(static::$RequestData['Index']);
		(new Verify(
			'query arguments: will they blend?',
			($router->QueryCooker(['donkey'=>'punch']))
		))->equals('?omg=lol&bbq=saucey&donkey=punch');

		return;
	}

}