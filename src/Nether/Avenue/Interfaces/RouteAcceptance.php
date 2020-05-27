<?php

namespace Nether\Avenue\Interfaces;

use
\Nether\Avenue\Router as Router,
\Nether\Avenue\RouteHandler as Handler;

interface RouteAcceptance {

	static public function
	WillHandleRequest(Router $Router, Handler $Handler):
	Bool;

}
