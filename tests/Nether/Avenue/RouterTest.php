<?php

namespace NetherTestSuite\Avenue\Router;
use PHPUnit;

use Exception;
use Nether\Avenue\Router;
use Nether\Avenue\Request;
use Nether\Avenue\Response;
use Nether\Avenue\Library;
use Nether\Avenue\Error\RouterRouteRootUndefined;
use Nether\Avenue\Error\RouterWebRootUndefined;
use Nether\Object\Datastore;

class RouterTest
extends PHPUnit\Framework\TestCase {

	/** @test */
	public function
	TestUnconfigured():
	void {

		$RouteRoot = sprintf('%s/routes', dirname(__FILE__, 4));
		$WebRoot = sprintf('%s/www', dirname(__FILE__, 4));
		$HadExcept = FALSE;
		$Conf = new Datastore;

		// try a fail due to having no route root config.

		try {
			$HadExcept = FALSE;
			new Router($Conf);
		}

		catch(Exception $Err) {
			$HadExcept = TRUE;
			$this->AssertInstanceOf(
				RouterRouteRootUndefined::class,
				$Err
			);
		}

		$this->AssertTrue($HadExcept);

		// try a fail due to having no web root config.

		$Conf[Library::ConfRouteRoot] = $RouteRoot;

		try {
			$HadExcept = FALSE;
			new Router($Conf);
		}

		catch(Exception $Err) {
			$HadExcept = TRUE;
			$this->AssertInstanceOf(
				RouterWebRootUndefined::class,
				$Err
			);
		}

		$this->AssertTrue($HadExcept);

		// try to succeed now that we have bare minimum config.

		$Conf[Library::ConfWebRoot] = $WebRoot;

		try {
			$HadExcept = FALSE;
			new Router($Conf);
		}

		catch(Exception $Err) {
			$HadExcept = TRUE;
		}

		$this->AssertFalse($HadExcept);

		return;
	}

	/** @test */
	public function
	TestStaticRouteFile():
	void {

		$RouteRoot = sprintf('%s/routes', dirname(__FILE__, 4));
		$RouteFile = sprintf('%s/routes-test.phson', dirname(__FILE__, 4));
		$WebRoot = sprintf('%s/www', dirname(__FILE__, 4));
		$Conf = new Datastore;
		$Handlers = NULL;
		$Handler = NULL;
		$Expect = NULL;
		$Methods = NULL;
		$Verb = NULL;
		$HadExcept = NULL;

		////////

		$Conf[Library::ConfRouteRoot] = $RouteRoot;
		$Conf[Library::ConfRouteFile] = $RouteFile;
		$Conf[Library::ConfWebRoot] = $WebRoot;
		$Router = new Router($Conf);

		$this->AssertInstanceOf(Datastore::class, $Router->Conf);
		$this->AssertInstanceOf(Request::class, $Router->Request);
		$this->AssertInstanceOf(Response::class, $Router->Response);
		$this->AssertTrue($Conf === $Router->Conf);
		$this->AssertEquals('cache', $Router->GetSource());

		// test that the router generated a seemingly usable route map.

		$Handlers = $Router->SortHandlers()->GetHandlers();

		$Expect = [
			'GET' => [
				'TestRoutes\\Blog::Index',
				'TestRoutes\\Blog::ViewPost',
				'TestRoutes\\Home::About',
				'TestRoutes\\Home::Index',
				'TestRoutes\\Dashboard::Index',
				'TestRoutes\\Dashboard::SingleConfirm'
			]
		];

		$this->AssertCount(count($Expect), $Handlers);

		foreach($Handlers as $Verb => $Methods)
		$this->AssertCount(count($Expect[$Verb]), $Methods);

		// test that the router returns the routes we expect.

		$Router->Request->ParseRequest('GET', 'avenue.test', '/index');
		$Handler = $Router->Select();
		$this->AssertEquals($Expect['GET'][3], $Handler->GetCallableName());

		// test that it fails to find a route for an unconfigured verb.

		$Router->Request->ParseRequest('DERP', 'avenue.test', '/index');
		$Handler = $Router->Select();
		$this->AssertNull($Handler);

		// test that it fails to find a route because it was tagged with
		// the confirm attribute but the method was never defined.

		try {
			$HadExcept = FALSE;
			$Router->Request->ParseRequest('GET', 'avenue.test', '/dashboard');
			$Handler = $Router->Select();
		}

		catch(Exception $Err) {
			$HadExcept = TRUE;
		}

		$this->AssertTrue($HadExcept);

		// test that it found a route that was configured well enough.

		$Router->Request->ParseRequest('GET', 'avenue.test', '/dashboard/singleconfirm');
		$Handler = $Router->Select();

		$this->AssertEquals($Expect['GET'][5], $Handler->GetCallableName());

		// test that it failed to find a route with a confirm.

		// ...

		// test that it succeeded to find a route but rejected executing

		// ...

		return;
	}

	/** @test */
	public function
	TestDynamicDirectory():
	void {

		$RouteRoot = sprintf('%s/routes', dirname(__FILE__, 4));
		$WebRoot = sprintf('%s/www', dirname(__FILE__, 4));

		$Conf = Library::PrepareDefaultConfig();
		$Conf[Library::ConfRouteRoot] = $RouteRoot;
		$Conf[Library::ConfWebRoot] = $WebRoot;
		$Router = new Router($Conf);

		$this->AssertInstanceOf(Datastore::class, $Router->Conf);
		$this->AssertInstanceOf(Request::class, $Router->Request);
		$this->AssertInstanceOf(Response::class, $Router->Response);
		$this->AssertTrue($Conf === $Router->Conf);
		$this->AssertEquals('dirscan', $Router->GetSource());

		// todo - validate expected routes.

		return;
	}

}
