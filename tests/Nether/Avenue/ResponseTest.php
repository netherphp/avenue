<?php

namespace NetherTestSuite\AvenueTests;
use PHPUnit;

use Nether\Avenue\Response;
use Nether\Object\Datafilter;

class ResponseTest
extends PHPUnit\Framework\TestCase {

	/** @test */
	public function
	TestBasic():
	void {

		$Resp = new Response();
		$this->AssertEquals(Response::CodeOK, $Resp->Code);

		////////

		$Resp->CaptureBegin();
		echo 'DOG';
		$Resp->CaptureEnd();
		$this->AssertEquals('DOG', $Resp->Content);

		// the default is to append content.

		$Resp->CaptureBegin();
		echo 'COPTER';
		$Resp->CaptureEnd();
		$this->AssertEquals('DOGCOPTER', $Resp->Content);

		// disable append with false will overwrite the buffer with only
		// the current contents.

		$Resp->CaptureBegin();
		echo 'BANANA';
		$Resp->CaptureEnd(FALSE);
		$this->AssertEquals('BANANA', $Resp->Content);

		// diable append with null with throw away what it collected
		// while doing nothing to the existing buffer.

		$Resp->CaptureBegin();
		echo 'LION';
		$Resp->CaptureEnd(NULL);
		$this->AssertEquals('BANANA', $Resp->Content);

		////////

		return;
	}

	/**
	 * @test
	 * @runInSeparateProcess
	 */
	public function
	TestRenderAndClear():
	void {

		$Resp = new Response();

		$Resp->CaptureBegin();
		echo 'BANANA';
		$Resp->CaptureEnd();

		////////

		ob_start();
		$Resp->Render();
		$Output = ob_get_clean();

		$this->AssertEquals('BANANA', $Resp->Content);
		$this->AssertEquals('BANANA', $Output);

		$Resp->Clear();
		$this->AssertEquals('', $Resp->Content);

		return;
	}

	/** @test */
	public function
	TestCode():
	void {

		$Resp = new Response;
		$this->AssertEquals(Response::CodeOK, $Resp->Code);

		$Resp->SetCode(Response::CodeNotFound);
		$this->AssertEquals(Response::CodeNotFound, $Resp->Code);

		return;
	}

	/** @test */
	public function
	TestContentType():
	void {

		$Resp = new Response;
		$this->AssertEquals(Response::ContentTypeHTML, $Resp->ContentType);

		$Resp->SetContentType(Response::ContentTypeJSON);
		$this->AssertEquals(Response::ContentTypeJSON, $Resp->ContentType);

		return;
	}

}
