<?php

namespace Warcry\ORM\Idiorm;

use Warcry\Contained;

class Helper extends Contained {
	protected function toArray($e) {
		return is_array($e) ? $e : $e->as_array();
	}
}
