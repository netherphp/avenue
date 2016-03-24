<?php

namespace Nether\Avenue;
use \Nether;
use \Exception;

class RouteHandler extends Nether\Object {

	public function __construct($opt=null) {
		$opt = new Nether\Object($opt,[
			'Class'  => null,
			'Method' => null,
			'Argv'   => []
		]);

		$this->Class = $opt->Class;
		$this->Method = $opt->Method;
		$this->Argv = $opt->Argv;

		return;
	}

	public function Run(Nether\Avenue\Router $router) {

		if(!class_exists($this->Class))
		throw new Exception("Class {$this->Class} does not exist.");

		if(!method_exists($this->Class,$this->Method))
		throw new Exception("Method {$this->Class}::{$this->Method} does not exist.");

		$this->SetRouter($router);

		return call_user_func_array(
			[(new $this->Class), $this->Method],
			((is_array($this->Argv))?(array_values($this->Argv)):([]))
		);
	}

	////////////////
	////////////////

	protected $Router;
	/*//
	@type Nether\Avenue\Router
	//*/

	public function GetRouter() {
		return $this->Router;
	}

	public function SetRouter(Nether\Avenue\Router $router) {
		$this->Router = $router;
		return $this;
	}

	////////////////
	////////////////

	protected $Domain;
	/*//
	@type string
	//*/

	public function GetDomain() {
		return $this->Domain;
	}

	public function SetDomain($d) {
		$this->Domain = $d;
		return $this;
	}

	////////////////
	////////////////

	protected $Path;
	/*//
	@type string
	//*/

	public function GetPath() {

		return $this->Path;
	}

	public function SetPath($p) {
		$this->Path = $p;
		return $this;
	}

	protected $Query = false;
	/*//
	@type array
	//*/

	public function GetQuery() {

		return $this->Query;
	}

	public function SetQuery(array $q) {
		$this->Query = $q;
		return $this;
	}

	////////////////
	////////////////

	protected $Class;
	/*//
	@type string
	//*/

	public function GetClass() {
		return $this->Class;
	}

	public function SetClass($c) {
		$this->Class = $c;
		return $this;
	}

	////////////////
	////////////////

	protected $Method;
	/*//
	@type string
	//*/

	public function GetMethod() {
		return $this->Method;
	}

	public function SetMethod($m) {
		$this->Method = $m;
		return $this;
	}

	////////////////
	////////////////

	protected
	$Argv = [];

	public function
	GetArg(Int $Offset=1) {
	/*//
	fetch the specified argument from the result of the route pattern matching.
	it is 1 indexed, and the result is merged with the domain and path matches
	so if you had a pattern like (.+)//object/(#), 1 will return you the domain
	and 2 will return you that object id. if you ask for something that did not
	exist then you will get NULL back.
	//*/

		$Offset -= 1;

		if(array_key_exists($Offset,$this->Argv))
		return $this->Argv[$Offset];

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
	Self {
	/*//
	force an array into the argument space. this is mainly used by the router
	to tell the route about the result of its route pattern.
	//*/

		$this->Argv = $Input;
		return $this;
	}

}
