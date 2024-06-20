<?php

namespace NetherTestSuite\Avenue\Struct;

use PHPUnit;
use Nether\Avenue;

use Throwable;

class FormDataTest
extends PHPUnit\Framework\TestCase {

	#[PHPUnit\Framework\Attributes\Test]
	public function
	TestFromMultipartRaw():
	void {

		$ReqData = file_get_contents(sprintf(
			'%s/zData/multipart-form1.txt',
			dirname(__FILE__, 2)
		));

		$Parsed = Avenue\Struct\FormData::FromMultipartRaw($ReqData);

		// check the basics of what was sent.

		$this->AssertEquals('YOLOSWAG', $Parsed->GetBoundaryMarker());
		$this->AssertCount(2, $Parsed->Fields);
		$this->AssertCount(1, $Parsed->Files);

		$this->AssertTrue($Parsed->Fields->HasKey('ID'));
		$this->AssertTrue($Parsed->Fields->HasKey('UUID'));
		$this->AssertTrue($Parsed->Files->HasKey('File'));

		// check that the fields look like fields.

		$this->AssertEquals(
			'69',
			$Parsed->Fields['ID']
		);

		$this->AssertEquals(
			'018c701f-d06e-7240-be8f-f9b74c1a5ba7',
			$Parsed->Fields['UUID']
		);

		// check that the file looks like a file.

		$File = $Parsed->Files->Get('File');
		$this->AssertArrayHasKey('name', $File);
		$this->AssertArrayHasKey('tmp_name', $File);
		$this->AssertArrayHasKey('size', $File);

		$this->AssertFileExists($File['tmp_name']);
		$this->AssertEquals(4, $File['size']);
		$this->AssertEquals(4, filesize($File['tmp_name']));

		unlink($File['tmp_name']);

		return;
	}

	#[PHPUnit\Framework\Attributes\Test]
	public function
	TestFromMultipartRawBadong1():
	void {

		$Input = '';
		$Thrown = NULL;

		////////

		try { Avenue\Struct\FormData::FromMultipartRaw($Input); }
		catch(Throwable $Thrown) { }

		$this->AssertInstanceOf(
			Avenue\Error\InvalidMultipartFormData::class,
			$Thrown
		);

		return;
	}

	#[PHPUnit\Framework\Attributes\Test]
	public function
	TestFromMultipartRawBadong2():
	void {

		$Input = "no matter how we roll the dice\n";
		$Thrown = NULL;

		////////

		try { Avenue\Struct\FormData::FromMultipartRaw($Input); }
		catch(Throwable $Thrown) { }

		$this->AssertInstanceOf(
			Avenue\Error\InvalidMultipartFormData::class,
			$Thrown
		);

		return;
	}

	#[PHPUnit\Framework\Attributes\Test]
	public function
	TestFromMultipartRawBadong3():
	void {

		$Input = "no matter how we roll the dice\r\n";
		$Thrown = NULL;

		////////

		try { Avenue\Struct\FormData::FromMultipartRaw($Input); }
		catch(Throwable $Thrown) { }

		$this->AssertInstanceOf(
			Avenue\Error\InvalidMultipartFormData::class,
			$Thrown
		);

		return;
	}

	#[PHPUnit\Framework\Attributes\Test]
	public function
	TestFromMultipartFucked1():
	void {

		// the file input is malformed but the fields are ok.

		$ReqData = file_get_contents(sprintf(
			'%s/zData/multipart-form-fucked1.txt',
			dirname(__FILE__, 2)
		));

		$Parsed = Avenue\Struct\FormData::FromMultipartRaw($ReqData);

		// check the basics of what was sent.

		$this->AssertEquals('YOLOSWAG', $Parsed->GetBoundaryMarker());
		$this->AssertCount(1, $Parsed->Fields);
		$this->AssertCount(0, $Parsed->Files);

		$this->AssertTrue($Parsed->Fields->HasKey('ID'));
		$this->AssertFalse($Parsed->Files->HasKey('File'));

		// check that the fields look like fields.

		$this->AssertEquals('69', $Parsed->Fields['ID']);

		return;
	}

	#[PHPUnit\Framework\Attributes\Test]
	public function
	TestFromMultipartFucked2():
	void {

		// the file is ok but the fields are malformed.

		$ReqData = file_get_contents(sprintf(
			'%s/zData/multipart-form-fucked2.txt',
			dirname(__FILE__, 2)
		));

		$Parsed = Avenue\Struct\FormData::FromMultipartRaw($ReqData);

		// check the basics of what was sent.

		$this->AssertEquals('YOLOSWAG', $Parsed->GetBoundaryMarker());
		$this->AssertCount(0, $Parsed->Fields);
		$this->AssertCount(1, $Parsed->Files);

		$this->AssertFalse($Parsed->Fields->HasKey('ID'));
		$this->AssertFalse($Parsed->Fields->HasKey('UUID'));
		$this->AssertTrue($Parsed->Files->HasKey('File'));

		// check that the file looks like a file.

		$File = $Parsed->Files->Get('File');
		$this->AssertArrayHasKey('name', $File);
		$this->AssertArrayHasKey('tmp_name', $File);
		$this->AssertArrayHasKey('size', $File);

		$this->AssertFileExists($File['tmp_name']);
		$this->AssertEquals(4, $File['size']);
		$this->AssertEquals(4, filesize($File['tmp_name']));

		unlink($File['tmp_name']);

		return;
	}

	#[PHPUnit\Framework\Attributes\Test]
	public function
	TestFromMultipartFucked3():
	void {

		// the request is malformed by never ending technically.

		$ReqData = file_get_contents(sprintf(
			'%s/zData/multipart-form-fucked3.txt',
			dirname(__FILE__, 2)
		));

		$Parsed = Avenue\Struct\FormData::FromMultipartRaw($ReqData);

		// check the basics of what was sent.

		$this->AssertEquals('YOLOSWAG', $Parsed->GetBoundaryMarker());
		$this->AssertCount(0, $Parsed->Fields);
		$this->AssertCount(0, $Parsed->Files);

		return;
	}

	#[PHPUnit\Framework\Attributes\Test]
	public function
	TestFromMultipartFucked4():
	void {

		// the request is malformed why does nothing have a name

		$ReqData = file_get_contents(sprintf(
			'%s/zData/multipart-form-fucked4.txt',
			dirname(__FILE__, 2)
		));

		$Parsed = Avenue\Struct\FormData::FromMultipartRaw($ReqData);

		// check the basics of what was sent.

		$this->AssertEquals('YOLOSWAG', $Parsed->GetBoundaryMarker());
		$this->AssertCount(0, $Parsed->Fields);
		$this->AssertCount(0, $Parsed->Files);

		return;
	}

};
