<?php

class Model_GoogleApi {
	
	public function __construct() {}
	
	public static function file_get_contents_curl($url) {
		if(!function_exists('curl_init')) {
			return '';
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Set curl to return the data instead of printing it to the browser.
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		
		$data = curl_exec($ch);
		
		curl_close($ch);
		
		return $data;
	}
	
	public static function getCordinatesByPlace($place) {
		$old_agent = ini_get('user_agent');
		ini_set('user_agent', 'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.15) Gecko/20110303 SUSE/3.6.15-0.2.2 Firefox/3.6.15');
		$url = 'http://maps.google.com/maps/geo?q=' . urlencode($place) . '&output=json&oe=utf8&sensor=false&key=' . JO_Registry::get('config_google_map');
		
		if (ini_get('allow_url_fopen')) {
			$code = file_get_contents($url);
		} else {
			$code = self::file_get_contents_curl($url);
		}
		
		ini_set('user_agent', $old_agent);
		
		if(!$code) {
			return array('Lat' => 0, 'Lng' => 0);
		}
		
		$result = JO_Json::decode($code, true);
		
		if(isset($result['Placemark'][0]['Point']['coordinates'][1])) {
			return array('Lat' => $result['Placemark'][0]['Point']['coordinates'][1], 'Lng' => $result['Placemark'][0]['Point']['coordinates'][0]);
		}
		return array('Lat' => 0, 'Lng' => 0);
	
	}
	
	
}

?>