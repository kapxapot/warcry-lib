<?php

namespace Warcry\Util;

use Warcry\Contained;

class Cases {
	const NOM = 1; // именительный падеж
	const GEN = 2; // родительный падеж
	const DAT = 3; // дательный падеж
	const ACC = 4; // винительный падеж
	const ABL = 5; // творительный падеж
	const PRE = 6; // предложный падеж
	
	const SINGLE = 1; // единственное число
	const PLURAL = 2; // множественное число
	
	const MAS = 1; // мужской род
	const FEM = 2; // женский род
	const NEU = 3; // средний род
	const PLU = 4; // множественный род (ножницы, вилы)
	
	const INFINITIVE = 1; // инфинитив
	const PAST = 2; // прошлое время
	const PRESENT = 3; // настоящее время
	const FUTURE = 4; // будущее время
	
	const FIRST = 1; // первое лицо
	const SECOND = 2; // второе лицо
	const THIRD = 3; // третье лицо

	private $caseTemplates = [
		// 0. [картин]ка
		[ [ '%ка', '%ки' ], [ '%ки', '%ок' ], [ '%ке', '%кам' ], [ '%ку', '%ки' ], [ '%кой', '%ками' ], [ 'о %ке', 'о %ках' ] ],
		// 1. [картин]а
		[ [ '%а', '%ы' ], [ '%ы', '%' ], [ '%е', '%ам' ], [ '%у', '%ы' ], [ '%ой', '%ами' ], [ 'о %е', 'о %ах' ] ],
		// 2. [выпуск]
		[ [ '%', '%и' ], [ '%а', '%ов' ], [ '%у', '%ам' ], [ '%', '%и' ], [ '%ом', '%ами' ], [ 'о %е', 'о %ах' ] ],
		// 3. [стрим]
		[ [ '%', '%ы' ], [ '%а', '%ов' ], [ '%у', '%ам' ], [ '%', '%ы' ], [ '%ом', '%ами' ], [ 'о %е', 'о %ах' ] ],
		// 4. [бу]й, [буга]й
		[ [ '%й', '%и' ], [ '%я', '%ёв' ], [ '%ю', '%ям' ], [ '%й', '%и' ], [ '%ём', '%ями' ], [ 'о %е', 'о %ях' ] ],
		// 5. [д]ень, [п]ень
		[ [ '%ень', '%ни' ], [ '%ня', '%ней' ], [ '%ню', '%ням' ], [ '%ень', '%ни' ], [ '%нём', '%нями' ], [ 'о %не', 'о %нях' ] ],
		//[ [ '%', '%' ], [ '%', '%' ], [ '%', '%' ], [ '%', '%' ], [ '%', '%' ], [ 'о %', 'о %' ] ],
	];
	
	private $caseData = [
		'картинка' => [ 'base' => 'картин', 'index' => 0 ],
		'выпуск' => [ 'base' => 'выпуск', 'index' => 2 ],
		'стрим' =>  [ 'base' => 'стрим', 'index' => 3 ],
		'день' =>  [ 'base' => 'д', 'index' => 5 ],
	];
	
	private $futureConjugationTemplates = [ [ 'буду', 'будем' ], [ 'будешь', 'будете' ], [ 'будет', 'будут' ] ];
	
	private $conjugationTemplates = [
		// 0. [игра]ть
		[
			self::INFINITIVE => '%ть',
			self::PAST => [ '%л', '%ла', '%ло', '%ли' ],
			self::PRESENT => [ [ '%ю', '%ем' ], [ '%ешь', '%ете' ], [ '%ет', '%ют' ] ],
		],
		// 1. [ве]сти
		[
			self::INFINITIVE => '%сти',
			self::PAST => [ '%л', '%ла', '%ло', '%ли' ],
			self::PRESENT => [ [ '%ду', '%дём' ], [ '%дёшь', '%дёте' ], [ '%дёт', '%дут' ] ],
		],
		// 2. [транслир]овать
		[
			self::INFINITIVE => '%овать',
			self::PAST => [ '%овал', '%овала', '%овало', '%овали' ],
			self::PRESENT => [ [ '%ую', '%уем' ], [ '%уешь', '%уете' ], [ '%ует', '%уют' ] ],
		],
	];
	
	private $conjugationData = [
		'играть' => [ 'base' => 'игра', 'index' => 0 ],
		'транслировать' => [ 'base' => 'транслир', 'index' => 2 ],
		'вести' => [ 'base' => 'ве', 'index' => 1 ],
	];
	
	// возвращает форму существительного, соответствующую указанному натуральному числу
	public function caseForNumber($word, $num) {
		if ($num < 0) {
			throw new \InvalidArgumentException('Number must be non-negative');
		}
		
		if (!array_key_exists($word, $this->caseData)) {
			throw new \InvalidArgumentException('Unknown word: ' . $word);
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
		
		$data = $this->caseData[$word];
		$templateIndex = $data['index'];
		$base = $data['base'];

		$templateData = $this->caseTemplates[$templateIndex];
		$template = $templateData[$case - 1][$caseNumber - 1];

		return str_replace('%', $base, $template);
	}
	
	public function conjugation($word, $form) {
		if (!array_key_exists($word, $this->conjugationData)) {
			throw new \InvalidArgumentException('Unknown word: ' . $word);
		}

		$data = $this->conjugationData[$word];
		$templateIndex = $data['index'];
		$base = $data['base'];
		
		$templateData = $this->conjugationTemplates[$templateIndex];

		$time = $form['time'];
		$person = $form['person'];
		$number = $form['number'];
		$gender = $form['gender'];

		if ($time == self::INFINITIVE) {
			$template = $templateData[self::INFINITIVE];
		}
		elseif ($time == self::PAST) {
			$template = $templateData[self::PAST][$gender - 1];
		}
		elseif ($time == self::PRESENT) {
			$template = $templateData[self::PAST][$person - 1][$number - 1];
		}
		elseif ($time == self::FUTURE) {
			if (array_key_exists(self::FUTURE, $templateData)) {
				$template = $templateData[self::FUTURE][$person - 1][$number - 1];
			}
			else {
				$template = $futureConjugationTemplates[$person - 1][$number - 1] . ' %';
			}
		}
		
		if ($template) {
			$result = str_replace('%', $base, $template);
		}
		
		if (!$result) {
			$error = "No conjugation found for word: {$word}, time: {$time}, person: {$person}, number: {$number}, gender: {$gender}";
		}
		
		return $result ?? $error ?? $word;
	}
}
