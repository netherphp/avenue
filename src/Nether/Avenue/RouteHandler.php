<?php

namespace
Nether\Avenue;

use
\Nether    as Nether,
\Exception as Exception;

class RouteHandler
extends Nether\Object\Mapped {

	public function
	__Construct($Opt=NULL) {

		$Opt = new Nether\Object\Mapped($Opt,[
			'Class'  => NULL,
			'Method' => NULL,
			'Argv'   => []
		]);

		$this->Class = $Opt->Class;
		$this->Method = $Opt->Method;
		$this->Argv = $Opt->Argv;
		return;
	}

	public function
	Run(Nether\Avenue\Router $Router) {

		if(!class_exists($this->Class))
		throw new Exception("Class {$this->Class} does not exist.");

		if(!method_exists($this->Class,$this->Method))
		throw new Exception("Method {$this->Class}::{$this->Method} does not exist.");

		$this->SetRouter($Router);

		return call_user_func_array(
			[(new $this->Class($Router)), $this->Method],
			((is_array($this->Argv))?(array_values($this->Argv)):([]))
		);
	}

	////////////////
	////////////////

	protected
	$Router = NULL;
	/*//
	@type Nether\Avenue\Router
	//*/

	public function
	GetRouter():
	?Nether\Avenue\Router {

		return $this->Router;
	}

	public function
	SetRouter(Nether\Avenue\Router $Router):
	self {

		$this->Router = $Router;
		return $this;
	}

	////////////////
	////////////////

	protected
	$Domain = NULL;
	/*//
	@type string
	//*/

	public function
	GetDomain():
	?String {

		return $this->Domain;
	}

	public function
	SetDomain(String $Input) {

		$this->Domain = $Input;
		return $this;
	}

	////////////////
	////////////////

	protected
	$Path = NULL;
	/*//
	@type string
	//*/

	public function
	GetPath():
	?String {

		return $this->Path;
	}

	public function
	SetPath(String $Input):
	self {

		$this->Path = $Input;
		return $this;
	}

	protected
	$Query = NULL;
	/*//
	@type array
	//*/

	public function
	GetQuery():
	?Array {

		return $this->Query;
	}

	public function
	SetQuery(Array $Input):
	self {

		$this->Query = $Input;
		return $this;
	}

	////////////////
	////////////////

	protected
	$Class = NULL;
	/*//
	@type string
	//*/

	public function
	GetClass():
	?String {

		return $this->Class;
	}

	public function
	SetClass(String $Input) {

		$this->Class = $Input;
		return $this;
	}

	////////////////
	////////////////

	protected
	$Method = NULL;
	/*//
	@type string
	//*/

	public function
	GetMethod():
	?String {

		return $this->Method;
	}

	public function
	SetMethod($M):
	self {

		$this->Method = $M;
		return $this;
	}

	////////////////
	////////////////

	protected
	$Argv = [];

	public function
	GetArg(Int $Offset=1):
	?String {
	/*//
	fetch the specified argument from the result of the route pattern matching.
	it is 1 indexed, and the result is merged with the domain and path matches
	so if you had a pattern like (.+)//object/(#), 1 will return you the domain
	and 2 will return you that object id. if you ask for something that did not
	exist then you will get NULL back.
	//*/

		$Offset -= 1;

		if(array_key_exists($Offset,$this->Argv))
		return (String)$this->Argv[$Offset];

		return NULL;
	}

	public function
	GetArgv():
	Array {
	/*//
	fetch the entire array that defines the arguments generated as the result
	of this route's pattern matching.
	//*/

		return $this->Argv;
	}

	public function
	SetArgv(Array $Input):
	self {
	/*//
	force an array into the argument space. this is mainly used by the router
	to tell the route about the result of its route pattern.
	//*/

		$this->Argv = $Input;
		return $this;
	}

}
