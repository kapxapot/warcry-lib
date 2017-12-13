<?php

namespace Warcry\ORM\Idiorm;

use Warcry\Contained;

class Helper extends Contained {
	public function toArray($e) {
		return is_array($e) ? $e : $e->asArray();
	}
}
