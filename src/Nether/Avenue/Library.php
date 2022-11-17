<?php

namespace Nether\Avenue;
use Nether;

use SplFileInfo;
use Nether\Object\Datastore;

class Library
extends Nether\Common\Library {

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
	Init(...$Argv):
	void {

		static::OnInit(...$Argv);
		return;
	}

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

	static public function
	OnInit(?Datastore $Config=NULL, ...$Argv):
	void {

		static::InitDefaultConfig($Config);

		return;
	}

}
