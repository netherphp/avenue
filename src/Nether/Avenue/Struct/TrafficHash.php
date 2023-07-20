<?php

namespace Nether\Avenue\Struct;

use Stringable;
use JsonSerializable;

class TrafficHash
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

		$Data = [
			'Hash'    => $this->Get(),
			'Visitor' => $this->GetVisitorHash(),
			'IP'      => $this->IP,
			'URL'     => $this->URL,
			'Agent'   => $this->Agent
		];

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
