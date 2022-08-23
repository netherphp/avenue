<?php

namespace TestRoutes;

use Nether\Avenue\Route;
use Nether\Avenue\Response;
use Nether\Avenue\Meta\ErrorHandler;

class Errors
extends Route {

	#[ErrorHandler(Response::CodeNotFound)]
	public function
	NotFound():
	void {

		echo 'Not Found';
		return;
	}

	#[ErrorHandler(Response::CodeForbidden)]
	public function
	Forbidden():
	void {

		echo 'Forbidden';
		return;
	}

}
