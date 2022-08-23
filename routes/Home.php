<?php

namespace TestRoutes;

use Nether\Avenue\Route;
use Nether\Avenue\Meta\RouteHandler;

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

}
