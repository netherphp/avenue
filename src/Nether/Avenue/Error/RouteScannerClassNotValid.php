<?php

namespace Nether\Avenue\Error;

use Exception;

class RouteScannerClassNotValid
extends Exception {

	public function
	__Construct(string $ClassName) {
		parent::__Construct("class {$ClassName} is not a valid route class.");
		return;
	}

}
