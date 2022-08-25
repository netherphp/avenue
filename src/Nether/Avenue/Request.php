<?php

namespace Nether\Avenue;

use Nether\Object\Prototype;
use Nether\Object\Datafilter;
use Nether\Object\Prototype\PropertyInfo;
use Nether\Avenue\Util;

class Request
extends Prototype {

	public string
	$Verb;

	public string
	$Domain;

	public string
	$Path;

	public Datafilter
	$Query;

	public Datafilter
	$Data;

	public Datafilter
	$File;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected int
	$DomainLvl = 2;

	protected string
	$DomainSep = '.';

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	__DebugInfo():
	array {

		$Output = [];
		$Props = static::GetPropertyIndex();
		$Prop = NULL;

		foreach($Props as $Prop)
		if($Prop instanceof PropertyInfo) {
			if($Prop->Access === 'public')
			$Output[$Prop->Name] = $this->{$Prop->Name};
		}

		return $Output;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	OnReady(Prototype\ConstructArgs $Args):
	void {

		return;
	}

	public function
	ParseRequest(?string $Verb=NULL, ?string $Domain=NULL, ?string $URI=NULL):
	static {

		$this
		->ParseRequestVerb($Verb)
		->ParseRequestDomain($Domain, $this->DomainLvl)
		->ParseRequestURI($URI)
		->ParseRequestData();

		return $this;
	}

	public function
	ParseRequestVerb(?string $Verb=NULL):
	static {

		if($Verb === NULL) {
			if(isset($_SERVER['REQUEST_METHOD']))
			$Verb = $_SERVER['REQUEST_METHOD'];
		}

		if($Verb === NULL)
		$Verb = 'GET';

		////////

		$this->Verb = strtoupper($Verb);

		return $this;
	}

	public function
	ParseRequestDomain(?string $Domain=NULL, int $Level=2):
	static {

		if($Domain === NULL) {
			if(isset($_SERVER['HTTP_HOST']))
			$Domain = $_SERVER['HTTP_HOST'];
		}

		if($Domain === NULL)
		$Domain = 'localhost';

		////////

		if(!str_contains($Domain, $this->DomainSep)) {
			$this->Domain = $Domain;
			return $this;
		}

		////////

		$Level = max(1, $Level);
		$Bits = explode('.', $Domain);
		$Bit = count($Bits);
		$Boops = [];

		while(($Bit--) > 0 && ($Level--) > 0)
		$Boops[] = $Bits[$Bit];

		$this->Domain = join(
			$this->DomainSep,
			array_reverse($Boops)
		);

		return $this;
	}

	public function
	ParseRequestURI(?string $URI=NULL):
	static {

		if($URI === NULL) {
			if(isset($_SERVER['REQUEST_URI']))
			$URI = $_SERVER['REQUEST_URI'];
		}

		if($URI === NULL)
		$URI = '/';

		$Path = $URI;
		$Query = NULL;
		$Vars = [];

		////////

		if(str_contains($URI, '?'))
		list($Path, $Query) = explode('?', $URI, 2);

		$Vars = Util::ParseQueryString($Query);

		if($Path === '/')
		$Path = '/index';
		else
		$Path = rtrim($Path, '/');

		////////

		$this->Path = $Path;
		$this->Query = new Datafilter($_GET);

		return $this;
	}

	public function
	ParseRequestData():
	static {

		// bind the most relevant input source to the data property
		// based on the http verb. only get and post get parsed data
		// from the engine, others like DELETE or any other verb including
		// nonstandard ones do not populate a global but can be parsed
		// from the php input.

		$this->Data = new Datafilter(match($this->Verb){
			'GET'
			=> $_GET,

			'POST'
			=> $_POST,

			default
			=> Util::ParseQueryString(file_get_contents('php://input'))
		});

		// bind the file data filter.

		$this->File = new Datafilter($_FILES);

		return $this;
	}

	public function
	GetRegInput():
	string {

		return "//{$this->Domain}{$this->Path}";
	}

}
