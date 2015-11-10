<?php

class JO_Date {

    const DAY_OF_MONTH 					= 'd';
    const DAY_OF_MONTH_TWO_DIGIT 		= 'dd';
    const DAY_OF_THE_YEAR 				= 'o';
    const DAY_OF_THE_YEAR_THREE_DIGIT 	= 'oo';
    const DAY_NAME_SHORT 				= 'D';
    const DAY_NAME_LONG 				= 'DD';

    const MONTH_OF_YEAR 				= 'm';
    const MONTH_OF_YEAR_TWO_DIGIT 		= 'mm';
    const MONTH_NAME_SHORT 				= 'M';
    const MONTH_NAME_LONG 				= 'MM';

    const YEAR_TWO_DIGIT 				= 'y';
    const YEAR_FOUR_DIGIT 				= 'yy';

    const TIMESTAMP						= 'UNIX';
    const ATOM 							= 'yy-mm-dd';
    const ATOM_FULL 					= 'yy-mm-dd H:i:sP';
    const COOKIE 						= 'D, dd M yy';
    const COOKIE_FULL 					= 'D, dd M yy H:i:s e';
    const ISO_8601 						= 'yy-mm-dd';
    const RFC_822 						= 'D, d M y';
    const RFC_822_FULL 					= 'D, d M y H:i:s O';
    const RFC_850 						= 'DD, dd-M-y';
    const RFC_850_FULL 					= 'DD, dd-M-y H:i:s e';
    const RFC_1036 						= 'D, d M y';
    const RFC_1036_FULL 				= 'D, d M y H:i:s O';
    const RFC_1123 						= 'D, d M yy';
    const RFC_1123_FULL 				= 'D, d M yy H:i:s O';
    const RFC_2822 						= 'D, d M yy';
    const RSS 							= 'D, d M y';
    const RSS_FULL 						= 'D, d M y H:i:s O';
    const W3C 							= 'yy-mm-dd';
    const W3C_FULL 						= 'yy-mm-dd\TH:i:sP';
    const SQL_FULL 						= 'yy-mm-dd H:i:s';

    private static $date;

    private static $format = JO_Date::W3C_FULL;

    private static $instance;

    /**
     * @param $date
     * @param $format
     * @param $reset
     * @return JO_Date
     */
    public static function getInstance($date = null, $format = null, $reset = false) {
		if(self::$instance == null || $reset === true) {
		    self::$instance = new self($date, $format);
		}
		return self::$instance;
    }

    /**
     * @param string|int $date
     * @param string|JO_Date::const $format
     */
    public function __construct($date = null, $format = null) { 
		if($date === null) {
		    self::$date = date(self::_toToken(JO_Date::W3C_FULL), time());
		} else {
			if($date) {
		    	self::$date = date(self::_toToken(JO_Date::W3C_FULL), self::dateToUnix($date));
			} else {
				self::$date = date(self::_toToken(JO_Date::W3C_FULL), time());
			}
		} 
		if($format) {
		    self::setFormat($format);
		}
    }

    /**
     * @param string||jo_Date::const $format
     * @return JO_Date
     */
    public static function setFormat($format) {
		self::$format = $format;
		return self::$instance;
    }

    public static function getFormat() {
		return self::$format;
    }
    
    /**
     * @param string $date
     * @return JO_Date
     */
    public static function setDate($date) {
		self::$date = $date;
		return self::$instance;
    }

    public static function getDate($format = null) {
		if($format) {
		    return date(self::_toToken($format), self::dateToUnix(self::$date));
		} else {
		    return date(self::_toToken(self::$format), self::dateToUnix(self::$date));
		}
    }
    
    /**
     * +/-1 day
     * +/-1 week
     * +/-2 week
     * +/-1 month
     * +/-30 days
     * +/-1 week 2 days 4 hours 2 seconds
     * @param string $interval
     * @return JO_Date
     */
    public static function setInterval($interval) {
		self::$date = strtotime(self::$date . ' ' . $interval);
		return self::$instance;
    }

    public function __toString() {
		return date(self::_toToken(self::$format), self::dateToUnix(self::$date));
    }

    public static function toString() {
		return date(self::_toToken(self::$format), self::dateToUnix(self::$date));
    }

    public static function dateToUnix($date = null) {
    	if(!$date) {
    		$date = date(self::_toToken(self::$format), self::dateToUnix(self::$date));;
    	} 
		if(preg_match('/^[0-9]{1,}$/',$date)) {
		    return $date;
		}
		if(strtotime($date)) {
			return strtotime($date);
		}
		return $date;
    }

    private function _toToken($part) {
        // get format tokens
        $comment = false;
        $format  = '';
        $orig    = '';
        for ($i = 0; $i < strlen($part); ++$i) {
            if ($part[$i] == "'") {
                $comment = $comment ? false : true;
                if (isset($part[$i+1]) && ($part[$i+1] == "'")) {
                    $comment = $comment ? false : true;
                    $format .= "\\'";
                    ++$i;
                }

                $orig = '';
                continue;
            }

            if ($comment) {
                $format .= '\\' . $part[$i];
                $orig = '';
            } else {
                $orig .= $part[$i];
                if (!isset($part[$i+1]) || (isset($orig[0]) && ($orig[0] != $part[$i+1]))) {
                    $format .= self::_parseIsoToDate($orig);
                    $orig  = '';
                }
            }
        }

        return $format;
    }

    public static function _parseIsoToDate($token) { 
		switch($token) {
		    case JO_Date::DAY_OF_MONTH:
			return 'j';
		    break;
		    case JO_Date::DAY_OF_MONTH_TWO_DIGIT:
			return 'd';
		    break;
		    case JO_Date::DAY_OF_THE_YEAR:
			return 'z';
		    break;
		    case JO_Date::DAY_OF_THE_YEAR_THREE_DIGIT:
			return self::_toComment(strftime("%j", self::dateToUnix(self::$date)));
		    break;
		    case JO_Date::DAY_NAME_SHORT: 
			return self::_toComment(strftime("%a", self::dateToUnix(self::$date)));
		    break;
		    case JO_Date::DAY_NAME_LONG:
			return self::_toComment(strftime("%A", self::dateToUnix(self::$date)));
		    break;
		    case JO_Date::MONTH_OF_YEAR:
			return 'n';
		    break;
		    case JO_Date::MONTH_OF_YEAR_TWO_DIGIT:
			return 'm';
		    break;
		    case JO_Date::MONTH_NAME_SHORT:
			return self::_toComment(strftime("%b", self::dateToUnix(self::$date)));
		    break;
		    case JO_Date::MONTH_NAME_LONG:
			return self::_toComment(strftime("%B", self::dateToUnix(self::$date)));
		    break;
		    case JO_Date::YEAR_TWO_DIGIT:
			return 'y';
		    break;
		    case JO_Date::YEAR_FOUR_DIGIT:
			return 'Y';
		    break;
		    default:
			return $token;
		    break;
		}
    }

    private static function _toComment($token) {
        $token = str_split($token);
        $result = '';
        foreach ($token as $tok) {
            $result .= '\\' . $tok;
        }

        return $result;
    }


} 

//header('Content-type: text/html; charset=utf-8');

//setlocale( LC_TIME, 'bg_BG.utf8');
//setlocale(LC_TIME, 'bulgarian');

//echo strftime("%A %e %B %Y", mktime(0, 0, 0, 12, 22, 1978));

//$test = new JO_Date('22.02.1982', JO_Date::RFC_1123_FULL);
//echo $test;
//$test->setFormat(JO_Date::RFC_1123_FULL);

//var_dump($test->getDate(JO_Date::ATOM));

//var_dump(str_split(JO_Date::RFC_850));


