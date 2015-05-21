<?php

namespace Nether\Avenue;
use \Nether;
use \Exception;

class RouteHandler extends Nether\Object {

	public function __construct($opt=null) {
		$opt = new Nether\Object($opt,[
			'Class'  => null,
			'Method' => null,
			'Argv'   => null
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

	protected $Argv;
	/*//
	@type string
	//*/

	public function GetArgv() {
		return $this->Argv;
	}

	public function SetArgv(array $a) {
		$this->Argv = $a;
		return $this;
	}

}
