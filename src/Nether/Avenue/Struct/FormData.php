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

	public function
	GetPaddedBoundaryStart(int $At):
	int {

		return $At + strlen(static::BMarkerPad);
	}

	public function
	GetPaddedBoundaryEnd(int $At):
	int {

		return $At - strlen(static::BMarkerPad);
	}

	public function
	GetFields():
	Common\Datastore {

		return $this->Fields->Copy();
	}

	public function
	GetFieldsArray():
	array {

		return $this->Fields->Export();
	}

	public function
	GetFiles():
	Common\Datastore {

		return $this->Files->Copy();
	}

	public function
	GetFilesArray():
	array {

		return $this->Files->Export();
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

		// check that the input looks like multipart data.

		if(!$Size || !str_contains($Input, static::EOL))
		throw new Avenue\Error\InvalidMultipartFormData('ParseRawInput: data seems bunk');

		// determine what the boundary marker is.

		$Here = strpos($Input, static::EOL, 0);

		if($Here === FALSE)
		throw new Avenue\Error\InvalidMultipartFormData('ParseRawInput: no boundary found');

		////////

		$this->BMarker = substr(
			$Input,
			$this->GetPaddedBoundaryStart(0),
			$this->GetPaddedBoundaryEnd($Here)
		);

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

			if(!$Head->HasKey(static::HeadDisposition))
			continue;

			if(!$Head[static::HeadDisposition]->HasKey('name'))
			continue;

			$Name = $Head[static::HeadDisposition]['name'];
			$BPos = ($DPos + strlen($DBrk));
			$BLen = $Stop - ($DPos + strlen($DBrk) + strlen(static::EOL));

			////////

			if($Head[static::HeadDisposition]->HasKey('filename')) {
				$Temp = Common\Filesystem\Util::MkTempFile();

				// TODO 2024-06-19 make this chunked write to disk
				// in case it was a massive file that ooms us.
				file_put_contents($Temp, substr($Input, $BPos, $BLen));

				$Mime = 'x-lulz/todo';

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


		return;
	}

	public function
	ParseRequestHeaders(string $Input):
	Common\Datastore {

		$Out = new Common\Datastore;
		$Lines = Common\Datastore::FromString(trim($Input), static::EOL);
		$Line = NULL;

		////////

		foreach($Lines as $Line) {
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

