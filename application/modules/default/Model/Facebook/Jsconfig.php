<?php

class Model_Facebook_Jsconfig {

	public function extendsConfig($config = array()) {
		
		$config['facebook_app_id'] = Helper_Config::get('facebook_oauth_key');
		
		return $config;
	}
	
}

?>