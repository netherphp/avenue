<?php

namespace Nether\Avenue\Meta;

use Attribute;
use Nether\Avenue\Response;

#[Attribute(Attribute::TARGET_METHOD)]
class ErrorHandler
extends RouteHandler {

	public int
	$Code = Response::CodeBadRequest;

	public function
	__Construct(int $Code) {

		$this->Code = $Code;
		$this->Path = '';

		return;
	}

}
