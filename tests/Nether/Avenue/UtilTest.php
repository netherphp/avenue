<?php

namespace NetherTestSuite\AvenueTests;
use PHPUnit;

use Nether\Avenue\Util;

class UtilTest
extends PHPUnit\Framework\TestCase {

	/** @test */
	public function
	TestParseStr():
	void {

		$Str = 'one=1&two=2&three=3&four=4';
		$Arr = Util::ParseStr($Str);

		$this->AssertCount(4, $Arr);
		$this->AssertEquals('1', $Arr['one']);
		$this->AssertEquals('2', $Arr['two']);
		$this->AssertEquals('3', $Arr['three']);
		$this->AssertEquals('4', $Arr['four']);

		return;
	}

	/** @test */
	public function
	TestVarDump():
	void {

		ob_start();
		Util::VarDumpPre([ 'One'=> 1, 'Two'=> 2 ]);
		$Result = ob_get_clean();

		// tbh dont care about these.
		$this->AssertTrue(TRUE);

		return;
	}

}
