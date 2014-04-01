<?php

namespace Nether;
use \Nether;

Nether\Option::Define([
	'nether-avenue-autoindex' => true,
	/*//option//
	@name nether-avenue-autoindex
	@type bool
	@default true
	determines if the router should add /index to the end of request uris to
	try and find the extra route in a subfolder based section structure.
	//*/

	'nether-avenue-autostash' => true,
	/*//option//
	@name nether-avenue-autostash
	@type bool
	@default true
	value which defines if avenue automatically attempts to throw itself into
	the Nether Stash when created.
	//*/

	'nether-avenue-stash-name' => 'avenue'
	/*//option//
	@name nether-avenue-stash-name
	@type string
	@default "avenue"
	the alias used when automatically storing itself into the nether stash.
	//*/
]);

class Avenue {
/*//
@type singleton
this class provides request routing capabilities, and is designed to work in
conjunction with an autoloader of some sorts. it should also work if your app
uses an include-all-the-things system too, though, as its a class_exists based
system.

* GET /test/hello-world
* check defined routes if they want to handle this request.
* check defined namespaces for route matching name Test\HelloWorld
* check defined commonspace for a fallback route matching Test\HelloWorld
* check if we have a 404 route defined, use it if we do.
* explode. (throw exception)
//*/

	protected $Commonspace;
	/*//
	@type string
	the default namespace where routes will be searched for if a more specific
	router was not found in a more specific namespace.
	//*/

	protected $ErrorRoutes = [];
	/*//
	@type array
	store classnames by their error number to use for errors. avenue makes use
	of 403 if a route accepts a request but won't allow it for some reason, and
	404 if no routes to handle the request were found.
	//*/

	protected $Namespaces = [];
	/*//
	@type array
	a list of namespaces that will be searched to find routes. first come first
	serve.
	//*/

	protected $Routable;
	/*//
	@type boolean
	if the current environment is actually routable. this will be false when
	running things from unroutable environments like the command line.
	//*/

	protected $Route;
	/*//
	@type m\Avenue\Route
	the route object that was determined for use to handle the current request.
	//*/

	protected $RouteName = 'Index';
	/*//
	@type string
	the calculated route name from the URI. this property is updated by the
	autorouter to reflect the actual route that it found after climbing
	through directories.
	URI: /test/route-test/yep
	Route Name: Test\RouteTest\Yep
	//*/

	protected $RouteNameRequested = 'Index';
	/*//
	@type stirng
	the calculated route name from the URI as as the request originally
	requested.
	//*/

	protected $Routes = [];
	/*//
	@type array
	holds the list of pre-registered routes that this router will take into
	consideration before attempting to autoroute.
	//*/

	////////////////
	////////////////

	protected $Domain;
	/*//
	@type string
	just the root domain as you buy one. whatever.tld.
	//*/

	protected $DomainFull;
	/*//
	@type string
	the full domain as it was given to the request, no fussing.
	//*/

	protected $HitHash;
	/*//
	@type string
	a hash that represents this hit, made from the user ip and request info.
	//*/

	protected $HitTime;
	/*//
	@type float
	the time that the hit occured.
	//*/

	protected $Path = 'index';
	/*//
	@type string
	the URI string of this request.
	//*/

	protected $PathList = [];
	/*//
	@type array
	the URI string broken up into path bits.
	//*/

	////////////////
	////////////////

	public function __construct($file=null) {

		// determine if this a routable environment (web hit vs cli, etc)
		$this->Routable = $this->IsRoutableEnvironment();

		// parse the request uri.
		// this fills the URI and RouteName property.
		if($this->Routable) $this->ParseRequest();

		// provide a simple way to identify this hit.
		$this->HitHash = md5("{$_SERVER['REMOTE_ADDR']}-{$this->DomainFull}-{$this->Path}");
		$this->HitTime = microtime(true);

		// load from file if specified.
		if($file) $this->SetFromFile($file);

		// throw itself into the stash.
		if(Nether\Option::Get('nether-avenue-autostash'))
		Nether\Stash::Set(Nether\Option::Get('nether-avenue-stash-name'),$this);

		return;
	}

	////////////////
	////////////////

	protected function IsRoutableEnvironment() {
	/*//
	@return boolean
	@flags internal
	attempts to determine if the current environment is even routable. e.g. is
	this a web request or are we being executed from the CLI? it matters.
	//*/

		// the best indication that we are running from a CLI is that there
		// is no request uri to process.
		if(!array_key_exists('REQUEST_URI',$_SERVER) && !$_SERVER['REQUEST_URI'])
		return false;

		// next best indication that we are running from CLI is that the argv
		// element exists and has content.
		if(array_key_exists('argv',$_SERVER) && count($_SERVER['argv']))
		return false;

		return true;
	}

	////////////////
	////////////////

	protected function ParseRequest() {
	/*//
	@flags internal
	begin the parse request chain of tests.
	//*/

		// parse out the domain name properties.
		$this->ParseRequest_Domain();

		// check that each chunk of the uri is uri safe. safe
		// (also nice/clean) uri may contain letters, numbers, dashes,
		// and underscores. if other things are found we will not process
		// it leaving the router in its default Index state.
		if(!$this->ParseRequest_IsSafeURI())
		throw new \Exception('Unclean URI found.');

		// make sure index is index.
		if(!$this->Path) {
			$this->Path = 'index';
			$this->PathList = ['index'];
		}

		// since we know the URI is safe we will process it into a route name
		// now.
		$this->ParseRequest_RouteName();

		// construct a full url from our sanitised data.
		$this->URL = sprintf(
			'%s://%s/%s',
			((array_key_exists('HTTPS',$_SERVER))?('https'):('http')),
			$this->DomainFull,
			$this->Path
		);

		return;
	}

	protected function ParseRequest_Domain() {
	/*//
	@flags internal
	fill in the domain name properties.
	//*/

		// store the full domain name as the request asked for it.
		$this->DomainFull = $_SERVER['HTTP_HOST'];

		// then clean it up to remove any subdomains.
		preg_match('/([^\.]+\.?[^\.]+)$/',$this->DomainFull,$match);
		$this->Domain = $match[1];

		return;
	}

	protected function ParseRequest_IsSafeURI() {
	/*//
	@return boolean
	@flags internal
	determine if a URI looks clean/safe.
	//*/

		// clean up trailing/proceeding slashes.
		$uri = trim($_SERVER['REQUEST_URI'],'/');

		// don't include the query string.
		if(strpos($uri,'?') !== false)
		$uri = trim(explode('?',$uri)[0],'/');

		// kill directory iteration attempts.
		$uri = preg_replace('/\.{2,}/','',$uri);

		// the rule being used to determine if a chunk is clean.
		$safex = '/^[a-zA-Z0-9\.\_\-]+$/';

		// blow the string up.
		$this->PathList = explode('/',$uri);

		$ok = true;
		foreach($this->PathList as $key => $chunk) {

			// discard empty chunks. most browsers and apps seem to ignore
			// them so we will too.
			if(!strlen($chunk)) {
				unset($this->PathList[$key]);
				continue;
			}

			// check that it is clean.
			if(!preg_match($safex,$chunk)) {
				$ok = false;
				break;
			}
		}

		// reconstruct the path using our clean data.
		if($ok)
		$this->Path = join('/',$this->PathList);

		return $ok;
	}

	protected function ParseRequest_RouteName() {
	/*//
	@flags internal
	convert: this/test-string/here
	into: This\TestString\Here
	and store into: this->RouteName
	//*/

		$chunks = explode('/',$this->Path);

		foreach($chunks as $key => $chunk) {
			$chunks[$key] =
			str_replace(' ','',ucwords(str_replace('-',' ',$chunk)));
		}

		$this->RouteNameRequested =
		$this->RouteName =
		join('\\',$chunks);

		return;
	}

	////////////////
	////////////////

	public function Run() {
	/*//
	allow the router to route and run. at this point it will step through the
	available methods to determine what route it should use, once found set
	an instance up, and then execute the route itself.

	if no routes are found, even the fallback ErrorRoutes404 route, then it will
	throw an exception for being exceptionally bad.
	//*/

		if(!$this->Routable)
		return;

		// attempt to find a class that is willing to handle the current
		// request, either from the list of pre-registered routes or from
		// allowing the autorouter to go.

		$class = null;

		{{{
			// check registered routes.
			$class = $this->SelectRouteFromList();

			// check autoloader.
			if(!$class)
			$class = $this->SelectRouteFromAuto();

			// check error handler.
			if(!$class)
			$class = $this->SelectRouteFromError(404);
		}}}

		if(!$class)
		throw new \Exception("No routes found willing to handle `{$this->Path}` ({$this->RouteNameRequested})");

		// setup the route and see if it said it would allow itself to be run
		// for this request.

		try { $this->RouteSetup($class); }
		catch(m\Avenue\Error\NotAllowed $e) {
			$class = $this->SelectRouteFromError(403);
			if($class) {
				$this->RouteSetup($class);
			} else {
				throw new \Exception("Route refused to handle this request.");
			}
		}

		// run the route.
		$this->RouteRun();

		return;
	}

	protected function RouteSetup($class) {
	/*//
	@flags internal
	setup an instance of the route that was selected for use.
	//*/

		$this->Route = new $class;
		return;
	}

	protected function RouteRun() {
	/*//
	@flags internal
	execute the selected route.
	//*/

		Nether\Ki::Flow('nether-avenue-request');
		Nether\Ki::Flow('nether-avenue-main');
		Nether\Ki::Flow('nether-avenue-output');

		return;
	}

	////////////////
	////////////////

	public function GetDomain($full=false) {
	/*//
	@argv bool FullDomain default false
	@return string
	fetch the domain name from this request. by default will only return the
	usual whatever.tld domain. if given true then will return the full domain
	name as written in the request, omg.wtf.bbq.whatever.tld etc.
	//*/

		if(!$full) return $this->Domain;
		else return $this->DomainFull;
	}

	public function GetHit() {
	/*//
	@return object
	return an object that defines the unique description of this request: the
	hit hash and the request time.
	//*/

		return (object)[
			'Hash' => $this->GetHitHash(),
			'Time' => $this->GetHitTime()
		];
	}

	public function GetHitHash() {
	/*//
	@return string
	return the hit hash for this request.
	//*/

		return $this->HitHash;
	}

	public function GetHitTime() {
	/*//
	@return float
	return the hit time for this request.
	//*/

		return $this->HitTime;
	}

	public function GetPath() {
	/*//
	@return string
	return the path of the request.
	//*/

		return $this->Path;
	}

	public function GetPathList() {
	/*//
	@return array
	return the path this request as an array broken at the slashes.
	//*/

		return $this->PathList;
	}

	public function GetPathSlot($slot) {
	/*//
	@return string
	return the specific slot of the path as broken up at the slashes.
	//*/

		if(array_key_exists($slot,$this->PathList))
		return $this->PathList[$slot];

		else
		return false;
	}

	////////////////
	////////////////

	public function AppendRoute($class) {
	/*//
	@argv string FullClassName
	@return self
	add a new route to check the request at the bottom of the list.
	//*/

		$this->Routes[] = $class;
		return $this;
	}

	public function PrependRoute($class) {
	/*//
	@argv string FullClassName
	@return self
	add a new route to check the request at the top of the list.
	//*/

		array_unshift($this->Routes,$class);
		return $this;
	}

	public function AppendNamespace($ns) {
	/*//
	@argv string NamespaceName
	@return self
	add a new namespace to search for routes at the bottom of the route list.
	//*/

		$this->Namespaces[] = $ns;
		return $this;
	}

	public function PrependNamespace($ns) {
	/*//
	@argv string NamespaceName
	@return self
	add a new namespace to search for routes at the top of the route list.
	//*/

		array_unshift($this->Namespace,$ns);
		return $this;
	}

	public function SetCommonspace($ns) {
	/*//
	@argv string NamespaceName
	@return self
	set the common namespace to look for routes not found in the specific
	namespace.
	//*/

		$this->Commonspace = $ns;
		return $this;
	}

	public function SetErrorRoute($errno,$class) {
	/*//
	@argv int Errno, string FullClassName
	@return self
	sets the class to use for the specific errors.
	//*/

		$this->ErrorRoutes[$errno] = $class;
		return $this;
	}

	public function SetFromFile($filename) {
	/*//
	@argv string Filename
	@return self
	load a route mapping from a json file on disk.
	//*/


		$file = new Nether\Avenue\ConfigFile($filename);
		$this->Routes = (array)$file->Avenues;
		$this->Namespaces = (array)$file->Namespaces;
		$this->Commonspace = (string)$file->Commonspace;
		$this->ErrorRoutes = (array)$file->ErrorRoutes;

		return $this;
	}

	////////////////
	////////////////

	protected function SelectRouteFromList() {
	/*//
	@return string
	@flags internal
	returns the class name of the first registered route that claims it can
	handle the current request. returns boolean false if none found.
	//*/

		foreach($this->Routes as $class) {
			if(!is_a($class,'m\Avenue\Route',true))
			continue;

			if($class::WillHandleRequest($this))
			return $class;
		}

		return false;
	}

	protected function SelectRouteFromAuto() {
	/*//
	@return string
	@flags internal
	attempt to find a class that will handle the current request as determined
	by the route name.
	//*/

		$class = null;

		// try and find a route from the list of namespaces.
		foreach($this->Namespaces as $ns) {
			$class = $this->SelectRouteFromAuto_Namespace($ns);
			if($class) break;
		}

		// try and find a route from a common namespace.
		if(!$class && $this->Commonspace)
		$class = $this->SelectRouteFromAuto_Namespace($this->Commonspace);

		// update the route name property with the class we found.
		if($class)
		$this->RouteName = $class;

		return $class;
	}

	protected function SelectRouteFromAuto_Namespace($ns) {
	/*//
	@return string
	@flags internal
	recursively check a namespace for a class that can handle the request.
	//*/

		$nslen = substr_count($ns,'\\') + 1;

		$target = "{$ns}\\{$this->RouteName}";

		if(Nether\Option::Get('nether-avenue-autoindex'))
		$target .= "\\Index";

		$chunks = explode('\\',$target);
		$class = null;

		do {
			$class = join('\\',$chunks);
			if(class_exists($class,true)) {
				if(!is_a($class,'Nether\Avenue\Route',true))
				goto NextTry;

				if(!$class::WillHandleRequest($this))
				goto NextTry;

				return $class;
			}

			NextTry:
			array_pop($chunks);
		} while(count($chunks) > $nslen);

		return false;
	}

	protected function SelectRouteFromError($errno) {
	/*//
	@return string
	@flags internal
	check if the class specified as the error route is able to be used as one.
	//*/

		if(!array_key_exists($errno,$this->ErrorRoutes))
		return false;

		if(class_exists($this->ErrorRoutes[$errno],true)) {
			if(is_a($this->ErrorRoutes[$errno],'Nether\Avenue\Route',true))
			return $this->ErrorRoutes[$errno];
		}

		return false;
	}

}
