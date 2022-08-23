<?php

namespace NetherTestSuite\AvenueTests;
use PHPUnit;

use Nether\Avenue\Library;
use Nether\Object\Datastore;

class LibraryTest
extends PHPUnit\Framework\TestCase {

	/** @test */
	public function
	TestInit():
	void {

		$Config = new Datastore;
		$this->AssertCount(0, $Config);

		Library::Init($Config);
		$this->AssertTrue(count($Config) >= 5);

		return;
	}


}
