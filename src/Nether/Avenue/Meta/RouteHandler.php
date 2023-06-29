<?php

namespace Nether\Avenue\Meta;

use Nether\Avenue;
use Nether\Common;

use Nether\Avenue\Route;
use Nether\Avenue\Request;
use Nether\Avenue\Response;
use Nether\Avenue\Struct\ExtraData;
use Nether\Avenue\Struct\RouteHandlerArg;
use Nether\Avenue\Error\RouteMissingWillAnswerRequest;
use Nether\Common\Prototype\MethodInfo;

use Attribute;
use Exception;
use ReflectionNamedType;
use ReflectionAttribute;
use ReflectionMethod;

#[Attribute(Attribute::TARGET_METHOD|Attribute::IS_REPEATABLE)]
class RouteHandler
implements Common\Prototype\MethodInfoInterface {

	const
	CanAnswerRequest = 1,
	WillAnswerRequest = 2,
	WillAllowRequest = 3;

	public string
	$Verb;

	public ?string
	$Domain = NULL;

	public ?string
	$Path = NULL;

	public ?string
	$Class = NULL;

	public ?string
	$Method = NULL;

	public ?string
	$Sort = NULL;

	public array
	$Args = [];

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	__Construct(?string $Path=NULL, ?string $Domain=NULL, ?string $Verb='GET', ?string $Sort=NULL) {

		$Var = NULL;

		// handle being able to look up global settings.

		foreach([ 'Path', 'Domain', 'Verb', 'Sort' ] as $Var)
		if(is_string($$Var) && str_starts_with($$Var, '@'))
		$$Var = (
			Avenue\Library::Get(str_replace('@', '', $$Var))
			?? $$Var
		);

		// proceed with processing.

		$this->Verb = strtoupper($Verb);
		$this->Domain = $Domain;
		$this->Path = $Path;
		$this->Sort = $Sort;

		return;
	}

	public function
	__ToString():
	string {

		return sprintf(
			'%s::%s(Path: %s, Domain: %s, Verb: %s);',
			$this->Class,
			$this->Method,
			$this->Path,
			($this->Domain ?? '<any>'),
			$this->Verb
		);
	}

	////////////////////////////////////////////////////////////////
	// implements MethodInfoInterface //////////////////////////////

	public function
	OnMethodInfo(MethodInfo $Info, ReflectionMethod $RefMethod, ReflectionAttribute $RefAttrib):
	void {

		$this->Class = $Info->Class;
		$this->Method = $Info->Name;
		$this->OnMethodInfo_DigestMethodArgs($RefMethod);

		if($this->Sort === NULL)
		$this->Sort = $this->GenerateSortKey();

		return;
	}

	protected function
	OnMethodInfo_DigestMethodArgs(ReflectionMethod $RefMethod):
	void {

		$RefParam = NULL;
		$RefParamType = NULL;
		$RefParamName = NULL;
		$RefParamTypeStr = NULL;

		// check what args this method expects.

		foreach($RefMethod->GetParameters() as $RefParam) {
			$RefParamName = $RefParam->GetName();
			$RefParamType = $RefParam->GetType();

			if($RefParamType instanceof ReflectionNamedType) {
				if($RefParamType->IsBuiltIn())
				$RefParamTypeStr = $RefParamType->GetName();
				else
				$RefParamTypeStr = 'mixed';
			}

			else {
				$RefParamTypeStr = 'mixed';
			}

			$this->Args[$RefParamName] = new RouteHandlerArg(
				$RefParamName,
				$RefParamTypeStr
			);
		}

		return;
	}

	////////////////////////////////////////////////////////////////
	// route acceptance api ////////////////////////////////////////

	public function
	GetRouteInstance(Request $Req, ?Response $Resp=NULL):
	Route {
	/*//
	spawn an instance of the route handler this attribute describes and
	keep it around for reuse later in the event route acceptance succeeds.
	//*/

		return new ($this->Class)($this, $Req, $Resp);
	}

	public function
	CanAnswerRequest(Request $Req):
	bool {
	/*//
	checks if this route is able to satisify the specified request by
	matching the request against its defined route expression. this does not
	need to spawn an instance of the route at this stage.
	//*/

		$RegEx = $this->GetRegEx();
		$Input = $Req->GetRegInput();
		$Data = [];

		// determine if we could answer this request.

		if($Req->Verb !== $this->Verb)
		return FALSE;

		$Found = preg_match($RegEx, $Input, $Data);

		if($Found === 0)
		return FALSE;

		// determine the method arguments we would need to execute.

		$Arg = NULL;
		$Iter = 2;

		// 0 is the matched string
		// 1 is the domain name
		// 2+ are the arguments.

		//Nether\Common\Dump::Var($this->Args);

		foreach($this->Args as $Arg) {
			if(array_key_exists($Iter, $Data))
			$Arg->Value = $Data[$Iter++];

			// data from the urls and stuff come in as strings they will
			// be recast for the method args.

			if($Arg->Type && $Arg->Type !== 'mixed') {

				// if we are not asking for a string, but it obviously
				// seemed like a string, then call it a falsy value
				// instead. this will prevent 'banana' from casting to
				// 0 and '42banana' from casting to 1, neither of which
				// are suitable when expecting things like numeric
				// ids in the url. this will still allow strings of
				// zero and one to parse as bools.

				if($Arg->Type !== 'string')
				if($Arg->Value !== NULL && !ctype_digit($Arg->Value))
				$Arg->Value = 0;

				settype($Arg->Value, $Arg->Type);
			}

		}

		return TRUE;
	}

	public function
	WillAnswerRequest(Request $Req, Response $Resp, ?ExtraData $ExtraData=NULL):
	?bool {
	/*//
	checks if this route is willing to satisify the specified request
	by spawning an instance and asking for its opinion. example use is do
	maybe check if a user exists in the db before showing their profile and
	if not, refuse to handle it, allowing your 404 to pick up up this
	request.
	//*/

		$Inst = $this->GetRouteInstance($Req, $Resp);
		$Info = ($Inst)::GetMethodInfo($this->Method);
		$ExtraDataName = 'ExtraData';
		$Attribs = NULL;
		$Attrib = NULL;
		$Confirm = NULL;

		$WillRef = NULL;
		$WillHdl = NULL;
		$WillArgs = NULL;

		// check if the method has a ConfirmWillAnswerRequest that defines
		// what method to use as a pre-check. that method should accept
		// a request as input and spit a boolean out regarding if it is
		// willing to handle the request for real or not.

		$Attribs = $Info->GetAttribute(ConfirmWillAnswerRequest::class);

		if($Attribs === NULL)
		return TRUE;

		////////

		if(!is_array($Attribs))
		$Attribs = [ $Attribs ];

		foreach($Attribs as $Attrib) {

			if(!method_exists($Inst, $Attrib->MethodName)) {
				$Resp->SetCode(Response::CodeServerError);
				throw new RouteMissingWillAnswerRequest(
					$Info->Name,
					$Attrib->MethodName
				);
			}

			////////

			// this is some foolery that allows the confirm method to
			// have differing arguments than the final handler method
			// while keeping the path slots intact. WillAnswer methods
			// can accept the $ExtraData argument while the final handler
			// will have the extra data expanded as arguments after the
			// path slots.

			$WillInfo = ($Inst)::GetMethodInfo($Attrib->MethodName);
			$WillArgs = array_intersect_key($this->Args, $WillInfo->Args);

			if($WillInfo->CountArgsOfType(ExtraData::class) > 0) {
				$ExtraDataName = $WillInfo->GetArgsOfType(ExtraData::class)[0];
				$WillArgs[$ExtraDataName] = new RouteHandlerArg(
					Name: $ExtraDataName,
					Type: ExtraData::class
				);
			}

			//if(array_key_exists('ExtraData', $WillInfo->Args))
			//$WillArgs['ExtraData'] = new RouteHandlerArg(
			//	Name: 'ExtraData',
			//	Type: ExtraData::class
			//);

			////////

			// give the confirm ready method a chance to provide the
			// same system to accept or bail that the actual confirm
			// method has. this will provide a way for a framework to
			// have stuff like a global auth check prior to whatever
			// it wanted to validate in the confirm method. specifically
			// for example atlantis uses attributes on the real handing
			// method to restrict to users or admins. this will allow
			// the will confirm ready to inspect those prior so that
			// the final will confirm would not need to.

			$Confirm = $Inst->OnWillConfirmReady($ExtraData);

			if($Confirm === Response::CodeOK) {
				$Confirm = ($Inst)->{$Attrib->MethodName}(
					...static::RemapArgValues($WillArgs, $ExtraData, FALSE, $ExtraDataName)
				);

				$Inst->OnWillConfirmDone();
			}

			// hard fails will push their response code into the
			// response object and quit asking. redirects are considered
			// fails too so that the client may close the connection and
			// deal with any location headers it got.

			if($Confirm >= 300) {
				$Resp->SetCode($Confirm);
				return NULL;
			}

			// a soft fail will allow the router to continue asking other
			// routes if they want to handle it, so a response status
			// is not quite relevant yet.

			if($Confirm === 0 || $Confirm === 100)
			return FALSE;
		}

		////////

		return TRUE;
	}

	////////////////////////////////////////////////////////////////
	// misc util ///////////////////////////////////////////////////

	public function
	GetRegEx():
	string {
	/*//
	convert the request into a regex that can be tested against the route
	mappings by the router.
	//*/

		if($this->Domain === NULL)
		$DomainComp = '([^\/]+)';
		else
		$DomainComp = sprintf('(%s)', preg_quote($this->Domain, '#'));

		if($this->Path === NULL)
		$PathComp = '/(.+?)';
		else
		$PathComp = preg_quote($this->Path, '#');

		// replace tokens with slotted regex wildcards.

		$RegEx = preg_replace(
			'#\\\\:[A-Za-z0-9]+\\\\:#',
			'([^\/]+)',
			"#^//{$DomainComp}{$PathComp}$#"
		);

		//\Nether\Common\Dump::Var($RegEx, TRUE);

		$RegEx = str_replace(
			'\:([^\/]+)\:',
			'(.+?)',
			$RegEx
		);

		return $RegEx;
	}

	public function
	GetCallableName():
	string {
	/*//
	generate a fqcn for the route method.
	//*/

		return "{$this->Class}::{$this->Method}";
	}

	public function
	GetMethodArgValues(?ExtraData $ExtraData=NULL, bool $ExpandExtra=FALSE):
	array {
	/*//
	remap the method arguments for dumping into the method calls.
	//*/

		return static::RemapArgValues(
			$this->Args,
			$ExtraData,
			$ExpandExtra
		);
	}

	protected function
	GenerateSortKey():
	string {
	/*//
	generate a string for the string key that will try to make sense to
	both people and the wildcard system.
	//*/

		// the goal with this iteration is that the sorting gets grouped
		// by the class file that contained the routes, with an attempt to
		// clever sort things needing wildcards down to the bottom so that
		// specific routes that smell too similar can still get their word
		// in before a wildcard consumes it all.

		$Bit = NULL;
		$Bits = explode('/', trim($this->Path ?? '', '/'));

		$Bout = sprintf(
			'%s',
			//str_replace('\\', '-', strtolower($this->Class)),
			($this->Domain ? 'ds' : 'dw')
		);

		foreach($Bits as $Bit)
		$Bout .= match(TRUE) {
			str_contains($Bit, ':')
			=> '-w',

			default
			=> '-s'
		};

		return $Bout;
	}

	static public function
	RemapArgValues(array $Args, ?ExtraData $ExtraData=NULL, bool $ExpandExtra=FALSE, string $NameExtra='ExtraData'):
	array {
	/*//
	remap the method arguments for dumping into the method calls.
	//*/

		// expand the extradata data set if there is a key in there that
		// matches an existing argument.

		if($ExpandExtra && $ExtraData)
		$ExtraData->Each(function(mixed $Val, string $Key) use($Args) {
			if(array_key_exists($Key, $Args))
			$Args[$Key]->Value = $Val;

			return;
		});

		// if an extradata argument was asked for them give it the entire
		// extradata data object.

		if(array_key_exists($NameExtra, $Args))
		$Args[$NameExtra]->Value = $ExtraData;

		// then remap the values to a flat list.

		return array_map(
			function(RouteHandlerArg $Arg){ return $Arg->Value; },
			$Args
		);
	}

}
