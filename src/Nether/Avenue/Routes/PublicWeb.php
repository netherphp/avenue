<?php

namespace Nether\Avenue\Routes;

use \Nether as Nether;

class PublicWeb {

	static protected
	$OnConstruct = TRUE;
	/*//
	@type Boolean
	@date 2017-08-17
	handles if the OnConstruct event should happen automaticlally. if set
	false in the definition of a child class they can then call OnConstruct()
	themselves at the end of their extended constructors to avoid the double
	post.
	//*/

	static public function
	Encode64URL(String $Input):
	String {
	/*//
	@date 2017-12-15
	@edit 2017-12-19
	encodes a base64 string in url mode.
	//*/

		return str_replace(
			['+','/'],
			['-','_'],
			rtrim(base64_encode($this->Router->GetURL()),'=')
		);
	}

	static public function
	Decode64URL(String $Input):
	String {
	/*//
	@date 2017-12-15
	@edit 2017-12-19
	decodes a url64 string.
	//*/

		return base64_decode(str_replace(
			['-','_'],
			['+','/'],
			$Input
		));
	}

	////////////////////////////////
	////////////////////////////////

	public
	$Get = TRUE;
	/*//
	@type Nether\Input\Filter
	@date 2017-12-19
	if set true in the class definition, will be replaced with a filter object
	pointing at GET data.
	//*/

	public
	$Post = TRUE;
	/*//
	@type Nether\Input\Filter
	@date 2017-12-19
	if set true in the class definition, will be replaced with a filter object
	pointing at POST data.
	//*/

	////////////////////////////////
	////////////////////////////////

	protected
	$SurfaceRenderScope = [
		'Route'   => NULL,
		'Router'  => 'Router',
		'Surface' => 'Surface'
	];
	/*//
	@date 2017-12-19
	the properties that will be applied via surface-render-scope so that they
	can be directly accessed by name in the theme area files.
	//*/

	protected function
	ApplySurfaceRenderScope():
	Void {
	/*//
	@date 2017-12-19
	apply the surface render scope ki for the theme engine.
	//*/

		Nether\Ki::Queue(
			'surface-render-scope',
			function(&$Scope){
				foreach($this->SurfaceRenderScope as $Key => $Source) {
					if($Source === NULL)
					$Scope[$Key] = $this;

					elseif(property_exists($this,$Key))
					$Scope[$Key] = $this->{$Source};
				}
				return;
			},
			TRUE
		);

		return;
	}

	public function
	GetSurfaceRenderScope():
	Nether\Object\Datastore {
	/*//
	@date 2017-12-19
	//*/

		return $this->SurfaceRenderScope;
	}

	public function
	SetSurfaceRenderScope(Array $Input):
	self {
	/*//
	@date 2017-12-19
	//*/

		$this->SurfaceRenderScope = $Input;
		return $this;
	}

	////////////////////////////////
	////////////////////////////////

	public function
	__construct() {

		if($this->Get === TRUE)
		$this->Get = new Nether\Input\Filter($_GET);

		if($this->Post === TRUE)
		$this->Post = new Nether\Input\Filter($_POST);

		// setup scopes for the route handler. other libraries can listen
		// for this ki, originally designed for other libraries to add new
		// properties to it for their application. responders to this ki
		// should accept a single argument that is this route.

		Nether\Ki::Flow('avenue-route-init',[
			'Router' => $this
		]);

		// setup the scopes for the theme engine from the requested
		// properties if they exist on this object.

		$this->ApplySurfaceRenderScope();

		// allow child classes to delay this until they are ready for
		// it at the end of their own constructors.

		if(static::$OnConstruct)
		$this->OnConstruct();

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	OnConstruct():
	Void {
	/*//
	@date 2017-12-19
	extension classes can fill this out if desired.
	//*/

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	GetRequestMethod():
	String {
	/*//
	@date 2017-10-25
	figure out what type of request this is. how is this not in nether avenue
	by default lulz.
	//*/

		if(array_key_exists('REQUEST_METHOD',$_SERVER))
		return (String)$_SERVER['REQUEST_METHOD'];

		return 'GET';
	}

	public function
	GetEncodedURL():
	String {
	/*//
	@date 2017-12-15
	@edit 2017-12-19
	returns a base64 encoded url safe string. actually a valid format check
	wikipedia.
	//*/

		return static::Encode64URL($this->Router->GetURL());
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	Goto(String $URI, String $AppendGoto=''):
	Void {
	/*//
	@date 2017-08-15
	@update 2017-12-15
	//*/

		if($AppendGoto) {

			// prepare the goto url.

			if($AppendGoto === 'nether://self')
			$AppendGoto = $this->GetEncodedURL();

			else
			$AppendGoto = static::Encode64URL($AppendGoto);

			// repare the final header url.

			if(strpos($URI,'?') === FALSE)
			$URI .= "?goto={$AppendGoto}";

			else
			$URI .= "&goto={$AppendGoto}";
		}

		header("Location: {$URI}");
		exit(0);
		return;
	}

}
