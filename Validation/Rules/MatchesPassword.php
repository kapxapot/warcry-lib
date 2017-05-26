<?php

namespace Warcry\Validation\Rules;

use Warcry\Util\Util;

class MatchesPassword extends ContainerRule {
	protected $password;
	
	public function __construct($password) {
		$this->password = $password;
	}
	
	public function validate($input) {
		return Util::verifyPassword($input, $this->password);
	}
}
