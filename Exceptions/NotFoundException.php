<?php

namespace Warcry\Exceptions;

class NotFoundException extends \Exception implements IApiException {
    public function __construct($message = 'Не найдено.') {
        parent::__construct($message);
    }
	
	public function GetErrorCode() {
		return 404;
	}
}
