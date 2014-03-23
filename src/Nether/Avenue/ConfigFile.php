<?php

namespace Nether\Avenue;
use \Nether;

class ConfigFile extends Nether\Object {

	public $Avenues;
	/*//
	@type array
	stores a list of all the static avenues that should be asked prior to any
	autorouting to determine if they are willing to handle the current request.
	//*/

	public $Namespaces;
	/*//
	@type array
	stores a list of all the namespaces that should be searched to fulfill a
	request via autorouting.
	//*/

	public $Commonspace;
	/*//
	@type string
	a fallback namespace to search in if nothing was found in the Namespaces
	list. provides a way to always have a fallback while allowing AddNamespace
	to append to the main list.
	//*/

	public $ErrorRoutes;
	/*//
	@type array
	stores a list of routes that should be used to handle any error conditions
	that the router may encounter (not found, forbidden, etc). these routes
	should be defined by their http status code: ['404'=>'RouteName',. ..]
	//*/

	////////////////
	////////////////

	public function __construct($file) {

		// load the specified file from disk.
		$obj = new Nether\Object($this->Load($file),[
			'Avenues'     => [],
			'Namespaces'  => [],
			'Commonspace' => null,
			'ErrorRoutes' => []
		]);

		// apply the config to the current object.
		$this->__apply_property_defaults($obj,true);

		return;
	}

	////////////////
	////////////////

	protected function Load($file) {
		if(!$this->IsReadable($file)) {
			// TODO - log file not found or not readable.
			echo "Route File Not Readable.";
			return null;
		}

		$obj = json_decode(file_get_contents($file));
		if(!is_object($obj)) {
			// TODO - log error parsing config file
			echo "Route File Parse Error";
			return null;
		}

		return $obj;
	}

	////////////////
	////////////////

	protected function IsReadable($file) {
		if(file_exists($file) && is_readable($file)) return true;
		else return false;
	}

}
