<?php

class JO_Encrypt_Md5 extends JO_Encrypt_Abstract {
	
	public static function encrypt($plain_text, $password = 123456, $bin = false, $iv_len = 16) {
	    $plain_text .= "\x13";
	    $n = strlen($plain_text);
	    if ($n % 16) $plain_text .= str_repeat("\0", 16 - ($n % 16));
	    $i = 0;
	    $enc_text = '';
		while ($iv_len-- > 0) {
	        $enc_text .= chr(mt_rand() & 0xff);
	    }
	    $iv = substr($password ^ $enc_text, 0, 512);
	    while ($i < $n) {
	        $block = substr($plain_text, $i, 16) ^ pack('H*', md5($iv));
	        $enc_text .= $block;
	        $iv = substr($block . $iv, 0, 512) ^ $password;
	        $i += 16;
	    }
	    
	    if($bin == 'url') {
	    	return urlencode($enc_text);
	    } elseif($bin == 'base64') {
	    	return base64_encode($enc_text);
	    } elseif($bin == 'base64url') {
	    	return self::base64url_encode($enc_text);
	    } else {
	    	return $enc_text;
	    }
	}
	
	public static function decrypt($enc_text, $password = 123456, $bin = false, $iv_len = 16) {
	    
	    if($bin == 'url') {
	    	$enc_text = urldecode($enc_text);
	    } elseif($bin == 'base64') {
	    	$enc_text = base64_decode($enc_text);
	    } elseif($bin == 'base64url') {
	    	$enc_text = self::base64url_decode($enc_text);
	    }  else {
	    	$enc_text = $enc_text;
	    }

	    $n = strlen($enc_text);
	    $i = $iv_len;
	    $plain_text = '';
	    $iv = substr($password ^ substr($enc_text, 0, $iv_len), 0, 512);
	    while ($i < $n) {
	        $block = substr($enc_text, $i, 16);
	        $plain_text .= $block ^ pack('H*', md5($iv));
	        $iv = substr($block . $iv, 0, 512) ^ $password;
	        $i += 16;
	    }
	    return preg_replace('/\\x13\\x00*$/', '', $plain_text);
	}
	
	public static function base64url_encode($data) {
	  return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
	}
	
	public static function base64url_decode($data) {
	  return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
	} 
	
	

}

?>