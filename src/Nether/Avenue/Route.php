<?php

namespace Nether\Avenue;
use Nether;

use Nether\Avenue;

use Nether\Avenue\Meta\RouteHandler;
use Nether\Common\Datafilters;
use Nether\Common\Datastore;
use Nether\Common\Package\ClassInfoPackage;
use Nether\Common\Package\PropertyInfoPackage;
use Nether\Common\Package\MethodInfoPackage;

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
	__Destruct() {

		$this->OnDestroy();
		return;
	}

	public function
	__Call(string $Name, array $Argv):
	mixed {

		if(method_exists($this, $Name))
		return $this->{$Name}(...$Argv);

		return NULL;
	}

	public function
	OnWillConfirmReady(?Avenue\Struct\ExtraData $ExtraData):
	int {
	/*// prepare any references that will be needed before the methods are
	called to do work. this mainly would be to absorb extradata in here for
	something like your app reference or whatevs. this only runs before
	queries are asked like the will confirm handlers. //*/

		return Response::CodeOK;
	}

	public function
	OnWillConfirmDone():
	void {

		return;
	}

	public function
	OnReady(?Avenue\Struct\ExtraData $ExtraData):
	void {
	/*// prepare any references that will be needed before the methods are
	called to do work. this mainly would be to absorb extradata in here for
	something like your app reference or whatevs. this only runs before the
	Router has decided it is done screwing around. //*/

		// the instance spawned for will confirm checks only ever
		// experiences to will confirm ready. the instance spawned for the
		// real run only experience the plain ready. most instances will
		// likely need to absorb something frome extra data.
		// a very typical scenerio might simply be:

		// $this->OnWillConfirmReady($ExtraData);
		// ... and then some.

		return;
	}

	public function
	OnDone():
	void {
	/*// this method should be called by whatever is being used to manage
	the route to tell the object that it is done probing it and can clean
	itself up. this is handled by the Router if you are using that. //*/

		return;
	}

	public function
	OnDestroy():
	void {

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	Goto(string $URI, string|bool $AppendGoto=FALSE):
	void {
	/*//
	@date 2022-11-11
	//*/

		$Output = $URI;

		// decide on if we want to add a goto url argument and if so what
		// it should actually be.

		if($AppendGoto === TRUE)
		$AppendGoto = Datafilters::Base64Encode(
			$this->Request->GetURL()
		);

		elseif(is_string($AppendGoto))
		$AppendGoto = Datafilters::Base64Encode($AppendGoto);

		// build final uri.

		if($AppendGoto) {
			if(str_contains($URI, '?'))
			$Output .= "&goto={$AppendGoto}";
			else
			$Output .= "?goto={$AppendGoto}";
		}

		// set the response accordingly to gtfo.

		($this->Response)
		->SetHeader('Location', $Output)
		->SetCode(Response::CodeRedirectTemp);

		exit(0);
		return;
	}

}
