<?php

class JO_View_Json extends JO_View_Abstract {
	
	private $data;
	
	public function __construct(JO_View $view) {
		$this->data = JO_Json::encode($view->getAll());
	}
	
	
	public function __toString() {
		if(JO_Registry::isRegistered('static_cache_options') && JO_Registry::forceGet('static_cache_enable')) {
			$options = (array)unserialize(JO_Registry::get('static_cache_options'));
			$cache_object = new JO_Cache_Static($options);
			$cache_object->add(false, $this->data);
		} 
		return $this->data;
	}
	
}