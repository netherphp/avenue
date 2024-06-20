<?php

namespace NetherTestSuite\Avenue\zRoutes;

use Nether\Avenue;
use Nether\Common;

class AnAPI
extends Avenue\Route {

	#[Avenue\Meta\RouteHandler('/api/test/basic', Verb: 'GET')]
	public function
	TestGet():
	void {

		echo Common\Filters\Text::ReadableJSON([
			'Error'   => 0,
			'Message' => 'GOT'
		]);

		return;
	}

	#[Avenue\Meta\RouteHandler('/api/test/basic', Verb: 'POST')]
	public function
	TestPost():
	void {

		echo Common\Filters\Text::ReadableJSON([
			'Error'   => 0,
			'Message' => 'POSTED'
		]);

		return;
	}

	#[Avenue\Meta\RouteHandler('/api/test/basic', Verb: 'PATCH')]
	public function
	TestPatch():
	void {

		echo Common\Filters\Text::ReadableJSON([
			'Error'   => 0,
			'Message' => 'PATCHED'
		]);

		return;
	}

};
