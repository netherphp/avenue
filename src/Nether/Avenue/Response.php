<?php

namespace Nether\Avenue;

use Nether\Object\Prototype;

class Response
extends Prototype {

	const
	ContentTypeBin  = 'application/octet-stream',
	ContentTypeCSS  = 'text/css',
	ContentTypeHTML = 'text/html',
	ContentTypeJS   = 'text/javascript',
	ContentTypeJSON = 'application/json',
	ContentTypeRSS  = 'application/rss+xml',
	ContentTypeText = 'text/plain',
	ContentTypeXML  = 'application/xml';

	const
	CodeNope         = 0,
	CodeOK           = 200,
	CodeRedirectPerm = 301,
	CodeNotModified  = 304,
	CodeRedirectTemp = 307,
	CodeBadRequest   = 400,
	CodeForbidden    = 403,
	CodeNotFound     = 404,
	CodeServerError  = 500;

	public bool
	$HTTP = TRUE;

	public int
	$Code = self::CodeOK;

	public string
	$ContentType = self::ContentTypeHTML;

	public string
	$Content = '';

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	OnReady(Prototype\ConstructArgs $Args):
	void {

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	CaptureBegin():
	void {
	/*//
	use php's overbuffer system to capture any output as belonging to the
	content of this response.
	//*/

		// php does not seem to execute the filter callback in the way
		// i wanted my capture end method to work so we will do it there
		// instead of specifying it here.

		ob_start(NULL, 0);

		return;
	}

	public function
	CaptureEnd(?bool $Append=TRUE):
	void {
	/*//
	stop capturing output. if append is true the content will be appended
	to the current content this response has. if append is false any existing
	content will be overwritten with the current content. if append is nulled
	then the content will be discarded.
	//*/

		match(TRUE) {

			// append content if boolean true.
			($Append === TRUE)
			=> $this->Content .= $this->CaptureFilter(ob_get_clean()),

			// replace content if boolean false.
			($Append === FALSE)
			=> $this->Content = $this->CaptureFilter(ob_get_clean()),

			// discard content if null.
			default
			=> ob_end_clean()

		};

		return;
	}

	public function
	CaptureFilter(string $Input):
	string {
	/*//
	a callable meant for filtering content provided by the overbuffer.
	//*/

		$Output = $Input;

		return $Output;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	SetCode(int $Code):
	static {

		$this->Code = $Code;
		return $this;
	}

	public function
	SetContentType(string $ContentType):
	static {

		$this->ContentType = $ContentType;
		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	Clear():
	static {

		$this->Content = '';

		return $this;
	}

	public function
	Render():
	static {
	/*//
	dump the captured content out.
	//*/

		if($this->HTTP === TRUE) {
			http_response_code($this->Code);
			header("Content-type: {$this->ContentType}");
		}

		echo $this->Content;

		return $this;
	}

}
