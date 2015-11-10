<?php

class JO_Utf8 {
	
	public static function array_change_key_case_unicode($arr, $c = CASE_LOWER) {
		$c = ($c == CASE_LOWER) ? MB_CASE_LOWER : MB_CASE_UPPER;
		foreach ($arr as $k => $v) {
			$ret[mb_convert_case($k, $c, "UTF-8")] = $v;
		}
		return $ret;
	}
	
	public static function array_change_value_case_unicode($arr, $c = CASE_LOWER) {
		$c = ($c == CASE_LOWER) ? MB_CASE_LOWER : MB_CASE_UPPER;
		foreach ($arr as $k => $v) {
			$ret[$k] = mb_convert_case($v, $c, "UTF-8");
		}
		return $ret;
	}
	
	public static function array_change_case_unicode($arr, $c = CASE_LOWER) {
		$c = ($c == CASE_LOWER) ? MB_CASE_LOWER : MB_CASE_UPPER;
		foreach ($arr as $k => $v) {
			$ret[mb_convert_case($k, $c, "UTF-8")] = mb_convert_case($v, $c, "UTF-8");
		}
		return $ret;
	}
	
	public static function splitText($string, $length, $comas = '') {
	    if($length<mb_strlen($string, 'utf-8')) {
	        $words = explode(' ', $string);
	        $result = '';
	        for($i=0; $i<count($words); $i++) {
	        	$result .= $words[$i] .' ';
	        	if(mb_strlen($result, 'utf-8') > $length) {
	        		return trim($result, ' ') . $comas;
	        	}
	        }
	        return trim($result, ' ') . $comas;
	    } else { 
	    	return $string;	
	    }
	}

	/**
	 * This function extracts the non-tags string and returns a correctly formatted string
	 * It can handle all html entities e.g. &amp;, &quot;, etc..
	 *
	 * @param string $s
	 * @param integer $srt
	 * @param integer $len
	 * @param bool/integer	Strict if this is defined, then the last word will be complete. If this is set to 2 then the last sentence will be completed.
	 * @param string A string to suffix the value, only if it has been chopped.
	 */
	public static function mb_html_substr( $s, $srt, $len = NULL, $strict=false, $suffix = NULL ) {
		if ( is_null($len) ){ $len = strlen( $s ); }
		
		$f = 'static $strlen=0; 
				if ( $strlen >= ' . $len . ' ) { return "><"; } 
				$html_str = html_entity_decode( $a[1], ENT_QUOTES, "utf-8");
				$subsrt   = max(0, ('.$srt.'-$strlen));
				$sublen = ' . ( empty($strict)? '(' . $len . '-$strlen)' : 'max(@mb_strpos( $html_str, "' . ($strict===2?'.':' ') . '", (' . $len . ' - $strlen + $subsrt - 1 ), "utf-8"), ' . $len . ' - $strlen)' ) . ';
				$new_str = mb_substr( $html_str, $subsrt,$sublen, "utf-8"); 
				$strlen += $new_str_len = mb_strlen( $new_str, "utf-8" );
				$suffix = ' . (!empty( $suffix ) ? '($new_str_len===$sublen?"'.$suffix.'":"")' : '""' ) . ';
				return ">" . htmlentities($new_str, ENT_QUOTES, "UTF-8") . "$suffix<";';
		
		return preg_replace( array( "#<[^/][^>]+>(?R)*</[^>]+>#", "#(<(b|h)r\s?/?>){2,}$#is"), "", trim( rtrim( ltrim( preg_replace_callback( "#>([^<]+)<#", create_function(
	            '$a',
	          $f
	        ), ">$s<"  ), ">"), "<" ) ) );
	}
	
	
	/**
	 * This function extracts the non-tags string and returns a correctly formatted string
	 * It can handle all html entities e.g. &amp;, &quot;, etc..
	 *
	 * @param string $string
	 * @param integer $maxlen
	 * @param string A string for dot's or other.
	 */
	public static function substr_avoid($string, $maxlen, $addon = '...') {
	    if(mb_strlen(strip_tags($string), 'utf-8') <= $maxlen) { 
	    	// Becouse It's supposed to be valid HTML...
	        return $string;
	    }
	    $closing_tags = array();
	    $string = str_replace(chr(0), '', $string); // No one is expecting it but let's make sure.
	    $string = preg_replace('/(<\/?[a-z][a-z0-9]*[^<>]*>)/im', chr(0) . '\\1' . chr(0), $string);
	    $result = explode(chr(0), $string);
	    for($i = 0, $n = count($result), $len = 0; $i < $n; $i++)
	    {
	        if($i % 2)
	        {
	            if(mb_substr($result[$i], -2, 1, 'utf-8') == '/')
	            {
	                continue;
	            }
	            if($result[$i]{1} == '/')
	            {
	                unset($closing_tags[array_search($result[$i], $closing_tags)]);
	                continue;
	            }
	            $closing_tags[$i] = '</' . ((mb_strpos($result[$i], ' ', 0, 'utf-8') === false) ? mb_substr($result[$i], 1, -1, 'utf-8') : mb_substr($result[$i], 1, mb_strpos($result[$i], ' ', 'utf-8') - 1, 'utf-8')) . '>';
	        }
	        else
	        {
	            $len += mb_strlen($result[$i], 'utf-8');            
	            if($len >= $maxlen)
	            {
	                $len -= mb_strlen($result[$i], 'utf-8');
	                break;
	            }
	        }
	    }
	    return implode('', array_slice($result, 0, $i)) . mb_substr($result[$i], 0, $maxlen - $len, 'utf-8') . $addon . implode(array_reverse($closing_tags));
	} 
	
	
	
	public static function mb_substrws($text, $max_length, $points = null)
	{
	    $tags   = array();
	    $result = "";
	    $is_open   = false;
	    $grab_open = false;
	    $is_close  = false;
	    $tag = "";
	    $i = 0;
	    $stripped = 0;
	    $stripped_text = strip_tags($text);
	    while ($i < mb_strlen($text, 'utf-8') && $stripped < mb_strlen($stripped_text, 'utf-8') && $stripped < $max_length) {

	        $symbol  = mb_substr($text, $i, 1, 'utf-8');
	        $result .= $symbol;
	        switch ($symbol) {
	            case '<':
	                $is_open   = true;
	                $grab_open = true;
	                break;
	            case '/':
	                if ($is_open) {
	                    $is_close  = true;
	                    $is_open   = false;
	                    $grab_open = false;
	                }
	                break;
	            case ' ':
	                if ($is_open) {
	                    $grab_open = false;
	                } else {
	                    $stripped++;
	                }
	                break;
	            case '>':
	                if ($is_open) {
	                    $is_open   = false;
	                    $grab_open = false;
	                    array_push($tags, $tag);
	                    $tag = "";
	                } else if ($is_close) {
	                    $is_close = false;
	                    array_pop($tags);
	                    $tag = "";
	                }
	                break;
	            default:
	                if ($grab_open || $is_close) {
	                    $tag .= $symbol;
	                }
	                if (!$is_open && !$is_close) {
	                    $stripped++;
	                }
	        }
	        $i++;
	    }
	    
	    $result .=  $points;
	    
	    while ($tags) {
	        $result .= "</".array_pop($tags).">";
	    }
	    return $result;
	}
	
	public static function convertToUtf8($in) 
	{ 
        if (is_array($in)) { 
            foreach ($in as $key => $value) { 
                $out[self::convertToUtf8($key)] = self::convertToUtf8($value); 
            } 
        } elseif(is_string($in)) {
            if(mb_detect_encoding($in) != "UTF-8") 
                return utf8_encode($in); 
            else 
                return $in; 
        } else { 
            return $in; 
        } 
        return $out; 
	} 

//	public static function convertToUtf8($array) { 
//		if( is_array($array) ) {
//			$convertedArray = array(); 
//			foreach($array as $key => $value) { 
//				if(!mb_check_encoding($key, 'UTF-8'))  {
//					$key = utf8_encode($key); 
//				}
//	    		if(is_array($value)) { 
//	    			$value = self::convertKeysToUtf8($value); 
//	    		} else {
//	    			if(!mb_check_encoding($value, 'UTF-8'))  {
//	    				$value = utf8_encode($value);
//	    			}
//	    		}
//	
//	    		$convertedArray[$key] = $value; 
//	  		} 
//	  		return $convertedArray; 
//		} else {
//			if(!mb_check_encoding($array, 'UTF-8'))  {
//    			$array = utf8_encode($array);
//    		}
//    		return $array;
//		}
//	} 
	
	public static function str_word_split($string, $length = 3) {
		$result = array();
	    $words = preg_split('/([\s\-_,:;?!\/\(\)\[\]{}<>\r\n"]|(?<!\d)\.(?!\d))/', $string, null, PREG_SPLIT_NO_EMPTY);

	    foreach($words AS $word) {
	    	if( mb_strlen($word, 'utf-8') >= $length ) {
	    		$result[] = $word;
	    	}
	    }
	    return $result;
	}
	
	
}

?>