<?php

namespace Nether\Avenue\Struct;

class RouteHandlerArg {

	public string
	$Name;

	public string
	$Type;

	public mixed
	$Value;

	public function
	__Construct(string $Name, string $Type='mixed', mixed $Value=NULL) {

		$this->Name = $Name;
		$this->Type = $Type;
		$this->Value = $Value;

		return;
	}

}
