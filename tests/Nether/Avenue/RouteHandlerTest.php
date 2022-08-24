<?php

namespace NetherTestSuite\Avenue\RouteHandler;
use PHPUnit;

use Exception;
use Nether\Avenue\Route;
use Nether\Avenue\Request;
use Nether\Avenue\Response;
use Nether\Avenue\Meta\RouteHandler;
use Nether\Avenue\Meta\ErrorHandler;
use Nether\Avenue\Meta\ConfirmWillAnswerRequest;

class TestRoute1
extends Route {

	public function
	Allow(...$Argv):
	int {

		return Response::CodeOK;
	}

	public function
	Deny(...$Argv):
	int {

		return Response::CodeNope;
	}

	public function
	Forbidden(...$Argv):
	int {

		return Response::CodeForbidden;
	}

	#[RouteHandler('/index')]
	public function
	Index():
	void { return; }

	#[RouteHandler('/index', 'avenue.test')]
	public function
	Domain():
	void { return; }

	#[RouteHandler('/index', Verb: 'POST')]
	public function
	VerbPost():
	void { return; }

	#[RouteHandler]
	public function
	DefaultEverything():
	void { return; }

	#[RouteHandler('/article/:Alias:')]
	public function
	Article(string $Alias):
	void { return; }

	#[RouteHandler('/weak/:ID:')]
	public function
	WeakArgs($ID):
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
	int {

		return Response::CodeNope;
	}

	#[RouteHandler('/page/invalidconfirm')]
	#[ConfirmWillAnswerRequest('DerpDerpDerp')]
	public function
	PageInvalidConfirm():
	void { return; }

	#[ErrorHandler(Response::CodeNotFound)]
	public function
	NotFound():
	void { return; }

}

class RouteHandlerTest
extends PHPUnit\Framework\TestCase {

	/** @test */
	public function
	TestBasicPathMatch():
	void {

		$Method = TestRoute1::GetMethodInfo('Index');
		$Req = new Request;
		$Handler = $Method->GetAttribute(RouteHandler::class);

		$Req->ParseRequest('GET', 'avenue.test', '/index');
		$this->AssertTrue($Handler->CanAnswerRequest($Req));

		$Req->ParseRequest('GET', 'avenue.test', '/nope');
		$this->AssertFalse($Handler->CanAnswerRequest($Req));

		$this->AssertEquals(
			"NetherTestSuite\\Avenue\\RouteHandler\\TestRoute1::Index",
			$Handler->GetCallableName()
		);

		////////

		$Method = TestRoute1::GetMethodInfo('DefaultEverything');
		$Handler = $Method->GetAttribute(RouteHandler::class);

		$Req->ParseRequest('GET', 'avenue.test', '/index');
		$this->AssertTrue($Handler->CanAnswerRequest($Req));

		$Req->ParseRequest('GET', 'avenue.test', '/nope');
		$this->AssertTrue($Handler->CanAnswerRequest($Req));

		$this->AssertEquals(
			"NetherTestSuite\\Avenue\\RouteHandler\\TestRoute1::DefaultEverything",
			$Handler->GetCallableName()
		);

		////////

		return;
	}

	/** @test */
	public function
	TestSlottedPathMatch():
	void {

		$Method = TestRoute1::GetMethodInfo('Article');
		$Req = new Request;
		$Handler = $Method->GetAttribute(RouteHandler::class);

		$Req->ParseRequest('GET', 'avenue.test', '/article/baseball');
		$this->AssertTrue($Handler->CanAnswerRequest($Req));
		$this->AssertEquals('baseball', $Handler->Args['Alias']->Value);

		$Req->ParseRequest('GET', 'avenue.test', '/article/42');
		$this->AssertTrue($Handler->CanAnswerRequest($Req));
		$this->AssertEquals('42', $Handler->Args['Alias']->Value);

		$Req->ParseRequest('GET', 'avenue.test', '/nope');
		$this->AssertFalse($Handler->CanAnswerRequest($Req));

		return;
	}

	/** @test */
	public function
	TestDomainMatch():
	void {

		$Method = TestRoute1::GetMethodInfo('Domain');
		$Req = new Request;
		$Handler = $Method->GetAttribute(RouteHandler::class);
		/** @var RouteHandler $Handler */

		$Req->ParseRequest('GET', 'avenue.test', '/index');
		$this->AssertTrue($Handler->CanAnswerRequest($Req));

		$Req->ParseRequest('GET', 'blahvenue.test', '/index');
		$this->AssertFalse($Handler->CanAnswerRequest($Req));

		return;
	}

	/** @test */
	public function
	TestVerbMatch():
	void {

		$Method = TestRoute1::GetMethodInfo('VerbPost');
		$Req = new Request;
		$Handler = $Method->GetAttribute(RouteHandler::class);
		/** @var RouteHandler $Handler */

		$Req->ParseRequest('POST', 'avenue.test', '/index');
		$this->AssertTrue($Handler->CanAnswerRequest($Req));

		$Req->ParseRequest('GET', 'avenue.test', '/index');
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
		$HadException = FALSE;

		// try a page that will accept the request.

		$Handler = $Methods['Index']->GetAttribute(RouteHandler::class);
		$Req->ParseRequest('GET', 'avenue.test', '/index');
		$this->AssertTrue($Handler->CanAnswerRequest($Req));
		$this->AssertTrue($Handler->WillAnswerRequest($Req, $Resp));
		$this->AssertEquals(Response::CodeOK, $Resp->Code);

		$Handler = $Methods['PageAllowed']->GetAttribute(RouteHandler::class);
		$Req->ParseRequest('GET', 'avenue.test', '/page/allowed');
		$this->AssertTrue($Handler->CanAnswerRequest($Req));
		$this->AssertTrue($Handler->WillAnswerRequest($Req, $Resp));
		$this->AssertEquals(Response::CodeOK, $Resp->Code);

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

		// try a page inconfigured correctly.

		$Handler = $Methods['PageInvalidConfirm']->GetAttribute(RouteHandler::class);
		$Req->ParseRequest('GET', 'avenue.test', '/page/invalidconfirm');
		$this->AssertTrue($Handler->CanAnswerRequest($Req));

		try {
			$HadException = FALSE;
			$this->AssertFalse($Handler->WillAnswerRequest($Req, $Resp));
		}

		catch(Exception $Err) {
			$HadException = TRUE;
		}

		$this->AssertEquals(Response::CodeServerError, $Resp->Code);
		$this->AssertTrue($HadException);

		return;
	}

	/** @test */
	public function
	TestSortString():
	void {

		$Method1 = TestRoute1::GetMethodInfo('Index');
		$Handler1 = $Method1->GetAttribute(RouteHandler::class);

		$Method2 = TestRoute1::GetMethodInfo('Article');
		$Handler2 = $Method2->GetAttribute(RouteHandler::class);

		$this->AssertEquals(
			'nethertestsuite\\avenue\\routehandler\\testroute1-s-s',
			$Handler1->Sort
		);

		$this->AssertEquals(
			'nethertestsuite\\avenue\\routehandler\\testroute1-s-s-w',
			$Handler2->Sort
		);

		return;
	}

	/** @test */
	public function
	TestWeakArguments():
	void {

		$Method = TestRoute1::GetMethodInfo('WeakArgs');
		$Handler = $Method->GetAttribute(RouteHandler::class);
		$Req = new Request;

		$Req->ParseRequest('GET', 'avenue.test', '/weak/42');
		$this->AssertTrue($Handler->CanAnswerRequest($Req));
		$this->AssertEquals('42', $Handler->Args['ID']->Value);

		$Req->ParseRequest('GET', 'avenue.test', '/weak/sauce');
		$this->AssertTrue($Handler->CanAnswerRequest($Req));
		$this->AssertEquals('sauce', $Handler->Args['ID']->Value);

		return;
	}

	/** @test */
	public function
	TestNotFound():
	void {

		$Method = TestRoute1::GetMethodInfo('NotFound');
		$Handler = $Method->GetAttribute(ErrorHandler::class);

		$this->AssertEquals($Handler->Code, Response::CodeNotFound);

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
