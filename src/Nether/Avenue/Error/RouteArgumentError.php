<?php

namespace Nether\Avenue\Error;

use Nether\Avenue;

use Exception;

class RouteArgumentError
extends Exception {

	public function
	__Construct(Avenue\Meta\RouteHandler $Handler, Avenue\Route $Route) {

		parent::__Construct(sprintf(
			'THIS USUALLY MEANS YOU NEED REGEN ROUTES.PHSON (%s::%s)',
			$Handler->Class,
			$Handler->Method
		));

		return;
	}

}
