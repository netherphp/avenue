<?php

namespace TestRoutes;

use Nether\Avenue\Route;
use Nether\Avenue\Meta\RouteHandler;
use Nether\Avenue\Meta\ConfirmWillAnswerRequest;

class Blog
extends Route {

	#[RouteHandler('/blog')]
	public function
	Index():
	void {

		return;
	}

	#[RouteHandler('/blog/:PostID:')]
	public function
	ViewPost(int $PostID):
	void {

		return;
	}

}
