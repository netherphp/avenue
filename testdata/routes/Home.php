<?php

namespace TestRoutes;

use Nether\Avenue\Route;
use Nether\Avenue\Meta\RouteHandler;
use Nether\Common\Datastore;

class Home
extends Route {

	#[RouteHandler('/index')]
	public function
	Index():
	void {
		echo 'Index Page';
		return;
	}

	#[RouteHandler('/about')]
	public function
	About():
	void {

		return;
	}

	#[RouteHandler('/extradata1')]
	public function
	ExtraData1():
	void { return; }

	#[RouteHandler('/extradata2/:Arg:')]
	public function
	ExtraData2(Datastore $ExtraData):
	void { return; }

	#[RouteHandler('/extradata3/:arg:')]
	public function
	ExtraData3(string $Arg, Datastore $ExtraData):
	void { return; }

	#[RouteHandler('/extradata3/:arg:')]
	public function
	ExtraData4(Datastore $ExtraData, string $Arg):
	void { return; }

}
