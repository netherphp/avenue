<?php

namespace Nether\Avenue;

use
\Nether  as Nether,
\PHPUnit as PHPUnit;

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

class LocalRouteTestLol {
	public function
	Test($Input=NULL) {

		if(!$Input) echo 'Default Test';
		else echo $Input;

		return;
	}
}

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

class RouteHandlerTest
extends PHPUnit\Framework\TestCase {

	static
	$RequestData = [
		'Test' => [ 'Class' => 'Nether\Avenue\LocalRouteTestLol', 'Method' => 'Test' ]
	];

	/** @test */
	public function
	TestCreateNoArgs() {

		$Handler = new Nether\Avenue\RouteHandler(static::$RequestData['Test']);

		$this->AssertEquals('Nether\Avenue\LocalRouteTestLol',$Handler->GetClass());
		$this->AssertEquals('Test',$Handler->GetMethod());
		$this->AssertCount(0,$Handler->GetArgv());

		return;
	}

	/** @test */
	public function
	TestCreateWithArgs() {

		$Handler = new Nether\Avenue\RouteHandler(static::$RequestData['Test']);

		// set the argv after in a manner similar to how the main router will
		// do it.
		$Handler->SetArgv(['Patrick Stewart']);

		$this->AssertEquals('Nether\Avenue\LocalRouteTestLol',$Handler->GetClass());
		$this->AssertEquals('Test',$Handler->GetMethod());
		$this->AssertEquals('Patrick Stewart',$Handler->GetArgv()[0]);

		return;
	}

	/** @test */
	public function
	TestExecuteRouteNoArgs() {

		$Router = new Nether\Avenue\Router;
		$Handler = new Nether\Avenue\RouteHandler(static::$RequestData['Test']);

		ob_start();
		$Handler->Run($Router);

		$this->AssertEquals('Default Test',ob_get_clean());

		return;
	}

	/** @test */
	public function
	TestExecuteRouteWithArgs() {

		$Router = new Nether\Avenue\Router;
		$Handler = new Nether\Avenue\RouteHandler(static::$RequestData['Test']);
		$Handler->SetArgv(['Patrick Stewart']);

		ob_start();
		$Handler->Run($Router);

		$this->AssertEquals('Patrick Stewart',ob_get_clean());

		return;
	}

}
