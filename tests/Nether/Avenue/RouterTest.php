<?php

namespace
Nether\Avenue;

use
\Nether  as Nether,
\PHPUnit as PHPUnit;

////////
////////

class LocalTestRouteAckHandle {
	static public function
	WillHandleRequest($R,$H) {
		//(new Verify($r instanceof Nether\Avenue\Router))->true();
		//(new Verify($h instanceof Nether\Avenue\RouteHandler))->true();

		// our Test route only wants to run on nether.io
		if($R->GetDomain() !== 'nether.io') return FALSE;

		// and if the url is Test.
		if($H->GetArgv()[1] === 'test') return TRUE;
		else return FALSE;
	}
	public function
	Test() {
		echo "Test";
		return;
	}
}

////////
////////

class RouterTest
extends PHPUnit\Framework\TestCase {

	static
	$RequestData = [
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

	/** @test */
	public function
	TestRequestParsingFromGlobals() {
	/*//
	testing all the primary features of the router in a method similiar to a
	web request from apache.
	//*/

		$_SERVER['HTTP_HOST'] = static::$RequestData['TestQuery']['Domain'];
		$_SERVER['REQUEST_URI'] = static::$RequestData['TestQuery']['Path'];
		$_GET['omg'] = 'true';
		$_GET['bbq'] = 'yum';

		$Router = new Nether\Avenue\Router;
		$this->AssertEquals('www.nether.io',$Router->GetFullDomain());
		$this->AssertEquals('nether.io',$Router->GetDomain());
		$this->AssertEquals('/test',$Router->GetPath());
		$this->AssertTrue(is_array($Router->GetPathArray()));
		$this->AssertCount(1,$Router->GetPathArray());
		$this->AssertEquals('test',$Router->GetPathArray()[0]);
		$this->AssertEquals('test',$Router->GetPathSlot(1));
		$this->AssertTrue(is_array($Router->GetQuery()));
		$this->AssertCount(2,$Router->GetQuery());
		$this->AssertEquals('true',$Router->GetQueryVar('omg'));
		$this->AssertNull($Router->GetQueryVar('nope'));

		unset(
			$_SERVER['HTTP_HOST'],
			$_SERVER['REQUEST_URI'],
			$_GET['omg'],
			$_GET['bbq']
		);

		return;
	}

	/** @test */
	public function
	TestRequestParsingFromInput() {
	/*//
	testing that things work when we specified data instead.
	//*/

		$Router = new Nether\Avenue\Router(static::$RequestData['Index']);

		$this->AssertEquals('nether.io',$Router->GetDomain());
		$this->AssertEquals('/index',$Router->GetPath());

		return;
	}

	/** @test */
	public function
	TestRequestRootIsAndIndex() {
	/*//
	testing that requests for / and /index are both reported as /index
	//*/

		$R1 = new Nether\Avenue\Router(static::$RequestData['Root']);
		$R2 = new Nether\Avenue\Router(static::$RequestData['Index']);

		$this->AssertEquals('/index',$R1->GetPath());
		$this->AssertEquals('/index',$R2->GetPath());

		return;
	}

	/** @test */
	public function
	TestAddingDefinedRoutes() {
	/*//
	testing that adding a basic route works.
	//*/

		$Router = new Nether\Avenue\Router(static::$RequestData['Test']);

		$Router->AddRoute('(@)//index','Nether\Avenue\RouteTest::Index');
		$Router->AddRoute('(@)//test','Nether\Avenue\RouteTest::Test');
		$Routes = $Router->GetRoutes();

		$this->AssertCount(
			2, $Routes,
			'routes added to router'
		);

		$this->AssertEquals(
			'`^(.+?)$`', $Routes[0]->GetDomain(),
			'route domain condition translation'
		);

		$this->AssertEquals(
			'`^\/index$`', $Routes[0]->GetPath(),
			'route path condition translation'
		);

		$this->AssertTrue(
			($Router->GetRoute() instanceof Nether\Avenue\RouteHandler),
			'route was found and returned'
		);

		$this->AssertEquals(
			'Class: Nether\Avenue\RouteTest, Method: Test',
			"Class: {$Router->GetRoute()->GetClass()}, Method: {$Router->GetRoute()->GetMethod()}",
			'check that TranslateRouteHandler parsed the class right'
		);

		$this->AssertEquals(
			'www.nether.io',
			$Router->GetRoute()->GetArgv()[0],
			'check GetRoute() found the arguments'
		);

		return;
	}

	/** @test */
	public function
	TestSlottedVsUnslottedConditions() {
	/*//
	test that an unslotted shortcut {} results in an empty Argv array whilst a
	slotted shortcut () results in a populated Argv array.
	//*/

		$R1 = new Nether\Avenue\Router(static::$RequestData['Index']);
		$R2 = new Nether\Avenue\Router(static::$RequestData['Index']);

		$R1->AddRoute('{@}//index','Nether\Avenue\RouteTest::Index');
		$R2->AddRoute('(@)//index','Nether\Avenue\RouteTest::Index');

		$this->AssertCount(
			0, $R1->GetRoute()->GetArgv(),
			'first route is unslotted'
		);

		$this->AssertCount(
			1, $R2->GetRoute()->GetArgv(),
			'second route is slotted'
		);

		return;
	}

	/** @test */
	public function
	TestRouteConditionTranslation() {
	/*//
	test that the shortcuts translate as expected.
	//*/

		$Old = NULL;
		$New = NULL;

		$Router = new Nether\Avenue\Router(static::$RequestData['Test']);

		foreach(Nether\Option::Get('nether-avenue-condition-shortcuts') as $Old => $New)
		$this->AssertEquals(
			$New, $Router->TranslateRouteCondition($Old),
			'pattern {$Old} translates as expected.'
		);

		return;
	}

	/** @test */
	public function
	TestNoRouteFound() {
	/*//
	test that when no routes are found we give back false.
	//*/

		$Router = new Nether\Avenue\Router(static::$RequestData['TestDeep']);
		$Router->AddRoute('{@}//index','herp::derp');

		$this->AssertNull(
			$Router->GetRoute(),
			'no route should have been found'
		);

		return;
	}

	/** @test */
	public function
	TestRouteConditionQueryVars() {
	/*//
	test that query vars are being accounted for in the route conditions.
	//*/

		$_GET['omg'] = 'true';
		$_GET['bbq'] = 'yey';

		$Router = new Nether\Avenue\Router(static::$RequestData['TestQuery']);

		$Router->ClearRoutes()->AddRoute('{@}//test??tacobell','herp::derp');
		$Route = $Router->GetRoute();

		$this->AssertFalse(
			($Route instanceof Nether\Avenue\RouteHandler),
			'this route should cuz no taco bell in get.'
		);


		$Router->ClearRoutes()->AddRoute('{@}//test??omg','herp::derp');
		$Route = $Router->GetRoute();
		$this->AssertTrue(
			($Route instanceof Nether\Avenue\RouteHandler),
			'this route pass because omg'
		);

		$Router->ClearRoutes()->AddRoute('{@}//test??omg&bbq','herp::derp');
		$Route = $Router->GetRoute();
		$this->AssertTrue(
			($Route instanceof Nether\Avenue\RouteHandler),
			'this route pass because omg and bbq'
		);

		$Router->ClearRoutes()->AddRoute('{@}//test??omg&wtf&bbq','herp::derp');
		$Route = $Router->GetRoute();
		$this->AssertFalse(
			($Route instanceof Nether\Avenue\RouteHandler),
			'this route fail because cuz no wtf'
		);

		return;
	}

	/** @test */
	public function
	TestRouteConditionAtSignMeaning() {
	/*//
	testing that the (@) {@} shortcuts means what i think they mean after
	translating and dropping them on preg_match. at-sign means nice-match for
	anything as long as there is something.
	//*/

		$Router = new Nether\Avenue\Router(static::$RequestData['TestDeep']);

		$Router->AddRoute('(@)//one/two/three/four','herp::derp');
		$this->AssertEquals('www.nether.io',$Router->GetRoute()->GetArgv()[0]);

		$Router->ClearRoutes()->AddRoute('(@)//one/two/three/four(@)','herp::derp');
		$this->AssertNull($Router->GetRoute());

		$Router->ClearRoutes()->AddRoute('www.(@)//one/two/three/four','herp::derp');
		$this->AssertEquals('nether.io',$Router->GetRoute()->GetArgv()[0]);

		$Router->ClearRoutes()->AddRoute('www.(@).io//one/two/three/four','herp::derp');
		$this->AssertEquals('nether',$Router->GetRoute()->GetArgv()[0]);

		$Router->ClearRoutes()->AddRoute('www.(@).io//(@)','herp::derp');
		$this->AssertEquals('nether',$Router->GetRoute()->GetArgv()[0]);
		$this->AssertEquals('one/two/three/four',$Router->GetRoute()->GetArgv()[1]);

		$Router->ClearRoutes()->AddRoute('www.(@).io//(@)/(@)/(@)/(@)','herp::derp');
		$this->AssertEquals('nether',$Router->GetRoute()->GetArgv()[0]);
		$this->AssertEquals('one',$Router->GetRoute()->GetArgv()[1]);
		$this->AssertEquals('two',$Router->GetRoute()->GetArgv()[2]);
		$this->AssertEquals('three',$Router->GetRoute()->GetArgv()[3]);
		$this->AssertEquals('four',$Router->GetRoute()->GetArgv()[4]);

		return;
	}

	/** @test */
	public function
	TestRouteConditionQuestionMarkMeaning() {
	/*//
	test that the (?) {?} shortcuts mean nice-match for anything even if
	there is nothing.
	//*/

		$Router = new Nether\Avenue\Router(static::$RequestData['TestDeep']);

		$Router->AddRoute('(?)//one/two/three/four','herp::derp');
		$Args = $Router->GetRoute()->GetArgv();
		$this->AssertEquals('www.nether.io',$Args[0]);

		$Router->ClearRoutes()->AddRoute('(?)//one/two/three/four(?)','herp::derp');
		$Args = $Router->GetRoute()->GetArgv();
		$this->AssertEquals('',$Args[1]);

		$Router->ClearRoutes()->AddRoute('www.(?)//one/two/three/four','herp::derp');
		$Args = $Router->GetRoute()->GetArgv();
		$this->AssertEquals('nether.io',$Args[0]);

		$Router->ClearRoutes()->AddRoute('www.(?).io//one/two/three/four','herp::derp');
		$Args = $Router->GetRoute()->GetArgv();
		$this->AssertEquals('nether',$Args[0]);

		$Router->ClearRoutes()->AddRoute('www.(?).io//(?)','herp::derp');
		$Args = $Router->GetRoute()->GetArgv();
		$this->AssertEquals('nether',$Args[0]);
		$this->AssertEquals('one/two/three/four',$Args[1]);

		$Router->ClearRoutes()->AddRoute('www.(?).io//(?)/(?)/(?)/(?)','herp::derp');
		$Args = $Router->GetRoute()->GetArgv();
		$this->AssertEquals('nether',$Args[0]);
		$this->AssertEquals('one',$Args[1]);
		$this->AssertEquals('two',$Args[2]);
		$this->AssertEquals('three',$Args[3]);
		$this->AssertEquals('four',$Args[4]);

		return;
	}

	/** @test */
	public function
	TestRouteConditionPoundSignMeaning() {
	/*//
	test that the (#) {#} shortcuts mean match any numbers.
	//*/

		$Router = new Nether\Avenue\Router(static::$RequestData['Test']);

		$Router->AddRoute('{@}//test/(#)','herp::derp');
		$this->AssertNull($Router->GetRoute());

		////////

		$Router = new Nether\Avenue\Router(static::$RequestData['TestInt']);

		$Router->AddRoute('{@}//test/(#)','herp::derp');
		$Args = $Router->GetRoute()->GetArgv();
		$this->AssertEquals(42,$Args[0]);

		$Router->AddRoute('{@}//test/(#)(?:/photos/{#})?','herp::derp');
		$Args = $Router->GetRoute()->GetArgv();
		$this->AssertEquals(42,$Args[0]);

		return;
	}

	/** @test */
	public function
	TestRouteConditionDollarSignMeaning() {
	/*//
	test that the ($) {$} shortcuts mean match any strings between slashes.
	//*/

		$Router = new Nether\Avenue\Router(static::$RequestData['TestDeep']);

		$Router->AddRoute('{@}//test/($)','herp::derp');
		$this->AssertNull($Router->GetRoute());

		////////

		$Router = new Nether\Avenue\Router(static::$RequestData['TestDeep']);

		$Router->AddRoute('{@}//($)','herp::derp');
		$this->AssertNull($Router->GetRoute());

		$Router->ClearRoutes()->AddRoute('{@}//($)/($)/($)/($)','herp::derp');
		$Args = $Router->GetRoute()->GetArgv();
		$this->AssertEquals('one',$Args[0]);
		$this->AssertEquals('two',$Args[1]);
		$this->AssertEquals('three',$Args[2]);
		$this->AssertEquals('four',$Args[3]);

		return;
	}

	/** @test */
	public function
	TestRouteConditionDomainMeaning() {
	/*//
	test that the (domain) shortcut pulls domain.tld without any subdomains and
	that it works as expected on dotless domains e.g. localhost.
	//*/

		$Router = new Nether\Avenue\Router(static::$RequestData['Root']);
		$Router->AddRoute('(domain)//index','herp::derp');
		$Args = $Router->GetRoute()->GetArgv();
		$this->AssertEquals('nether.io',$Args[0]);

		$Router = new Nether\Avenue\Router(static::$RequestData['LocalhostRoot']);
		$Router->AddRoute('(domain)//index','herp::derp');
		$Args = $Router->GetRoute()->GetArgv();
		$this->AssertEquals('localhost',$Args[0]);

		return;
	}

	/** @test */
	public function
	TestHitHashAndTime() {
	/*//
	test that the hit hashing stuff is working from web requests.
	//*/

		// @todo - redo this when i change to uuid.

		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		$R1 = new Nether\Avenue\Router(static::$RequestData['Root']);
		$R2 = new Nether\Avenue\Router(static::$RequestData['Test']);

		$this->AssertTrue(strlen($R1->GetHitHash()) === 128);
		$this->AssertTrue($R1->GetHitHash() !== $R2->GetHitHash());
		$this->AssertTrue(is_float($R1->GetHitTime()));

		// i don't really know why it should matter if the /time/ is the
		// same across hits.
		// $this->AssertTrue($R1->GetHitTime() !== $R2->GetHitTime());

		$H1 = $R1->GetHit();
		$this->AssertEquals($R1->GetHitHash(),$H1->Hash);
		$this->AssertEquals($R1->GetHitTime(),$H1->Time);

		return;
	}

	/** @test */
	public function
	TestProtocolDetect() {
	/*//
	test https detection.
	//*/

		$Router = new Nether\Avenue\Router(static::$RequestData['Test']);
		$this->AssertEquals('http',$Router->GetProtocol());

		$_SERVER['HTTPS'] = TRUE;
		$this->AssertEquals('https',$Router->GetProtocol());
		unset($_SERVER['HTTPS']);

		return;
	}

	/** @test */
	public function
	TestUrlReconstruction() {
	/*//
	test that the url reconstruction builds accurate urls from the parsed
	request data.
	//*/

		$Router = new Nether\Avenue\Router(static::$RequestData['TestQuery']);

		$this->AssertEquals(
			'http://www.nether.io/test?omg=true&bbq=yey',
			$Router->GetURL()
		);

		return;
	}

	/** @test */
	public function
	TestRouteConfirmWillHandle() {

		$Router = new Nether\Avenue\Router(static::$RequestData['Test']);
		$Router->AddRoute('(@)//(test)','Nether\Avenue\LocalTestRouteAckHandle::Test');
		$Route = $Router->GetRoute();
		$this->AssertTrue($Route instanceof Nether\Avenue\RouteHandler);

		////////

		$Router = new Nether\Avenue\Router(static::$RequestData['Index']);
		$Router->AddRoute('(@)//(test)','Nether\Avenue\LocalTestRouteAckHandle::Test');
		$Route = $Router->GetRoute();
		$this->AssertFalse($Route instanceof Nether\Avenue\RouteHandler);

		return;
	}

	/** @test */
	public function
	TestWillTheQueryBlend() {

		$_GET['omg'] = 'lol';
		$_GET['bbq'] = 'saucey';

		$Router = new Nether\Avenue\Router(static::$RequestData['Index']);
		$this->AssertEquals(
			'?omg=lol&bbq=saucey&donkey=punch',
			$Router->QueryCooker(['donkey'=>'punch']),
			'query arguments: will they blend?'
		);

		return;
	}

}
