<?php

class WM_Date {
	
	public static function x_week_range($data = null) {
		$interval = array();
		$interval['from'] = JO_Date::getInstance($data, JO_Date::ATOM, true)->setInterval('monday this week')->toString();
		$interval['to'] = JO_Date::getInstance($data, JO_Date::ATOM, true)->setInterval('sunday this week')->toString();
		return $interval;
	}
	
	
	public static function format($date, $format = null) {
		if(!$format) {
			$format = JO_Registry::get('config_date_format_short');
		}
		return (string) new JO_Date($date, $format);
	}
	
	public static function getDaysListBetweenTwoDate($startDate, $endDate = 'now') {
	    $date = JO_Date::getInstance();
	    $date->setFormat('yy-mm-dd');
		$pastDateTS = $date->dateToUnix($startDate);
	    $currentDateArray = array();
	    for ($currentDateTS = $pastDateTS; $currentDateTS < strtotime($endDate); $currentDateTS += (86400)) {
			$currentDateArray[] = $date->setDate($currentDateTS)->toString();
	    }
	    return $currentDateArray;
	}
	
	public static function dateDiff($time1, $time2, $precision = 7, $full = false) {
    	// If not numeric then convert texts to unix timestamps
		if (!is_int($time1)) {
			$time1 = JO_Date::dateToUnix($time1);
		}
		if (!is_int($time2)) {
			$time2 = JO_Date::dateToUnix($time2);
		}
 
		// If time1 is bigger than time2
		// Then swap time1 and time2
		if ($time1 > $time2) {
			$ttime = $time1;
			$time1 = $time2;
			$time2 = $ttime;
		}
 
		// Set up intervals and diffs arrays
		$intervals = array('year','month','week','day','hour','minute','second');
		$diffs = array();
 
		// Loop thru all intervals
		foreach ($intervals as $interval) {
			// Set default diff to 0
			$diffs[$interval] = 0;
			// Create temp time from time1 and interval
			$ttime = strtotime("+1 " . $interval, $time1);
			// Loop until temp time is smaller than time2
			while ($time2 >= $ttime) {
				$time1 = $ttime;
				$diffs[$interval]++;
				// Create new temp time from time1 and interval
				$ttime = strtotime("+1 " . $interval, $time1);
			}
		}
 
		$count = 0;
		$times = array();
		// Loop thru all diffs
		foreach ($diffs as $interval => $value) {
			// Break if we have needed precission
			if (!$full && $count >= $precision) {
				break;
			}
			// Add value and interval 
			// if value is bigger than 0
			if ($value > 0 || $full) {
				// Add s if value is not 1
				if ($value != 1) {
					$interval .= "s";
				}
				// Add value and interval to times array
				$times[] = array('key' => $interval, 'value' => $value);
				$count++;
			}
		}
	 
	    // Return string with times
	    return $times;
	}
	
	

}

?>