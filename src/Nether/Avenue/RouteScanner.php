<?php

namespace Nether\Avenue;

use Nether\Object\Datastore;
use Nether\Object\Prototype\MethodInfo;
use Nether\Avenue\Meta\RouteHandler;
use Nether\Avenue\Meta\ErrorHandler;
use SplFileInfo;
use FilesystemIterator;

class RouteScanner {

	protected const
	RouteBaseClass = 'Nether\\Avenue\\Route';

	public SplFileInfo
	$Directory;

	public array
	$Classes = [];

	public array
	$Routes = [];

	public array
	$ErrorRoutes = [];

	public function
	__Construct(string $Path) {

		$this->Directory = new SplFileInfo($Path);

		////////

		if(!$this->Directory->IsDir())
		throw new Error\RouteScannerDirInvalid($Path);

		if(!$this->Directory->IsReadable())
		throw new Error\RouteScannerDirUnreadable($Path);

		////////

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	Generate():
	Datastore {

		$Output = new Datastore;
		$Verbs = new Datastore;
		$Errors = new Datastore;

		$ClassFiles = NULL;
		$RouteClasses = NULL;
		$RouteClass = NULL;
		$RouteMethods = NULL;
		$RouteMethod = NULL;

		////////

		$ClassFiles = $this->FetchFilesInDir($this->Directory);
		$RouteClasses = $this->DetermineRoutableClasses($ClassFiles);

		////////

		foreach($RouteClasses as $RouteClass) {
			$RouteMethods = $this->DetermineRoutableMethods($RouteClass);
			foreach($RouteMethods as $RouteMethod) {
				if(!isset($Verbs[$RouteMethod->Verb]))
				$Verbs[$RouteMethod->Verb] = new Datastore;

				$Verbs[$RouteMethod->Verb]->Push($RouteMethod);
			}

			$ErrorMethods = $this->DetermineErrorMethods($RouteClass);
			foreach($ErrorMethods as $RouteMethod) {

				$Errors[$RouteMethod->Code] = $RouteMethod;
			}
		}

		$Output
		->SetFullSerialize(FALSE)
		->Shove('Verbs', $Verbs)
		->Shove('Errors', $Errors);

		return $Output;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	FetchFilesInDir(SplFileInfo $Directory):
	Datastore {

		$Output = new Datastore;
		$Dir = NULL;

		$File = NULL;
		$Path = NULL;

		////////

		$Dir = new FilesystemIterator(
			$Directory->GetRealPath(),
			(
				0
				| FilesystemIterator::CURRENT_AS_FILEINFO
				| FilesystemIterator::SKIP_DOTS
			)
		);

		////////

		foreach($Dir as $File) {
			$Path = $File->GetRealPath();

			if(str_ends_with(strtolower($Path), 'php'))
			$Output->Push($Path);
		}

		return $Output;
	}

	public function
	DetermineRoutableClasses(Datastore $Input):
	Datastore {

		$Output = new Datastore;
		$Item = NULL;
		$ClassesPre = get_declared_classes();
		$ClassesPost = NULL;

		////////

		foreach($Input as $Item)
		(fn($F)=> require($F))($Item);

		$ClassesPost = array_diff(
			get_declared_classes(),
			$ClassesPre
		);

		foreach($ClassesPost as $Item) {
			if(is_subclass_of($Item, static::RouteBaseClass))
			$Output->Push($Item);
		}

		////////

		return $Output;
	}

	public function
	DetermineRoutableMethods(string $ClassName):
	Datastore {

		$Output = new Datastore;

		if(!is_subclass_of($ClassName, static::RouteBaseClass))
		throw new Error\RouteScannerClassNotValid($ClassName);

		$Output
		->SetData(
			($ClassName)::FetchMethodsWithAttribute(RouteHandler::class)
		)
		->Remap(
			fn(MethodInfo $Method)=>
			($Method->Attributes[RouteHandler::class])
		);

		return $Output;
	}

	public function
	DetermineErrorMethods(string $ClassName):
	Datastore {

		$Output = new Datastore;

		if(!is_subclass_of($ClassName, static::RouteBaseClass))
		throw new Error\RouteScannerClassNotValid($ClassName);

		$Output
		->SetData(
			($ClassName)::FetchMethodsWithAttribute(ErrorHandler::class)
		)
		->Remap(
			fn(MethodInfo $Method)=>
			($Method->Attributes[ErrorHandler::class])
		);

		return $Output;
	}

}
