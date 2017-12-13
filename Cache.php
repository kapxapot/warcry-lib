<?php

namespace Warcry;

class Cache extends Contained {
	private $cache;
	
	public function __construct($c) {
		parent::__construct($c);
		
		$this->cache = [];
	}
	
	public function get($path) {
		/*$value = null;
		$chunks = explode('.', $path);
		while (count($))*/
		return $this->cache[$path] ?? null;
	}
	
	public function set($path, $value) {
		$this->cache[$path] = $value;
	}
}
