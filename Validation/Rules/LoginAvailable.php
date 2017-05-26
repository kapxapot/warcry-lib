<?php

namespace Warcry\Validation\Rules;

class LoginAvailable extends TableFieldAvailable {
	public function __construct($id = null) {
		parent::__construct('users', 'login', $id);
	}
}
