<?php

namespace Warcry\Exceptions;

class AuthorizationException extends \Exception implements IApiException {
    public function __construct($message = 'Недостаточно прав.') {
        parent::__construct($message);
    }
	
	public function GetErrorCode() {
		return 401;
	}
}
