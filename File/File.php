<?php

namespace Warcry\File;

class File {
	public function save($file, $data) {
	    file_put_contents($file, $data);
	}
	
	public function delete($file) {
		if (file_exists($file)) {
			unlink($file);
		}
	}
}
