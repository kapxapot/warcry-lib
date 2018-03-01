<?php

namespace Warcry\Validation\Rules;

use Warcry\Util\Security;

class MatchesPassword extends ContainerRule {
	protected $password;
	
	public function __construct($password) {
		$this->password = $password;
	}
	
	public function validate($input) {
		return Security::verifyPassword($input, $this->password);
	}
}
