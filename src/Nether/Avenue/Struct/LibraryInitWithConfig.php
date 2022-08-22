<?php

namespace Nether\Avenue\Struct;

use Nether\Object\Datastore;

interface LibraryInitWithConfig {

	static public function
	Init(Datastore $Config):
	bool;

}
