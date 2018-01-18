<?php

namespace Warcry\Util;

class Util {
	// obsolete, use Date
	const DATE_FORMAT = 'Y-m-d H:i:s';

	// obsolete, use Date
	static public function now() {
		return date(self::DATE_FORMAT);	
	}

	// obsolete, use Security
	static public function verifyPassword($password, $hashedPassword) {
		return password_verify($password, $hashedPassword);
	}

	// obsolete, use Security
	static public function encodePassword($password) {
		return password_hash($password, PASSWORD_DEFAULT);
	}
	
	// obsolete, use Security
	static public function rehashPasswordNeeded($password) {
		return password_needs_rehash($password, PASSWORD_DEFAULT);
	}
	
	// obsolete, use Security
	static public function generateToken() {
		return bin2hex(openssl_random_pseudo_bytes(16));
	}
	
	// obsolete, use Security
	static public function generateExpirationTime($minutes = 60) {
		return date(self::DATE_FORMAT, strtotime("+{$minutes} minutes"));
	}
	
	// obsolete, use String
	static public function toPascalCase($str) {
		return str_replace('_', '', ucwords($str, '_'));
	}
}
