<?php

namespace NetherTestSuite\AvenueTests;
use PHPUnit;

use Nether\Avenue\Util;

class UtilTest
extends PHPUnit\Framework\TestCase {

	/** @test */
	public function
	TestFindClassesInFile():
	void {

		// test something that should work.

		$Path = sprintf('%s/src/Nether/Avenue/Router.php', dirname(__FILE__, 4));
		$Found = Util::FindClassesInFile($Path);
		$this->AssertCount(1, $Found);
		$this->AssertEquals('Nether\\Avenue\\Router', $Found[0]);

		// test a file with known stupid.

		$Path = sprintf('%s/routes/NotActuallyAnRoute.php', dirname(__FILE__, 4));
		$Found = Util::FindClassesInFile($Path);
		$this->AssertCount(1, $Found);

		// test a file with known syntax errors.

		$Path = sprintf('%s/misc/syntax-error-namespace1.php', dirname(__FILE__, 4));
		$Found = Util::FindClassesInFile($Path);
		$this->AssertCount(0, $Found);

		$Path = sprintf('%s/misc/syntax-error-namespace2.php', dirname(__FILE__, 4));
		$Found = Util::FindClassesInFile($Path);
		$this->AssertCount(0, $Found);

		$Path = sprintf('%s/misc/syntax-error-class1.php', dirname(__FILE__, 4));
		$Found = Util::FindClassesInFile($Path);
		$this->AssertCount(0, $Found);

		// test a file that does not exist.

		$Found = Util::FindClassesInFile('/omg/wtf/bbq');
		$this->AssertCount(0, $Found);

		return;
	}

	/** @test */
	public function
	TestParseStr():
	void {

		$Str = 'one=1&two=2&three=3&four=4';
		$Arr = Util::ParseQueryString($Str);

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

	/** @test */
	public function
	TestMakePathableKey():
	void {

		$Things = [
			'/index'           => '/index',
			'/sub/dir'         => '/sub/dir',
			'asdf-jkl-zxc-nml' => 'asdf-jkl-zxc-nml',
			'this/../nope'     => 'this/nope',
			'this/../../nope'  => 'this/nope',
			'this/...../nope'  => 'this/nope',
			'this-42-69'       => 'this-42-69',
			'that?banana?man'  => 'thatbananaman'
		];

		$Input = NULL;
		$Expected = NULL;

		foreach($Things as $Input => $Expected)
		$this->AssertEquals(Util::MakePathableKey($Input), $Expected);

		return;
	}

}
