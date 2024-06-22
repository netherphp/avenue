<?php ##########################################################################
################################################################################

namespace Nether\Avenue\Struct;

use Nether\Avenue;
use Nether\Common;

################################################################################
################################################################################

class FormData
extends Common\Prototype {

	const
	EOL = "\r\n",
	BMarkerPad = '--';

	const
	HeadDisposition = 'content-disposition';

	#[Common\Meta\PropertyFactory([ Common\UUID::class, 'V7' ])]
	protected string
	$BMarker;

	#[Common\Meta\PropertyFactory('FromArray')]
	public array|Common\Datastore
	$Fields = [];

	#[Common\Meta\PropertyFactory('FromArray')]
	public array|Common\Datastore
	$Files = [];

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	GetBoundaryMarker():
	string {

		return $this->BMarker;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	ParseRawInput(string $Input):
	void {

		$this->ParseRawInputDetermineBoundaryMarker($Input);
		$this->ParseRawInputIterateOverData($Input);

		return;
	}

	protected function
	ParseRawInputDetermineBoundaryMarker(string $Input):
	void {

		$Size = strlen($Input);
		$Here = 0;
		$Mark = NULL;

		// check that the input looks like multipart data.

		if(!$Size || !str_contains($Input, static::EOL))
		throw new Avenue\Error\InvalidMultipartFormData('ParseRawInput: data seems bunk');

		// determine what the boundary marker is.

		$Here = strpos($Input, static::EOL, 0);
		$Mark = substr($Input, 0, $Here);

		if(!str_starts_with($Mark, static::BMarkerPad))
		throw new Avenue\Error\InvalidMultipartFormData('ParseRawInput: invalid boundary marker');

		////////

		$this->BMarker = substr($Mark, strlen(static::BMarkerPad));
		return;
	}

	protected function
	ParseRawInputIterateOverData(string $Input):
	void {

		// purposely doing this the hard way with low level string things
		// to avoid something like explode using a lot of ram if someone
		// sent us a huge file.

		$Size = strlen($Input);
		$Mark = sprintf('%s%s', static::BMarkerPad, $this->BMarker);
		$MLen = strlen($Mark) + strlen(static::BMarkerPad);
		$DBrk = str_repeat(static::EOL, 2);
		$DPos = 0;
		$BPos = 0;
		$BLen = 0;

		$Here = 0;
		$Stop = 0;
		$Step = 0;
		$Chop = 0;
		$Head = NULL;

		$Name = NULL;
		$Temp = NULL;
		$Mime = NULL;

		while($Step < $Size) {

			// find the beginning of this boundary.

			$Here = strpos($Input, $Mark, $Step);
			if($Here === FALSE) break;

			// find the end of this data by finding the next boundary.

			$Chop = $Here + $MLen;
			$Stop = strpos($Input, $Mark, $Chop);
			if($Stop === FALSE) break;

			// find the header break within this segment of data.

			$DPos = strpos($Input, $DBrk, $Chop);

			// extract the headers and determine if we can do anything
			// with the data.

			$Head = $this->ParseRequestHeaders(substr($Input, $Chop, $DPos - $Chop));

			if(!$Head->HasKey(static::HeadDisposition)) {
				$Step = $Stop;
				continue;
			}

			if(!($Head[static::HeadDisposition] instanceof Common\Datastore)) {
				$Step = $Stop;
				continue;
			}

			if(!$Head[static::HeadDisposition]->HasKey('name')) {
				$Step = $Stop;
				continue;
			}

			$Name = $Head[static::HeadDisposition]['name'];
			$BPos = ($DPos + strlen($DBrk));
			$BLen = $Stop - ($DPos + strlen($DBrk) + strlen(static::EOL));

			////////

			if($Head[static::HeadDisposition]->HasKey('filename')) {
				$Temp = Common\Filesystem\Util::MkTempFile();

				// TODO 2024-06-19 make this chunked write to disk
				// in case it was a massive file that ooms us.
				file_put_contents($Temp, substr($Input, $BPos, $BLen));

				$Mime = Common\Filesystem\Util::MimeType($Temp);

				$this->Files[$Name] = [
					'error'    => 0,
					'name'     => $Head[static::HeadDisposition]['filename'],
					'type'     => $Mime,
					'size'     => filesize($Temp),
					'tmp_name' => $Temp
				];
			}

			else {
				$this->Fields[$Name] = substr($Input, $BPos, $BLen);
			}

			$Step = $Stop;
			continue;
		}

		$this->Fields = $this->ParseRawInputExpandFields();

		return;
	}

	protected function
	ParseRawInputExpandFields():
	Common\Datastore {

		$Output = [];
		$Key = NULL;
		$Val = NULL;
		$Found = NULL;

		foreach($this->Fields as $Key => $Val) {

			if(preg_match('/^(.+?)\[(.+?)\]$/', $Key, $Found)) {
				if(!array_key_exists($Found[1], $Output))
				$Output[$Found[1]] = [];

				$Output[$Found[1]][$Found[2]] = $Val;
				continue;
			}

			$Output[$Key] = $Val;
			continue;
		}

		//Common\Dump::Var($this->Fields);
		//Common\Dump::Var($Output);

		return Common\Datastore::FromArray($Output);
	}

	public function
	ParseRequestHeaders(string $Input):
	Common\Datastore {

		$Out = new Common\Datastore;
		$Lines = Common\Datastore::FromString(trim($Input), static::EOL);
		$Line = NULL;

		////////

		foreach($Lines as $Line) {
			if(!str_contains($Line, ':'))
			continue;

			list($Field, $Data) = explode(':', $Line, 2);

			$Field = strtolower($Field);
			$Data = trim($Data);

			if(str_contains($Data, '; ')) {
				$Data = Common\Datastore::FromString(trim($Data), '; ');
				$Data->RemapKeys(function(int $K, string $V) {
					$F = NULL;

					if(preg_match('/(.+?)="(.+?)"/', $V, $F))
					return [ strtolower($F[1])=> $F[2] ];

					return [ $V=> TRUE ];
				});
			}

			$Out->Set($Field, $Data);
			continue;
		}

		return $Out;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	FromMultipartRaw(string $Input):
	static {

		$Output = new static;
		$Output->ParseRawInput($Input);

		return $Output;
	}

};

