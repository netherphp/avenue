<?php

namespace NetherTestSuite\Avenue\zRoutes;

use Nether\Avenue;
use Nether\Common;

class AnRoute
extends Avenue\Route {

	#[Avenue\Meta\RouteHandler('/eda/false')]
	#[Avenue\Meta\ConfirmWillAnswerRequest('ExtraDataArgsWillAnswerRequest')]
	public function
	ExtraDataArgsFalse(?Avenue\Struct\ExtraData $Data):
	void {

		echo 'ExtraDataArgs=FALSE';

		return;
	}

	#[Avenue\Meta\RouteHandler('/eda/true')]
	#[Avenue\Meta\ConfirmWillAnswerRequest('ExtraDataArgsWillAnswerRequest')]
	#[Avenue\Meta\ExtraDataArgs]
	public function
	ExtraDataArgsTrue(int $One, string $Two):
	void {

		printf('ExtraDataArgs=TRUE, %d, %s', $One, $Two);

		return;
	}

	protected function
	ExtraDataArgsWillAnswerRequest(?Avenue\Struct\ExtraData $Data):
	int {

		$Data['One'] = (int)$this->Request->Query->Get('One');
		$Data['Two'] = (string)$this->Request->Query->Get('Two');

		return Avenue\Response::CodeOK;
	}

};
