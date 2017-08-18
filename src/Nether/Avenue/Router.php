<?php

namespace
Nether\Avenue;

use
\Nether    as Nether,
\Exception as Exception;

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

// define a list of shortcuts which can be used in the route conditions to make
// regular expressions easier to deal with. with care, you can also add your
// own shortcuts if there is anything you find yourself doing often.

Nether\Option::Define([
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

class
Router {

	public function
	__construct($Opt=NULL) {
	/*//
	//*/

		$Userpart = '';

		////////

		$Opt = new Nether\Object\Mapped($Opt,[
			'Domain' => $this->GetRequestDomain(),
			'Path' => $this->GetRequestPath(),
			'Query' => $this->GetRequestQuery()
		]);

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

	public function Run(Nether\Avenue\RouteHandler $route=null) {
	/*//
	argv(Nether\Avenue\RouteHandler ForcedRoute)
	if given a route it will attempt to execute it. you can use this in the
	event you want to GetRoute() prior to Run() to see if it would have run
	anything. that feature mostly useful if you are in the middle of migrating
	between routers.
	//*/

		if($route) $this->Route = $route;
		else $this->Route = $this->GetRoute();

		if(!$this->Route)
		throw new Exception("No routes found to handle request. TODO: make this a nicer 404 handler.");

		return $this->Route->Run($this);
	}

	////////////////
	////////////////

	protected $Domain;
	/*//
	type(string)
	store the requested host name as it was given to us.
	//*/

	public function GetDomain() {
	/*//
	return(string)
	return only the part of the domain that is most useful to most apps aka the
	main top level domain that is being used. this cuts off any subdomains. if
	you need the full host name as it was requested use GetFullDomain() instead.
	//*/

		if($this->Domain === null)
		return null;

		return preg_replace(
			'/.*?([^\.]+(?:\.[^\.]+)?)$/',
			'\1',
			$this->Domain
		);
	}

	public function GetFullDomain() {
	/*//
	return(string)
	fetch the domain asked for in this request. by default we will truncate the
	domain to not include the subdomain. if you pass full as true then we will
	just dump the entire string as it was, which will include any subdomains.
	//*/

		return $this->Domain;
	}

	protected $Path;
	/*//
	type(string)
	store the requested path as it was given to us.
	//*/

	public function GetPath() {
	/*//
	return(string)
	fetch the requested path as a string.
	//*/

		return $this->Path;
	}

	public function GetPathArray() {
	/*//
	return(array)
	return the path string as an array.
	//*/

		return explode('/',trim($this->Path,'/'));
	}

	public function GetPathSlot($slot) {
	/*//
	return(string)
	return(null) slot out of bounds.
	return the specified slot from the path.
	//*/

		// oob
		if($slot < 1)
		return false;

		$path = $this->GetPathArray();

		// oob
		if($slot > count($path))
		return false;

		return $path[$slot-1];
	}

	protected $HitHash;
	/*//
	type(string)
	a hash that represents this hit, made form the user ip and request info.
	//*/

	public function GetHitHash() {
	/*//
	return(string)
	return the hit hash for this request.
	//*/

		return $this->HitHash;
	}

	////////////////
	////////////////

	protected $HitTime;
	/*//
	type(float)
	the time that the hit occured.
	//*/

	public function GetHitTime() {
	/*//
	return(float)
	return the hit time for this request.
	//*/
		return $this->HitTime;
	}

	////////////////
	////////////////

	public function GetHit() {
	/*//
	return(object)
	return an object that defines the unique description of this request: the
	hit hash and the request time.
	//*/
		return (object)[
			'Hash' => $this->HitHash,
			'Time' => $this->HitTime
		];
	}

	public function GetProtocol() {
	/*//
	return(string)
	returns http or https lol.
	//*/

		return ((array_key_exists('HTTPS',$_SERVER))?
		('https'):
		('http'));
	}

	public function GetURL() {
	/*//
	return(string)
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

	public function GetQuery() {
	/*//
	return(array)
	return the query array as it was given to us.
	//*/

		return $this->Query;
	}

	public function GetQueryVar($key) {
	/*//
	return(mixed)
	return(null) if key not defined.
	fetch a specific query var.
	//*/

		// if we have that data give it.
		if(array_key_exists($key,$this->Query))
		return $this->Query[$key];

		// else nope.
		return null;
	}

	////////////////
	////////////////

	public function GetRequestDomain() {
	/*//
	return(null) running from cli.
	return(false) unable to determine domain.
	return(string) the current domain.
	//*/

		// if we have a hostname request then return what that was, even on cli
		// in the event we are mocking something.
		if(array_key_exists('HTTP_HOST',$_SERVER))
		return $_SERVER['HTTP_HOST'];

		// if there was no hostname and we are command line then return a null
		// to symbolise that.
		if(php_sapi_name() === 'cli') return null;

		// else we still thought we were in web mode, and with no hostname
		// to process we will return a false.
		return false;
	}

	public function GetRequestPath() {
	/*//
	return(null) running from cli.
	return(false) unable to determine path.
	return(string) the current request path.
	//*/

		if(array_key_exists('REQUEST_URI',$_SERVER)) {
			$path = rtrim(explode('?',$_SERVER['REQUEST_URI'])[0],'/');

			if($path) return $path;
			else return '/index';
		}

		if(php_sapi_name() === 'cli') return null;

		return false;
	}

	public function GetRequestQuery($which='get') {
	/*//
	argv(string SourceArray)
	return(false) no query data found.
	return(array) the input query data as requested.
	//*/

		switch($which) {
			case 'get': {
				if(isset($_GET)) return $_GET;
				else return false;
			}
			case 'post': {
				if(isset($_POST)) return $_POST;
				else return false;
			}
		}

		return false;
	}

	////////////////
	////////////////

	protected $Route;
	/*//
	type(string)
	the currently selected route.
	//*/

	protected $Routes = [];
	/*//
	type(array)
	a list of all the routes that have been specified.
	//*/

	public function AddRoute($cond,$hand) {
	/*//
	argv(string Condition, string Handler)
	return(self)
	//*/

		{{{ // parse the route conditions.
			if(!$this->IsRouteConditionValid($cond))
			throw new Exception("Route condition ({$cond}) is not valid.");

			list($domain,$path) = explode('//',$cond);

			if(strpos($path,'??') !== false) list($path,$query) = explode('??',$path);
			else $query = '';
		}}}

		{{{ // parse the route handler.
			if(!$this->IsRouteHandlerValid($hand))
			throw new Exception("Route handler ({$hand}) is not valid.");

			$handler = $this->TranslateRouteHandler($hand);
		}}}

		// throw in our extra data.
		$handler->SetDomain("`^{$this->TranslateRouteCondition($domain)}$`");
		$handler->SetPath("`^\/{$this->TranslateRouteCondition($path)}$`");
		$handler->SetQuery(explode('&',$query));

		$this->Routes[] = $handler;
		return $this;
	}

	public function GetRoute() {
	/*//
	return(object)
	//*/

		$dm = $pm = null;

		foreach($this->Routes as $handler) {

			// require a domain hard match.
			if(!preg_match($handler->GetDomain(),$this->Domain,$dm)) continue;

			// require a path hard match.
			if(!preg_match($handler->GetPath(),$this->Path,$pm)) continue;

			// require a query soft match.
			$nope = false;
			foreach($handler->GetQuery() as $q) {
				if(!$q) continue;

				if(!array_key_exists($q,$this->GetQuery()))
				$nope = true;
			}

			if($nope) continue;

			// fetch the arguments found by the route match.
			unset($dm[0],$pm[0]);
			$handler->SetArgv(array_merge($dm,$pm));

			// ask the route if it is willing to handle the request.
			if(!$this->WillHandlerAcceptRequest($handler)) continue;

			// and since we found a match we are done.
			return $handler;
		}

		return false;
	}

	public function ClearRoutes() {
	/*//
	return(self)
	//*/

		$this->Routes = [];
		return $this;
	}

	public function GetRoutes() {
	/*//
	return(array)
	//*/

		return $this->Routes;
	}

	public function WillHandlerAcceptRequest(Nether\Avenue\RouteHandler $h) {
	/*//
	return(bool)
	//*/

		$class = $h->GetClass();

		// if the handler class does not have the query method then assume
		// that it will handle it.
		if(!method_exists($class,'WillHandleRequest')) return true;

		return call_user_func_array(
			[$class,'WillHandleRequest'],
			[ $this, $h ]
		);
	}

	public function TranslateRouteCondition($cond) {
	/*//
	return(string)
	//*/

		foreach(Nether\Option::Get('nether-avenue-condition-shortcuts') as $old => $new)
		$cond = str_replace($old,$new,$cond);

		return $cond;
	}

	public function TranslateRouteHandler($hand) {
	/*//
	return(Nether\Avenue\RouteHandler)
	//*/

		if(strpos($hand,'::') !== false)
		return $this->TranslateRouteHandler_ClassMethod($hand);

		else
		return $this->TranslateRouteHandler_ClassOnly($hand);
	}

	protected function TranslateRouteHandler_ClassMethod($hand) {
	/*//
	return(Nether\Avenue\RouteHandler)
	//*/

		list($class,$method) = explode('::',$hand);

		return new RouteHandler([
			'Class' => $class,
			'Method' => $method
		]);
	}

	protected function TranslateRouteHandler_ClassOnly($hand) {
	/*//
	return(Nether\Avenue\RouteHandler)
	//*/

		return new RouteHandler([
			'Class' => $hand
		]);
	}

	protected function IsRouteConditionValid($cond) {
	/*//
	argv(string Condition)
	return(bool)
	//*/

		if(strpos($cond,'//') === false) return false;

		return true;
	}

	protected function IsRouteHandlerValid($hand) {
	/*//
	return(bool)
	//*/

		if(strpos($hand,'::') === false) return false;

		return true;
	}

	public function
	QueryMerger($Input=[], Bool $DropUnused=false) {
	/*//
	return(array)
	merge the input with the original query array to generate an updated query
	string that we may want to pass in for hyperlinking.
	//*/

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
	QueryBlender($Input=[], Bool $DropUnused=false) {
	/*//
	return(string)
	use the QueryMerger to output a final string of the merged query.
	//*/

		return http_build_query($this->QueryMerger($Input,$DropUnused));
	}

	public function
	QueryCooker($Input=[], Bool $DropUnused=false) {
	/*//
	return(string)
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
