<?php

namespace Nether\Avenue;

use Nether\Object\Prototype;
use Nether\Object\Datafilter;
use Nether\Object\Prototype\PropertyInfo;
use Nether\Avenue\Util;

class Request
extends Prototype {

	public string
	$Protocol;

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

	protected bool
	$VerbRewrite = FALSE;

	protected string
	$VerbSource = 'verb';

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
		->ParseProtocol()
		->ParseRequestVerb($Verb)
		->ParseRequestDomain($Domain, $this->DomainLvl)
		->ParseRequestURI($URI)
		->ParseRequestData();

		return $this;
	}

	public function
	ParseProtocol():
	static {

		$this->Protocol = 'http';

		if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'])
		$this->Protocol = 'https';

		return $this;
	}

	public function
	ParseRequestVerb(?string $Verb=NULL):
	static {

		if($Verb === NULL) {
			if($this->VerbRewrite && isset($_GET[$this->VerbSource]))
			$Verb = $_GET[$this->VerbSource];
		}

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

		$this->Data = new Datafilter(match($this->Verb) {
			'GET'
			=> $_GET,

			'POST'
			=> $_POST,

			// @todo 2022-11-16 in the default case detect query string
			// format vs form multipart format. and find a good form
			// multi part decoder.

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

	public function
	GetURL():
	string {

		$Output = sprintf(
			'%s://%s%s',
			$this->Protocol,
			(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $this->Domain),
			$this->Path
		);

		if($this->Query->Count())
		$Output .= "?{$this->Query->GetQueryString()}";

		return $Output;
	}

}
