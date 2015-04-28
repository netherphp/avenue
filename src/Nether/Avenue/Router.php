<?php

namespace Nether\Avenue;

use \Nether;
use \Exception;

class Router {

	public function __construct($opt=null) {
	/*//
	argv(array Options)
	//*/

		$opt = new Nether\Object($opt,[
			'Domain' => $this->GetRequestDomain(),
			'Path' => $this->GetRequestPath(),
			'Query' => $this->GetRequestQuery()
		]);

		$this->Domain = $opt->Domain;
		$this->Query = $opt->Query;

		// take care for paths.
		$this->Path = rtrim($opt->Path,'/');
		if(!$this->Path) $this->Path = '/index';

		return;
	}

	////////////////
	////////////////

	public function Run() {
		$this->Route = $this->GetSelectedRoute();

		return;
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
			'/.*?([^\.]+(?:\.[^\.]+)?)$/msi',
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

	protected $RouteUsed;
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

		if(!$this->IsRouteConditionValid($cond))
		throw new Exception("Route condition ({$cond}) is not valid.");

		if(!$this->IsRouteHandlerValid($hand))
		throw new Exception("Route handler ({$hand}) is not valid.");

		list($domain,$path) = explode('//',$cond);

		$this->Routes[] = (object)[
			'Domain' => "/^{$this->TranslateRouteCondition($domain)}$/",
			'Path' => "/^\/{$this->TranslateRouteCondition($path)}$/",
			'Handler' => $hand
		];

		return $this;
	}

	public function GetRoutes() {
	/*//
	return(array)
	//*/

		return $this->Routes;
	}

	public function GetRoute() {
	/*//
	return(array)
	//*/

		$dm = $pm = null;
		$selected = false;

		foreach($this->Routes as $route) {
			if(!preg_match($route->Domain,$this->Domain,$dm)) continue;
			if(!preg_match($route->Path,$this->Path,$pm)) continue;

			unset($dm[0],$pm[0]);
			$route->Argv = array_merge($dm,$pm);
			$selected = $route; break;
		}

		return $selected;
	}

	public function TranslateRouteCondition($cond) {
	/*//
	return(string)
	//*/

		$shortcut = [
			'(@)' => '(.+?)',
			'{@}' => '(?:.+?)',
			'(?)' => '(.*?)',
			'{?}' => '(?:.*?)',
			'(#)' => '(\d+)',
			'{#}' => '(?:\d+)',
			'($)' => '(\w+)',
			'{$}' => '(?:\w+)'
		];

		foreach($shortcut as $old => $new)
		$cond = str_replace($old,$new,$cond);

		return $cond;
	}

	public function TranslateRouteHandler($hand) {
	/*//
	return(string)
	//*/

		return $hand;
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

}
