<?php

namespace Nether\Avenue;
use \m as m;

class Upload {
/*//
utility class for dealing with images that have been uploaded to the system.
give it one of the entries in the _FILES array for the file you want to deal
with.
//*/

	public $Opt;
	/*//
	@type object
	store the options object so that later methods can reference the options
	if so needed.
	//*/

	public $UploadFile;
	/*//
	@type string
	the name of the file as it was originally made by php from the image
	upload.
	//*/

	public $UploadType;
	/*//
	@type string
	the mime type of the uploaded file.
	//*/

	public $UploadSize;
	/*//
	@type int
	the size in bytes of the uploaded file.
	//*/

	public $Dir;
	/*//
	@type string
	the directory to move the file to after upload.
	//*/

	public $File;
	/*//
	@type string
	the name to give the file after moving it to the final directory.
	//*/

	///////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////

	public function __construct($file,$opt = null) {
		if(!is_array($file) || !array_key_exists('tmp_name',$file))
		throw new \Exception('expected an upload array');

		$this->Opt = new m\Object($opt,[
			'Owner' => 0,
			'Dir'   => '.'
		]);

		if($file['error'])
		throw new \Exception('The upload was a failure.');

		// populate from the file upload.
		$this->UploadFile = $file['tmp_name'];
		$this->UploadType = $file['type'];
		$this->UploadSize = $file['size'];
		$this->File = $file['name'];

		// populate from the options.
		$this->Dir = $this->Opt->Dir;

		return;
	}

	public function Process() {

		// perform checks.
		$this->CheckUploadExists();
		$this->CheckUploadSize();

		// perform custom checks.
		if(!$this->Check())
		throw new \Exception('The Check() method returned error status.');

		// perform move operation.
		if(!$this->Move())
		throw new \Exception('The Move() method returned error status.');

		// perform processing.
		if(!$this->Ready())
		throw new \Exception('The Process() method returned error status.');

		return;
	}

	///////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////

	protected function CheckUploadExists() {
	/*//
	@return bool
	check that the file that is rumored to have been uploaded exists as an
	actual file on the filesystem. if the file is not found an exception is
	thrown.
	//*/

		if(!file_exists($this->UploadFile))
		throw new m\Error\FileNotFound($this->UploadFile);

		return true;
	}

	protected function CheckUploadSize() {
	/*//
	@return bool
	check that the upload file is not empty. throws an exception if it is.
	//*/

		if(!filesize($this->UploadFile))
		throw new m\Error\FileEmpty($this->UploadFile);

		return true;
	}

	///////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////

	public function Check() {
	/*//
	@return bool
	perform any checks on the uploaded file before commiting to having it moved
	and processed. overrie this method to do what you want.
	//*/

		return true;
	}

	public function Move() {
	/*//
	@return bool
	the method to move the file to where the final user will want it. override
	it to do what you need. return a boolean value representing if the move
	was successful or not.
	//*/

		if(!is_dir($this->Dir)) {
			if(!mkdir($this->Dir,0777,true))
			throw new \Exception($this->Dir);
		}

		if(!is_writable($this->Dir)) {
			if(!@chmod($this->Dir,0777))
			throw new \Exception($this->Dir);
		}

		$filepath = ("{$this->Dir}/{$this->File}");
		@rename($this->UploadFile,$filepath);

		if(!file_exists($filepath) || !filesize($filepath)) return false;
		else return true;
	}

	public function Ready() {
	/*//
	@return bool
	this method can be used to make an upload ready for whatever it needs to
	be ready for. be it processing thumbnails of an image or inserting data
	into a database. return a boolean representing the pass/fail status
	of your readiness.
	//*/

		return true;
	}

}
