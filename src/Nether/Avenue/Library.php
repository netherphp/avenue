<?php

namespace Nether\Avenue;

use SplFileInfo;
use Nether\Object\Datastore;
use Nether\Avenue\Struct\LibraryInitWithConfig;

class Library
implements LibraryInitWithConfig {

	public const
	ConfRouteFile    = 'Nether.Avenue.RouteFile',
	ConfRouteRoot    = 'Nether.Avenue.RouteRoot',
	ConfWebRoot      = 'Nether.Avenue.WebRoot',
	ConfDomainLvl    = 'Nether.Avenue.DomainLvl',
	ConfDomainSep    = 'Nether.Avenue.DomainSep';

	public const
	RouteSourceScan = 'dirscan',
	RouteSourceFile = 'file';

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	Init(Datastore $Config=NULL):
	bool {

		static::InitDefaultConfig($Config);

		return TRUE;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	InitDefaultConfig(?Datastore $Config=NULL):
	Datastore {

		if($Config === NULL)
		$Config = new Datastore;

		$Config->BlendRight([
			static::ConfDomainLvl => 2,
			static::ConfDomainSep => '.',
			static::ConfRouteFile => '../routes.phson',
			static::ConfRouteRoot => '../routes',
			static::ConfWebRoot   => 'www'
		]);

		return $Config;
	}

}
