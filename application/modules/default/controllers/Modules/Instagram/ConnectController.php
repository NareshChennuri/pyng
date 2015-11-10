<?php

class Modules_Instagram_ConnectController extends Helper_Controller_Default {
	
	public $user_data = null;
	
	private function initInstagram(&$instagramoauth) {
		
		if($this->user_data) {
			return true;
		}
		
		$instagramoauth = new Helper_Modules_Instagram();
		
		$InstagramAccessToken = $instagramoauth->getAccessToken();
		$user_data = JO_Json::decode($instagramoauth->getUser(), true);
		
		if( isset($user_data['meta']['code']) && $user_data['meta']['code'] == 200 ) {
			JO_Session::set('InstagramAccessToken', $InstagramAccessToken);
			$this->user_data = $user_data['data'];
		} elseif($InstagramAccessToken) {
			JO_Session::set('InstagramAccessToken', $InstagramAccessToken);
			$instagramoauth->setAccessToken($InstagramAccessToken);
		} elseif(JO_Session::get('InstagramAccessToken')) {
			$instagramoauth->setAccessToken(JO_Session::get('InstagramAccessToken'));
		}
		
		if(!$this->user_data) {
			$user_data = JO_Json::decode($instagramoauth->getUser(), true);
			if( isset($user_data['meta']['code']) && $user_data['meta']['code'] == 200 ) {
				$this->user_data = $user_data['data'];
			}
		}

		return $this->user_data ? true : false;
		
	}

	public function indexAction() {

		$settings = Model_Extensions::getSettingsPairs('instagram');
		if(isset($settings['instagram_module_status_enable']) && $settings['instagram_module_status_enable']) {
			if(isset($settings['instagram_login_with_instagram']) && $settings['instagram_login_with_instagram']) {
				
				$request = $this->getRequest();
				
				$next = WM_Router::create( $request->getBaseUrl() . '?controller=modules_instagram_connect&action=connect' );
				
				$this->view->instagram_connect_url = WM_Router::create( $request->getBaseUrl() . '?controller=modules_instagram_login&instagram_fnc=connect&action=forward&next=' . $next );
				$this->view->instagram_fetch = WM_Router::create( $request->getBaseUrl() . '?controller=modules_instagram_media' );
				
				$connectObject = new Model_Instagram_Login();
				$user_info = $connectObject->getDataByUserId(JO_Session::get('user[user_id]'));
				
				$this->view->instagram_connect = false;
				$this->view->fetch_instagram_media = false;
				if($user_info) {
					$this->view->instagram_connect = true;
					$this->view->fetch_instagram_media = isset($settings['instagram_fetch_instagram_media']) && $settings['instagram_fetch_instagram_media'];
				}
				
				$this->view->site_name = Helper_Config::get('site_name');
				
				
			} else {
				$this->noViewRenderer(true);
			}
		} else {
			$this->noViewRenderer(true);
		}
		
	}
	
	public function connectAction() {
		
		$request = $this->getRequest();
		$this->noLayout(true);
		$this->view->close_box = false;
		if(JO_Session::get('user[user_id]')) {
			
			$instagramoauth = null;
			$this->initInstagram($instagramoauth);
			
			$user_data = $this->user_data;
			
			$connectObject = new Model_Instagram_Login();
		
			$user_info = $connectObject->getDataByUserId(JO_Session::get('user[user_id]'));
			if($user_data) {
				
				$connectObjectCheck = new Model_Instagram_Login($user_data['id']);
				if($connectObjectCheck->row) {
					if($connectObjectCheck->row['user_id'] == JO_Session::get('user[user_id]')) {
						$enable_action = true;
					} else {
						$is_user = new Model_Users_User($connectObjectCheck->row['user_id']);
						if(!$is_user->count()) {
							$enable_action = true;
							$connectObject->deleteDataByUserId($connectObjectCheck->row['user_id']);
						} else {
							$enable_action = $this->translate('There is another profile that is associated with your instagram account');
						}
					}
				} else {
					$enable_action = true;
				}
				
				if($enable_action === true) {
					if($user_info) {
						if($connectObject->deleteDataByUserId(JO_Session::get('user[user_id]'))) {
							$this->view->close_box = true;
						} else {
							$this->view->close_box = true;
						}
					} else {
						$res = $connectObject->insert(array(
								'username' => $user_data['username'],
								'user_id' => JO_Session::get('user[user_id]'),
								'oauth_uid' => $user_data['id'],
								'access_token' => JO_Session::get('InstagramAccessToken')
								
						)); 
						if($res) {
							$this->view->close_box = true;
						} else {
							$this->view->close_box = true;
						}
					}
				} else {
					JO_Session::set('connect_error', $enable_action);
					$this->view->close_box = true;
				}
			} else {
				$instagramoauth->openAuthorizationUrl();
			}
		}
		$this->view->close_box = true;
		
	}
	
	
	
}

?>