<?php

namespace Warcry\Exceptions;

class AuthenticationException extends \Exception implements IApiException {
    public function __construct($message = 'Отказано в доступе.') {
        parent::__construct($message);
    }
	
	public function GetErrorCode() {
		return 401;
	}
}
