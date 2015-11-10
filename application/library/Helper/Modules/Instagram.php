<?php

class Helper_Modules_Instagram extends WM_Instagram {
	
	public $key, $secret;
	
	public function __construct($key = null, $secret = null, $redirect_uri = null) {
		$this->key = $key ? $key : Helper_Config::get('instagram_oauth_key');
		$this->secret = $secret ? $secret : Helper_Config::get('instagram_oauth_secret');
		if(!$redirect_uri) {
			$redirect_uri = WM_Router::create(JO_Request::getInstance()->getBaseUrl() . '?controller=modules_instagram_login');
		}
		
		parent::__construct(array(
			'client_id' => $this->key,
			'client_secret' => $this->secret,
			'grant_type' => 'authorization_code',
			'redirect_uri' => $redirect_uri
		));
	}
	
	public function checkValidAppId() {
		if($this->key && $this->secret) {
			return true;
		}
		return false;
	}
}

?>