<?php

namespace NetherTestSuite\Avenue\Route;

use PHPUnit;
use Nether\Avenue\Route;
use Nether\Avenue\Request;
use Nether\Avenue\Response;
use Nether\Avenue\Meta\RouteHandler;
use Nether\Avenue\Meta\ConfirmWillAnswerRequest;
use Nether\Common\Prototype\MethodInfo;
use Nether\Common\Datastore;

class TestRoute1
extends Route {

	#[RouteHandler('/index')]
	public function
	Index():
	void { return; }

	#[RouteHandler('/extradata1')]
	public function
	ExtraData1():
	void { return; }

	#[RouteHandler('/extradata2/:Arg:')]
	public function
	ExtraData2(Datastore $ExtraData):
	void { return; }

	#[RouteHandler('/extradata3/:arg:')]
	public function
	ExtraData3(string $Arg, Datastore $ExtraData):
	void { return; }

	#[RouteHandler('/extradata3/:arg:')]
	public function
	ExtraData4(Datastore $ExtraData, string $Arg):
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

		// test the magic method nullcase.

		$this->AssertNull($Route->DoesNotExist());

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
