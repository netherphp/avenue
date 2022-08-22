<?php

namespace Nether\Avenue\Meta;

use Attribute;
use ReflectionMethod;
use ReflectionAttribute;
use Nether\Object\Prototype\MethodInfo;
use Nether\Object\Prototype\MethodInfoInterface;

#[Attribute(Attribute::TARGET_METHOD|Attribute::IS_REPEATABLE)]
class ConfirmWillAnswerRequest
implements MethodInfoInterface {

	public ?string
	$MethodName;

	public function
	__Construct(?string $MethodName=NULL) {

		$this->MethodName = $MethodName;

		return;
	}

	public function
	OnMethodInfo(MethodInfo $Info, ReflectionMethod $RefMethod, ReflectionAttribute $RefAttrib):
	void {

		if($this->MethodName === NULL)
		$this->MethodName = "{$Info->Name}WillAnswerRequest";

		return;
	}


}
