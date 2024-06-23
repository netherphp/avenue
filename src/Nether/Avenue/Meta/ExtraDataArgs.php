<?php

namespace Nether\Avenue\Meta;

use Attribute;
use ReflectionMethod;
use ReflectionAttribute;
use Nether\Common\Prototype\MethodInfo;
use Nether\Common\Prototype\MethodInfoInterface;

#[Attribute(Attribute::TARGET_METHOD)]
class ExtraDataArgs
implements MethodInfoInterface {

	public function
	__Construct() {



		return;
	}

	public function
	OnMethodInfo(MethodInfo $Info, ReflectionMethod $RefMethod, ReflectionAttribute $RefAttrib):
	void {

		return;
	}

}
