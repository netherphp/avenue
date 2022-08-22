<?php

namespace NetherTestSuite\AvenueTests;
use PHPUnit;

use Exception;
use SplFileInfo;
use Nether\Avenue\RouteScanner;
use Nether\Avenue\Meta\RouteHandler;
use Nether\Avenue\Error\RouteScannerDirInvalid;
use Nether\Avenue\Error\RouteScannerDirUnreadable;
use Nether\Avenue\Error\RouteScannerClassNotValid;
use Nether\Object\Datastore;

class RouteScannerTest
extends PHPUnit\Framework\TestCase {

	/**
	 * @test
	 * @runInSeparateProcess
	 */
	public function
	TestLocateRoutes():
	void {

		$Path = sprintf('%s/routes', dirname(__FILE__, 4));
		$HadExcept = NULL;

		// try a path that should be valid and working.

		try {
			$HadExcept = FALSE;
			new RouteScanner($Path);
		}

		catch(Exception $Err) {
			$HadExcept = TRUE;
		}

		$this->AssertFalse($HadExcept);

		// try a path that should be invalid.

		try {
			$HadExcept = FALSE;
			new RouteScanner('/omg/wtf/bbq');
		}

		catch(Exception $Err) {
			$HadExcept = TRUE;
			$this->AssertInstanceOf(
				RouteScannerDirInvalid::class,
				$Err
			);
		}

		$this->AssertTrue($HadExcept);

		// try a path that should be valid but not readable.

		try {
			$HadExcept = FALSE;
			chmod("{$Path}-fail", 0000);
			$Scanner = new RouteScanner("{$Path}-fail");
			chmod("{$Path}-fail}", 0666);
		}

		catch(Exception $Err) {
			$HadExcept = TRUE;
			$this->AssertInstanceOf(
				RouteScannerDirUnreadable::class,
				$Err
			);
		}

		$this->AssertTrue($HadExcept);

		return;
	}

	/**
	 * @test
	 * @runInSeparateProcess
	 */
	public function
	TestFetchFiles():
	void {

		$Path = sprintf('%s/routes', dirname(__FILE__, 4));
		$Scanner = new RouteScanner($Path);
		$Files = NULL;
		$Expect = NULL;
		$Key = NULL;
		$Value = NULL;
		$Info = NULL;

		////////

		$Files = $Scanner->FetchFilesInDir($Scanner->Directory);
		$Files->Sort()->Revalue();
		$Expect = [ 'Blog.php', 'Errors.php', 'Home.php', 'NotActuallyAnRoute.php' ];

		$this->AssertEquals(count($Expect), $Files->Count());

		foreach($Files as $Key => $Value) {
			$Info = new SplFileInfo($Value);
			$this->AssertTrue($Info->IsFile());
			$this->AssertEquals($Expect[$Key], $Info->GetBasename());
		}

		return;
	}

	/**
	 * @test
	 * @runInSeparateProcess
	 */
	public function
	TestDetermineRoutableClasses():
	void {

		$Path = sprintf('%s/routes', dirname(__FILE__, 4));
		$Scanner = new RouteScanner($Path);
		$Files = $Scanner->FetchFilesInDir($Scanner->Directory);
		$Classes = NULL;
		$Expect = NULL;
		$Key = NULL;
		$Value = NULL;

		////////

		$Classes = $Scanner->DetermineRoutableClasses($Files);
		$Classes->Sort()->Revalue();
		$Expect = [ 'TestRoutes\\Blog', 'TestRoutes\\Errors', 'TestRoutes\\Home' ];

		$this->AssertEquals(count($Expect), $Classes->Count());

		foreach($Classes as $Key => $Value) {
			$this->AssertEquals($Expect[$Key], $Value);
			$this->AssertTrue(class_exists($Value));
		}

		return;
	}

	/**
	 * @test
	 * @runInSeparateProcess
	 */
	public function
	TestDetermineRoutableMethods():
	void {

		// @todo 2022-08-22 fix method to not need run in sep process.
		// its because the array_diff method used. it should scan the file
		// for tokens.

		$Path = sprintf('%s/routes', dirname(__FILE__, 4));
		$Scanner = new RouteScanner($Path);
		$Files = $Scanner->FetchFilesInDir($Scanner->Directory);
		$Classes = $Scanner->DetermineRoutableClasses($Files);
		$Class = NULL;
		$Methods = NULL;
		$Expect = NULL;
		$Key = NULL;
		$Value = NULL;
		$HadExcept = FALSE;

		////////

		// check that we found route handlers we expect.

		$Classes->Sort()->Revalue();

		$Expect = [
			'TestRoutes\\Home'   => [ 'Index' ],
			'TestRoutes\\Errors' => [],
			'TestRoutes\\Blog'   => [ 'Index', 'ViewPost' ]
		];

		foreach($Classes as $Class) {
			$Methods = $Scanner->DetermineRoutableMethods($Class);

			$this->AssertCount(
				count($Expect[$Class]),
				$Methods
			);

			foreach($Methods as $Key => $Value) {
				/** @var MethodInfo $Value */

				$this->AssertTrue(in_array($Key, $Expect[$Class]));
				$this->AssertInstanceOf(
					RouteHandler::class,
					$Value
				);
			}
		}

		// check that we found error handlers where we expect.

		$Expect = [
			'TestRoutes\\Home'   => [ ],
			'TestRoutes\\Errors' => [ 'NotFound', 'Forbidden' ],
			'TestRoutes\\Blog'   => [ ]
		];

		foreach($Classes as $Class) {
			$Methods = $Scanner->DetermineErrorMethods($Class);

			$this->AssertCount(
				count($Expect[$Class]),
				$Methods
			);

			foreach($Methods as $Key => $Value) {
				/** @var MethodInfo $Value */

				$this->AssertTrue(in_array($Key, $Expect[$Class]));
				$this->AssertInstanceOf(
					RouteHandler::class,
					$Value
				);
			}
		}

		// try feeding it a class that doesn't extend our base. in typical
		// use this does not happen as the determine routable classes will
		// check. but you can force feed it as well.

		try {
			$HadExcept = FALSE;
			$Methods = $Scanner->DetermineRoutableMethods('SplFileInfo');
		}

		catch(Exception $Err) {
			$HadExcept = TRUE;
			$this->AssertInstanceOf(
				RouteScannerClassNotValid::class,
				$Err
			);
		}

		$this->AssertTrue($HadExcept);

		try {
			$HadExcept = FALSE;
			$Methods = $Scanner->DetermineErrorMethods('SplFileInfo');
		}

		catch(Exception $Err) {
			$HadExcept = TRUE;
			$this->AssertInstanceOf(
				RouteScannerClassNotValid::class,
				$Err
			);
		}

		$this->AssertTrue($HadExcept);

		return;
	}

}
