<?php

namespace NetherTestSuite\Avenue\RouteHandler;

use PHPUnit;
use Nether\Avenue\Route;
use Nether\Avenue\Request;
use Nether\Avenue\Response;
use Nether\Avenue\Meta\RouteHandler;
use Nether\Avenue\Meta\ConfirmWillAnswerRequest;

class TestRoute1
extends Route {

	public function
	Allow():
	?bool {

		return TRUE;
	}

	public function
	Deny():
	?bool {

		return FALSE;
	}

	public function
	Forbidden():
	?bool {

		$this->Response->SetCode(Response::CodeForbidden);
		return NULL;
	}

	#[RouteHandler('/index')]
	public function
	Index():
	void { return; }

	#[RouteHandler('/article/:Alias:')]
	public function
	Article(string $Alias):
	void { return; }

	#[RouteHandler('/user/:ID:')]
	public function
	User(int $ID):
	void { return; }

	#[RouteHandler('/page/allowed')]
	#[ConfirmWillAnswerRequest('Allow')]
	public function
	PageAllowed():
	void { return; }

	#[RouteHandler('/page/denied')]
	#[ConfirmWillAnswerRequest('Deny')]
	public function
	PageDenied():
	void { return; }

	#[RouteHandler('/page/forbidden')]
	#[ConfirmWillAnswerRequest('Forbidden')]
	public function
	PageForbidden():
	void { return; }

	#[RouteHandler('/page/confirm')]
	#[ConfirmWillAnswerRequest]
	public function
	PageConfirm():
	void { return; }

	public function
	PageConfirmWillAnswerRequest():
	bool {

		return FALSE;
	}

}

class RouteHandlerTest
extends PHPUnit\Framework\TestCase {

	/** @test */
	public function
	TestBasic1():
	void {


		$Method = TestRoute1::GetMethodInfo('Index');
		$Req = new Request;
		$Handler = NULL;

		$Req->ParseRequest('GET', 'avenue.test', '/index');
		$Handler = $Method->GetAttribute(RouteHandler::class);
		$this->AssertTrue($Handler->CanAnswerRequest($Req));

		$Req->ParseRequest('GET', 'avenue.test', '/nope');
		$Handler = $Method->GetAttribute(RouteHandler::class);
		$this->AssertFalse($Handler->CanAnswerRequest($Req));

		return;
	}

	/** @test */
	public function
	TestBasic2():
	void {

		$Method = TestRoute1::GetMethodInfo('Article');
		$Req = new Request;
		$Handler = NULL;

		$Req->ParseRequest('GET', 'avenue.test', '/article/baseball');
		$Handler = $Method->GetAttribute(RouteHandler::class);
		$this->AssertTrue($Handler->CanAnswerRequest($Req));

		$Req->ParseRequest('GET', 'avenue.test', '/nope');
		$Handler = $Method->GetAttribute(RouteHandler::class);
		$this->AssertFalse($Handler->CanAnswerRequest($Req));

		return;
	}

	/** @test */
	public function
	TestCanAnswerRequests():
	void {

		$Method = TestRoute1::GetMethodInfo('User');
		$Req = new Request;
		$Handler = $Method->GetAttribute(RouteHandler::class);
		/** @var RouteHandler $Handler */

		$Req->ParseRequest('GET', 'avenue.test', '/user/42');
		$this->AssertTrue($Handler->CanAnswerRequest($Req));
		$this->AssertEquals(42, $Handler->Args['ID']->Value);

		$Req->ParseRequest('GET', 'avenue.test', '/user/nope');
		$this->AssertTrue($Handler->CanAnswerRequest($Req));
		$this->AssertEquals(0, $Handler->Args['ID']->Value);

		$Req->ParseRequest('GET', 'avenue.test', '/user/42nope');
		$this->AssertTrue($Handler->CanAnswerRequest($Req));
		$this->AssertEquals(0, $Handler->Args['ID']->Value);

		$Req->ParseRequest('GET', 'avenue.test', '/nope');
		$this->AssertFalse($Handler->CanAnswerRequest($Req));
		$this->AssertEquals(0, $Handler->Args['ID']->Value);

		return;
	}

	/** @test */
	public function
	TestWillAnswerRequests():
	void {

		$Req = new Request;
		$Resp = new Response;
		$Methods = TestRoute1::GetMethodsWithAttribute(RouteHandler::class);

		// try a page that will accept the request.

		$Handler = $Methods['Index']->GetAttribute(RouteHandler::class);
		$Req->ParseRequest('GET', 'avenue.test', '/index');
		$this->AssertTrue($Handler->CanAnswerRequest($Req));
		$this->AssertTrue($Handler->WillAnswerRequest($Req, $Resp));

		$Handler = $Methods['PageAllowed']->GetAttribute(RouteHandler::class);
		$Req->ParseRequest('GET', 'avenue.test', '/page/allowed');
		$this->AssertTrue($Handler->CanAnswerRequest($Req));
		$this->AssertTrue($Handler->WillAnswerRequest($Req, $Resp));

		// try a page that will pass on handling the request.

		$Handler = $Methods['PageDenied']->GetAttribute(RouteHandler::class);
		$Req->ParseRequest('GET', 'avenue.test', '/page/denied');
		$this->AssertTrue($Handler->CanAnswerRequest($Req));
		$this->AssertFalse($Handler->WillAnswerRequest($Req, $Resp));

		// try a page that will handle but rejects access.

		$Handler = $Methods['PageForbidden']->GetAttribute(RouteHandler::class);
		$Req->ParseRequest('GET', 'avenue.test', '/page/forbidden');
		$this->AssertTrue($Handler->CanAnswerRequest($Req));
		$this->AssertNull($Handler->WillAnswerRequest($Req, $Resp));
		$this->AssertEquals(Response::CodeForbidden, $Resp->Code);

		// try a page using the default confirm method.

		$Handler = $Methods['PageConfirm']->GetAttribute(RouteHandler::class);
		$Req->ParseRequest('GET', 'avenue.test', '/page/confirm');
		$this->AssertTrue($Handler->CanAnswerRequest($Req));
		$this->AssertFalse($Handler->WillAnswerRequest($Req, $Resp));

		return;
	}

	/** @test */
	public function
	TestDebugInfo():
	void {

		$Handler = new RouteHandler('/', 'avenue.test');

		ob_start();
		echo (string)$Handler;
		$Buffer = ob_get_clean();

		$this->AssertTrue(TRUE);

		return;
	}

}
