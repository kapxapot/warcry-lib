<?php

namespace Warcry;

use Illuminate\Support\Arr;

class Contained {
	public $container;

	public function __construct($container) {
		$this->container = $container;
	}
	
	public function __get($property) {
		if ($this->container->{$property} || is_array($this->container->{$property})) {
			return $this->container->{$property};
		}
	}
	
	public function getSettings($path = null) {
		$result = $this->container->get('settings');
		
		if ($path) {
			$result = Arr::get($result, $path);
		}
		
		return $result;
	}
}
