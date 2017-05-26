<?php

namespace Warcry;

class Contained {
	protected $container;

	public function __construct($container) {
		$this->container = $container;
	}
	
	public function __get($property) {
		if ($this->container->{$property} || is_array($this->container->{$property})) {
			return $this->container->{$property};
		}
	}
	
	public function getSettings($module = null) {
		$s = $this->container->get('settings');
		return $module ? $s[$module] : $s;
	}
}
