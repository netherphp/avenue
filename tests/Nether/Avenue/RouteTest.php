<?php

namespace NetherTestSuite\Avenue\Route;

use PHPUnit;
use Nether\Avenue\Route;
use Nether\Avenue\Request;
use Nether\Avenue\Response;
use Nether\Avenue\Meta\RouteHandler;
use Nether\Object\Prototype\MethodInfo;

class TestRoute1
extends Route {

	#[RouteHandler('/index')]
	public function
	Index():
	void { return; }

}

class RouteTest
extends PHPUnit\Framework\TestCase {

	/** @test */
	public function
	TestBasic():
	void {

		$Handler = new RouteHandler('/index', 'avenue.test');
		$Req = new Request;
		$Resp = new Response;
		$Route = new TestRoute1($Handler, $Req, $Resp);

		$this->AssertInstanceOf(RouteHandler::class, $Route->Handler);
		$this->AssertInstanceOf(Request::class, $Route->Request);
		$this->AssertInstanceOf(Response::class, $Route->Response);

		$this->AssertTrue($Handler === $Route->Handler);
		$this->AssertTrue($Req === $Route->Request);
		$this->AssertTrue($Resp === $Route->Response);

		return;
	}

	/** @test */
	public function
	TestBasic2():
	void {



		$this->AssertTrue(TRUE);
		return;
	}

}
