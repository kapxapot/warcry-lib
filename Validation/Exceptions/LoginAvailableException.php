<?php

namespace Warcry\Validation\Exceptions;

use Respect\Validation\Exceptions\ValidationException;

class LoginAvailableException extends ValidationException {
	public static $defaultTemplates = [
		self::MODE_DEFAULT => [
			self::STANDARD => 'Указанный логин уже занят.'
		]
	];
}
