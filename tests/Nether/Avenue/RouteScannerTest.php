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

	/** @test */
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
		// the library performs fine on windows but this hack to chmod
		// a file to be unreadable just for this test does not. on github
		// its running both ubuntu-latest and windows-latest so it'll get
		// caught if its an issue.

		if(PHP_OS_FAMILY !== 'Windows') {
			try {
				$HadExcept = FALSE;
				chmod("{$Path}-fail", 0000);
				$Scanner = new RouteScanner("{$Path}-fail");
			}

			catch(Exception $Err) {
				$HadExcept = TRUE;
				$this->AssertInstanceOf(
					RouteScannerDirUnreadable::class,
					$Err
				);
			}

			chmod("{$Path}-fail", 0777);
			$this->AssertTrue($HadExcept);
		}

		return;
	}

	/** @test */
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
		$Expect = [
			'Blog.php',
			'Dashboard.php',
			'DeepRoute.php',
			'Errors.php',
			'Home.php',
			'NotActuallyAnRoute.php',
			'Sorts.php'
		];

		$this->AssertEquals(count($Expect), $Files->Count());

		foreach($Files as $Key => $Value) {
			$Info = new SplFileInfo($Value);
			$this->AssertTrue($Info->IsFile());
			$this->AssertEquals($Expect[$Key], $Info->GetBasename());
		}

		return;
	}

	/** @test */
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
		$Expect = [
			'TestRoutes\\Blog',
			'TestRoutes\\Dashboard',
			'TestRoutes\\Errors',
			'TestRoutes\\Home',
			'TestRoutes\\Deep\\Deeper\\DeepRoute',
			'TestRoutes\\Sorts'
		];

		sort($Expect);
		$this->AssertEquals(count($Expect), $Classes->Count());

		foreach($Classes as $Key => $Value) {
			$this->AssertEquals($Expect[$Key], $Value);
			$this->AssertTrue(class_exists($Value));
		}

		return;
	}

	/** @test */
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
			'TestRoutes\\Home'   => [ 'Index', 'About' ],
			'TestRoutes\\Errors' => [ 'NotFound', 'Forbidden' ],
			'TestRoutes\\Blog'   => [ 'Index', 'ViewPost' ],
			'TestRoutes\\Dashboard' => [ 'FailConfirm', 'SingleConfirm', 'DoubleConfirm' ],
			'TestRoutes\\Deep\\Deeper\\DeepRoute' => [ 'SoDeep' ],
			'TestRoutes\\Sorts' => [ 'SortA', 'SortB', 'SortC', 'SortX', 'SortY' ,'SortZ' ]
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
			'TestRoutes\\Home'      => [ ],
			'TestRoutes\\Errors'    => [ 'NotFound', 'Forbidden' ],
			'TestRoutes\\Blog'      => [ ],
			'TestRoutes\\Dashboard' => [ ],
			'TestRoutes\\Deep\\Deeper\\DeepRoute' => [ ],
			'TestRoutes\\Sorts'     => [ ]
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

	/** @test */
	public function
	TestGenerate():
	void {

		$Path = sprintf('%s/routes', dirname(__FILE__, 4));
		$Scanner = new RouteScanner($Path);
		$Map = $Scanner->Generate();
		$Verbs = NULL;
		$Handlers = NULL;
		$Handler = NULL;

		////////

		$Verbs = [ 'GET' ];

		$RouteHandlers = [
			'TestRoutes\\Home::Index',
			'TestRoutes\\Home::About',
			'TestRoutes\\Blog::Index',
			'TestRoutes\\Blog::ViewPost',
			'TestRoutes\\Dashboard::FailConfirm',
			'TestRoutes\\Dashboard::SingleConfirm',
			'TestRoutes\\Dashboard::DoubleConfirm',
			'TestRoutes\\Deep\\Deeper\\DeepRoute::SoDeep',
			'TestRoutes\\Sorts::SortA',
			'TestRoutes\\Sorts::SortB',
			'TestRoutes\\Sorts::SortC',
			'TestRoutes\\Sorts::SortX',
			'TestRoutes\\Sorts::SortY',
			'TestRoutes\\Sorts::SortZ'
		];

		$ErrorHandlers = [
			'TestRoutes\\Errors::NotFound',
			'TestRoutes\\Errors::Forbidden'
		];

		$this->AssertTrue($Map->HasKey('Verbs'));
		$this->AssertTrue($Map->HasKey('Errors'));
		$this->AssertCount(count($Verbs), $Map['Verbs']);

		foreach($Map['Verbs'] as $Handlers) {
			$this->AssertCount(
				count($RouteHandlers),
				$Handlers
			);

			foreach($Handlers as $Handler) {
				$this->assertContains(
					"{$Handler->Class}::{$Handler->Method}",
					$RouteHandlers
				);
			}
		}

		$this->AssertCount(
			count($ErrorHandlers),
			$Map['Errors']
		);

		foreach($Map['Errors'] as $Handler) {
			$this->assertContains(
				"{$Handler->Class}::{$Handler->Method}",
				$ErrorHandlers
			);
		}

		return;
	}

}
