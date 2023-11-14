<?php

namespace Nether\Avenue;

use Nether\Common;

use Nether\Avenue\Meta\RouteHandler;
use ArgumentCountError;
use Throwable;

class Router {

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public Common\Datastore
	$Conf;

	public Request
	$Request;

	public Response
	$Response;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected bool
	$HasExecuted = FALSE;

	protected bool
	$HasRendered = FALSE;

	protected ?string
	$RouteFile = NULL;

	protected ?string
	$RouteRoot = NULL;

	protected ?string
	$RouteSource = 'none';

	protected ?string
	$WebRoot = NULL;

	protected Common\Datastore
	$Handlers;

	protected Common\Datastore
	$ErrorHandlers;

	protected ?RouteHandler
	$CurrentHandler = NULL;

	protected ?Route
	$CurrentRoute = NULL;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	__Construct(Common\Datastore $Conf) {

		$this->Conf = $Conf;

		$this->Handlers = new Common\Datastore;
		$this->ErrorHandlers = new Common\Datastore;

		$this->OnReady();
		return;
	}

	public function
	__Destruct() {

		if($this->HasExecuted && !$this->HasRendered)
		$this->Render();

		return;
	}

	protected function
	OnReady():
	void {

		// make sure we know what we need to know about the route handlers.

		$this->RouteFile = $this->Conf[Library::ConfRouteFile];
		$this->RouteRoot = $this->Conf[Library::ConfRouteRoot];

		if($this->RouteFile === NULL && $this->RouteRoot === NULL)
		throw new Error\RouterRouteRootUndefined;

		// make sure we know what we need to know about the web root.

		$this->WebRoot = $this->Conf[Library::ConfWebRoot];

		if($this->WebRoot === NULL) {
			if(isset($_SERVER['DOCUMENT_ROOT']))
			$this->WebRoot = $_SERVER['DOCUMENT_ROOT'] ?: NULL;
		}

		if($this->WebRoot === NULL)
		throw new Error\RouterWebRootUndefined;

		////////

		$this->Request = new Request([
			'DomainLvl'
			=> ($this->Conf[Library::ConfDomainLvl] ?? 2),

			'DomainSep'
			=> ($this->Conf[Library::ConfDomainSep] ?? '.'),

			'VerbRewrite'
			=> ($this->Conf[Library::ConfVerbRewrite] ?? FALSE)
		]);

		$this->Request->ParseRequest();

		////////

		$this->Response = new Response;

		////////

		// read from the cached index if it exists.

		if($this->RouteFile && is_readable($this->RouteFile)) {
			$this->ReadRouteFile();
		}

		// scan the directory if needed.

		elseif($this->RouteRoot && is_dir($this->RouteRoot)) {
			$this->ScanForRoutes();
		}

		return;
	}

	public function
	ResetExecutedRendered():
	void {

		$this->HasExecuted = FALSE;
		$this->HasRendered = FALSE;

		return;
	}

	public function
	ReadRouteFile():
	static {

		$Map = Common\Datastore::NewFromFile($this->RouteFile);

		$this->RouteSource = Library::RouteSourceFile;
		$this->Handlers->MergeRight($Map['Verbs']);
		$this->ErrorHandlers->MergeRight($Map['Errors']);

		return $this;
	}

	public function
	ScanForRoutes():
	static {

		$Scanner = new RouteScanner($this->RouteRoot);
		$Map = $Scanner->Generate();

		$this->RouteSource = Library::RouteSourceScan;
		$this->Handlers->MergeRight($Map['Verbs']);
		$this->ErrorHandlers->MergeRight($Map['Errors']);

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	GetCurrentHandler():
	?RouteHandler {

		return $this->CurrentHandler;
	}

	public function
	GetCurrentRoute():
	?Route {

		return $this->CurrentRoute;
	}

	public function
	GetRouteFile():
	?string {

		return $this->RouteFile;
	}

	public function
	GetSource():
	string {

		return $this->RouteSource;
	}

	public function
	AddHandler(Meta\RouteHandler $Handler):
	static {

		if(!$this->Handlers->HasKey($Handler->Verb))
		$this->Handlers->Shove($Handler->Verb, new Common\Datastore);

		$this->Handlers[$Handler->Verb]->Push($Handler);

		return $this;
	}

	public function
	AddHandlers(iterable $Handlers):
	static {

		$Handler = NULL;

		foreach($Handlers as $Handler) {
			if($Handler instanceof Meta\RouteHandler)
			$this->AddHandler($Handler);
		}

		return $this;
	}

	public function
	AddErrorHandler(int $Code, Meta\RouteHandler $Handler):
	static {

		$this->ErrorHandlers["e{$Code}"] = $Handler;

		return $this;
	}

	public function
	AddErrorHandlers(iterable $Handlers):
	static {

		$Key = NULL;
		$Handler = NULL;

		foreach($Handlers as $Key => $Handler) {
			if(!($Handler instanceof Meta\RouteHandler))
			continue;

			$this->ErrorHandlers["e{$Key}"] = $Handler;
		}

		return $this;
	}

	public function
	GetHandlers():
	Common\Datastore {

		return $this->Handlers;
	}

	public function
	GetErrorHandlers():
	Common\Datastore {

		return $this->ErrorHandlers;
	}

	public function
	SortHandlers():
	static {

		($this->Handlers)
		->Each(
			fn(Common\Datastore $Verb)
			=> $Verb->Sort(
				function(RouteHandler $A, RouteHandler $B) {
					$CA = substr_count($A->Sort, '-');
					$CB = substr_count($B->Sort, '-');

					if($CA !== $CB)
					return $CB <=> $CA;

					if($A->Sort !== $B->Sort)
					return $A->Sort <=> $B->Sort;

					if($A->Domain !== $B->Domain)
					return $A->Domain <=> $B->Domain;

					if($A->Path !== $B->Path)
					return $A->Path <=> $B->Path;

					return $A->Method <=> $B->Method;
				}
			)
		);

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	Select(?Struct\ExtraData $ExtraData=NULL):
	?RouteHandler {
	/*//
	inspect the current request and determine if we have a handler that can
	be executed to satisfy it.
	//*/

		if(!$this->Handlers->HasKey($this->Request->Verb))
		return NULL;

		////////

		$Handler = NULL;
		$Code = NULL;

		foreach($this->Handlers[$this->Request->Verb] as $Handler) {
			/** @var Meta\RouteHandler $Handler */

			// check if this route can answer for this request.
			// try the next one.

			$this->CurrentHandler = $Handler;

			if(!$Handler->CanAnswerRequest($this->Request))
			continue;

			////////

			$Code = $Handler->WillAnswerRequest(
				$this->Request,
				$this->Response,
				$ExtraData
			);

			// false means this did not care to handle this request and
			// it is ok to ask another route to try.

			if($Code === FALSE)
			continue;

			// null means it wanted to handle it but it refuses and
			// also does not want other routes to try.

			if($Code === NULL)
			return NULL;

			////////

			return $Handler;
		}

		return NULL;
	}

	public function
	Execute(?RouteHandler $Handler, ?Common\Datastore $ExtraData=NULL):
	static {
	/*//
	execute the specified handler. if none was specified then it will try to
	run the proper 404 handler.
	//*/

		$this->HasExecuted = TRUE;

		if($Handler !== NULL)
		return $this->Execute_RouteHandler($Handler, $ExtraData);

		// if we made it this far and the response code is ok that pretty
		// likely means our dev was super lazy as at this point we are now
		// processing which error handler to run. we will go ahead and

		if($this->Response->Code === Response::CodeOK)
		$this->Response->Code = Response::CodeNotFound;

		////////

		$CodeKey = "e{$this->Response->Code}";

		if(isset($this->ErrorHandlers[$CodeKey]))
		if($this->ErrorHandlers[$CodeKey] instanceof RouteHandler)
		return $this->Execute_RouteHandler(
			$this->ErrorHandlers[$CodeKey],
			$ExtraData
		);

		////////

		return $this;
	}

	protected function
	Execute_RouteHandler(RouteHandler $Handler, ?Common\Datastore $ExtraData):
	static {
	/*//
	perform route handler execution.
	//*/

		$this->CurrentHandler = $Handler;
		$this->CurrentRoute = $Handler->GetRouteInstance($this->Request, $this->Response);

		$this->Response->CaptureBegin();
		$this->CurrentRoute->OnReady($ExtraData);

		////////

		if($Handler instanceof Meta\ErrorHandler)
		$this->Response->SetCode($Handler->Code);

		////////

		try {
			$this->CurrentRoute->{$Handler->Method}(...$Handler->GetMethodArgValues($ExtraData, TRUE));
		}

		catch(ArgumentCountError $Err) {
			// @todo 2023-09-13 inspect the trace stack better so that it
			// catches fewer app fuckups.
			Common\Dump::Var($Err, TRUE);
			throw new Error\RouteArgumentError($this->CurrentHandler, $this->CurrentRoute);
		}

		catch(Throwable $Err) {
			$ErrMsg = strtolower($Err->GetMessage());

			// imagine if php errors had codes that weren't all zero
			// wouldn't that be amazing if they used their own features
			// lmao.

			if(str_contains($ErrMsg, 'unknown named parameter'))
			throw new Error\RouteArgumentError($this->CurrentHandler, $this->CurrentRoute);

			throw $Err;
		}

		$this->CurrentRoute->OnDone();
		$this->Response->CaptureEnd(TRUE);

		////////

		return $this;
	}

	public function
	Render():
	static {
	/*//
	tells the current response object to render out.
	//*/

		$this->HasRendered = TRUE;
		$this->Response->Render();

		return $this;
	}

	public function
	Run(?Struct\ExtraData $ExtraData=NULL):
	static {
	/*//
	perform all the operations needed to select execute and render out
	a request and response in one shot.
	//*/

		if($ExtraData === NULL)
		$ExtraData = new Struct\ExtraData;

		////////

		if($this->RouteSource !== Library::RouteSourceFile)
		$this->SortHandlers();

		////////

		$this
		->Execute($this->Select($ExtraData), $ExtraData)
		->Render();

		return $this;
	}

}
