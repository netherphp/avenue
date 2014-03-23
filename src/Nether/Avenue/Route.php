<?php

namespace Nether\Avenue;
use \Nether;

class Route {

	static function WillHandleRequest($avenue) {
	/*//
	@argv Nether\Avenue Aveune
	@return boolean
	@override yes
	decides if this avenue can handle the request. you override this method
	to perform any custom checks you need to do to see if your extended class
	will be able to handle the request.
	//*/

		return true;
	}

	////////////////////////////////
	////////////////////////////////

	public $EMS = false;
	/*//
	@type string
	a key value that the router can check to see what event system the route
	decided to use for launching events. set by the SetupEvents method.
	//*/

	public function __construct() {

		if(!$this->Allow())
		throw new \Exception('Access Denied');

		$this->SetupEvents();

		return;
	}

	////////////////////////////////
	////////////////////////////////

	protected function Allow() {
	/*//
	@return boolean
	//*/

		return true;
	}

	////////////////////////////////
	////////////////////////////////

	protected function SetupEvents() {
	/*//
	@return boolean
	attach the avenue to an EMS. returns a boolean stating if it found a
	supported event system to latch on to or not.
	//*/

		Nether\Ki::Queue('nether-avenue-request',[$this,'Request']);
		Nether\Ki::Queue('nether-avenue-main',[$this,'Main']);
		Nether\Ki::Queue('nether-avenue-output',[$this,'Output']);

		return;
	}


	////////////////////////////////
	////////////////////////////////

	public function Request() {
		return;
	}

	public function Main() {
		return;
	}

	public function Output() {
		return;
	}

}
