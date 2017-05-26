<?php

namespace Warcry\Exceptions;

class BadRequestException extends \Exception implements IApiException {
	public function GetErrorCode() {
		return 400;
	}
}
