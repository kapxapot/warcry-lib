<?php

namespace Warcry\Util;

class Date {
	const DATE_FORMAT = 'Y-m-d H:i:s';
	
	const SHORT_MONTHS = [
		1 => 'Янв',
		2 => 'Фев',
		3 => 'Мар',
		4 => 'Апр',
		5 => 'Май',
		6 => 'Июн',
		7 => 'Июл',
		8 => 'Авг',
		9 => 'Сен',
		10 => 'Окт',
		11 => 'Ноя',
		12 => 'Дек',
	];
	
	const MONTHS = [
		1 => 'Январь',
		2 => 'Февраль',
		3 => 'Март',
		4 => 'Апрель',
		5 => 'Май',
		6 => 'Июнь',
		7 => 'Июль',
		8 => 'Август',
		9 => 'Сентябрь',
		10 => 'Октябрь',
		11 => 'Ноябрь',
		12 => 'Декабрь',
	];
	
	// null = now()
	static public function dt($date = null) {
		return ($date instanceof \DateTime)
			? $date
			: new \DateTime($date);
	}
	
	static public function interval($interval) {
		return ($interval instanceof \DateInterval)
			? $interval
			: new \DateInterval($interval);
	}

	// deprecated, use dbNow
	static public function now() {
		return date(self::DATE_FORMAT);	
	}
	
	static public function dbNow() {
		return $this->formatDb(self::dt());
	}
	
	// null = now()
	static public function diff($start, $end = null) {
		$startDate = self::dt($start);
		$endDate = self::dt($end);

		return $startDate->diff($endDate);
	}
	
	static public function age($date) {
		return self::diff($date);
	}
	
	static public function exceedsInterval($start, $end, $interval) {
		$startDate = self::dt($start);
		$endDate = self::dt($end);
		
		$interval = self::interval($interval);

		$startWithInterval = $startDate->add($interval);

		return $endDate >= $startWithInterval;
	}
	
	static public function happened($date) {
		if (!$date) {
			return false;
		}
		
		$now =  new \DateTime;
		$dt = new \DateTime($date);
		
		return $now >= $dt;
	}
	
	static public function toAgo($date) {
		if ($date) {
			$now = new \DateTime;
			$today = new \DateTime("today");
			$yesterday = new \DateTime("yesterday");		

			$dt = new \DateTime($date);
	
			if ($dt > $today) {
				$str = 'сегодня';
			}
			elseif ($dt > $yesterday) {
				$str = 'вчера';
			}
			else {
				$age = self::age($date);
				$days = $age->days;
				
				$cases = new Cases;
				$str = $days . ' ' . $cases->caseForNumber('день', $days) . ' назад';
			}
		}
		
		return $str ?? 'неизвестно когда';
	}
	
	static public function startOfHour(\DateTime $date) {
		$copy = clone $date;
		$copy->setTime($copy->format('H'), 0, 0);
		
		return $copy;
	}
	
	static public function formatDb(\DateTime $date) {
		return $date->format(self::DATE_FORMAT);
	}
	
	static public function formatIso($date) {
		return ($date instanceof \DateTime)
			? $date->format('c')
			: strftime('%FT%T%z', $date);
	}
}
