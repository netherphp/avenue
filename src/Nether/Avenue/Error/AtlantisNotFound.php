<?php

namespace Nether\Avenue\Error;

use Exception;

class AtlantisNotFound
extends Exception {

	public function
	__Construct() {
		parent::__Construct('Nether Atlantis does not seem to be installed.');
		return;
	}

}
