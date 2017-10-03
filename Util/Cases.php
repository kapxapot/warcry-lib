<?php

namespace Warcry\Util;

class Cases {
	const NOM = 0;
	const GEN = 1;
	const DAT = 2;
	const ACC = 3;
	const ABL = 4;
	const PRE = 5;
	
	const SINGLE = 0;
	const PLURAL = 1;
	
	const MAS = 0;
	const FEM = 1;
	const NEU = 2;
	const PLU = 3;

	private $cases = [
		self::NOM => [ 'ru' => 'именительный', 'en' => 'nominative' ],
		self::GEN => [ 'ru' => 'родительный', 'en' => 'genitive' ],
		self::DAT => [ 'ru' => 'дательный', 'en' => 'dative' ],
		self::ACC => [ 'ru' => 'винительный', 'en' => 'accusative' ],
		self::ABL => [ 'ru' => 'творительный', 'en' => 'ablative' ],
		self::PRE => [ 'ru' => 'предложный', 'en' => 'prepositional' ],
	];
	
	private $genders = [
		self::MAS => [ 'ru' => 'мужской', 'en' => 'masculine' ],
		self::FEM => [ 'ru' => 'женский', 'en' => 'feminine' ],
		self::NEU => [ 'ru' => 'средний', 'en' => 'neuter' ],
		self::PLU => [ 'ru' => 'множественный', 'en' => 'plural' ],
	];
	
	private $templates = [
		// [картин]ка
		[
			[ '%ка', '%ки' ],
			[ '%ки', '%ок' ],
			[ '%ке', '%кам' ],
			[ '%ку', '%ки' ],
			[ '%кой', '%ками' ],
			[ 'о %ке', 'о %ках' ],
		],
		// [картин]а
		[
			[ '%а', '%ы' ],
			[ '%ы', '%' ],
			[ '%е', '%ам' ],
			[ '%у', '%ы' ],
			[ '%ой', '%ами' ],
			[ 'о %е', 'о %ах' ],
		],
		// [выпуск]
		[
			[ '%', '%и' ],
			[ '%а', '%ов' ],
			[ '%у', '%ам' ],
			[ '%', '%и' ],
			[ '%ом', '%ами' ],
			[ 'о %е', 'о %ах' ],
		],
		// [стрим]
		[
			[ '%', '%ы' ],
			[ '%а', '%ов' ],
			[ '%у', '%ам' ],
			[ '%', '%ы' ],
			[ '%ом', '%ами' ],
			[ 'о %е', 'о %ах' ],
		],
		// [бу]й, [буга]й
		[
			[ '%й', '%и' ],
			[ '%я', '%ёв' ],
			[ '%ю', '%ям' ],
			[ '%й', '%и' ],
			[ '%ём', '%ями' ],
			[ 'о %е', 'о %ях' ],
		],
	];
	
	private $data = [
		'картинка' => [ 'base' => 'картин', 'index' => 0 ],
		'выпуск' => [ 'base' => 'выпуск', 'index' => 2 ],
		'стрим' =>  [ 'base' => 'стрим', 'index' => 3 ],
	];
	
	public function forNumber($word, $num) {
		if (!array_key_exists($word, $this->data)) {
			throw new \InvalidArgumentException('Undefined base: ' . $base);
		}

		$case = self::GEN;
		$caseNumber = self::PLURAL;

		if ($num < 5 || $num > 20) {
			switch ($num % 10) {
				case 1:
					$case = self::NOM;
					$caseNumber = self::SINGLE;
					break;

				case 2:
				case 3:
				case 4:
					$case = self::GEN;
					$caseNumber = self::SINGLE;
					break;
			}
		}
		
		$wordData = $this->data[$word];
		$templateIndex = $wordData['index'];
		$base = $wordData['base'];

		$template = $this->templates[$templateIndex];
		$image = $template[$case][$caseNumber];

		return str_replace('%', $base, $image);
	}
}
