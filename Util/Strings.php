<?php

namespace Warcry\Util;

class Strings {
	static public function toPascalCase($str) {
		return str_replace('_', '', ucwords($str, '_'));
	}
	
	static public function normalize($str) {
		$str = trim($str);
		$str = mb_strtolower($str);
		$str = preg_replace("/\s+/", " ", $str);
		
		return $str;
	}
	
	static public function toTags($str) {
		$tags = array_map(function($t) {
			return self::normalize($t);
		}, explode(',', $str));
		
		return array_unique($tags);
	}
	
	// truncates string to (limit) characters and strips html tags (by default)
	static public function trunc($str, $limit, $stripTags = true) {
		if ($stripTags === true) {
			$str = strip_tags($str);
		}
		
		return mb_substr($str, 0, $limit);
	}
}
