<?php

namespace Warcry\Util;

class Text {
	// breaks text to lines array
	static public function toLines($text) {
		return preg_split("/\r\n|\n|\r/", $text);
	}

	// joins lines array into text
	static public function fromLines($lines) {
		return implode(PHP_EOL, $lines);
	}
	
	// removes empty lines from start and end of array
	static public function trimLines($lines) {
		while (count($lines) > 0 && strlen($lines[0]) == 0) {
			array_shift($lines);
		}
		
		while (count($lines) > 0 && strlen($lines[count($lines) - 1]) == 0) {
			array_pop($lines);
		}
		
		return $lines;
	}
	
	// trims <br/> from start and end of text
	static public function trimBrs($text) {
		$br = '(\<br\s*\/\>)*';
		
		$text = preg_replace("/^{$br}/s", '', $text);
		$text = preg_replace("/{$br}$/s", '', $text);

		return $text;		
	}

	// breaks text into lines
	// executes lines processing
	// trims empty lines
	// builds text from lines
	//
	// function $process(Array $lines) : Array
	static public function processLines($text, $process, $trimEmpty = true) {
		$lines = self::toLines($text);
		$result = $process($lines);

		if ($trimEmpty) {
			$result = self::trimLines($result);
		}
		
		$text = self::fromLines($result);

		return $text;
	}
}
