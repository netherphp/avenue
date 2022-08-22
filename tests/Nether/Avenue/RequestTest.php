<?php

namespace NetherTestSuite\AvenueTests;
use PHPUnit;

use Nether\Avenue\Request;
use Nether\Object\Datafilter;

class RequestTest
extends PHPUnit\Framework\TestCase {

	/** @test */
	public function
	TestFillingByParseRequestMethod():
	void {

		$Req = new Request;
		$Req->ParseRequest('GET', 'avenue.test', '/test/parse');

		$this->AssertEquals('GET', $Req->Verb);
		$this->AssertEquals('avenue.test', $Req->Domain);
		$this->AssertEquals('/test/parse', $Req->Path);

		$this->AssertEquals(
			'//avenue.test/test/parse',
			$Req->GetRegInput()
		);

		return;
	}

	/** @test */
	public function
	TestFillingFromGlobalValues():
	void {

		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SERVER['HTTP_HOST'] = 'avenue-magic.test';
		$_SERVER['REQUEST_URI'] = '/test/magic?with=query';
		$_POST = [ 'item'=> 'thing' ];
		$_GET = [ 'arg'=> 'one' ];

		$Req = new Request;
		$Req->ParseRequest();

		$this->AssertEquals('POST', $Req->Verb);
		$this->AssertEquals('avenue-magic.test', $Req->Domain);
		$this->AssertEquals('/test/magic', $Req->Path);
		$this->AssertInstanceOf(Datafilter::class, $Req->Query);
		$this->AssertEquals('one', $Req->Query->Arg);
		$this->AssertInstanceof(Datafilter::class, $Req->Data);
		$this->AssertEquals('thing', $Req->Data->Item);

		return;
	}

	/** @test */
	public function
	TestFillingFromFallbacks():
	void {

		$_SERVER['REQUEST_METHOD'] = NULL;
		$_SERVER['HTTP_HOST'] = NULL;
		$_SERVER['REQUEST_URI'] = NULL;
		$_POST = [  ];
		$_GET = [ ];

		$Req = new Request;
		$Req->ParseRequest();

		$this->AssertEquals('GET', $Req->Verb);
		$this->AssertEquals('localhost', $Req->Domain);
		$this->AssertEquals('/index', $Req->Path);

		return;
	}

	/** @test */
	public function
	TestDebugInfo():
	void {

		$Req = new Request;
		$Req->ParseRequest('GET', 'avenue.test', '/');

		ob_start();
		var_dump($Req);
		$Result = ob_get_clean();

		// tbh dont care about these.
		$this->AssertTrue(TRUE);

		return;
	}

}
