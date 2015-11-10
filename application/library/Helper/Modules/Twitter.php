<?php

class Helper_Modules_Twitter extends JO_Api_Twitter_OAuth {

	public function __construct($key = null, $secret = null, $oauth_token = NULL, $oauth_token_secret = NULL) {
		$this->key = $key ? $key : Helper_Config::get('twitter_oauth_key');
		$this->secret = $secret ? $secret : Helper_Config::get('twitter_oauth_secret');
		parent::__construct(
			$this->key,
			$this->secret,
			$oauth_token,
			$oauth_token_secret
		);
	}
	
	public function checkValidAppId() {
		if($this->key && $this->secret) {
			return true;
		}
		
		return false;
	}
	
}

?>