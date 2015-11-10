<?php

class JO_View_Xml extends JO_View_Abstract {
	
	private $data;
	
	public function __construct($view) {	
		$this->data = $this->array_transform($view->getAll());
	}
	
	public function __toString() {
		$result = '<?xml version="1.0" encoding="utf-8"?>' . "\n<array>\n" . $this->data . "</array>";
		return $result;
	}
	
	public function array_transform($array){
		
		$array_text = '';
		foreach($array as $key => $value){
			$key = is_int($key) ? ('int_' . $key) : $key;
			if(is_array($value) || is_object($value)){
				$array_text .= "<$key>\n";
				$array_text .= $this->array_transform($value);
				$array_text .= "</$key>\n";
			} else {
				$array_text .=  "<$key>$value</$key>\n";
			}
		}
		return $array_text;
	}
	
}