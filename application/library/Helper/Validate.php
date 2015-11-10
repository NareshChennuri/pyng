<?php

class Helper_Validate {

	/**
	 * @var JO_Validate_Form
	 */
	private $validate;
	
	public function __construct() {
		$this->validate = new JO_Validate_Form();
		
		$translate = WM_Translate::getInstance();
		
		$this->validate->_set_errors('not_empty', 	$translate->translate('Field {field} must not be empty'));
		$this->validate->_set_errors('max_length', 	$translate->translate('Field {field} must contain no more than {symbols} characters'));
		$this->validate->_set_errors('min_length', 	$translate->translate('Field {field} must contain not less than {symbols} characters'));
		$this->validate->_set_errors('abc', 		$translate->translate('Field {field} must contain only letters'));
		$this->validate->_set_errors('number', 		$translate->translate('Field {field} must contain only numbers'));
		$this->validate->_set_errors('num_abc',		$translate->translate('Field {field} must contain letters and numbers'));
		$this->validate->_set_errors('username',	$translate->translate('Field {field} must contain only A-Z, a-z, 0-9, _ and .'));
		$this->validate->_set_errors('email', 		$translate->translate('Field {field} must contain a valid E-mail address'));
		$this->validate->_set_errors('date', 		$translate->translate('Field {field} must contain a valid date'));
		$this->validate->_set_errors('count',		$translate->translate('Field {field} must be chosen more than {symbols}'));
		$this->validate->_set_errors('domain',		$translate->translate('Field {field} is not valid'));
		$this->validate->_set_errors('price',		$translate->translate('Field {field} must contain only 0-9 and .'));
		return $this->validate;
	}
	
	/**
	 * @param unknown_type $fnc
	 * @param unknown_type $arg
	 * @return JO_Validate_Form
	 */
	public function __call($fnc, $arg = array()) {
		if(method_exists($this->validate, $fnc)) {
			return call_user_func_array(array($this->validate, $fnc), $arg);
		} else {
			throw new JO_Exception(sprintf('Method "%s" does not exist and was not trapped in __call()', $fnc), 500);
		}
	}
	
}

?>