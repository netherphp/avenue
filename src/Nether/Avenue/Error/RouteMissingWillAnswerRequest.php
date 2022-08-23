<?php

namespace Nether\Avenue\Error;

use Exception;

class RouteMissingWillAnswerRequest
extends Exception {

	public function
	__Construct(string $Method, string $OtherCall) {
		parent::__Construct("route method {$Method} is missing {$OtherCall} counterpart.");
		return;
	}

}
