<?php

namespace Nether\Avenue\Error;

use Exception;

class RouteScannerDirUnreadable
extends Exception {

	public function
	__Construct(string $Dir) {
		parent::__Construct("Route Directory Invalid: {$Dir}");
		return;
	}

}
