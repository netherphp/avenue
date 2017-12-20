<?php

namespace Nether\Avenue\Routes;

use \Nether as Nether;

class PublicAPI
extends PublicWeb {

	static protected
	$OnConstruct = FALSE;

	static protected
	$DefaultMessage = 'OK',
	$DefaultError = 0;

	//////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////

	public function
	__construct() {
		parent::__construct(func_get_args());

		$this->Surface
		->SetTheme('json')
		->Set('Error',static::$DefaultError)
		->Set('Message',static::$DefaultMessage)
		->Set('Payload',NULL);

		$this->OnConstruct();
		return;
	}

	//////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////

	static protected
	$MethodMap = [];
	/*//
	@type Array
	@date 2017-08-17
	if using the default Index method this maps the input given as the request
	argument to the method that should be called.
	//*/

	public function
	Index($What=''):
	Void {
	/*//
	@date 2017-08-17
	//*/

		$What = strtolower($What);

		if(!array_key_exists($What,static::$MethodMap)) {
			$this->Quit('method not found',-1);
			return;
		}

		////////

		$Verb = ucfirst(strtolower($this->GetRequestMethod()));
		$Method = static::$MethodMap[$What];

		if(method_exists($this,"{$Method}{$Verb}")) {
			call_user_func([$this,"{$Method}{$Verb}"]);
			return;
		}

		elseif(method_exists($this,$Method)) {
			call_user_func([$this,$Method]);
			return;
		}

		$this->Quit('method not found',-2);
		return;
	}

	//////////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////////

	public function
	Quit(String $Message='', Int $Error=0):
	Void {
	/*//
	@date 2017-08-17
	//*/

		if($Error)
		$this->Surface->Set('Error',$Error);

		if($Message)
		$this->Surface->Set('Message',$Message);

		exit(0);
		return;
	}

}
