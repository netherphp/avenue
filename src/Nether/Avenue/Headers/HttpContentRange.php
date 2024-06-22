<?php ##########################################################################
################################################################################

namespace Nether\Avenue\Headers;

use Nether\Avenue;
use Nether\Common;

################################################################################
################################################################################

class HttpContentRange {

	public const
	NameServerVar = 'HTTP_CONTENT_RANGE',
	NameProtocol  = 'content-range';

	protected const
	RegexContentRange  = '/(bytes) ([\d]+)-([\d]+)\/([\d]+)/';

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public string
	$Unit = 'bytes';

	public int
	$Begin = 0;

	public int
	$End = 0;

	public int
	$Total = 0;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	Import(string $Data):
	static {

		$Bits = NULL;
		$Result = preg_match(static::RegexContentRange, $Data, $Bits);

		if(!$Result)
		throw new Common\Error\RegexMatchFailure(
			static::RegexContentRange,
			$Data
		);

		////////

		$this->Unit = $Bits[1];
		$this->Begin = (int)$Bits[2];
		$this->End = (int)$Bits[3];
		$this->Total = (int)$Bits[4];

		return $this;
	}

	public function
	ReadFromEnv():
	static {

		// try the easy route if it is in a global var.

		if(static::NameServerVar !== NULL)
		if(isset($_SERVER[static::NameServerVar]))
		return $this->Import($_SERVER[static::NameServerVar]);

		// try a little harder by smelling headers.

		$Heads = new Common\Datafilter(Avenue\Util::FetchRequestHeaders());

		if($Heads->Exists('content-range'))
		return $this->Import($Heads->Get('content-range'));

		////////

		throw new Common\Error\RequiredDataMissing(
			'Content-Type', 'HTTP Header'
		);

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	From(string $Data):
	?static {

		$Output = NULL;
		$Err = NULL;

		////////

		$Output = new static;
		$Output->Import($Data);

		////////

		return $Output;
	}

	static public function
	FromEnv():
	?static {

		$Output = NULL;
		$Err = NULL;

		////////

		try {
			$Output = new static;
			$Output->ReadFromEnv();
		}

		catch(Common\Error\RequiredDataMissing $Err) {
			return NULL;
		}

		////////

		return $Output;
	}

};