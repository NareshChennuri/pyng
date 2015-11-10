<?php

class Helper_Modules_Facebook {

	/**
	 * @var WM_Facebook_Base
	 */
	public $facebook;
	

	public $key, $secret;
	
	public $req_perms = 'email,user_likes,user_birthday,offline_access,read_friendlists';
	public $scope = 'email,user_likes,user_birthday,offline_access,read_friendlists';
	
	public function __construct($key = null, $secret = null) {
		$this->key = $key ? $key : Helper_Config::get('facebook_oauth_key');
		$this->secret = $secret ? $secret : Helper_Config::get('facebook_oauth_secret');
		$this->facebook = new WM_Facebook_Api(array(
			'appId' => $this->key,
			'secret' => $this->secret		
		));
	}
	
	public function checkValidAppId() {
		if($this->key && $this->secret) {
			return true;
		}
		return false;
	}
	
	public function getLoginUrl($next = null, $controller='modules_facebook_login') {
		return $this->facebook->getLoginUrl(array(
			'redirect_uri' => WM_Router::create( JO_Request::getInstance()->getBaseUrl() . '?controller=' . $controller . ( $next ? '&next=' . $next : '' ) ),
			'req_perms' => $this->req_perms,
			'scope' => $this->scope,
			'display' => 'popup'
		));
	}
	
	public function getUser( $check = false ) {
		
		$connectObject = new Model_Facebook_Login();

		$user_fb = $connectObject->getDataByUserId(JO_Session::get('user[user_id]'));
		if($user_fb && $user_fb['access_token']) {
			$this->facebook->setAccessToken($user_fb['access_token']);
		}
		
		$fbData = $this->facebook->api('/me');

		$this->facebook->setExtendedAccessToken();

		if(isset($fbData['id'])) {

			return $fbData;

		}
		
		$fbData = $this->facebook->api('/me');
		if(isset($fbData['id'])) {
			return $fbData;
		}
		

		return false;
		
	}
	
	public function getAccessToken() {
		return $this->facebook->getAccessToken();
	}
	
}

?>