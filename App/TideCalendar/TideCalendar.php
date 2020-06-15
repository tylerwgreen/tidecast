<?php
class TideCalendar { 

	private $credentials		= null;
	private $tideStations		= null;
	private $weekStartsOnSunday	= true;
	private $dayLabelsMon		= array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');
	private $dayLabelsSun		= array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
	private $dayLabels			= null;
	private $currentYear		= 0;
	private $currentMonth		= 0;
	private $currentDay			= 0;
	private $currentDate		= null;
	private $daysInMonth		= 0;
	private $naviHref			= null;
	private $tideEventHeadingClasses = [];
	
	public function __construct(object $credentials, object $tideStations){
		$this->credentials	= $credentials;
		$this->tideStations	= $tideStations;
		$this->naviHref		= htmlentities($_SERVER['PHP_SELF']);
		$this->dayLabels	= $this->weekStartsOnSunday ? $this->dayLabelsSun : $this->dayLabelsMon;
	}

	public function show(){
		$year	= !empty($_GET['y']) ? $_GET['y'] : date('Y');
		$month	= !empty($_GET['m']) ? $_GET['m'] : date('m');
		$this->currentYear	= $year;
		$this->currentMonth	= $month;
		$this->daysInMonth	= $this->daysInMonth($month, $year);
		$weeksInMonth = $this->weeksInMonth($month, $year);
		$currentMonthAndYear = $year . '-' . $month;
		$firstDayOfTheMonth	= date('Y-m-d 00:00:00', strtotime('first day of this month',	strtotime($currentMonthAndYear)));
		$lastDayOfTheMonth	= date('Y-m-d 23:59:59', strtotime('last day of this month',		strtotime($currentMonthAndYear)));
		$tideData = $this->getTideData($firstDayOfTheMonth, $lastDayOfTheMonth);
		$content = '<div id="calendar">' . $this->createNavi($tideData) . $this->createLabels() . '<ul class="dates' . ($weeksInMonth == 6 ? ' row-six' : '') . '">';
		for($i = 0; $i < $weeksInMonth; $i++){
			for($j = 1; $j <= 7; $j++){
				$cellNumber = $i * 7 + $j;
				$content .= $this->showDay($cellNumber, $tideData);
			}
		}
		return $content . '</ul></div>';
	}

	private function createNavi(object $tideData){
		$nextMonth	= $this->currentMonth == 12	? 1		:	intval($this->currentMonth)	+ 1;
		$nextYear	= $this->currentMonth == 12	?			intval($this->currentYear)	+ 1 : $this->currentYear;
		$preMonth	= $this->currentMonth == 1	? 12	:	intval($this->currentMonth)	- 1;
		$preYear	= $this->currentMonth == 1	? 			intval($this->currentYear)	- 1 : $this->currentYear;
		return
			'<div class="header">' .
				$this->createRegionSelector($tideData) . 
				'<a class="prev" href="' . $this->naviHref . '?m=' . sprintf('%02d', $preMonth) . '&y=' . $preYear . '">&#171;</a>' .
				'<a class="next" href="' . $this->naviHref . '?m=' . sprintf("%02d", $nextMonth) . '&y=' . $nextYear . '">&#187;</a>' .
				'<span class="title">' . date('M. Y', strtotime($this->currentYear . '-' . $this->currentMonth . '-1')) . '</span>' .
			'</div>';
	}
	
	private function createRegionSelector(object $tideRegionData){
		$content = '';
		foreach($tideRegionData as $tideStateID => $dailyTideData){
			foreach($dailyTideData as $tideRegionID => $tideData)
				$content .= '<option value="' . $tideRegionID . '">' . $tideData->name . '</option>';
		}
		return '<span class="region-selector"><label for="region-selector">Region</label><select id="region-selector" name="region">' . $content . '</select></span>';
	}

	private function createLabels(){ 
		$content = '';
		foreach($this->dayLabels as $index => $label)
			$content .= '<li class="' . ($label == 6 ? 'end title' : 'start title') . ' title">' . $label . '</li>';
		return '<ul class="labels">' . $content . '</ul>';
	}

	private function showDay(float $cellNumber, object $tideData){
		if($this->currentDay == 0){
			if($this->weekStartsOnSunday)
				$firstDayOfTheWeek = date('w', strtotime($this->currentYear . '-' . $this->currentMonth . '-01')) + 1;
			else
				$firstDayOfTheWeek = date('N', strtotime($this->currentYear . '-' . $this->currentMonth . '-01'));
			if(intval($cellNumber) == intval($firstDayOfTheWeek))
				$this->currentDay = 1;
		}
		if(
				($this->currentDay != 0)
			&&	($this->currentDay <= $this->daysInMonth)
		){
			$this->currentDate = date('Y-m-d', strtotime($this->currentYear . '-' . $this->currentMonth . '-' . ($this->currentDay)));
			$cellContent = $this->createTideContent($cellNumber, $this->currentDate, $tideData);
			$this->currentDay++;
		}else{
			$this->currentDate = null;
			$cellContent = null;
		}
// $cellContent .= '<br/><small>' . $this->currentDate . '</small>';
		return '<li id="li-' . $this->currentDate . '" class="' . ($cellNumber % 7 == 1 ? ' start ' : ($cellNumber % 7 == 0 ? ' end ' : ' ')) .
			($cellContent == null ? 'mask' : '') . '">' .
			(isset($this->currentDate) ? '<h2 class="' . (date('mdy', strtotime($this->currentDate)) == date('mdy') ? 'today' : '') . '">' . date('j', strtotime($this->currentDate)) : '') . '</h2>' .
			$cellContent . '</li>';
	}
	
	private function createTideContent(float $cellNumber, string $currentDate, object $tideData){
		$content = '';
		foreach($tideData as $tideStateID => $tideRegionData){
			foreach($tideRegionData as $tideRegionID => $dailyTideData){
				$regionContent = array();
				$currentDate = date('Ymd', strtotime($currentDate));
				$this->resetTideEventHeadingClasses();
				foreach($dailyTideData->data as $tideData){
					$sunRiseSetSet	= false;
					$tideDate = date('Ymd', strtotime($tideData->t));
					if($tideDate == $currentDate){
						$sunInfo		= date_sun_info(strtotime($tideData->t), $dailyTideData->lat, $dailyTideData->lon);
						$tideEventTime	= strtotime($tideData->t);
						$sunriseDiff	= $sunInfo['sunrise']	- $tideEventTime;
						$sunsetDiff		= $sunInfo['sunset']	- $tideEventTime;
						if(!$sunRiseSetSet){
							$regionContent[$sunInfo['sunrise']]	= '<li class="sunrise"><span class="time">' . $this->getPrettyDate($sunInfo['sunrise'])	. '</span><span class="value value-sun">Rise</span></li>';
							$regionContent[$sunInfo['sunset']]	= '<li class="sunset"><span class="time">'	. $this->getPrettyDate($sunInfo['sunset'])	. '</span><span class="value value-sun">Set</span></li>';
							$sunRiseSetSet = true;
						}
						$regionContent[$tideEventTime] = 
							'<li class="' . $this->getTideEventClasses($tideData, $sunriseDiff, $sunsetDiff) . '">' .
								'<span class="time ' . $this->getTimeEventClasses($sunriseDiff, $sunsetDiff) . '">' . $this->getPrettyDate($tideEventTime) . '</span> ' .
								'<span class="value ' . $this->getTideEventClasses($tideData, $sunriseDiff, $sunsetDiff, true) . '">' . number_format($tideData->v, 1) . '\'</span><span class="tide-indicator ' . $this->getTideIndicatorClass($tideData->type, $sunriseDiff, $sunsetDiff) . '">' . $tideData->type . '</span> ' .
							'</li>';
					}
				}
				if(!empty($regionContent)){
					ksort($regionContent);
					$content .=
						'<div id="region-tide-data-' . $cellNumber . '-' . $tideRegionID . '" class="region-tide-data region-tide-data-' . $tideRegionID . '">' . 
							'<h3 class="' . $this->getTideEventHeadingClasses() . '" title="' . $tideRegionID . '">' .
								'<span class="region-name">' . $dailyTideData->name . '</span>' .
								'<span class="tide-indicator-h '	. $this->getHighTideEventClass()	. '">H</span>' .
								'<span class="tide-indicator-l '	. $this->getLowTideEventClass()		. '">L</span>' .
							'</h3>' .
							'<ul class="tide-data">' . implode(PHP_EOL, $regionContent) . '</ul>' .
						'</div>';
				}
			}
		}
		return $content;
	}
	
	private function isSunriseBest(float $timeDiff){
		return -1800 < $timeDiff && $timeDiff <= 0 ? true : false;	// 30 mins before and 0 mins after sunrise
	}
	
	private function isSunriseBetter(float $timeDiff){
		return -2700 < $timeDiff && $timeDiff < 900 ? true : false;	// 45 mins before and 15 mins after sunrise
	}
	
	private function isSunriseGood(float $timeDiff){
		return -3600 < $timeDiff && $timeDiff < 1800 ? true : false;	// 60 mins before and 30 mins after sunrise
	}
	
	private function isSunsetBest(float $timeDiff){
		return 0 <= $timeDiff && $timeDiff < 1800 ? true : false;	// 0 mins before and 30 mins after sunset
	}
	
	private function isSunsetBetter(float $timeDiff){
		return -900 < $timeDiff && $timeDiff < 2700 ? true : false;	// 15 mins before and 45 mins after sunset
	}
	
	private function isSunsetGood(float $timeDiff){
		return -1800 < $timeDiff && $timeDiff < 3600 ? true : false;	// 15 mins before and 45 mins after sunset
	}
	
	private function getTimeEventClasses(float $sunriseDiff, float $sunsetDiff){
		if(
				!$this->isSunriseGood($sunriseDiff)
			&&	!$this->isSunsetGood($sunsetDiff)
		)
			return '';
		$classes = [];
		// sunrise events
		if($this->isSunriseGood($sunriseDiff)){
			if($this->isSunriseBest($sunriseDiff))
				$classes[] = 'sunrise-event-best';
			elseif($this->isSunriseBetter($sunriseDiff))
				$classes[] = 'sunrise-event-better';
			else
				$classes[] = 'sunrise-event-good';
		}
		// sunset events
		if($this->isSunsetGood($sunsetDiff)){
			if($this->isSunsetBest($sunsetDiff))
				$classes[] = 'sunset-event-best';
			elseif($this->isSunsetBetter($sunsetDiff))
				$classes[] = 'sunset-event-better';
			else
				$classes[] = 'sunset-event-good';
		}
		return implode(' ', $classes);
	}

	private function resetTideEventHeadingClasses(){
		$this->tideEventHeadingClasses = [];
	}
	
	private function setTideEventHeadingClass(string $class){
		if(!in_array($class, $this->tideEventHeadingClasses))
			$this->tideEventHeadingClasses[] = $class;
	}
	
	private function setTideEventHeadingClasses(array $classes){
		foreach($classes as $class){
			if(!in_array($class, $this->tideEventHeadingClasses))
				$this->tideEventHeadingClasses[] = $class;
		}
	}
	
	private function getTideEventHeadingClasses(){
		$classes = [];
		if($this->hasHighTideEvent())
			$classes[] = 'high-tide-event';
		if($this->hasLowTideEvent())
			$classes[] = 'low-tide-event';
		return implode(' ', $classes);
	}
	
	private function hasHighTideEvent(){
		return in_array('high-tide-event', $this->tideEventHeadingClasses) ? true : false;
	}
	
	private function hasLowTideEvent(){
		return in_array('low-tide-event', $this->tideEventHeadingClasses) ? true : false;
	}
	
	private function getHighTideEventClass(){
		if(in_array('high-tide-event-best', $this->tideEventHeadingClasses))
			return 'high-tide-event-best';
		elseif(in_array('high-tide-event-better', $this->tideEventHeadingClasses))
			return 'high-tide-event-better';
		elseif(in_array('high-tide-event-good', $this->tideEventHeadingClasses))
			return 'high-tide-event-good';
		else
			return '';
	}
	
	private function getLowTideEventClass(){
		if(in_array('low-tide-event-best', $this->tideEventHeadingClasses))
			return 'low-tide-event-best';
		elseif(in_array('low-tide-event-better', $this->tideEventHeadingClasses))
			return 'low-tide-event-better';
		elseif(in_array('low-tide-event-good', $this->tideEventHeadingClasses))
			return 'low-tide-event-good';
		else
			return '';
	}
	
	private function getTideEventClasses(object $tideData, float $sunriseDiff, float $sunsetDiff, bool $detailed = null){
		if(
				!$this->isSunriseGood($sunriseDiff)
			&&	!$this->isSunsetGood($sunsetDiff)
		)
			return '';
		$classes = [];
		if($tideData->type == 'L'){
			if($detailed){
				if($tideData->v < -1)
					$classes[] = 'low-tide-event-best';
				elseif($tideData->v < 1)
					$classes[] = 'low-tide-event-better';
				else
					$classes[] = 'low-tide-event-good';
			}else{
				$class = 'low-tide-event';
				$classes[] = $class;
			}
		}else if($tideData->type == 'H'){
			if($detailed){
				if($tideData->v > 7)
					$classes[] = 'high-tide-event-best';
				elseif($tideData->v > 6)
					$classes[] = 'high-tide-event-better';
				else
					$classes[] = 'high-tide-event-good';
			}else{
				$class = 'high-tide-event';
				$classes[] = $class;
			}
		}
		$this->setTideEventHeadingClasses($classes);
		return implode(' ', $classes);
	}

	private function getTideIndicatorClass($tideType, $sunriseDiff, $sunsetDiff){
		if(
				!$this->isSunriseGood($sunriseDiff)
			&&	!$this->isSunsetGood($sunsetDiff)
		)
			return '';
		return 'tide-indicator-' . strtolower($tideType) . '';
	}
	
	private function getPrettyDate(float $time){
		return str_replace(
			['am', 'pm'],
			['a', 'p'],
			date('g:i<\s\u\p>a</\s\u\p>', $time)
		);
	}

	private function weeksInMonth(float $month = null, float $year = null){
		if(null == ($year))
			$year = date('Y', time()); 
		if(null == ($month))
			$month = date('m', time());
		$daysInMonths	= $this->daysInMonth($month,$year);
		$numOfweeks		= ($daysInMonths % 7 == 0 ? 0 : 1) + intval($daysInMonths / 7);
		if($this->weekStartsOnSunday){
			$monthEndingDay	= date('w', strtotime($year . '-' . $month . '-' . $daysInMonths)) + 1;
			$monthStartDay	= date('w', strtotime($year . '-' . $month . '-01')) + 1;
		}else{
			$monthEndingDay	= date('N', strtotime($year . '-' . $month . '-' . $daysInMonths));
			$monthStartDay	= date('N', strtotime($year . '-' . $month . '-01'));
		}
		if($monthEndingDay < $monthStartDay)
			$numOfweeks++;
		return $numOfweeks;
	}

	private function daysInMonth(float $month = null, float $year = null){
		if(null == ($year))
			$year = date("Y", time());
 		if(null == ($month))
			$month = date("m", time());
		return date('t', strtotime($year . '-' . $month . '-01'));
	}
	
	private function getTideData(string $dateBegin, string $dateEnd){
		$tidesApi = new TidesApi($this->credentials);
		$tideData = new stdclass();
		foreach($this->tideStations as $tideRegionKey => $tideRegion){
			$tideData->$tideRegionKey = new stdclass();
			foreach($tideRegion as $tideRegionName => $tideRegionData){
				$tideRegionDataNew = new stdclass();
				$tideRegionDataNew->name	= $tideRegionName;
				$tidesData = $tidesApi->getTides($tideRegionData->ID, $dateBegin, $dateEnd);
				$tideRegionDataNew->data	= $tidesData->predictions;
				$tideRegionDataNew->lat		= $tideRegionData->lat;
				$tideRegionDataNew->lon		= $tideRegionData->lon;
				$tideData->$tideRegionKey->{$tideRegionData->ID} = $tideRegionDataNew;
			}
		}
		return $tideData;
	}

}