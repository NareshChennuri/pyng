<?php

class JO_Cache {
	
	/**
	 * @var JO_Cache
	 */
	private static $instance = null;
	
	/**
	 * @var JO_Cache_Abstract
	 */
	private static $driver;
	
	public function __constrict() {}
	
	/**
	 * @param array|string $options
	 * @return JO_Cache
	 */
	public static function getInstance($driver, $options = array()) {
		if(self::$instance == null) {
			self::$instance = new self();
			$driver_name = 'JO_Cache_' . ucfirst(strtolower($driver));
			self::$driver = new $driver_name($options);
		}
		return self::$instance;
	} 
	
	/**
	 * @param string $key
	 * @param mixed $data
	 * @return boolean
	 */
	public function store($key, $data) {
		return self::$driver->store($key, $data);
	}
	
	/**
	 * @param string $key
	 * @param mixed $data
	 * @return boolean
	 */
	public function add($key, $data) {
		return self::$driver->add($key, $data);
	}
	
	/**
	 * @param string $key
	 * @return mixed|boolean
	 */
	public function get($key) {
		return self::$driver->get($key);
	}
	
	/**
	 * @return boolean
	 */
	public function clear() {
		return self::$driver->clear();	
	}
	
	/**
	 * @param string $key
	 * @return boolean
	 */
	public function delete($key) {
		return self::$driver->delete($key);	
	}
	
	/**
	 * @param string $key
	 * @return boolean
	 */
	public function deleteRegExp($regExp) {
		return self::$driver->deleteRegExp($regExp);	
	}
	
	/**
	 * @param string $key
	 * @return boolean
	 */
	public function deleteStrPos($pos) {
		return self::$driver->deleteStrPos($pos);
	}
	
	/**
	 * @param string $key
	 * @return boolean
	 */
	public function deleteExpired() {
		return self::$driver->deleteExpired();
	}

}

?>