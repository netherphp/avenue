<?php

namespace Nether\Avenue;

use PhpToken;

class Util {

	static public function
	FindClassesInFile(string $Filename):
	array {
	/*//
	@date 2022-08-23
	use the tokenizer to find all the classes in the specified file.
	//*/

		$Output = [];

		if(!is_readable($Filename))
		return $Output;

		////////

		$Namespace = NULL;
		$Tokens = PhpToken::Tokenize(file_get_contents($Filename));
		$T = -1;

		for($T = 0; $T < count($Tokens); $T++) {

			if($Tokens[$T]->IsIgnorable())
			continue;

			// make note of the namespace we are in. still have to do the
			// thing where we iterate over all the strings and seps to
			// compile the full namespace name because of course we do.

			if($Tokens[$T]->Is(T_NAMESPACE)) {
				$Namespace = [];

				while($T < count($Tokens)) {
					if(!array_key_exists((++$T), $Tokens))
					break;

					if($Tokens[$T]->Is([';', '{']))
					break;

					if($Tokens[$T]->IsIgnorable())
					continue;

					if(!$Tokens[$T]->Is([T_STRING, T_NAME_QUALIFIED]))
					continue;

					$Namespace[] = $Tokens[$T]->__ToString();
				}
			}

			// when we find a class keyword we need to find the next
			// string forward as the class name.

			if($Tokens[$T]->Is(T_CLASS)) {
				while($T < count($Tokens)) {
					if(!array_key_exists(++$T, $Tokens))
					break;

					if($Tokens[$T]->Is(['(']))
					continue;

					if(!$Tokens[$T]->Is(T_STRING))
					continue;

					$Output[] = sprintf(
						'%s\\%s',
						join('\\', $Namespace),
						$Tokens[$T]->__ToString()
					);

					break;
				}
			}

		}

		return $Output;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

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

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	VarDump(mixed $Input):
	void {
	/*//
	@date 2022-03-31
	wrap var_dump to make it more readable.
	//*/

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
	/*//
	@date 2022-03-31
	wrap var_dump to make it more readable in html.
	//*/

		echo '<pre>';
		static::VarDump($Input);
		echo '</pre>';

		return;
	}

}
