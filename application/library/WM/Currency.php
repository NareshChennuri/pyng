<?php

class WM_Currency {
	
	protected static $code;
	protected static $currencies = array();
	
	public static function format($number, $currency = '', $value = '', $format = true, $decimal_place_set = false) {
		self::construct();
		if ($currency && self::hasCurrency($currency)) {
      		$symbol_left   = self::$currencies[$currency]['symbol_left'];
      		$symbol_right  = self::$currencies[$currency]['symbol_right'];
      		$decimal_place = self::$currencies[$currency]['decimal_place'];
      		$decimal_point = self::$currencies[$currency]['decimal_point'];
      		$thousand_point = self::$currencies[$currency]['thousand_point'];
    	} else {
      		$symbol_left   = self::$currencies[self::$code]['symbol_left'];
      		$symbol_right  = self::$currencies[self::$code]['symbol_right'];
      		$decimal_place = self::$currencies[self::$code]['decimal_place'];
      		$decimal_point = self::$currencies[self::$code]['decimal_point'];
      		$thousand_point = self::$currencies[self::$code]['thousand_point'];
			
			$currency = self::$code;
    	}
    	
    	if($decimal_place_set !== false && is_int($decimal_place_set)) {
    		$decimal_place = $decimal_place_set;
    	}
		
    	if(!$value) {
    		$value = self::$currencies[$currency]['value'];
    	}
    	
		if ($value) {
      		$value = $number * $value;
    	} else {
      		$value = $number;
    	}
    	
    	$string = '';
    	
		if (($symbol_left) && ($format)) {
      		$string .= $symbol_left;
    	}
    	
		if (!$format) {
			$decimal_point = '.';
		}
		
		if (!$format) {
			$thousand_point = '';
		}
    	
		$string .= number_format(round($value, (int)$decimal_place), (int)$decimal_place, $decimal_point, $thousand_point);

    	if (($symbol_right) && ($format)) {
      		$string .= $symbol_right;
    	}
		
		return $string;
	}
	
	public static function normalize($number, $dec = false) {
		self::construct();
		$value = self::$currencies[self::$code]['value'];
		if ($value) {
      		$value = $number * $value;
    	} else {
      		$value = $number;
    	}
    	if(!$dec) {
    		$dec = self::$currencies[self::$code]['decimal_place'];
    	}
		return number_format(round($value, (int)$dec), (int)$dec, '.', '');
	}
	
	public static function toDb($value) {
		$value = str_replace(',','.',$value);
		return $value;
	} 

	
	//////////////////////////
	
	public static function construct() {
		if(!self::$currencies) {
			$request = JO_Request::getInstance();
			$currencies = self::getCurrencies();
			if($currencies) {
				foreach ($currencies as $result) {
					self::$currencies[$result['code']] = array(
		        		'currency_id'   => $result['currency_id'],
		        		'title'         => $result['title'],
		        		'symbol_left'   => $result['symbol_left'],
		        		'symbol_right'  => $result['symbol_right'],
		        		'decimal_place' => $result['decimal_place'],
		        		'value'         => $result['value'],
						'decimal_point' => $result['decimal_point'],
						'thousand_point'=> $result['thousand_point'],
						'code'			=> $result['code']
		      		); 
				}
			}
			
			if($request->getRequest('currency') && array_key_exists($request->getRequest('currency'), self::$currencies)) {
				self::setCurrency($request->getRequest('currency'));
			} elseif (JO_Session::get('currency') && array_key_exists(JO_Session::get('currency'), self::$currencies)) {
				self::setCurrency(JO_Session::get('currency'));
			} elseif($request->getCookie('currency') && array_key_exists($request->getCookie('currency'), self::$currencies)) {
				self::setCurrency($request->getCookie('currency'));
			} else {
				self::setCurrency(Helper_Config::get('config_currency'));
			}
			
			if(JO_Request::getInstance()->getModule() == 'admin') {
				self::setCurrency(Helper_Config::get('config_currency'));
			}
			
// 			self::setCurrency(Helper_Config::get('config_currency'));
			
		}
	}
	
	public static function hasCurrency($currency) {
    	return isset(self::$currencies[$currency]);
  	}
	
	public static function setCurrency($currency) {
		self::$code = $currency;
		if(JO_Session::get('currency') != $currency) {
			JO_Session::set('currency', $currency);
		}
		if(JO_Request::getInstance()->getCookie('currency') != $currency) {
			setcookie('currency', $currency, time() + 60 * 60 * 24 * 30, '/', JO_Request::getInstance()->getServer('HTTP_HOST'));
		}
	}
	
	public static function getCurrencyCode($currency = '') {
		self::construct();
		return self::$code;
	}
	
	public static function getCurrency($currency = '') {
		self::construct();
		if ($currency && self::hasCurrency($currency)) {
      		return self::$currencies[$currency];
		} else {
			return self::$currencies[self::$code];
		}
	}
	
	public static function getCurrencies() {
		$db = JO_Db::getDefaultAdapter();
		$query = $db->select()
					->from('currency')
					->where('status = 1');
		return $db->fetchAll($query);
	}
	
	public static function updateCurrencies($code = null, $from_admin = false) {
		
		if (extension_loaded('curl')) {
			$db = JO_Db::getDefaultAdapter();
			$query = $db->select()
						->from('currency')
						->where('code != ?', (string)($code ? $code : Helper_Config::get('config_currency')) );
						
			if(!$from_admin) {
				$query->where('date_modified < ?', JO_Date::getInstance('-1 day', 'yy-mm-dd H:i:s', true)->toString());
			}
						
			$data = array();
			$results = $db->fetchAll($query);
			if($results) {
				foreach($results AS $result) {
					$data[] = Helper_Config::get('config_currency') . $result['code'] . '=X';
				}
				
				$db->update('currency', array(
						'value' => '1.00000',
						'date_modified' => new JO_Db_Expr('NOW()')
				), array('code = ?' => Helper_Config::get('config_currency')));
				
			} 
			
			if($data) {
				
				if (ini_get('allow_url_fopen')) {
					$content = @file_get_contents('http://download.finance.yahoo.com/d/quotes.csv?s=' . implode(',', $data) . '&f=sl1&e=.csv');
				} else {
					$content = self::file_get_contents_curl('http://download.finance.yahoo.com/d/quotes.csv?s=' . implode(',', $data) . '&f=sl1&e=.csv');
				}
				
				
				$lines = explode("\n", trim($content));
				
				foreach ($lines as $line) {
					$currency = substr($line, 4, 3);
					$value = substr($line, 11, 6);
					
					if ((float)$value) {
						$db->update('currency', array(
							'value' => (float)$value,
							'date_modified' => new JO_Db_Expr('NOW()')
						), array('code = ?' => $currency));
					}
				}
				
				
				
			}
			
		}
	}
	
	public static function file_get_contents_curl($url) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		if(!ini_get('safe_mode') && !ini_get('open_basedir')) {
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		}
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);	
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1");
		curl_setopt($ch, CURLOPT_HTTPGET, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
		curl_setopt($ch, CURLOPT_MAXCONNECTS, 5);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		$Rec_Data = curl_exec($ch);
		curl_close($ch);
		return $Rec_Data;	
	}
}

?>