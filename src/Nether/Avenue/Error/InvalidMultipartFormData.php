<?php

namespace Nether\Avenue\Error;

use Exception;

class InvalidMultipartFormData
extends Exception {

	public function
	__Construct(string $Msg = 'invalid multipart/form-data') {
		parent::__Construct($Msg);
		return;
	}

};
