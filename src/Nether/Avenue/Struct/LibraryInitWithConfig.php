<?php

namespace Nether\Avenue\Struct;

use Nether\Common\Datastore;

interface LibraryInitWithConfig {

	static public function
	Init(Datastore $Config):
	bool;

}
