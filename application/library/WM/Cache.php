<?php

class WM_Cache {
	
	private static $cache_time = 86400;
	
	/**
	 * @var JO_Cache
	 */
	private static $cache_instance = null;
	
	public static function initCache() {
		if(self::$cache_instance == null) {
			self::$cache_instance = JO_Cache::getInstance('sqlfile', array('livetime' => self::$cache_time));
		}
		return self::$cache_instance;
	}
	
	public static function store($key, $data) { return false;
		return self::initCache()->store($key, $data);
	} 
	
	public static function add($key, $data) { return false;
		return self::initCache()->add($key, $data);
	} 
	
	public static function get($key) { return false;
		return self::initCache()->get($key);
	} 
	
	public static function clear() { return false;
		return self::initCache()->clear();
	}
	
	public static function delete($key) { return false;
		return self::initCache()->delete($key);
	}
	
	public static function deleteRegExp($regExp) { return false;
		return self::initCache()->deleteRegExp($regExp);
	}
	
	public static function deleteStrPos($pos) { return false;
		return self::initCache()->deleteStrPos($pos);
	}
	
	public static function deleteExpired() { return false;
		return self::initCache()->deleteExpired();	
	}

}

?>