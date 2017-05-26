<?php

namespace Warcry\Validation\Rules;

class EmailAvailable extends TableFieldAvailable {
	public function __construct($id = null) {
		parent::__construct('users', 'email', $id);
	}
}
