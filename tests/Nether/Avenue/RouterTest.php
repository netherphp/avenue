<?php

namespace NetherTestSuite\Avenue\Router;
use PHPUnit;

use Exception;
use Nether\Avenue\Router;
use Nether\Avenue\Request;
use Nether\Avenue\Response;
use Nether\Avenue\Library;
use Nether\Avenue\Meta\RouteHandler;
use Nether\Avenue\Error\RouterRouteRootUndefined;
use Nether\Avenue\Error\RouterWebRootUndefined;
use Nether\Avenue\Error\RouteMissingWillAnswerRequest;
use Nether\Object\Datastore;

class RouterTest
extends PHPUnit\Framework\TestCase {

	/**
	 * @test
	 * @runInSeparateProcess
	 */
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

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	/**
	 * @test
	 * @runInSeparateProcess
	 */
	public function
	TestRouterDynamic():
	void {

		$RouteRoot = sprintf('%s/routes', dirname(__FILE__, 4));
		$WebRoot = sprintf('%s/www', dirname(__FILE__, 4));

		$Conf = Library::PrepareDefaultConfig();
		$Conf[Library::ConfRouteFile] = NULL;
		$Conf[Library::ConfRouteRoot] = $RouteRoot;
		$Conf[Library::ConfWebRoot] = $WebRoot;
		$Router = new Router($Conf);

		$this->AssertInstanceOf(Datastore::class, $Router->Conf);
		$this->AssertInstanceOf(Request::class, $Router->Request);
		$this->AssertInstanceOf(Response::class, $Router->Response);
		$this->AssertTrue($Conf === $Router->Conf);
		$this->AssertEquals('dirscan', $Router->GetSource());

		$this->ContinueTestRouterAfterLoading($Router);
		return;
	}

	/**
	 * @test
	 * @runInSeparateProcess
	 */
	public function
	TestRouterStatic():
	void {

		$RouteRoot = sprintf('%s/routes', dirname(__FILE__, 4));
		$RouteFile = sprintf('%s/routes-test.phson', dirname(__FILE__, 4));
		$WebRoot = sprintf('%s/www', dirname(__FILE__, 4));
		$Conf = new Datastore;

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

		$this->ContinueTestRouterAfterLoading($Router);
		return;
	}

	protected function
	ContinueTestRouterAfterLoading(Router $Router):
	void {

		$Handlers = NULL;
		$Handler = NULL;
		$Expect = NULL;
		$Methods = NULL;
		$Verb = NULL;
		$HadExcept = NULL;

		// test that the router generated a seemingly usable route map.

		$Handlers = $Router->SortHandlers()->GetHandlers();

		$Expect = [
			'GET' => [
				'TestRoutes\\Blog::Index',
				'TestRoutes\\Blog::ViewPost',
				'TestRoutes\\Home::About',
				'TestRoutes\\Home::Index',
				'TestRoutes\\Dashboard::FailConfirm',
				'TestRoutes\\Dashboard::SingleConfirm',
				'TestRoutes\\Dashboard::DoubleConfirm'
			]
		];

		$this->AssertCount(count($Expect), $Handlers);

		foreach($Handlers as $Verb => $Methods)
		$this->AssertCount(count($Expect[$Verb]), $Methods);

		// test that it found a route that was configured well enough.

		$Router->Request->ParseRequest('GET', 'avenue.test', '/index');
		$Handler = $Router->Select();
		$this->AssertEquals($Expect['GET'][3], $Handler->GetCallableName());

		// test that it fails to find a route for an unconfigured verb.

		$Router->Request->ParseRequest('DERP', 'avenue.test', '/index');
		$Handler = $Router->Select();
		$this->AssertNull($Handler);

		// test that it failed to find a route because it just didn't
		// want to answer.

		$Router->Request->ParseRequest('GET', 'avenue.test', '/dashboard/singleconfirm');
		$Handler = $Router->Select();
		$this->AssertNull($Handler);

		// test that it failed to find a route because it did find one but
		// it refused to continue and demand nobody else tries.

		$Router->Request->ParseRequest('GET', 'avenue.test', '/dashboard/doubleconfirm');
		$Handler = $Router->Select();
		$this->AssertNull($Handler);

		// test that it fails to find a route because it was tagged with
		// the confirm attribute but the method was never defined.

		try {
			$HadExcept = FALSE;
			$Router->Request->ParseRequest('GET', 'avenue.test', '/dashboard/failconfirm');
			$Handler = $Router->Select();
		}

		catch(Exception $Err) {
			$HadExcept = TRUE;
			$this->AssertInstanceOf(
				RouteMissingWillAnswerRequest::class,
				$Err
			);
		}

		$this->AssertTrue($HadExcept);

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	/**
	 * @test
	 * @runInSeparateProcess
	 */
	public function
	TestRouterExecute():
	void {

		$RouteFile = sprintf('%s/routes-test.phson', dirname(__FILE__, 4));
		$WebRoot = sprintf('%s/www', dirname(__FILE__, 4));
		$Conf = new Datastore;
		$Handler = NULL;

		$Conf[Library::ConfRouteFile] = $RouteFile;
		$Conf[Library::ConfWebRoot] = $WebRoot;
		$Router = new Router($Conf);

		$Router->Request->ParseRequest('GET', 'avenue.test', '/index');
		$Handler = $Router->Select();
		$this->AssertInstanceOf(RouteHandler::class, $Handler);

		// test that it executes a selected route.

		$Router->Response->Clear();
		$Router->Execute($Handler);
		$this->AssertEquals('Index Page', $Router->Response->Content);

		// test that it executes a not found route.

		$Router->Response->Clear();
		$Router->Execute(NULL);
		$this->AssertEquals('Not Found', $Router->Response->Content);

		// test that it executes a forbidden route.

		$Router->Response->Clear();
		$Router->Response->SetCode(Response::CodeForbidden);
		$Router->Execute(NULL);
		$this->AssertEquals('Forbidden', $Router->Response->Content);

		// test that nothing happens if it just cant.

		$Router->Response->Clear();
		$Router->Response->SetCode(69);
		$Router->Execute(NULL);
		$this->AssertEquals('', $Router->Response->Content);

		return;
	}

	/**
	 * @test
	 * @runInSeparateProcess
	 */
	public function
	TestRouterRender():
	void {

		$RouteFile = sprintf('%s/routes-test.phson', dirname(__FILE__, 4));
		$WebRoot = sprintf('%s/www', dirname(__FILE__, 4));
		$Conf = new Datastore;
		$Handler = NULL;

		$Conf[Library::ConfRouteFile] = $RouteFile;
		$Conf[Library::ConfWebRoot] = $WebRoot;
		$Router = new Router($Conf);

		$Router->Request->ParseRequest('GET', 'avenue.test', '/index');
		$Handler = $Router->Select();
		$Router->Execute($Handler);

		ob_start();
		$Router->Render();
		$Buffer = ob_get_clean();

		$this->AssertEquals('Index Page', $Buffer);

		return;
	}

	/**
	 * @test
	 * @runInSeparateProcess
	 */
	public function
	TestRouterRun():
	void {

		$RouteFile = sprintf('%s/routes-test.phson', dirname(__FILE__, 4));
		$WebRoot = sprintf('%s/www', dirname(__FILE__, 4));
		$Conf = new Datastore;
		$Handler = NULL;

		$Conf[Library::ConfRouteFile] = $RouteFile;
		$Conf[Library::ConfWebRoot] = $WebRoot;
		$Router = new Router($Conf);

		$Router->Request->ParseRequest('GET', 'avenue.test', '/index');

		ob_start();
		$Router->Run();
		$Buffer = ob_get_clean();

		$this->AssertEquals('Index Page', $Buffer);

		return;
	}


}
