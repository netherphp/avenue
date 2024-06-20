<?php

namespace NetherTestSuite\Avenue\Struct;

use PHPUnit;
use Nether\Avenue\Struct\FormData;

class FormDataTest
extends PHPUnit\Framework\TestCase {

	#[PHPUnit\Framework\Attributes\Test]
	public function
	TestDigestion():
	void {

		$ReqData = file_get_contents(sprintf(
			'%s/testdata/misc/multipart-form1.txt',
			dirname(__FILE__, 3)
		));

		$Parsed = FormData::FromMultipartRaw($ReqData);

		// check the basics of what was sent.

		$this->AssertEquals('YOLOSWAG', $Parsed->GetBoundaryMarker());
		$this->AssertCount(2, $Parsed->GetFieldsArray());
		$this->AssertCount(1, $Parsed->GetFilesArray());

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

};
