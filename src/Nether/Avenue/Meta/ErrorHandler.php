<?php

namespace Nether\Avenue\Meta;

use Nether\Avenue\Route;
use Nether\Avenue\Request;
use Nether\Object\Prototype\MethodInfo;
use Nether\Object\Prototype\MethodInfoInterface;

use Attribute;
use ReflectionNamedType;
use ReflectionAttribute;
use ReflectionMethod;

#[Attribute(Attribute::TARGET_METHOD)]
class ErrorHandler
extends RouteHandler {

	public int
	$Code = 400;

	public function
	__Construct(int $Code) {
		$this->Code = $Code;
		return;
	}


}
