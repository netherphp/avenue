<?php

namespace Nether\Avenue;

use SplFileInfo;
use Nether\Object\Datastore;
use Nether\Avenue\Struct\LibraryInitWithConfig;

class Library
implements LibraryInitWithConfig {

	public const
	ConfRouteFile           = 'Nether.Avenue.RouteFile',
	ConfRouteRoot           = 'Nether.Avenue.RouteRoot',
	ConfWebRoot             = 'Nether.Avenue.WebRoot',
	ConfDomainLvl           = 'Nether.Avenue.DomainLvl',
	ConfDomainSep           = 'Nether.Avenue.DomainSep',
	ConfParsePathableConfig = 'Nether.Avenue.ParsePathableConfig';

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	Init(Datastore $Config):
	bool {

		static::PrepareDefaultConfig($Config);

		if($Config[static::ConfParsePathableConfig])
		static::ParsePathableConfig($Config);

		return TRUE;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static protected function
	PrepareDefaultConfig(Datastore $Config):
	void {

		$Config->BlendRight([
			static::ConfDomainLvl           => 2,
			static::ConfDomainSep           => '.',
			static::ConfRouteFile           => '../routes.phson',
			static::ConfRouteRoot           => '../routes',
			static::ConfWebRoot             => '../www',
			static::ConfParsePathableConfig => TRUE
		]);

		return;
	}

	static protected function
	ParsePathableConfig(Datastore $Config):
	void {

		$Pathise = [
			static::ConfRouteFile,
			static::ConfRouteRoot,
			static::ConfWebRoot
		];

		$Key = NULL;

		foreach($Pathise as $Key) {
			$Info = new SplFileInfo($Config[$Key]);

			if($Info->IsReadable())
			$Config[$Key] = $Info->GetRealPath();

			unset($Info);
		}

		return;
	}

}
