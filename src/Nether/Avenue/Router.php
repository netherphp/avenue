<?php

namespace Nether\Avenue;

use Nether\Object\Prototype;
use Nether\Object\Datastore;
use Nether\Avenue\Meta\RouteHandler;

use SplFileInfo;

class Router {

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public Datastore
	$Conf;

	public Request
	$Request;

	public Response
	$Response;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected ?string
	$RouteFile = NULL;

	protected ?string
	$RouteRoot = NULL;

	protected ?string
	$RouteSource = NULL;

	protected ?string
	$WebRoot = NULL;

	protected Datastore
	$Handlers;

	protected Datastore
	$ErrorHandlers;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	__Construct(Datastore $Conf) {

		$this->Conf = $Conf;

		$this->OnReady();
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
			=> ($this->Conf[Library::ConfDomainSep] ?? '.')
		]);

		$this->Request->ParseRequest();

		////////

		$this->Response = new Response;

		////////

		// read from the cached index if it exists.

		if($this->RouteFile && is_readable($this->RouteFile)) {
			$Map = Datastore::NewFromFile($this->RouteFile);

			$this->RouteSource = 'cache';
			$this->Handlers = $Map['Verbs'];
			$this->ErrorHandlers = $Map['Errors'];
		}

		// scan the directory if needed.

		elseif($this->RouteRoot && is_dir($this->RouteRoot)) {
			$Scanner = new RouteScanner($this->RouteRoot);
			$Map = $Scanner->Generate();

			$this->RouteSource = 'dirscan';
			$this->Handlers = $Map['Verbs'];
			$this->ErrorHandlers = $Map['Errors'];
		}

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	GetSource():
	string {

		return $this->RouteSource;
	}

	public function
	GetHandlers():
	Datastore {

		return $this->Handlers;
	}

	public function
	SortHandlers():
	static {

		($this->Handlers)
		->Each(
			fn(Datastore $Verb)
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
	Select(?Datastore $ExtraData=NULL):
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
	Execute(?RouteHandler $Handler, ?Datastore $ExtraData=NULL):
	static {
	/*//
	execute the specified handler. if none was specified then it will try to
	run the proper 404 handler.
	//*/

		if($Handler !== NULL)
		return $this->Execute_RouteHandler($Handler, $ExtraData);

		// if we made it this far and the response code is ok that pretty
		// likely means our dev was super lazy as at this point we are now
		// processing which error handler to run. we will go ahead and

		if($this->Response->Code === Response::CodeOK)
		$this->Response->Code = Response::CodeNotFound;

		////////

		if(isset($this->ErrorHandlers[$this->Response->Code]))
		if($this->ErrorHandlers[$this->Response->Code] instanceof RouteHandler)
		return $this->Execute_RouteHandler(
			$this->ErrorHandlers[$this->Response->Code],
			$ExtraData
		);

		////////

		return $this;
	}

	protected function
	Execute_RouteHandler(RouteHandler $Handler, ?Datastore $ExtraData):
	static {
	/*//
	perform route handler execution.
	//*/

		$Inst = $Handler->GetRouteInstance($this->Request, $this->Response);

		$this->Response->CaptureBegin();
		$Inst->OnReady($ExtraData);
		$Inst->{$Handler->Method}(...$Handler->GetMethodArgValues());
		$Inst->OnDone();
		$this->Response->CaptureEnd(TRUE);

		return $this;
	}

	public function
	Render():
	static {
	/*//
	tells the current response object to render out.
	//*/

		$this->Response->Render();
		return $this;
	}

	public function
	Run(?Datastore $ExtraData=NULL):
	static {
	/*//
	perform all the operations needed to select execute and render out
	a request and response in one shot.
	//*/

		$this
		->SortHandlers()
		->Execute($this->Select($ExtraData), $ExtraData)
		->Render();

		return $this;
	}

}
