<?php

namespace Nether\Avenue;

use \Nether as Nether;

use
\Exception as Exception,
\StdClass as StdClass;

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

// define a list of shortcuts which can be used in the route conditions to make
// regular expressions easier to deal with. with care, you can also add your
// own shortcuts if there is anything you find yourself doing often.

Nether\Option::Define([
	'nether-avenue-path-cs'             => TRUE,
	'nether-avenue-condition-shortcuts' => [
		// match anything, as long as there is something.
		'(@)' => '(.+?)', '{@}' => '(?:.+?)',

		// match anything, even if there is nothing.
		'(?)' => '(.*?)', '{?}' => '(?:.*?)',

		// match numbers.
		'(#)' => '(\d+)', '{#}' => '(?:\d+)',

		// match a string within a path fragment e.g. between the slashes.
		'($)' => '([^\/]+)', '{$}' => '(?:[^\/]+)',

		// match a relevant domain e.g. domain.tld without subdomains. it
		// should also work on dotless domains like localhost. it will still
		// match a full domain like www.nether.io, but it will only store
		// nether.io in the slot.
		'(domain)' => '.*?([^\.]+(?:\.[^\.]+)?)',
		'{domain}' => '.*?(?:[^\.]+(?:\.[^\.]+)?)'
	]
]);

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

class Router {

	public function
	__Construct($Opt=NULL) {

		$Opt = new Nether\Object\Mapped($Opt,[
			'Domain' => $this->GetRequestDomain(),
			'Path' => $this->GetRequestPath(),
			'Query' => $this->GetRequestQuery()
		]);

		$Userpart = '';

		////////

		$this->Domain = $Opt->Domain;
		$this->Query = $Opt->Query;
		$this->Path = (($Opt->Path=='/')?
			('/index'):
			($Opt->Path)
		);

		////////

		if(array_key_exists('REMOTE_ADDR',$_SERVER))
		$Userpart = $_SERVER['REMOTE_ADDR'];

		$this->HitHash = md5("{$Userpart}-{$this->GetFullDomain()}-{$this->GetPath()}");
		$this->HitTime = microtime(TRUE);

		// take care for paths. remove trailing slashes and query strings if
		// they made it into the path.

		$this->Path = preg_replace(
			'/\?.*$/', '',
			rtrim($Opt->Path,'/')
		);

		if(!$this->Path)
		$this->Path = '/index';

		return;
	}

	////////////////
	////////////////

	public function
	Run(Nether\Avenue\RouteHandler $Route=NULL) {
	/*//
	argv(Nether\Avenue\RouteHandler ForcedRoute)
	if given a route it will attempt to execute it. you can use this in the
	event you want to GetRoute() prior to Run() to see if it would have run
	anything. that feature mostly useful if you are in the middle of migrating
	between routers.
	//*/

		if($Route) $this->Route = $Route;
		else $this->Route = $this->GetRoute();

		if(!$this->Route)
		throw new Exception("No routes found to handle request. TODO: make this a nicer 404 handler.");

		return $this->Route->Run($this);
	}

	////////////////
	////////////////

	protected
	$Domain = NULL;
	/*//
	@type String
	store the requested host name as it was given to us.
	//*/

	public function
	GetDomain():
	?String {
	/*//
	return only the part of the domain that is most useful to most apps aka the
	main top level domain that is being used. this cuts off any subdomains. if
	you need the full host name as it was requested use GetFullDomain() instead.
	//*/

		if($this->Domain === NULL)
		return NULL;

		return preg_replace(
			'/.*?([^\.]+(?:\.[^\.]+)?)$/',
			'\1',
			$this->Domain
		);
	}

	public function
	GetFullDomain():
	?String {
	/*//
	fetch the domain asked for in this request. by default we will truncate the
	domain to not include the subdomain. if you pass full as true then we will
	just dump the entire string as it was, which will include any subdomains.
	//*/

		return $this->Domain;
	}

	protected
	$Path = NULL;
	/*//
	type(string)
	store the requested path as it was given to us.
	//*/

	public function
	GetPath():
	?String {
	/*//
	return(string)
	fetch the requested path as a string.
	//*/

		return $this->Path;
	}

	public function
	GetPathArray():
	?Array {
	/*//
	return(array)
	return the path string as an array.
	//*/

		if(!$this->Path)
		return NULL;

		return explode('/',trim($this->Path,'/'));
	}

	public function
	GetPathSlot($Slot):
	?String {
	/*//
	return the specified slot from the path.
	//*/

		// oob
		if($Slot < 1)
		return NULL;

		$Path = $this->GetPathArray();

		// oob
		if($Slot > count($Path))
		return NULL;

		return (String)$Path[$Slot-1];
	}

	protected
	$HitHash = NULL;
	/*//
	@type String
	a hash that represents this hit, made form the user ip and request info.
	//*/

	public function
	GetHitHash():
	?String {
	/*//
	return the hit hash for this request.
	//*/

		return $this->HitHash;
	}

	////////////////
	////////////////

	protected
	$HitTime = NULL;
	/*//
	@type Float
	the time that the hit occured.
	//*/

	public function
	GetHitTime():
	?Float {
	/*//
	return the hit time for this request.
	//*/

		return $this->HitTime;
	}

	////////////////
	////////////////

	public function
	GetHit():
	StdClass {
	/*//
	return an object that defines the unique description of this request: the
	hit hash and the request time.
	//*/

		// @todo 2020-05-22 Return a HitHash object.

		return (object)[
			'Hash' => $this->HitHash,
			'Time' => $this->HitTime
		];
	}

	public function
	GetProtocol():
	?String {
	/*//
	returns http or https lol.
	//*/

		return ((array_key_exists('HTTPS',$_SERVER))?
		('https'):
		('http'));
	}

	public function
	GetURL():
	?String {
	/*//
	returns a recompiled url from the current request using the parsed data.
	//*/

		return sprintf(
			'%s://%s%s%s',
			$this->GetProtocol(),
			$this->GetFullDomain(),
			(($this->GetPath() === '/index')?
				('/'):
				($this->GetPath())
			),
			((count($this->Query) >= 1)?
				($this->QueryCooker($this->Query)):
				(''))
		);
	}

	////////////////
	////////////////

	public function
	GetQuery():
	?Array {
	/*//
	return the query array as it was given to us.
	//*/

		return $this->Query;
	}

	public function
	GetQueryVar($Key):
	?String {
	/*//
	fetch a specific query var.
	//*/

		// if we have that data give it.
		if(array_key_exists($Key,$this->Query))
		return (String)$this->Query[$Key];

		// else nope.
		return NULL;
	}

	////////////////
	////////////////

	public function
	GetRequestDomain():
	?String {
	/*//
	get the domain as it was requested in the global.
	//*/

		// if we have a hostname request then return what that was, even on cli
		// in the event we are mocking something.
		if(array_key_exists('HTTP_HOST',$_SERVER))
		return $_SERVER['HTTP_HOST'];

		return NULL;
	}

	public function
	GetRequestPath():
	?String {
	/*//
	get the request as it was in the global.
	//*/

		if(array_key_exists('REQUEST_URI',$_SERVER)) {
			$Path = rtrim(explode('?',$_SERVER['REQUEST_URI'])[0],'/');

			if($Path) return $Path;
			else return '/index';
		}

		return NULL;
	}

	public function
	GetRequestQuery(String $Which='get'):
	?Array {
	/*//
	get a request datasource variable.
	//*/

		switch($Which) {
			case 'get': {
				if(isset($_GET)) return $_GET;
				break;
			}
			case 'post': {
				if(isset($_POST)) return $_POST;
				break;
			}
			case 'request': {
				if(isset($_REQUEST)) return $_REQUEST;
				break;
			}
		}

		return NULL;
	}

	////////////////
	////////////////

	protected
	$Route = NULL;
	/*//
	@type String
	the currently selected route.
	//*/

	protected
	$Routes = [];
	/*//
	@type Array
	a list of all the routes that have been specified.
	//*/

	public function
	AddRoute(String $Cond, String $Hand):
	self {
	/*//
	add a route to the condition table.
	//*/

		$Domain = NULL;
		$Path = NULL;
		$Query = NULL;
		$Handler = NULL;

		// parse the route conditions.

		if(!$this->IsRouteConditionValid($Cond))
		throw new Exception("Route condition ({$Cond}) is not valid.");

		list($Domain,$Path) = explode('//',$Cond);

		if(strpos($Path,'??') !== FALSE) list($Path,$Query) = explode('??',$Path);
		else $Query = '';

		// parse the route handler.

		if(!$this->IsRouteHandlerValid($Hand))
		throw new Exception("Route handler ({$Hand}) is not valid.");

		$Handler = $this->TranslateRouteHandler($Hand);

		// throw in our extra data.

		$Handler->SetDomain(sprintf(
			'`^%s$`',
			$this->TranslateRouteCondition($Domain)
		));

		if(Nether\Option::Get('nether-avenue-path-cs'))
		$Handler->SetPath(sprintf(
			'`^\/%s$`',
			$this->TranslateRouteCondition($Path)
		));

		else
		$Handler->SetPath(sprintf(
			'`^\/%s$`i',
			$this->TranslateRouteCondition($Path)
		));

		$Handler->SetQuery(explode('&',$Query));

		$this->Routes[] = $Handler;
		return $this;
	}

	public function
	GetRoute():
	?Nether\Avenue\RouteHandler {
	/*//
	find a route from the handler table.
	//*/

		$Handler = NULL;
		$Dm = $Pm = NULL;
		$Nope = NULL;
		$Q = NULL;

		foreach($this->Routes as $Handler) {

			// require a domain hard match.

			if(!preg_match($Handler->GetDomain(),$this->Domain,$Dm))
			continue;

			// require a path hard match.

			if(!preg_match($Handler->GetPath(),$this->Path,$Pm))
			continue;

			// require a query soft match.

			$Nope = FALSE;
			foreach($Handler->GetQuery() as $Q) {
				if(!$Q)
				continue;

				if(!array_key_exists($Q,$this->GetQuery()))
				$Nope = TRUE;
			}

			if($Nope)
			continue;

			// fetch the arguments found by the route match.
			unset($Dm[0],$Pm[0]);
			$Handler->SetArgv(array_merge($Dm,$Pm));

			// ask the route if it is willing to handle the request.
			if(!$this->WillHandlerAcceptRequest($Handler))
			continue;

			// and since we found a match we are done.
			return $Handler;
		}

		return NULL;
	}

	public function
	ClearRoutes():
	self {
	/*//
	empty the routing table.
	//*/

		$this->Routes = [];
		return $this;
	}

	public function
	GetRoutes():
	?Array {
	/*//
	fetch the routing table.
	//*/

		return $this->Routes;
	}

	public function
	WillHandlerAcceptRequest(Nether\Avenue\RouteHandler $H):
	Bool {
	/*//
	ask a handler if it is willing to handle this request.
	//*/

		$Class = $H->GetClass();

		// if the handler class does not have the query method then assume
		// that it will handle it.
		if(!method_exists($Class,'WillHandleRequest'))
		return TRUE;

		return (Bool)call_user_func_array(
			[$Class,'WillHandleRequest'],
			[ $this, $H ]
		);
	}

	public function
	TranslateRouteCondition(String $Cond):
	String {
	/*//
	parses a route condition to replace shortcuts into any regex that was defined.
	//*/

		$Old = NULL;
		$New = NULL;

		foreach(Nether\Option::Get('nether-avenue-condition-shortcuts') as $Old => $New)
		$Cond = str_replace($Old,$New,$Cond);

		return $Cond;
	}

	public function
	TranslateRouteHandler(String $Hand):
	Nether\Avenue\RouteHandler {
	/*//
	get a handler from its definition.
	//*/

		if(strpos($Hand,'::') !== FALSE)
		return $this->TranslateRouteHandler_ClassMethod($Hand);

		else
		return $this->TranslateRouteHandler_ClassOnly($Hand);
	}

	protected function
	TranslateRouteHandler_ClassMethod(String $Hand):
	Nether\Avenue\RouteHandler {
	/*//
	get a handler that was defined with a specific method.
	//*/

		$Class = NULL;
		$Method = NULL;

		list($Class,$Method) = explode('::',$Hand);

		return new RouteHandler([
			'Class' => $Class,
			'Method' => $Method
		]);
	}

	protected function
	TranslateRouteHandler_ClassOnly(String $Hand):
	Nether\Avenue\RouteHandler {
	/*//
	get a handler that was defined without a specific method.
	//*/

		return new RouteHandler([
			'Class' => $Hand
		]);
	}

	protected function
	IsRouteConditionValid(String $Cond):
	Bool {
	/*//
	check if a route condition seems a valid format.
	//*/

		if(strpos($Cond,'//') === FALSE)
		return FALSE;

		return TRUE;
	}

	protected function
	IsRouteHandlerValid(String $Hand):
	Bool {
	/*//
	check if a route handler seems a valid format.
	//*/

		if(strpos($Hand,'::') === FALSE)
		return FALSE;

		return TRUE;
	}

	public function
	QueryMerger($Input=[], Bool $DropUnused=FALSE):
	Array {
	/*//
	@todo 2020-05-22 union type $Input as array|object in PHP 8
	merge the input with the original query array to generate an updated query
	string that we may want to pass in for hyperlinking.
	//*/

		$Key = NULL;
		$Value = NULL;

		if(!is_object($Input) && !is_array($Input))
		throw new Exception('QueryMerger expects an array or object, so good job with that.');

		if(!is_array($Input))
		$Input = (array)$Input;

		// clean out anything that was left empty string, leaving 0's,
		// booleans, nulls, and everything else that evals to them in the
		// event those cases were needed.

		$Source = $this->Query;

		foreach($Input as $Key => $Value)
		if($Value === '') {
			unset($Input[$Key]);

			if(array_key_exists($Key,$Source))
			unset($Source[$Key]);
		}

		// if we only want what we passed then return the cleaned input.

		if($DropUnused)
		return $Input;

		// else merge it with the current request as overwrites.

		return array_merge($Source,$Input);
	}

	public function
	QueryBlender($Input=[], Bool $DropUnused=FALSE):
	String {
	/*//
	use the QueryMerger to output a final string of the merged query.
	//*/

		return http_build_query($this->QueryMerger($Input,$DropUnused));
	}

	public function
	QueryCooker($Input=[], Bool $DropUnused=FALSE):
	?String {
	/*//
	returns the exact same thing as QueryBlender but with a question mark in
	front of it. did i try too hard with these series of methods? i don't think
	so. in the apps i work on i often need the blended and cooked versions for
	many different reasons. i sure as hell don't want to manually append
	question marks at times. life should be easy. this library is easy.
	//*/

		$Result = $this->QueryBlender($Input,$DropUnused);

		if($Result)
		return sprintf('?%s',$Result);

		return '';
	}

}
