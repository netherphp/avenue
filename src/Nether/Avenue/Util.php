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

			// this can only really happen when passing in a file that
			// is syntax errored to have a never ending namespace.

			if(!array_key_exists($T, $Tokens))
			continue;

			// when we find a class keyword we need to find the next
			// string forward as the class name.

			if($Tokens[$T]->Is(T_CLASS)) {
				while($T < count($Tokens)) {
					if(!array_key_exists(++$T, $Tokens))
					break;

					if($Tokens[$T]->Is(['(']))
					break;

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
	MakePathableKey(string $Input):
	string {
	/*//
	utility method i have in almost all of my projects to take input uris and
	spit out versions that would break anything if we tried to use it in a
	file path. so its a super sanitiser only allowing alphas, numerics,
	dashes, periods, and forward slashes. does not allow dot stacking
	to prevent traversal foolery.
	//*/

		$Output = strtolower(trim($Input));

		// allow things that could be nice clean file names.

		$Output = preg_replace(
			'#[^a-zA-Z0-9\-\/\.]#', '',
			str_replace(' ', '-', $Output)
		);

		// disallow traversal foolery.

		$Output = preg_replace(
			'#[\.]{2,}#', '',
			$Output
		);

		$Output = preg_replace(
			'#[\/]{2,}#', '/',
			$Output
		);

		return $Output;
	}

	static public function
	MakeKey(string $Input):
	string {
	/*//
	utility method i have in almost all of my projects to take input uris and
	spit out versions that would break anything if we tried to use it in a
	file path. so its a super sanitiser only allowing alphas, numerics,
	dashes, periods, and forward slashes. does not allow dot stacking
	to prevent traversal foolery.
	//*/

		$Output = strtolower(trim($Input));

		// allow things that could be nice clean file names.

		$Output = preg_replace(
			'#[^a-zA-Z0-9\-\.]#', '',
			str_replace(' ', '-', $Output)
		);

		// disallow traversal foolery.

		$Output = preg_replace(
			'#[\.]{2,}#', '',
			$Output
		);

		$Output = preg_replace(
			'#[\/]{2,}#', '/',
			$Output
		);

		return $Output;
	}


	static public function
	ParseQueryString(?string $Input):
	array {
	/*//
	@date 2022-03-31
	wrap parse_str to make it not stupid by dealing with null and the
	stupid need to do a c-style strcpy instead of returning.
	//*/

		if($Input === NULL)
		return [];

		////////

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
				=> str_repeat("\t", floor(strlen($Result[1]) / 2.0))
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
