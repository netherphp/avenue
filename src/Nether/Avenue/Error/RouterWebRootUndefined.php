<?php

namespace Nether\Avenue\Error;

use Exception;

class RouterWebRootUndefined
extends Exception {

	public function
	__Construct() {
		parent::__Construct('router web root is undefined');
		return;
	}

}
