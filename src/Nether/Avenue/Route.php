<?php

namespace Nether\Avenue;

use Nether\Avenue\Router;
use Nether\Avenue\Meta\RouteHandler;
use Nether\Object\Package\ClassInfoPackage;
use Nether\Object\Package\PropertyInfoPackage;
use Nether\Object\Package\MethodInfoPackage;

class Route {

	/*** example route method structure. *************************************
	**************************************************************************

	#[RouteHandler('/index')]
	public function
	Index():
	void {

		return;
	}

	#[RouteHandler('/page/:Key:')]
	public function
	Key(string $Key):
	void {

		return;
	}

	**************************************************************************
	*************************************************************************/

	use
	ClassInfoPackage,
	PropertyInfoPackage,
	MethodInfoPackage;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public RouteHandler
	$Handler;

	public Request
	$Request;

	public ?Response
	$Response;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	__Construct(RouteHandler $Handler, Request $Req, ?Response $Resp=NULL) {

		$this->Handler = $Handler;
		$this->Request = $Req;
		$this->Response = $Resp;

		return;
	}

	public function
	__Call(string $Name, array $Argv):
	mixed {

		if(method_exists($this, $Name))
		return $this->{$Name}(...$Argv);

		return NULL;
	}

}
