<?php

class JO_Validate {
	
	/**
	 * @param string $Address
	 * @param boolean $is_online
	 * @return string|Ambigous <string, mixed>|mixed|string
	 */
	public static function validateHost($Address, $is_online = false) {
   		$parseUrl = @parse_url(trim($Address));
   		$host = '';
   		if( isset($parseUrl['host']) && $parseUrl['host'] ) {
   			$host = $parseUrl['host'];
   		} elseif( isset($parseUrl['path']) && $parseUrl['path'] && strpos($parseUrl['path'], '.')) {
   			$parts = explode('/', $parseUrl['path'], 2);
   			$host = array_shift($parts);
   		} 
   		if(filter_var('http://' . $host, FILTER_VALIDATE_URL)) {
   			if($is_online && function_exists('gethostbyname')) {
   				if( gethostbyname( $host ) == $host) {
   					return false;
   				} else {
   					return $host;
   				}
   			} else {
   				return $host;
   			}
   		}
   		return false;
	} 
	
	public static function validateEmail($email) {
		if(preg_match('/^[A-Z0-9._%-+]+@[A-Z0-9][A-Z0-9.-]{0,61}[A-Z0-9]\.[A-Z]{2,6}$/i', $email)) {
			return true;
		} else {
			return false;
		}
	}

}

?>