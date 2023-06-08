<?php

namespace Nether\Avenue;
use Nether;

use SplFileInfo;
use Nether\Common\Datastore;

class Library
extends Nether\Common\Library {

	public const
	ConfRouteFile    = 'Nether.Avenue.RouteFile',
	ConfRouteRoot    = 'Nether.Avenue.RouteRoot',
	ConfWebRoot      = 'Nether.Avenue.WebRoot',
	ConfDomainLvl    = 'Nether.Avenue.DomainLvl',
	ConfDomainSep    = 'Nether.Avenue.DomainSep',
	ConfVerbRewrite  = 'Nether.Avenue.VerbRewrite';

	public const
	RouteSourceScan = 'dirscan',
	RouteSourceFile = 'file';

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	OnLoad(...$Argv):
	void {

		static::$Config->BlendRight([
			static::ConfDomainLvl   => 2,
			static::ConfDomainSep   => '.',
			static::ConfRouteFile   => '../routes.phson',
			static::ConfRouteRoot   => '../routes',
			static::ConfWebRoot     => 'www',
			static::ConfVerbRewrite => FALSE
		]);

		return;
	}

}
