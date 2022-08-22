<?php

namespace Nether\Avenue;

class Util {

	static public function
	ParseStr(string $Input):
	array {
	/*//
	@date 2022-03-31
	wrap parse_str to make it not stupid.
	//*/

		$StupidFuckingTempVariable = NULL;

		parse_str(
			$Input,
			$StupidFuckingTempVariable
		);

		return $StupidFuckingTempVariable;
	}

	static public function
	VarDump(mixed $Input):
	void {

		ob_start();
		var_dump($Input);
		$Output = ob_get_clean();

		// fixes the annoying newline after the arrow.

		$Output = preg_replace(
			'/\]=>\n\h+/', '] => ',
			$Output
		);

		// convert indention to tabs.

		$Output = preg_replace_callback(
			'#^(\h+)#ms',
			(
				fn(array $Result)
				=> str_repeat("\t", strlen($Result[1]) / 2)
			),
			$Output
		);

		echo $Output;
		return;
	}

	static public function
	VarDumpPre(mixed $Input):
	void {

		echo '<pre>';
		static::VarDump($Input);
		echo '</pre>';

		return;
	}

}
