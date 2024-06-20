<?php

namespace NetherTestSuite\Avenue\Router;

use PHPUnit;
use Nether\Avenue;
use Nether\Common;
use NetherTestSuite\Avenue\zRoutes;

class RouterTest
extends PHPUnit\Framework\TestCase {

	static public function
	PrepareRouter():
	Avenue\Router {

		$Config = new Common\Datastore([
			Avenue\Library::ConfRouteFile => 'routes.phson',
			Avenue\Library::ConfRouteRoot => sprintf('%s/zRoutes', dirname(__FILE__, 2)),
			Avenue\Library::ConfWebRoot   => sprintf('%s/zWeb', dirname(__FILE__, 2))
		]);

		$Router = new Avenue\Router($Config);

		return $Router;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[PHPUnit\Framework\Attributes\Test]
	public function
	TestBasic1():
	void {

		$Router = static::PrepareRouter();
		$Handlers = $Router->SortHandlers()->GetHandlers();

		$this->AssertEquals('dirscan', $Router->GetSource());
		$this->AssertGreaterThan(0, $Handlers->Count());
		$this->AssertArrayHasKey('GET', $Handlers);

		return;
	}

	#[PHPUnit\Framework\Attributes\Test]
	public function
	TestVerbGet():
	void {

		$Router = static::PrepareRouter();
		$Router->Request->ParseRequest('GET', 'nether.local', '/api/test/basic');
		$Handler = $Router->Select();

		$this->AssertEquals(zRoutes\AnAPI::class, $Handler->Class);

		////////

		$Router->Response->Clear();
		$Router->Execute($Handler);
		$Result = Common\Filters\Text::DatastoreFromJSON($Router->Response->Content);

		$this->AssertEquals(0, $Result->Get('Error'));
		$this->AssertEquals('GOT', $Result->Get('Message'));
		$Router->Response->Clear();

		return;
	}

	#[PHPUnit\Framework\Attributes\Test]
	public function
	TestVerbPost():
	void {

		$Router = static::PrepareRouter();
		$Router->Request->ParseRequest('POST', 'nether.local', '/api/test/basic');
		$Handler = $Router->Select();

		$this->AssertEquals(zRoutes\AnAPI::class, $Handler->Class);

		////////

		$Router->Response->Clear();
		$Router->Execute($Handler);
		$Result = Common\Filters\Text::DatastoreFromJSON($Router->Response->Content);

		$this->AssertEquals(0, $Result->Get('Error'));
		$this->AssertEquals('POSTED', $Result->Get('Message'));
		$Router->Response->Clear();

		return;
	}

	#[PHPUnit\Framework\Attributes\Test]
	public function
	TestVerbPatch():
	void {

		$Router = static::PrepareRouter();
		$Router->Request->ParseRequest('PATCH', 'nether.local', '/api/test/basic');
		$Handler = $Router->Select();

		$this->AssertEquals(zRoutes\AnAPI::class, $Handler->Class);

		////////

		$Router->Response->Clear();
		$Router->Execute($Handler);
		$Result = Common\Filters\Text::DatastoreFromJSON($Router->Response->Content);

		$this->AssertEquals(0, $Result->Get('Error'));
		$this->AssertEquals('PATCHED', $Result->Get('Message'));
		$Router->Response->Clear();

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[PHPUnit\Framework\Attributes\Test]
	#[Common\Meta\Info('Test default handling of multipart/form-data (POST)')]
	public function
	TestVerbPostwithMultipartData():
	void {

		// at the moment we trust that PHP handles POST as it always has.
		// it is other verbs that we need to test more.

		$Router = static::PrepareRouter();
		$Router->Request->ParseRequest('POST', 'nether.local', '/api/test/basic');
		//$Router->Request->ParseRequestData(
		//	[ 'content-type' => 'multipart/form-data' ],
		//	file_get_contents(sprintf('%s/zData/multipart-form1.txt', dirname(__FILE__, 2)))
		//);

		$Router->Execute($Router->Select());
		$Result = Common\Filters\Text::DatastoreFromJSON($Router->Response->Content);

		////////

		$this->AssertEquals(0, $Result->Get('Error'));
		$this->AssertEquals('POSTED', $Result->Get('Message'));
		//$this->AssertTrue($Router->Request->File->Exists('file'));
		//$this->AssertTrue($Router->Request->Data->Exists('ID'));
		//$this->AssertTrue($Router->Request->Data->Exists('UUID'));

		//unlink($Router->Request->File['file']['tmp_name']);
		$Router->Response->Clear();

		return;
	}

	#[PHPUnit\Framework\Attributes\Test]
	#[Common\Meta\Info('Test custom implementation of multipart/form-data parsing (PATCH et al).')]
	public function
	TestVerbPatchwithMultipartData():
	void {

		// PHP 8.4 will be adding a native function to do this same thing
		// finally so we can use it on other verbs like this.

		$Router = static::PrepareRouter();
		$Router->Request->ParseRequest('PATCH', 'nether.local', '/api/test/basic');
		$Router->Request->ParseRequestData(
			[ 'content-type' => 'multipart/form-data' ],
			file_get_contents(sprintf('%s/zData/multipart-form1.txt', dirname(__FILE__, 2)))
		);

		$Router->Execute($Router->Select());
		$Result = Common\Filters\Text::DatastoreFromJSON($Router->Response->Content);

		////////

		$this->AssertEquals(0, $Result->Get('Error'));
		$this->AssertEquals('PATCHED', $Result->Get('Message'));
		$this->AssertTrue($Router->Request->File->Exists('file'));
		$this->AssertTrue($Router->Request->Data->Exists('ID'));
		$this->AssertTrue($Router->Request->Data->Exists('UUID'));

		unlink($Router->Request->File['file']['tmp_name']);
		$Router->Response->Clear();

		return;
	}

};
