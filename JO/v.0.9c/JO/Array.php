<?php

class JO_Array {
	
	public static function getPart($array, $size) {
		$return = array();
		$i=0;
		foreach($array AS $key => $val) {
			$return[$key] = $val;
			if($i > $size) {
				break;
			}
			$i++;
		}
		return $return;
	} 
	
	public static function array_sort($array, $on, $order='SORT_DESC') {
		$new_array = array();
		$sortable_array = array();
 
		if (count($array) > 0) {
			foreach ($array as $k => $v) {
				if (is_array($v)) {
					foreach ($v as $k2 => $v2) {
						if ($k2 == $on) {
							$sortable_array[$k] = $v2;
						}
					}
				} else {
					$sortable_array[$k] = $v;
				}
			}
 
			switch($order) {
				case 'SORT_ASC':
					asort($sortable_array);
				break;
				case 'SORT_DESC':
					arsort($sortable_array);
				break;
			}
 
			foreach($sortable_array as $k => $v) {
				$new_array[] = $array[$k];
			}
		}
		return $new_array;
	} 
	
	public static function array_search($needle, array $haystack) {
		if (empty($needle) || empty($haystack)) {
            return false;
        }
        
        $return = array();
        foreach($haystack AS $key => $value) {
        	if($needle === $value) {
        		$return[] = $key;
        	}
        }
        return count($return) > 0 ? $return : false;
	}
	
	public static function mb_strpos_array($haystack, $needles, $offset =0, $charset = 'utf-8') {
	    if ( is_array($needles) ) {
	        foreach ($needles as $str) {
	            if ( is_array($str) ) {
	                $pos = self::mb_strpos_array($haystack, $str, $offset, $charset);
	            } else {
	                $pos = mb_strpos($haystack, $str, $offset, $charset);
	            }
	            if ($pos !== FALSE) {
	                return $pos;
	            }
	        }
	    } else {
	        return mb_strpos($haystack, $needles, $offset, $charset);
	    }
	}
	
	public static function explodeTree($array, $delimiter = '_', $baseval = false) {
		if(!is_array($array)) return false;
		$splitRE   = '/' . preg_quote($delimiter, '/') . '/';
		$returnArr = array();
		foreach ($array as $key => $val) {
			// Get parent parts and the current leaf
			$parts  = preg_split($splitRE, $key, -1, PREG_SPLIT_NO_EMPTY);
			$leafPart = array_pop($parts);
	 
			// Build parent structure
			// Might be slow for really deep and large structures
			$parentArr = &$returnArr;
			foreach ($parts as $part) {
				if (!isset($parentArr[$part])) {
					$parentArr[$part] = array();
				} elseif (!is_array($parentArr[$part])) {
					if ($baseval) {
						$parentArr[$part] = array('__base_val' => $parentArr[$part]);
					} else {
						$parentArr[$part] = array();
					}
				}
				$parentArr = &$parentArr[$part];
			}
	 
			// Add the final part to the structure
			if (empty($parentArr[$leafPart])) {
				$parentArr[$leafPart] = $val;
			} elseif ($baseval && is_array($parentArr[$leafPart])) {
				$parentArr[$leafPart]['__base_val'] = $val;
			}
		}
		return $returnArr;
	}
	
	public static function array_extend($a, $b) {
		foreach($b as $k=>$v) {
			if( is_array($v) ) {
				if( !isset($a[$k]) ) {
					$a[$k] = $v;
				} else {
					$a[$k] = self::array_extend($a[$k], $v);
				}
			} else {
				$a[$k] = $v;
			}
		}
		return $a;
	}
	
	public function array_mt_rand(array $array, $numberOfKeys = 1) {
		if (!is_int($numberOfKeys)) throw new Exception;
		if ($numberOfKeys < 1) throw new Exception;
		$keys = array_keys($array);
		$maximum = count($array) - 1;
		if ($numberOfKeys == 1) {
			return $keys[mt_rand(0, $maximum)];
		} else {
			$randomKeys = array();
			for ($i = 0; $i < $numberOfKeys; $i++) {
				$randomKeys[] = $keys[mt_rand(0, $maximum)];
			}
			return $randomKeys;
		}
	}

}

?>