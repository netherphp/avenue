<?php

namespace Nether\Avenue;

use \Nether;
use \Codeception\Verify;

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

class LocalRouteTestLol {
	public function Test($input=null) {
		if(!$input) echo 'Default Test';
		else echo $input;

		return;
	}
}

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

class RouteHandler_Test extends \Codeception\TestCase\Test {

	static $RequestData = [
		'Test' => [ 'Class' => 'Nether\Avenue\LocalRouteTestLol', 'Method' => 'Test' ]
	];

	public function testCreateNoArgs() {

		$handler = new Nether\Avenue\RouteHandler(static::$RequestData['Test']);

		(new Verify(
			'make sure $Class was set right.',
			$handler->GetClass()
		))->equals('Nether\Avenue\LocalRouteTestLol');

		(new Verify(
			'make sure $Method was set right.',
			$handler->GetMethod()
		))->equals('Test');

		(new Verify(
			'make sure $Argv was set right.',
			$handler->GetArgv()
		))->null();

		return;
	}

	public function testCreateWithArgs() {

		$handler = new Nether\Avenue\RouteHandler(static::$RequestData['Test']);

		// set the argv after in a manner similar to how the main router will
		// do it.
		$handler->SetArgv(['Patrick Stewart']);

		(new Verify(
			'make sure $Class was set right.',
			$handler->GetClass()
		))->equals('Nether\Avenue\LocalRouteTestLol');

		(new Verify(
			'make sure $Method was set right.',
			$handler->GetMethod()
		))->equals('Test');

		(new Verify(
			'make sure $Argv was set right.',
			$handler->GetArgv()[0]
		))->equals('Patrick Stewart');

		return;
	}

	public function testExecuteRouteNoArgs() {

		$router = new Nether\Avenue\Router;
		$handler = new Nether\Avenue\RouteHandler(static::$RequestData['Test']);

		ob_start();
		$handler->Run($router);
		(new Verify(
			'check for default output',
			ob_get_clean()
		))->equals('Default Test');

		return;
	}

	public function testExecuteRouteWithArgs() {

		$router = new Nether\Avenue\Router;
		$handler = new Nether\Avenue\RouteHandler(static::$RequestData['Test']);
		$handler->SetArgv(['Patrick Stewart']);

		ob_start(); $handler->Run($router);
		(new Verify(
			'check for specific output',
			ob_get_clean()
		))->equals('Patrick Stewart');

		return;
	}

}