<?php

namespace Warcry\File;

use Warcry\Exceptions\ApplicationException;

class Image {
	public $data;
	public $imgType;
	
	public function parseBase64($base64) {
		if (preg_match("#^data:image/(\w+);base64,(.*)$#i", $base64, $matches)) {
			$imgType = $matches[1];
			$data = $matches[2];

			if (strlen($data) > 0) {
				$this->data = base64_decode($data);
				$this->imgType = $imgType;
			}
			/*else {
				throw \InvalidArgumentException('Изображение отсутствует.');
			}*/
		}
	}

	public function notEmpty() {
		return strlen($this->data) > 0;
	}
	
	public function save($fileName) {
		if ($this->notEmpty()) {
			$file = new File();
			$file->save($fileName, $this->data);
		}
		else {
			throw new ApplicationException('Отсутствуют данные для сохранения.');
		}
	}
}
