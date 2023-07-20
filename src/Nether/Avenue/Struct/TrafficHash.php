<?php

namespace Nether\Avenue\Struct;

use Nether\Atlantis;

use Stringable;
use JsonSerializable;

class TrafficHash
extends Atlantis\Prototype
implements
	Stringable,
	JsonSerializable {

	public string
	$IP;

	public string
	$URL;

	public string
	$Agent;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	__Construct(string $IP, string $URL, string $Agent) {

		$this->IP = $IP;
		$this->URL = $URL;
		$this->Agent = $Agent;

		return;
	}

	////////////////////////////////////////////////////////////////
	// implements Stringable ///////////////////////////////////////

	public function
	__ToString():
	string {

		return $this->Get();
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	JsonSerialize():
	mixed {

		$Data = $this->DescribeForPublicAPI();

		$Data['Hash'] = $this->Get();

		return $Data;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	Get():
	string {

		return hash('sha512', "{$this->IP}:{$this->URL}:{$this->Agent}");
	}

	public function
	GetVisitorHash():
	string {

		return hash('sha512', "{$this->IP}:{$this->Agent}");
	}

}
