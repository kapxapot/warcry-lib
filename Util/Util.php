<?php

namespace Warcry\Util;

class Util {
	const DATE_FORMAT = 'Y-m-d H:i:s';

	static public function now() {
		return date(self::DATE_FORMAT);	
	}
	
	static public function verifyPassword($password, $hashedPassword) {
		return password_verify($password, $hashedPassword);
	}

	static public function encodePassword($password) {
		return password_hash($password, PASSWORD_DEFAULT);
	}
	
	static public function rehashPasswordNeeded($password) {
		return password_needs_rehash($password, PASSWORD_DEFAULT);
	}
	
	static public function generateToken() {
		return bin2hex(openssl_random_pseudo_bytes(16));
	}
	
	static public function generateExpirationTime($minutes = 60) {
		return date(self::DATE_FORMAT, strtotime("+{$minutes} minutes"));
	}
}
