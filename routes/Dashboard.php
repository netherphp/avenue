<?php

namespace TestRoutes;

use Nether\Avenue\Route;
use Nether\Avenue\Meta\RouteHandler;
use Nether\Avenue\Meta\ConfirmWillAnswerRequest;

class Dashboard
extends Route {

	#[RouteHandler('/dashboard')]
	#[ConfirmWillAnswerRequest]
	public function
	Index():
	void {

		// this method will fail in the test because the
		// IndexWillAnswerRequest method is missing.

		return;
	}

	#[RouteHandler('/dashboard/singleconfirm')]
	#[ConfirmWillAnswerRequest]
	public function
	SingleConfirm():
	void {

		return;
	}

	public function
	SingleConfirmWillAnswerRequest():
	?bool {

		return TRUE;
	}

	public function
	RequireAdminUser():
	?bool {

		return NULL;
	}

}
