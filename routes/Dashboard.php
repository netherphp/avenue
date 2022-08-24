<?php

namespace TestRoutes;

use Nether\Avenue\Route;
use Nether\Avenue\Response;
use Nether\Avenue\Meta\RouteHandler;
use Nether\Avenue\Meta\ConfirmWillAnswerRequest;

class Dashboard
extends Route {

	#[RouteHandler('/dashboard/failconfirm')]
	#[ConfirmWillAnswerRequest]
	public function
	FailConfirm():
	void {

		// this method will fail in the test because the
		// FailConfirmWillAnswerRequest method is missing.

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[RouteHandler('/dashboard/singleconfirm')]
	#[ConfirmWillAnswerRequest]
	public function
	SingleConfirm():
	void {

		return;
	}

	public function
	SingleConfirmWillAnswerRequest():
	int {

		return 0;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[RouteHandler('/dashboard/doubleconfirm')]
	#[ConfirmWillAnswerRequest]
	#[ConfirmWillAnswerRequest('RequireAdminUser')]
	public function
	DoubleConfirm():
	void {

		return;
	}

	public function
	DoubleConfirmWillAnswerRequest():
	int {

		// we want this method to approve so that the second
		// confirm gets executed and rejects it hard.

		return Response::CodeOK;
	}

	public function
	RequireAdminUser():
	int {

		return Response::CodeForbidden;
	}

}
