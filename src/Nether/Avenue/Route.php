<?php

namespace Nether\Avenue;

use Nether\Avenue\Meta\RouteHandler;
use Nether\Object\Datastore;
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
	OnWillConfirmReady(?Datastore $ExtraData):
	void {
	/*// prepare any references that will be needed before the methods are
	called to do work. this mainly would be to absorb extradata in here for
	something like your app reference or whatevs. this only runs before
	queries are asked like the will confirm handlers. //*/

		return;
	}

	public function
	OnWillConfirmDone():
	void {

		return;
	}

	public function
	OnReady(?Datastore $ExtraData):
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
	Goto(string $URI, string $AppendGoto=''):
	void {
	/*//
	@date 2022-11-11
	//*/

		if($AppendGoto) {
			if($AppendGoto === 'nether://self')
			$AppendGoto = $this->GetEncodedURL();
			else
			$AppendGoto = base64_encode($AppendGoto);

			// repare the final header url.

			if(strpos($URI,'?') === FALSE)
			$URI .= "?goto={$AppendGoto}";
			else
			$URI .= "&goto={$AppendGoto}";
		}

		($this->Response)
		->SetHeader('Location', $URI)
		->SetCode(Response::CodeFound);

		exit(0);
		return;
	}

}
