<?php

namespace m\Avenue;
use \m as m;

/* TODO

 * Add redirect types (301, 302, etc)

 * Add URL completion. As in, by the spec if we want to go to /place/ we
need to really say http://domain/place/ to be by the spec. browsers
are smarter than the spec though so i just laid this class down to
get rolling. The URL completion will probably be done by another function
in a utility library that can be reused here.

 */

class Redirect {

	public $Location;

	public function __construct($location = null) {
		$this->Location = $location;
		$this->Parse();
		return;
	}

	public function Go($location=null) {
		if($location) {
			$this->Location = $location;
			$this->Parse();
		}

		// this ki flow will allow libraries to shut themselves down
		// early if a page redirect is requested.
		if(class_exists('Nether\\Ki'))
		Nether\Ki::Flow('nether-avenue-redirect');

		header("Location: {$this->Location}");
		exit(0); // *wave*
	}

	protected function Parse() {

		// TODO - instead of redirecting to the root, they should
		// redirect to the set site root which may not be the actual
		// domain root.

		if(!$this->Location || !is_string($this->Location))
			goto DoHome;

		switch($this->Location) {
			case 'nether://home': {
				goto DoHome;
				break;
			}

			case 'nether://back':
			case 'nether://referer':
			case 'nether://referrer': {
				goto DoBack;
				break;
			}

			case 'nether://refresh':
			case 'nether://reload': {
				goto DoReload;
				break;
			}

			case 'nether://current':
			case 'nether://self': {
				goto DoSelf;
				break;
			}

			default: {
				return;
			}
		}

		DoHome:
			$this->Location = '/';
			return;

		DoBack:
			if(array_key_exists('HTTP_REFERER',$_SERVER)) {
				$this->Location = $_SERVER['HTTP_REFERER'];
			} else { goto DoHome; }

		DoReload:
			if(array_key_exists('REQUEST_URI',$_SERVER)) {
				$this->Location = "{$_SERVER['REQUEST_URI']}?{$_SERVER['QUERY_STRING']}";
			} else { goto DoHome; }

		DoSelf:
			if(array_key_exists('REQUEST_URI',$_SERVER)) {
				$this->Location = $_SERVER['REQUEST_URI'];
			} else { goto DoHome; }

	}

	static function Now($where) {
	/*//
	you do not want to deal with creating a redirect object yourself, mostly
	because you might not care about proceedurally generating the destination.
	whatever. that is cool. redirect now will do it for you.
	//*/

		$bye = new self($where);
		$bye->Go();
	}

}
