<?php

class JO_Request_Server {

	// encoded --
	public static function encode_ip ($ip) {
	    $d = explode('.', $ip);
	    if (count($d) == 4) return sprintf('%02x%02x%02x%02x', $d[0], $d[1], $d[2], $d[3]);
	 
	    $d = explode(':', preg_replace('/(^:)|(:$)/', '', $ip));
	    $res = '';
	    foreach ($d as $x)
	        $res .= sprintf('%0'. ($x == '' ? (9 - count($d)) * 4 : 4) .'s', $x);
	    return $res;
	} 
	
	public static function decode_ip ($int_ip) {
	 
	    if (strlen($int_ip) == 32) {
	        $int_ip = substr(chunk_split($int_ip, 4, ':'), 0, 39);
	        $int_ip = ':'. implode(':', array_map(array('JO_Request_Server', 'hexhex'), explode(':',$int_ip))) .':';
	        if(preg_match_all("/(:0)+/", $int_ip, $zeros)) {
		        if (count($zeros[0]) > 0) {
		            $match = '';
		            foreach($zeros[0] as $zero)
		                if (strlen($zero) > strlen($match))
		                    $match = $zero;
		            $int_ip = preg_replace('/'. $match .'/', ':', $int_ip, 1);
		        }
	        }
	        return preg_replace('/(^:([^:]))|(([^:]):$)/', '$2$4', $int_ip);
	    }
	    $hexipbang = explode('.', chunk_split($int_ip, 2, '.'));
	    return hexdec($hexipbang[0]). '.' . hexdec($hexipbang[1]) . '.' . hexdec($hexipbang[2]) . '.' . hexdec($hexipbang[3]);
	} 
	
	public static function hexhex($value) { 
		return dechex(hexdec($value)); 
	}
	
	public static function _IP2LONG($a){
		$d = 0.0;
		$b = explode(".", $a,4);
		for ($i = 0; $i < 4; $i++) {
			$d *= 256.0;
			$d += $b[$i];
		};
		return $d;
	}
	
	public static function _LONG2IP_($a){
		$b=array(0,0,0,0);
		$c = 16777216.0;
		$a += 0.0;
		for ($i = 0; $i < 4; $i++) {
			$k = (int) ($a / $c);
			$a -= $c * $k;
			$b[$i]= $k;
			$c /=256.0;
		};
		$d=join('.', $b);
		return($d);
	}
	
}

?>