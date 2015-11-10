<?php

class JO_Log {

	private static $filename;
	
	private static $_instance;
	
	/**
	 * @param array $options
	 * @return JO_Log
	 */
	public static function getInstance($options = array()) {
		if(self::$_instance == null) {
			self::$_instance = new self($options);
		}
		return self::$_instance;
	}

	/**
	 * @param array $options
	 */
	public function __construct($options = array()) {
		foreach($options AS $key => $data) {
			$method_name = 'set' . $key;
			if(method_exists($this, $method_name)) {
				$this->$method_name($data);
			}
		}
	}
	
	/**
	 * @param string $filename
	 * @return JO_Log
	 */
	public static function setFilename($filename) {
		self::getInstance();
		self::$filename = $filename;
		return self::$_instance;
	}
	
	/**
	 * @return string
	 */
	public static function getFilename() {
		self::getInstance();
		if(!self::$filename) {
			self::setFilename('error.log');
		}
		return self::$filename;
	}
	
	public static function write($message) {
		try {
			$file = BASE_PATH . '/cache/' . self::getFilename();
			
			$handle = fopen($file, 'a+'); 
			
			fwrite($handle, date('Y-m-d G:i:s') . ' - ' . $message );
			fwrite($handle, "\n/*******************************************/\n");
				
			fclose($handle);
		} catch (JO_Exception $e) {} 
	}
	
	
	
}

?>