<?php

class JO_Translate {
	
	/**
	 * @var JO_Translate
	 */
	protected static $_instance;
	
	protected static $data = array();
	
	/**
	 * @param array $options
	 */
	public function __construct($options = array()) {
		if(isset($options['data']) && is_array($options['data'])) {
			self::$data = $options['data'];
		}
	}
	
	/**
	 * @param array $options
	 * @return JO_Translate
	 */
	public static function getInstance($options = array()) {
		if(self::$_instance == null) {
			self::$_instance = new self($options);
		}
		return self::$_instance;
	}
	
	/**
	 * @param string $value
	 * @return Ambigous <unknown, multitype:>
	 */
	public function translate($key,$value = null) {
		return isset(self::$data[$key]) && self::$data[$key] ? str_replace(array("'",'"'), array("&#039;",'&quot;'),self::$data[$key]) : str_replace(array("'",'"'), array("&#039;",'&quot;'),$value);
	}
	
}

?>